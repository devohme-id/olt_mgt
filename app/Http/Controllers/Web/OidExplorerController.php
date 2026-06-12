<?php

namespace App\Http\Controllers\Web;

use App\Domain\Device\Models\Olt;
use FreeDSx\Snmp\SnmpClient;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class OidExplorerController extends Controller
{
    public function index()
    {
        return Inertia::render('Tools/OidExplorer', [
            'olts' => Olt::get(['id', 'name', 'ip_address']),
        ]);
    }

    public function explore(Request $request)
    {
        $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'oid'    => 'required|string',
            'method' => 'required|in:get,walk',
        ]);

        $olt = Olt::findOrFail($request->olt_id);
        
        $config = [
            'host'    => $olt->ip_address,
            'port'    => $olt->snmp_port ?? 161,
            'version' => $olt->snmp_version === 'v2c' ? 2 : ($olt->snmp_version === 'v1' ? 1 : 3),
            'timeout' => 5,
            'retries' => 1,
        ];

        if ($olt->snmp_version !== 'v3') {
            $config['community'] = $olt->snmp_community;
        } else {
            // (v3 setup omitted for brevity in explorer)
            $config['user'] = $olt->snmp_v3_username;
        }

        try {
            $snmp = new SnmpClient($config);
            $results = [];
            $startTime = microtime(true);

            if ($request->method === 'get') {
                $response = $snmp->get($request->oid);
                foreach ($response->getOids() as $oid) {
                    $results[] = [
                        'oid'   => $oid->getOid(),
                        'type'  => get_class($oid->getValue()),
                        'value' => (string) $oid->getValue(),
                    ];
                }
            } else {
                $walk = $snmp->walk($request->oid);
                while ($walk->hasOids()) {
                    $oid = $walk->next();
                    $results[] = [
                        'oid'   => $oid->getOid(),
                        'type'  => class_basename($oid->getValue()),
                        'value' => (string) $oid->getValue(),
                    ];
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'data'    => $results,
                'time_ms' => $duration,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);
        }
    }
}
