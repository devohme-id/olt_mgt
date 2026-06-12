<?php

namespace App\Infrastructure\Snmp;

use App\Domain\Device\Models\Olt;
use FreeDSx\Snmp\SnmpClient;

class SnmpClientFactory
{
    /**
     * Create an SNMP client configured for the given OLT device.
     */
    public function create(Olt $olt): SnmpClient
    {
        $options = [
            'host'    => $olt->ip_address,
            'port'    => $olt->snmp_port,
            'timeout' => config('snmp.default_timeout', 5),
            'retries' => config('snmp.default_retries', 3),
            'version' => $this->resolveVersion($olt->snmp_version),
        ];

        if ($olt->snmp_version === 'v3') {
            $options['user']          = $olt->snmp_v3_username;
            $options['auth_mech']     = $this->resolveAuthProto($olt->snmp_v3_auth_proto);
            $options['auth_pwd']      = $olt->snmp_v3_auth_pass;
            $options['priv_mech']     = $this->resolvePrivProto($olt->snmp_v3_priv_proto);
            $options['priv_pwd']      = $olt->snmp_v3_priv_pass;
            $options['security_level'] = $this->resolveSecurityLevel($olt);
        } else {
            $options['community'] = $olt->snmp_community_read;
        }

        return new SnmpClient($options);
    }

    /**
     * Create a write-capable SNMP client.
     */
    public function createWritable(Olt $olt): SnmpClient
    {
        $client = $this->create($olt);

        // Override community to write community for v1/v2c
        if ($olt->snmp_version !== 'v3' && $olt->snmp_community_write) {
            return new SnmpClient(array_merge(
                $this->getBaseOptions($olt),
                ['community' => $olt->snmp_community_write]
            ));
        }

        return $client;
    }

    private function getBaseOptions(Olt $olt): array
    {
        return [
            'host'    => $olt->ip_address,
            'port'    => $olt->snmp_port,
            'timeout' => config('snmp.default_timeout', 5),
            'retries' => config('snmp.default_retries', 3),
            'version' => $this->resolveVersion($olt->snmp_version),
        ];
    }

    private function resolveVersion(string $version): int
    {
        return match ($version) {
            'v1'  => 1,
            'v2c' => 2,
            'v3'  => 3,
            default => 2,
        };
    }

    private function resolveAuthProto(?string $proto): ?string
    {
        return match (strtoupper($proto ?? '')) {
            'MD5' => 'md5',
            'SHA', 'SHA1' => 'sha1',
            'SHA256' => 'sha256',
            'SHA512' => 'sha512',
            default => null,
        };
    }

    private function resolvePrivProto(?string $proto): ?string
    {
        return match (strtoupper($proto ?? '')) {
            'DES'    => 'des',
            'AES', 'AES128' => 'aes128',
            'AES192' => 'aes192',
            'AES256' => 'aes256',
            default  => null,
        };
    }

    private function resolveSecurityLevel(Olt $olt): string
    {
        if ($olt->snmp_v3_priv_pass) return 'authPriv';
        if ($olt->snmp_v3_auth_pass) return 'authNoPriv';
        return 'noAuthNoPriv';
    }
}
