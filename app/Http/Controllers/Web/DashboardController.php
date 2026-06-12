<?php

namespace App\Http\Controllers\Web;

use App\Domain\Device\Models\Olt;
use App\Domain\Device\Models\Onu;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Get high-level stats
        $stats = [
            'total_olts'   => Olt::count(),
            'online_olts'  => Olt::where('status', 'online')->count(),
            'total_onus'   => Onu::count(),
            'online_onus'  => Onu::where('status', 'online')->count(),
            'offline_onus' => Onu::whereIn('status', ['offline', 'los'])->count(),
            'active_alarms'=> \App\Domain\Alarm\Models\Alarm::where('status', 'active')->count(),
        ];

        // Network health percentage
        $healthScore = 100;
        if ($stats['total_onus'] > 0) {
            $offlineRatio = $stats['offline_onus'] / $stats['total_onus'];
            $healthScore = max(0, 100 - ($offlineRatio * 100));
        }

        $stats['network_health'] = round($healthScore);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'olts'  => Olt::with('vendor:id,name')->get(['id', 'name', 'ip_address', 'status', 'vendor_id']),
        ]);
    }
}
