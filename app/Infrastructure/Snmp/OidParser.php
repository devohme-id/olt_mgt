<?php

namespace App\Infrastructure\Snmp;

use Illuminate\Support\Facades\Log;

class OidParser
{
    /**
     * Parse a raw SNMP value based on its data type.
     */
    public function parse(mixed $rawValue, string $dataType, float $multiplier = 1, ?string $parserClass = null): mixed
    {
        // Use custom parser class if specified
        if ($parserClass && class_exists($parserClass)) {
            return (new $parserClass)->parse($rawValue);
        }

        $value = $this->extractValue($rawValue);

        return match ($dataType) {
            'integer', 'gauge', 'gauge32'   => (int) $value * $multiplier,
            'counter', 'counter32', 'counter64' => (int) $value,
            'float', 'decimal'              => round((float) $value * $multiplier, 4),
            'string', 'octet_string'        => trim((string) $value),
            'mac'                           => $this->parseMacAddress($value),
            'optical_power'                 => $this->parseOpticalPower($value),
            'timeticks'                     => $this->parseTimeTicks($value),
            'ip_address'                    => (string) $value,
            'boolean'                       => (bool) $value,
            default                         => $value,
        };
    }

    /**
     * Parse MAC address from various SNMP formats.
     */
    public function parseMacAddress(mixed $rawValue): ?string
    {
        $value = $this->extractValue($rawValue);

        if (empty($value)) return null;

        // Handle hex string format: "0x001122334455" or "\x00\x11\x22\x33\x44\x55"
        if (is_string($value)) {
            // Remove 0x prefix
            $hex = preg_replace('/^0x/', '', $value);
            // Remove non-hex characters
            $hex = preg_replace('/[^0-9a-fA-F]/', '', $hex);

            if (strlen($hex) === 12) {
                return strtoupper(implode(':', str_split($hex, 2)));
            }

            // Binary string (6 bytes)
            if (strlen($value) === 6) {
                return strtoupper(implode(':', array_map('dechex', array_map('ord', str_split($value)))));
            }
        }

        return (string) $value;
    }

    /**
     * Parse optical power value (typically in 0.01 dBm units from SNMP).
     */
    public function parseOpticalPower(mixed $rawValue): ?float
    {
        $value = $this->extractValue($rawValue);

        if ($value === null || $value === '' || $value === 'noSuchInstance') {
            return null;
        }

        $intValue = (int) $value;

        // Many vendors report in 0.01 dBm or 0.001 dBm units
        // HSGQ typically reports in 0.01 dBm (value / 100)
        if (abs($intValue) > 1000) {
            return round($intValue / 100, 2);
        }

        // Already in dBm
        return round((float) $value, 2);
    }

    /**
     * Parse ONU status integer to string.
     */
    public function parseOnuStatus(mixed $rawValue): string
    {
        $value = (int) $this->extractValue($rawValue);

        return match ($value) {
            1 => 'online',
            2 => 'offline',
            3 => 'los',
            4 => 'disabled',
            5 => 'power_off',
            default => 'unknown',
        };
    }

    /**
     * Parse TimeTicks to seconds.
     */
    public function parseTimeTicks(mixed $rawValue): int
    {
        $value = (int) $this->extractValue($rawValue);
        // TimeTicks are in hundredths of a second
        return (int) ($value / 100);
    }

    /**
     * Extract the actual value from FreeDSx SNMP response objects.
     */
    private function extractValue(mixed $rawValue): mixed
    {
        if (is_object($rawValue)) {
            if (method_exists($rawValue, 'getValue')) {
                return $rawValue->getValue();
            }
            if (method_exists($rawValue, '__toString')) {
                return (string) $rawValue;
            }
        }

        return $rawValue;
    }
}
