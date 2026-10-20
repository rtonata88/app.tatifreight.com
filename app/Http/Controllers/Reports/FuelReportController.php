<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Services\FuelReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/** Fuel used per vehicle and per driver against logbook distance and the expected rate. */
class FuelReportController extends Controller
{
    public function __construct(private FuelReport $report) {}

    public function show(Request $request): Response
    {
        [$dateFrom, $dateTo] = $this->range($request);

        return Inertia::render('reports/fuel', [
            'filters' => ['dateFrom' => $dateFrom, 'dateTo' => $dateTo],
            ...$this->report->build($dateFrom, $dateTo),
            // Rows further over expected than this are highlighted, as fill-ups are flagged.
            'tolerance' => CompanySetting::get()->fuel_consumption_tolerance,
        ]);
    }

    public function pdf(Request $request): HttpResponse
    {
        [$dateFrom, $dateTo] = $this->range($request);

        return Pdf::loadView('pdf.fuel-report', [
            ...$this->report->build($dateFrom, $dateTo),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'company' => $company = CompanySetting::get(),
            'tolerance' => $company->fuel_consumption_tolerance,
        ])->setPaper('a4', 'landscape')->download("Fuel-Report-{$dateFrom}-to-{$dateTo}.pdf");
    }

    /**
     * The last three months by default: enough for full-to-full fills and a trend.
     *
     * @return array{0: string, 1: string}
     */
    private function range(Request $request): array
    {
        $request->validate(['dateFrom' => 'nullable|date', 'dateTo' => 'nullable|date|after_or_equal:dateFrom']);

        return [
            (string) ($request->query('dateFrom') ?: now()->subMonths(2)->startOfMonth()->format('Y-m-d')),
            (string) ($request->query('dateTo') ?: now()->endOfMonth()->format('Y-m-d')),
        ];
    }
}
