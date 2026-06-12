<?php

namespace App\Http\Controllers\Web;

use App\Application\Reporting\Actions\ReportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
    ) {}

    public function index()
    {
        return Inertia::render('Report/Index', [
            'overview' => $this->reportService->generateOverviewReport(),
        ]);
    }

    public function exportOptical()
    {
        $data = $this->reportService->generateOpticalReport();

        $response = new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ONU SN', 'Customer', 'Status', 'OLT', 'Port', 'Rx Power (dBm)']);

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['serial_number'] ?? 'N/A',
                    $row['customer_name'] ?? 'Unassigned',
                    $row['status'],
                    $row['olt']['name'] ?? 'N/A',
                    $row['pon_port']['port_name'] ?? 'N/A',
                    $row['optical_rx_power'] ?? 'N/A',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="optical_report_' . date('Ymd_His') . '.csv"');

        return $response;
    }

    public function exportAlarms(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString() . ' 00:00:00');
        $endDate = $request->input('end_date', now()->toDateString() . ' 23:59:59');

        $data = $this->reportService->generateAlarmReport($startDate, $endDate);

        $response = new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Time', 'Severity', 'Entity', 'Source', 'Message', 'Status', 'Cleared At']);

            foreach ($data as $row) {
                $source = $row['entity_type'] === 'olt' ? ($row['olt']['name'] ?? $row['entity_id']) :
                          ($row['entity_type'] === 'onu' ? ($row['onu']['serial_number'] ?? $row['entity_id']) : 'System');

                fputcsv($handle, [
                    $row['created_at'],
                    $row['severity'],
                    $row['entity_type'],
                    $source,
                    $row['message'],
                    $row['status'],
                    $row['cleared_at'] ?? '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="alarm_report_' . date('Ymd') . '.csv"');

        return $response;
    }
}
