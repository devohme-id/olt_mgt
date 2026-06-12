<?php

namespace App\Http\Controllers\Web;

use App\Application\Alarm\Actions\AlarmService;
use App\Domain\Alarm\Models\Alarm;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class AlarmController extends Controller
{
    public function __construct(
        private AlarmService $alarmService,
    ) {}

    public function index(Request $request)
    {
        $alarms = $this->alarmService->list($request->only([
            'status', 'severity', 'entity_type', 'per_page'
        ]));

        return Inertia::render('Alarm/Index', [
            'alarms'  => $alarms,
            'filters' => $request->only(['status', 'severity', 'entity_type']),
        ]);
    }

    public function acknowledge(Alarm $alarm)
    {
        $this->alarmService->acknowledge($alarm, auth()->id());
        return back()->with('success', 'Alarm acknowledged.');
    }

    public function resolve(Request $request, Alarm $alarm)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);
        $this->alarmService->resolve($alarm, auth()->id(), $request->notes);
        return back()->with('success', 'Alarm resolved.');
    }
}
