<?php

namespace App\Http\Controllers\Web;

use App\Application\Onu\Actions\OnuService;
use App\Domain\Device\Models\Olt;
use App\Domain\Device\Models\Onu;
use App\Domain\Device\Models\ServiceProfile;
use App\Http\Requests\UpdateOnuRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class OnuController extends Controller
{
    public function __construct(
        private OnuService $onuService,
    ) {}

    /**
     * ONU List page.
     */
    public function index(Request $request)
    {
        $onus = $this->onuService->list($request->only([
            'olt_id', 'pon_port_id', 'status', 'auth_status', 'search', 'per_page', 'sort_by', 'sort_dir',
        ]));

        return Inertia::render('Onu/Index', [
            'onus'    => $onus,
            'filters' => $request->only(['olt_id', 'pon_port_id', 'status', 'auth_status', 'search']),
            'olts'    => Olt::get(['id', 'name', 'ip_address']),
            'pon_ports'=> \App\Domain\Device\Models\PonPort::get(['id', 'port_name', 'olt_id']),
            'profiles' => ServiceProfile::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    /**
     * ONU Detail page.
     */
    public function show(Onu $onu)
    {
        $data = $this->onuService->detail($onu);

        return Inertia::render('Onu/Show', [
            'onu'            => $data['onu'],
            'opticalHistory' => $data['opticalHistory'],
            'recentEvents'   => $data['recentEvents'],
            'profiles'       => ServiceProfile::where('is_active', true)->get(['id', 'name', 'upstream_bw_kbps', 'downstream_bw_kbps']),
        ]);
    }

    /**
     * Update ONU.
     */
    public function update(UpdateOnuRequest $request, Onu $onu)
    {
        $this->onuService->update($onu, $request->validated(), auth()->id());

        return back()->with('success', 'ONU updated successfully.');
    }

    /**
     * Reboot ONU.
     */
    public function reboot(Onu $onu)
    {
        $result = $this->onuService->reboot($onu, auth()->id());
        $status = ($result['success'] ?? false) ? 'success' : 'error';
        $msg = ($result['success'] ?? false) ? 'ONU reboot command sent.' : 'Reboot failed: ' . ($result['message'] ?? 'Unknown');

        return back()->with($status, $msg);
    }

    /**
     * Apply service profile to ONU.
     */
    public function applyProfile(Request $request, Onu $onu)
    {
        $request->validate(['service_profile_id' => 'required|exists:service_profiles,id']);
        $result = $this->onuService->applyProfile($onu, $request->service_profile_id, auth()->id());

        $status = ($result['success'] ?? false) ? 'success' : 'error';
        return back()->with($status, ($result['success'] ?? false) ? 'Profile applied.' : 'Failed: ' . ($result['error'] ?? ''));
    }

    /**
     * Delete ONU.
     */
    public function destroy(Request $request, Onu $onu)
    {
        $removeFromOlt = $request->boolean('remove_from_olt', false);
        $this->onuService->delete($onu, $removeFromOlt, auth()->id());

        return redirect()->route('onu.index')
            ->with('success', 'ONU deleted successfully.');
    }

    /**
     * Get real-time optical data (API-like for AJAX).
     */
    public function optical(Onu $onu)
    {
        $data = $this->onuService->getOptical($onu);
        return response()->json($data);
    }
}
