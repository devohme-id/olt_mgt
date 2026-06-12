<?php

namespace App\Infrastructure\Snmp;

use App\Domain\Device\Models\Olt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SnmpWalkCache
{
    private string $basePath = 'snmp-cache';

    /**
     * Perform a full SNMP walk and cache the results.
     */
    public function walkAndCache(Olt $olt, string $baseOid = '1.3.6.1'): array
    {
        $pollingService = app(SnmpPollingService::class);
        $client = app(SnmpClientFactory::class)->create($olt);

        Log::info("Starting SNMP walk cache for OLT {$olt->name} ({$olt->ip_address}) at OID {$baseOid}");

        $results = $pollingService->snmpWalk($client, $baseOid);

        try { $client->close(); } catch (\Exception $e) {}

        if (!empty($results)) {
            $this->save($olt, $results);
            Log::info("SNMP walk cache saved: " . count($results) . " OIDs for OLT {$olt->name}");
        }

        return $results;
    }

    /**
     * Save walk results to disk.
     */
    public function save(Olt $olt, array $results): void
    {
        $data = [
            'olt_id'     => $olt->id,
            'olt_name'   => $olt->name,
            'ip_address' => $olt->ip_address,
            'vendor'     => $olt->vendor?->name,
            'model'      => $olt->deviceModel?->model_number,
            'firmware'   => $olt->firmwareProfile?->version,
            'walked_at'  => now()->toIso8601String(),
            'oid_count'  => count($results),
            'oids'       => $this->serializeResults($results),
        ];

        $path = $this->getCachePath($olt);
        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Load cached walk results.
     */
    public function load(Olt $olt): ?array
    {
        $path = $this->getCachePath($olt);

        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        $content = Storage::disk('local')->get($path);
        return json_decode($content, true);
    }

    /**
     * Check if cache exists and is fresh.
     */
    public function isFresh(Olt $olt): bool
    {
        $data = $this->load($olt);

        if (!$data) return false;

        $walkedAt = \Carbon\Carbon::parse($data['walked_at']);
        $ttl = config('snmp.cache.walk_ttl', 86400);

        return $walkedAt->addSeconds($ttl)->isFuture();
    }

    /**
     * Search cached walk for OIDs matching a pattern.
     */
    public function search(Olt $olt, string $pattern): array
    {
        $data = $this->load($olt);

        if (!$data || !isset($data['oids'])) return [];

        $results = [];
        foreach ($data['oids'] as $oid => $info) {
            if (str_contains($oid, $pattern) || str_contains(strtolower($info['value'] ?? ''), strtolower($pattern))) {
                $results[$oid] = $info;
            }
        }

        return $results;
    }

    private function getCachePath(Olt $olt): string
    {
        return "{$this->basePath}/{$olt->id}/walk_latest.json";
    }

    private function serializeResults(array $results): array
    {
        $serialized = [];
        foreach ($results as $oid => $value) {
            $serialized[$oid] = [
                'value' => is_object($value) ? (string) $value : $value,
                'type'  => is_object($value) ? get_class($value) : gettype($value),
            ];
        }
        return $serialized;
    }
}
