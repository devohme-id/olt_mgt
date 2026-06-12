<?php

namespace App\Infrastructure\Snmp;

use App\Domain\Device\Models\Olt;
use App\Domain\Device\Models\VendorOidMapping;
use Illuminate\Support\Facades\Cache;

class OidRegistry
{
    /**
     * Resolve OID mappings for a given OLT device.
     * Uses layered lookup: firmware-specific → model-specific → vendor-wide.
     * Results are cached in Redis for performance.
     */
    public function resolve(Olt $olt, string $metricKey): ?VendorOidMapping
    {
        $cacheKey = "oid_map:{$olt->vendor_id}:{$olt->device_model_id}:{$metricKey}";
        $ttl = config('snmp.cache.oid_map_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($olt, $metricKey) {
                // 1. Firmware-specific mapping (most specific)
                if ($olt->firmware_profile_id) {
                    $mapping = VendorOidMapping::where('vendor_id', $olt->vendor_id)
                        ->where('device_model_id', $olt->device_model_id)
                        ->where('firmware_profile_id', $olt->firmware_profile_id)
                        ->where('metric_key', $metricKey)
                        ->where('is_active', true)
                        ->first();
                    if ($mapping) return $mapping;
                }

                // 2. Model-specific mapping
                $mapping = VendorOidMapping::where('vendor_id', $olt->vendor_id)
                    ->where('device_model_id', $olt->device_model_id)
                    ->whereNull('firmware_profile_id')
                    ->where('metric_key', $metricKey)
                    ->where('is_active', true)
                    ->first();
                if ($mapping) return $mapping;

                // 3. Vendor-wide mapping (least specific)
                return VendorOidMapping::where('vendor_id', $olt->vendor_id)
                    ->whereNull('device_model_id')
                    ->whereNull('firmware_profile_id')
                    ->where('metric_key', $metricKey)
                    ->where('is_active', true)
                    ->first();
            });
    }

    /**
     * Get all OID mappings for a given OLT, grouped by metric key.
     */
    public function resolveAll(Olt $olt): array
    {
        $cacheKey = "oid_map_all:{$olt->vendor_id}:{$olt->device_model_id}";
        $ttl = config('snmp.cache.oid_map_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($olt) {
                $mappings = VendorOidMapping::where('vendor_id', $olt->vendor_id)
                    ->where(function ($q) use ($olt) {
                        $q->where('device_model_id', $olt->device_model_id)
                          ->orWhereNull('device_model_id');
                    })
                    ->where('is_active', true)
                    ->orderBy('firmware_profile_id', 'desc') // firmware-specific first
                    ->orderBy('device_model_id', 'desc')     // model-specific second
                    ->get();

                $resolved = [];
                foreach ($mappings as $mapping) {
                    // First encountered wins (most specific)
                    if (!isset($resolved[$mapping->metric_key])) {
                        $resolved[$mapping->metric_key] = $mapping;
                    }
                }

                return $resolved;
            });
    }

    /**
     * Flush cached OID mappings for a vendor.
     */
    public function flushCache(?string $vendorId = null): void
    {
        if ($vendorId) {
            // Flush specific vendor cache
            Cache::forget("oid_map_all:{$vendorId}:*");
        }
        // For a complete flush, use Redis KEYS pattern or tagged cache
    }
}
