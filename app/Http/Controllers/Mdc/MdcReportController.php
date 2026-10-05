<?php

namespace App\Http\Controllers\Mdc;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\MdcCalculation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt component livewire/reports/mdc.
 */
class MdcReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $dateFrom = (string) $request->query('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = (string) $request->query('date_to', now()->endOfMonth()->format('Y-m-d'));
        $groupBy = (string) $request->query('group_by', 'none');
        if (! in_array($groupBy, ['none', 'vehicle', 'client', 'month'], true)) {
            $groupBy = 'none';
        }

        $period = fn () => MdcCalculation::whereBetween('calculation_date', [$dateFrom, $dateTo]);

        $totalMdc = (float) $period()->sum('mdc_amount');
        $totalPaid = (float) $period()->sum('amount_paid');
        $totalCount = $period()->count();
        $totalDistance = (float) $period()->sum('distance_km');
        $totalMass = (float) $period()->sum('total_mass');

        return Inertia::render('reports/mdc', [
            'filters' => ['date_from' => $dateFrom, 'date_to' => $dateTo, 'group_by' => $groupBy],
            'totalMdc' => $totalMdc,
            'totalPaid' => $totalPaid,
            'totalOutstanding' => $totalMdc - $totalPaid,
            'totalCount' => $totalCount,
            'totalDistance' => $totalDistance,
            'totalMass' => $totalMass,
            'avgPerCalculation' => $totalCount > 0 ? $totalMdc / $totalCount : 0,
            'groupedData' => $this->groupedData($groupBy, $dateFrom, $dateTo),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groupedData(string $groupBy, string $dateFrom, string $dateTo): array
    {
        if ($groupBy === 'vehicle') {
            return MdcCalculation::with('vehicle.vehicleType')
                ->whereBetween('calculation_date', [$dateFrom, $dateTo])
                ->select('vehicle_id',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(distance_km) as total_distance'),
                    DB::raw('SUM(mdc_amount) as total_amount'))
                ->groupBy('vehicle_id')
                ->get()
                ->map(fn ($row) => [
                    'vehicle_id' => $row->vehicle_id,
                    'reg_number' => $row->vehicle?->reg_number,
                    'type' => $row->vehicle?->vehicleType?->name,
                    'count' => (int) $row->count,
                    'total_distance' => (float) $row->total_distance,
                    'total_amount' => (float) $row->total_amount,
                ])
                ->all();
        }

        if ($groupBy === 'client') {
            $rows = MdcCalculation::query()
                ->whereBetween('calculation_date', [$dateFrom, $dateTo])
                ->join('logbooks', 'mdc_calculations.logbook_id', '=', 'logbooks.id')
                ->leftJoin('bookings', 'logbooks.booking_id', '=', 'bookings.id')
                ->select('bookings.client_id',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(mdc_calculations.distance_km) as total_distance'),
                    DB::raw('SUM(mdc_calculations.mdc_amount) as total_amount'))
                ->whereNotNull('bookings.client_id')
                ->groupBy('bookings.client_id')
                ->get();

            // The old view looked each client up with Client::find() (soft-deleted clients show N/A).
            $clients = Client::whereIn('id', $rows->pluck('client_id'))->get()->keyBy('id');

            return $rows->map(fn ($row) => [
                'client_id' => $row->client_id,
                'client' => ($client = $clients->get($row->client_id))
                    ? ['name' => $client->name, 'company_name' => $client->company_name]
                    : null,
                'count' => (int) $row->count,
                'total_distance' => (float) $row->total_distance,
                'total_amount' => (float) $row->total_amount,
            ])->all();
        }

        if ($groupBy === 'month') {
            // DATE_FORMAT is MySQL; the SQLite test database needs strftime.
            $monthExpression = DB::getDriverName() === 'sqlite'
                ? "strftime('%Y-%m', calculation_date)"
                : 'DATE_FORMAT(calculation_date, "%Y-%m")';

            $runningTotal = 0.0;

            return MdcCalculation::whereBetween('calculation_date', [$dateFrom, $dateTo])
                ->select(
                    DB::raw($monthExpression.' as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(distance_km) as total_distance'),
                    DB::raw('SUM(mdc_amount) as total_amount'))
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(function ($row) use (&$runningTotal) {
                    $runningTotal += (float) $row->total_amount;

                    return [
                        'month' => $row->month,
                        // Day pinned to the 1st: createFromFormat('Y-m') borrows today's day and can overflow (e.g. 31 Feb).
                        'month_label' => Carbon::createFromFormat('Y-m-d', $row->month.'-01')->format('F Y'),
                        'count' => (int) $row->count,
                        'total_distance' => (float) $row->total_distance,
                        'total_amount' => (float) $row->total_amount,
                        'running_total' => $runningTotal,
                    ];
                })
                ->all();
        }

        return MdcCalculation::with(['logbook.booking.client', 'logbook.driver', 'vehicle.vehicleType'])
            ->whereBetween('calculation_date', [$dateFrom, $dateTo])
            ->orderBy('calculation_date', 'desc')
            ->get()
            ->map(fn (MdcCalculation $mdc) => MdcController::row($mdc))
            ->all();
    }
}
