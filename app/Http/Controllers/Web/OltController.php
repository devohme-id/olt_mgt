<?php

namespace App\Http\Controllers\Web;

use App\Application\Olt\Actions\OltService;
use App\Domain\Device\Models\DeviceModel;
use App\Domain\Device\Models\FirmwareProfile;
use App\Domain\Device\Models\Olt;
use App\Domain\Device\Models\Site;
use App\Domain\Device\Models\Vendor;
use App\Http\Requests\StoreOltRequest;
use App\Http\Requests\UpdateOltRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class OltController extends Controller
{
    public function __construct(
        private OltService $oltService,
    ) {}

    /**
     * OLT List page.
     */
    public function index(Request $request)
    {
        $olts = $this->oltService->list($request->only([
            'status', 'vendor_id', 'site_id', 'search', 'per_page',
        ]));

        return Inertia::render('Olt/Index', [
            'olts'    => $olts,
            'filters' => $request->only(['status', 'vendor_id', 'site_id', 'search']),
            'vendors' => Vendor::where('is_active', true)->get(['id', 'name']),
            'sites'   => Site::get(['id', 'name']),
        ]);
    }

    /**
     * OLT Create form.
     */
    public function create()
    {
        return Inertia::render('Olt/Create', [
            'vendors'          => Vendor::where('is_active', true)->get(['id', 'name']),
            'deviceModels'     => DeviceModel::with('vendor:id,name')->get(['id', 'vendor_id', 'name', 'model_number', 'device_type', 'max_pon_ports']),
            'firmwareProfiles' => FirmwareProfile::where('is_active', true)->get(['id', 'device_model_id', 'version']),
            'sites'            => Site::get(['id', 'name']),
        ]);
    }

    /**
     * Store new OLT.
     */
    public function store(StoreOltRequest $request)
    {
        $data = $request->validated();
        
        // Map form fields to database columns
        if (isset($data['snmp_community'])) {
            $data['snmp_community_read'] = $data['snmp_community'];
            $data['snmp_community_write'] = $data['snmp_community'];
            unset($data['snmp_community']);
        }

        $olt = $this->oltService->create($data, auth()->id());

        return redirect()->route('olt.show', $olt)
            ->with('success', "OLT '{$olt->name}' created successfully.");
    }

    /**
     * OLT Detail page.
     */
    public function show(Olt $olt)
    {
        $olt = $this->oltService->detail($olt);

        return Inertia::render('Olt/Show', [
            'olt'     => $olt,
            'metrics' => $this->oltService->getLatestMetrics($olt),
        ]);
    }

    /**
     * OLT Edit form.
     */
    public function edit(Olt $olt)
    {
        return Inertia::render('Olt/Edit', [
            'olt'              => $olt->load(['vendor', 'deviceModel', 'site']),
            'vendors'          => Vendor::where('is_active', true)->get(['id', 'name']),
            'deviceModels'     => DeviceModel::with('vendor:id,name')->get(['id', 'vendor_id', 'name', 'model_number', 'device_type', 'max_pon_ports']),
            'firmwareProfiles' => FirmwareProfile::where('is_active', true)->get(['id', 'device_model_id', 'version']),
            'sites'            => Site::get(['id', 'name']),
        ]);
    }

    /**
     * Update OLT.
     */
    public function update(UpdateOltRequest $request, Olt $olt)
    {
        $data = $request->validated();
        
        // Map form fields to database columns
        if (isset($data['snmp_community'])) {
            $data['snmp_community_read'] = $data['snmp_community'];
            $data['snmp_community_write'] = $data['snmp_community'];
            unset($data['snmp_community']);
        }

        $this->oltService->update($olt, $data, auth()->id());

        return redirect()->route('olt.show', $olt)
            ->with('success', "OLT '{$olt->name}' updated successfully.");
    }

    /**
     * Delete OLT.
     */
    public function destroy(Olt $olt)
    {
        $name = $olt->name;
        $this->oltService->delete($olt, auth()->id());

        return redirect()->route('olt.index')
            ->with('success', "OLT '{$name}' deleted successfully.");
    }

    /**
     * Sync OLT — immediate SNMP poll.
     */
    public function sync(Olt $olt)
    {
        $metrics = $this->oltService->sync($olt);

        $status = ($metrics['poll_success'] ?? false) ? 'success' : 'error';
        $message = ($metrics['poll_success'] ?? false)
            ? "OLT '{$olt->name}' synced successfully."
            : "Failed to sync OLT '{$olt->name}': " . ($metrics['error'] ?? 'Unknown error');

        return back()->with($status, $message);
    }

    /**
     * Sync ONUs from this OLT.
     */
    public function syncOnus(Olt $olt)
    {
        $this->oltService->syncOnus($olt);

        return back()->with('success', "ONU sync job dispatched for '{$olt->name}'.");
    }

    /**
     * Backup OLT config.
     */
    public function backup(Olt $olt)
    {
        try {
            $this->oltService->backupConfig($olt);
            return back()->with('success', "Config backup saved for '{$olt->name}'.");
        } catch (\Exception $e) {
            return back()->with('error', "Backup failed: {$e->getMessage()}");
        }
    }
}
