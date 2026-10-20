<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\FuelAlert;
use App\Models\Logbook;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The fuel report (fuel monitoring spec, phase 2): for a date range, how much fuel each vehicle
 * and driver used against the distance the logbook shows, and against what was expected.
 * Fill-ups count unless rejected, so pending ones are watched too. Shared by the page and the PDF.
 */
class FuelReport
{
    public function __construct(private FuelChecks $checks) {}

    /**
     * @return array{totals: array<string, float|int|null>, vehicles: list<array<string, mixed>>, drivers: list<array<string, mixed>>, trend: array<string, mixed>}
     */
    public function build(string $dateFrom, string $dateTo): array
    {
        $fills = Expense::with(['vehicle', 'driver', 'user'])
            ->where('category', 'fuel')
            ->where('status', '!=', 'rejected')
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->get();

        $trips = Logbook::with('vehicle')
            ->whereNotNull('end_odometer')
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->get();

        $alerts = FuelAlert::whereIn('expense_id', $fills->pluck('id'))->get(['expense_id', 'reviewed_at']);

        $baselines = Vehicle::whereIn('id', $fills->pluck('vehicle_id')->merge($trips->pluck('vehicle_id'))->filter()->unique())
            ->get()
            ->mapWithKeys(fn (Vehicle $vehicle) => [$vehicle->id => $this->checks->vehicleBaseline($vehicle)]);

        $vehicles = $this->vehicleRows($fills, $trips, $alerts, $baselines);
        $drivers = $this->driverRows($fills, $trips, $alerts, $baselines);

        $litres = (float) $fills->sum('litres');
        $distance = $this->distance($trips);

        return [
            'totals' => [
                'litres' => round($litres, 2),
                'spend' => round((float) $fills->sum('amount'), 2),
                'distance' => round($distance, 1),
                'consumption' => $this->per100($litres, $distance),
                'fills' => $fills->count(),
                'open_alerts' => $alerts->whereNull('reviewed_at')->count(),
            ],
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'trend' => $this->trend($fills, $trips, $baselines, $dateFrom, $dateTo),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function vehicleRows(Collection $fills, Collection $trips, Collection $alerts, Collection $baselines): array
    {
        $ids = $fills->pluck('vehicle_id')->filter()->unique();
        $vehicles = Vehicle::with('vehicleType')->whereIn('id', $ids)->get()->keyBy('id');

        return $this->sorted($ids->map(function (int $id) use ($fills, $trips, $alerts, $baselines, $vehicles) {
            $vehicleFills = $fills->where('vehicle_id', $id);

            return $this->row(
                ['id' => $id, 'name' => $vehicles->get($id)?->reg_number, 'detail' => $vehicles->get($id)?->vehicleType?->name],
                $vehicleFills,
                $this->distance($trips->where('vehicle_id', $id)),
                $baselines->get($id),
                $alerts->whereIn('expense_id', $vehicleFills->pluck('id')),
            );
        }));
    }

    /**
     * A driver's fill-ups against the distance they logged. Their expected rate is the baseline
     * of each vehicle they drove, weighted by the km they drove it.
     *
     * @return list<array<string, mixed>>
     */
    private function driverRows(Collection $fills, Collection $trips, Collection $alerts, Collection $baselines): array
    {
        $driverOf = fn (Expense $fill) => $fill->driver_id ?? $fill->user_id;
        $ids = $fills->map($driverOf)->filter()->unique();
        $users = User::whereIn('id', $ids)->get()->keyBy('id');

        return $this->sorted($ids->map(function (int $id) use ($fills, $trips, $alerts, $baselines, $users, $driverOf) {
            $driverFills = $fills->filter(fn (Expense $fill) => $driverOf($fill) === $id);
            $driverTrips = $trips->where('driver_id', $id);

            $weighted = 0.0;
            $weight = 0.0;
            foreach ($driverTrips->groupBy('vehicle_id') as $vehicleId => $vehicleTrips) {
                if (($baseline = $baselines->get($vehicleId)) !== null) {
                    $km = $this->distance($vehicleTrips);
                    $weighted += $baseline * $km;
                    $weight += $km;
                }
            }

            $vehicles = $driverFills->pluck('vehicle.reg_number')->merge($driverTrips->pluck('vehicle.reg_number'))->filter()->unique()->sort()->values();

            return $this->row(
                ['id' => $id, 'name' => $users->get($id)?->name, 'detail' => $vehicles->join(', ')],
                $driverFills,
                $this->distance($driverTrips),
                $weight > 0 ? round($weighted / $weight, 2) : null,
                $alerts->whereIn('expense_id', $driverFills->pluck('id')),
            );
        }));
    }

    /** @return array<string, mixed> */
    private function row(array $who, Collection $fills, float $distance, ?float $expected, Collection $alerts): array
    {
        $litres = (float) $fills->sum('litres');
        $spend = (float) $fills->sum('amount');
        $consumption = $this->per100($litres, $distance);

        return [
            ...$who,
            'fills' => $fills->count(),
            'litres' => round($litres, 2),
            'spend' => round($spend, 2),
            'distance' => round($distance, 1),
            'consumption' => $consumption,
            'expected' => $expected,
            'over_percent' => $consumption !== null && $expected ? round(($consumption / $expected - 1) * 100, 1) : null,
            'cost_per_km' => $distance > 0 ? round($spend / $distance, 2) : null,
            'alerts' => $alerts->count(),
            'open_alerts' => $alerts->whereNull('reviewed_at')->count(),
        ];
    }

    /**
     * Furthest over expected first; rows that cannot be compared go last, most litres first.
     *
     * @return list<array<string, mixed>>
     */
    private function sorted(Collection $rows): array
    {
        return $rows->sort(function (array $a, array $b) {
            if (($a['over_percent'] === null) !== ($b['over_percent'] === null)) {
                return $a['over_percent'] === null ? 1 : -1;
            }

            return $a['over_percent'] === null
                ? $b['litres'] <=> $a['litres']
                : $b['over_percent'] <=> $a['over_percent'];
        })->values()->all();
    }

    /**
     * L/100 km per vehicle per month across the range, with each vehicle's expected rate.
     *
     * @return array{months: list<string>, vehicles: list<array<string, mixed>>}
     */
    private function trend(Collection $fills, Collection $trips, Collection $baselines, string $dateFrom, string $dateTo): array
    {
        $months = [];
        for ($month = CarbonImmutable::parse($dateFrom)->startOfMonth(); $month->lte(CarbonImmutable::parse($dateTo)); $month = $month->addMonth()) {
            $months[] = $month->format('Y-m');
        }

        $vehicles = $fills->pluck('vehicle')->filter()->unique('id')->sortBy('reg_number')->values();

        return [
            'months' => $months,
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'reg_number' => $vehicle->reg_number,
                'expected' => $baselines->get($vehicle->id),
                'points' => collect($months)->map(fn (string $month) => $this->per100(
                    (float) $fills->where('vehicle_id', $vehicle->id)->filter(fn (Expense $fill) => $fill->expense_date->format('Y-m') === $month)->sum('litres'),
                    $this->distance($trips->where('vehicle_id', $vehicle->id)->filter(fn (Logbook $trip) => $trip->date->format('Y-m') === $month)),
                ))->all(),
            ])->all(),
        ];
    }

    private function distance(Collection $trips): float
    {
        return (float) $trips->sum(fn (Logbook $trip) => max(0, (float) $trip->end_odometer - (float) $trip->start_odometer));
    }

    private function per100(float $litres, float $distance): ?float
    {
        return $litres > 0 && $distance > 0 ? round($litres / $distance * 100, 1) : null;
    }
}
