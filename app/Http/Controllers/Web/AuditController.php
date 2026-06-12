<?php

namespace App\Http\Controllers\Web;

use App\Domain\Identity\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name');

        if ($request->has('module') && $request->module) {
            $query->where('module', $request->module);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'filters' => $request->only(['module']),
        ]);
    }
}
