<?php

namespace App\Application\Reporting\Actions;

use App\Domain\Alarm\Models\Alarm;
use App\Domain\Device\Models\Olt;
use App\Domain\Device\Models\Onu;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate an overview report of the network.
     */
    public function generateOverviewReport(): array
    {
        return [
            'olt_count' => Olt::count(),
            'onu_count' => Onu::count(),
            'online_olts' => Olt::where('status', 'online')->count(),
            'online_onus' => Onu::where('status', 'online')->count(),
            'total_alarms' => Alarm::count(),
            'active_alarms' => Alarm::where('status', 'active')->count(),
        ];
    }

    /**
     * Generate ONU optical power report (e.g. finding ONUs with poor signals).
     */
    public function generateOpticalReport(): array
    {
        return Onu::select('id', 'olt_id', 'pon_port_id', 'serial_number', 'customer_name', 'status', 'optical_rx_power')
            ->with(['olt:id,name', 'ponPort:id,port_name'])
            ->whereNotNull('optical_rx_power')
            ->orderBy('optical_rx_power', 'asc') // Worst signals first
            ->get()
            ->toArray();
    }

    /**
     * Generate Alarm history report for a given date range.
     */
    public function generateAlarmReport(string $startDate, string $endDate): array
    {
        return Alarm::with(['olt:id,name', 'onu:id,serial_number'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}
