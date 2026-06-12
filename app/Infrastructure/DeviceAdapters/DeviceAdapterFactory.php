<?php

namespace App\Infrastructure\DeviceAdapters;

use App\Domain\Device\Models\Olt;

class DeviceAdapterFactory
{
    /**
     * Create the appropriate device adapter for an OLT.
     */
    public function create(Olt $olt): DeviceAdapterInterface
    {
        $vendorCode = strtolower($olt->vendor?->code ?? 'generic');
        $adapterClass = config("devices.adapters.{$vendorCode}");

        if ($adapterClass && class_exists($adapterClass)) {
            return new $adapterClass($olt);
        }

        // Fallback to generic adapter
        $genericClass = config('devices.adapters.generic');
        return new $genericClass($olt);
    }
}
