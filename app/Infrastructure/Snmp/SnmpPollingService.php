<?php

namespace App\Infrastructure\Snmp;

use App\Domain\Device\Models\Olt;
use App\Domain\Device\Models\VendorOidMapping;
use FreeDSx\Snmp\SnmpClient;
use FreeDSx\Snmp\Oid;
use FreeDSx\Snmp\Request\GetBulkRequest;
use Illuminate\Support\Facades\Log;

class SnmpPollingService
{
    public function __construct(
        private SnmpClientFactory $clientFactory,
        private OidRegistry $oidRegistry,
        private OidParser $oidParser,
    ) {}

    /**
     * Poll OLT health metrics (CPU, Memory, Temperature, Uptime).
     */
    public function pollOltHealth(Olt $olt): array
    {
        $client = $this->clientFactory->create($olt);
        $metrics = [];

        try {
            $healthOids = ['cpu_usage', 'memory_usage', 'memory_total', 'memory_used', 'temperature', 'uptime'];
            $oidList = [];

            foreach ($healthOids as $key) {
                $mapping = $this->oidRegistry->resolve($olt, $key);
                if ($mapping) {
                    $oidList[$key] = $mapping;
                }
            }

            // Batch GET for all health OIDs
            $oids = array_map(fn($m) => $m->oid, $oidList);

            if (!empty($oids)) {
                $response = $client->get(...array_values($oids));

                foreach ($response->toArray() as $i => $oidResponse) {
                    $keys = array_keys($oidList);
                    if (isset($keys[$i])) {
                        $mapping = $oidList[$keys[$i]];
                        $metrics[$keys[$i]] = $this->oidParser->parse(
                            $oidResponse->getValue(),
                            $mapping->data_type,
                            $mapping->multiplier,
                            $mapping->parser_class
                        );
                    }
                }
            }

            // Calculate memory usage percentage
            if (isset($metrics['memory_total'], $metrics['memory_used']) && $metrics['memory_total'] > 0) {
                $metrics['memory_usage'] = round(($metrics['memory_used'] / $metrics['memory_total']) * 100, 1);
            }

            $metrics['poll_success'] = true;
            $metrics['polled_at'] = now()->toIso8601String();

        } catch (\Exception $e) {
            Log::error("SNMP poll failed for OLT {$olt->name} ({$olt->ip_address}): {$e->getMessage()}");
            $metrics['poll_success'] = false;
            $metrics['error'] = $e->getMessage();
        } finally {
            try { $client->close(); } catch (\Exception $e) {}
        }

        return $metrics;
    }

    /**
     * Poll ONU table from OLT to discover/update ONUs.
     */
    public function pollOnuTable(Olt $olt): array
    {
        $client = $this->clientFactory->create($olt);
        $onus = [];

        try {
            $onuTableMapping = $this->oidRegistry->resolve($olt, 'onu_table');
            if (!$onuTableMapping) {
                Log::warning("No ONU table OID mapping found for OLT {$olt->name}");
                return [];
            }

            // (Removed unused full table walk)

            // Also get optical power data
            $rxPowerMapping = $this->oidRegistry->resolve($olt, 'onu_rx_power');
            $txPowerMapping = $this->oidRegistry->resolve($olt, 'onu_tx_power');
            $statusMapping = $this->oidRegistry->resolve($olt, 'onu_status');
            $macMapping = $this->oidRegistry->resolve($olt, 'onu_mac');
            $distanceMapping = $this->oidRegistry->resolve($olt, 'onu_distance');
            $nameMapping = $this->oidRegistry->resolve($olt, 'onu_name');
            $deviceTypeMapping = $this->oidRegistry->resolve($olt, 'onu_device_type');
            $onuTypeMapping = $this->oidRegistry->resolve($olt, 'onu_type');

            $rxPowers = $rxPowerMapping ? $this->snmpWalk($client, $rxPowerMapping->oid) : [];
            $txPowers = $txPowerMapping ? $this->snmpWalk($client, $txPowerMapping->oid) : [];
            $statuses = $statusMapping ? $this->snmpWalk($client, $statusMapping->oid) : [];
            $macs = $macMapping ? $this->snmpWalk($client, $macMapping->oid) : [];
            $distances = $distanceMapping ? $this->snmpWalk($client, $distanceMapping->oid) : [];
            $names = $nameMapping ? $this->snmpWalk($client, $nameMapping->oid) : [];
            $deviceTypes = $deviceTypeMapping ? $this->snmpWalk($client, $deviceTypeMapping->oid) : [];
            $onuTypes = $onuTypeMapping ? $this->snmpWalk($client, $onuTypeMapping->oid) : [];

            // Parse and correlate by ONU index
            foreach ($statuses as $oid => $value) {
                $index = $this->extractOnuIndex($oid, $statusMapping->oid);
                if ($index === null) continue;

                $rxOid = $this->findMatchingOidKey($rxPowers, $rxPowerMapping?->oid, $index);
                $txOid = $this->findMatchingOidKey($txPowers, $txPowerMapping?->oid, $index);
                $macOid = $this->findMatchingOidKey($macs, $macMapping?->oid, $index);
                $distOid = $this->findMatchingOidKey($distances, $distanceMapping?->oid, $index);
                $nameOid = $this->findMatchingOidKey($names, $nameMapping?->oid, $index);
                $deviceTypeOid = $this->findMatchingOidKey($deviceTypes, $deviceTypeMapping?->oid, $index);
                $onuTypeOid = $this->findMatchingOidKey($onuTypes, $onuTypeMapping?->oid, $index);

                $onus[$index] = [
                    'onu_index'     => $index,
                    'status'        => $this->oidParser->parseOnuStatus($value),
                    'mac_address'   => $macOid !== null
                        ? $this->oidParser->parseMacAddress($macs[$macOid])
                        : null,
                    'name'          => $nameOid !== null
                        ? $this->oidParser->parse($names[$nameOid], 'string')
                        : null,
                    'device_type'   => $deviceTypeOid !== null
                        ? $this->oidParser->parse($deviceTypes[$deviceTypeOid], 'string')
                        : null,
                    'onu_type'      => $onuTypeOid !== null
                        ? $this->oidParser->parse($onuTypes[$onuTypeOid], 'string')
                        : null,
                    'rx_power_dbm'  => $rxOid !== null
                        ? $this->oidParser->parseOpticalPower($rxPowers[$rxOid], $rxPowerMapping?->multiplier ?? 1)
                        : null,
                    'tx_power_dbm'  => $txOid !== null
                        ? $this->oidParser->parseOpticalPower($txPowers[$txOid], $txPowerMapping?->multiplier ?? 1)
                        : null,
                    'distance_meters' => $distOid !== null
                        ? $this->oidParser->parse($distances[$distOid], 'integer')
                        : null,
                ];
            }

        } catch (\Exception $e) {
            Log::error("ONU table poll failed for OLT {$olt->name}: {$e->getMessage()}");
        } finally {
            try { $client->close(); } catch (\Exception $e) {}
        }

        return $onus;
    }

    /**
     * Perform an SNMP Walk on a subtree.
     */
    public function snmpWalk(SnmpClient $client, string $baseOid): array
    {
        $results = [];
        $maxOids = config('snmp.walk_max_oids', 10000);
        $count = 0;

        try {
            $walk = $client->walk($baseOid);
            while ($walk->hasOids() && $count < $maxOids) {
                $oid = $walk->next();
                $results[$oid->getOid()] = $oid->getValue();
                $count++;
            }
        } catch (\Exception $e) {
            Log::debug("SNMP walk ended at {$count} OIDs for {$baseOid}: {$e->getMessage()}");
        }

        return $results;
    }

    /**
     * Single SNMP GET request.
     */
    public function snmpGet(Olt $olt, string $oid): mixed
    {
        $client = $this->clientFactory->create($olt);
        try {
            $response = $client->get($oid);
            return $response->toArray()[0]->getValue();
        } finally {
            try { $client->close(); } catch (\Exception $e) {}
        }
    }

    /**
     * Extract ONU index from a full OID by removing the base OID prefix.
     */
    private function extractOnuIndex(string $fullOid, string $baseOid): ?string
    {
        if (str_starts_with($fullOid, $baseOid . '.')) {
            return substr($fullOid, strlen($baseOid) + 1);
        }
        return null;
    }

    /**
     * Find a matching OID key in an array of walked OIDs that starts with the base OID and index.
     * Some vendors append extra indices like .0.0 to related tables.
     */
    private function findMatchingOidKey(array $walkedData, ?string $baseOid, string $index): ?string
    {
        if (!$baseOid) return null;
        
        $expectedPrefix = $baseOid . '.' . $index;
        
        if (isset($walkedData[$expectedPrefix])) {
            return $expectedPrefix;
        }

        foreach (array_keys($walkedData) as $oid) {
            if (str_starts_with($oid, $expectedPrefix . '.')) {
                return $oid;
            }
        }

        return null;
    }

    /**
     * Rebuild a full OID from base OID and index.
     */
    private function rebuildOid(string $baseOid, string $index): string
    {
        return $baseOid . '.' . $index;
    }
}
