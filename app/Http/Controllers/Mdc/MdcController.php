<?php

namespace App\Http\Controllers\Mdc;

use App\Http\Controllers\Controller;
use App\Models\MdcCalculation;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt component livewire/mdc/index.
 */
class MdcController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $vehicleFilter = (string) $request->query('vehicle', '');

        // The old screen defaulted the period to the current month on first load.
        // Once the user touches either date (the page then sends range=custom), an empty
        // value means "no date filter", exactly like clearing the Livewire inputs.
        $hasDateFilter = $request->query('range') === 'custom' || $request->has('date_from') || $request->has('date_to');
        $dateFrom = $hasDateFilter ? (string) $request->query('date_from', '') : now()->startOfMonth()->format('Y-m-d');
        $dateTo = $hasDateFilter ? (string) $request->query('date_to', '') : now()->endOfMonth()->format('Y-m-d');

        // Same query as before, including the ungrouped orWhereHas on vehicle.
        $query = MdcCalculation::with(['logbook.booking.client', 'logbook.driver', 'vehicle.vehicleType'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('logbook', function ($logbookQuery) use ($search) {
                    $logbookQuery->where('purpose', 'like', '%'.$search.'%')
                        ->orWhereHas('booking.client', function ($clientQuery) use ($search) {
                            $clientQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('company_name', 'like', '%'.$search.'%');
                        });
                })
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                        $vehicleQuery->where('reg_number', 'like', '%'.$search.'%');
                    });
            })
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('calculation_date', [$dateFrom, $dateTo]);
            })
            ->when($vehicleFilter, function ($q) use ($vehicleFilter) {
                $q->where('vehicle_id', $vehicleFilter);
            })
            ->orderBy('calculation_date', 'desc');

        $statsQuery = fn () => MdcCalculation::query()
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('calculation_date', [$dateFrom, $dateTo]);
            });

        // Total paid is all-time while total owed is for the selected period (as before).
        $totalPaid = (float) MdcCalculation::sum('amount_paid');
        $totalOwed = (float) $statsQuery()->sum('mdc_amount');
        $totalCount = $statsQuery()->count();

        return Inertia::render('mdc/index', [
            'mdcCalculations' => $query->paginate(15)->withQueryString()->through(fn (MdcCalculation $mdc) => self::row($mdc)),
            'vehicles' => Vehicle::orderBy('reg_number')->get()->map(fn (Vehicle $v) => ['value' => $v->id, 'label' => $v->reg_number]),
            // Hide links the user would only get a 403 from.
            'can' => [
                'recordPayment' => $request->user()->can('manage-mdc'),
                'viewReport' => $request->user()->can('view-reports'),
            ],
            'filters' => [
                'search' => $search,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'vehicle' => $vehicleFilter,
                'range' => $hasDateFilter ? 'custom' : '',
            ],
            'stats' => [
                'total_accumulated' => $totalOwed,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOwed - $totalPaid,
                'total_count' => $totalCount,
                'this_month' => (float) MdcCalculation::whereBetween('calculation_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('mdc_amount'),
                'average_per_calculation' => $totalCount > 0 ? $totalOwed / $totalCount : 0,
                'total_distance' => (float) $statsQuery()->sum('distance_km'),
                'total_mass' => (float) $statsQuery()->sum('total_mass'),
            ],
        ]);
    }

    /**
     * Row shape shared by the MDC list and the MDC report.
     *
     * @return array<string, mixed>
     */
    public static function row(MdcCalculation $mdc): array
    {
        $logbook = $mdc->logbook;
        $client = $logbook?->booking?->client;

        return [
            'id' => $mdc->id,
            'calculation_date' => $mdc->calculation_date?->format('Y-m-d'),
            'logbook' => $logbook ? [
                'date' => $logbook->date?->format('Y-m-d'),
                'origin_from' => $logbook->origin_from,
                'origin_to' => $logbook->origin_to,
                'booking_number' => $logbook->booking?->booking_number,
            ] : null,
            'client' => $client ? ['name' => $client->name, 'company_name' => $client->company_name] : null,
            'vehicle' => $mdc->vehicle ? [
                'reg_number' => $mdc->vehicle->reg_number,
                'type' => $mdc->vehicle->vehicleType?->name,
            ] : null,
            'distance_km' => (float) $mdc->distance_km,
            'gvm_tonnes' => $mdc->gvm_tonnes !== null ? (float) $mdc->gvm_tonnes : null,
            'mdc_amount' => (float) $mdc->mdc_amount,
            'amount_paid' => (float) $mdc->amount_paid,
            'outstanding_amount' => $mdc->outstanding_amount,
            'payment_status' => $mdc->payment_status,
        ];
    }
}
