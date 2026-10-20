<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\FuelAlert;
use App\Models\Logbook;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

/**
 * Checks a fuel fill-up against the vehicle's history and logbook, as the fuel monitoring
 * spec describes, and keeps one FuelAlert per failed check. Runs whenever a fuel expense is
 * saved; reviewed alerts are left as they are.
 */
class FuelChecks
{
    /** Odometer readings within this many km of the logbook's range for the day still match. */
    private const ODOMETER_SLACK_KM = 10;

    /** How many earlier fills give the vehicle's usual price, and how many are needed to judge. */
    private const PRICE_HISTORY = 5;

    private const PRICE_HISTORY_MIN = 3;

    /** Full-to-full fills averaged into a learned baseline when the vehicle has none set. */
    private const LEARNED_BASELINE_FILLS = 5;

    public function check(Expense $expense): void
    {
        $expense->fuelAlerts()->open()->delete();

        if ($expense->category !== 'fuel' || $expense->trashed()) {
            if ($expense->consumption_l_per_100km !== null) {
                $expense->forceFill(['consumption_l_per_100km' => null])->saveQuietly();
            }

            return;
        }

        $expense->loadMissing('vehicle');
        $settings = CompanySetting::get();
        $findings = [];

        $consumption = $this->consumption($expense);
        $expense->forceFill(['consumption_l_per_100km' => $consumption])->saveQuietly();

        if ($consumption !== null && ($baseline = $this->baseline($expense)) !== null) {
            $limit = $baseline * (1 + $settings->fuel_consumption_tolerance / 100);
            if ($consumption > $limit) {
                $over = ($consumption / $baseline - 1) * 100;
                $findings['high_consumption'] = sprintf(
                    'Used %s L/100 km against %s expected (+%d%%).',
                    $this->number($consumption), $this->number($baseline), round($over),
                );
            }
        }

        $capacity = $expense->vehicle?->tank_capacity;
        if ($capacity && $expense->litres !== null && (float) $expense->litres > (float) $capacity) {
            $findings['over_tank_capacity'] = sprintf(
                '%s L filled; the tank holds %s L.', $this->number($expense->litres), $this->number($capacity),
            );
        }

        if ($expense->vehicle_id && ! $this->hadTrips($expense, $settings->fuel_trip_window_days)) {
            $findings['no_trips'] = sprintf(
                'No logbook trip for this vehicle within %d %s of the fill-up.',
                $settings->fuel_trip_window_days, $settings->fuel_trip_window_days === 1 ? 'day' : 'days',
            );
        }

        $previous = $this->previousFill($expense);
        if ($previous && $expense->odometer !== null) {
            $distance = (float) $expense->odometer - (float) $previous->odometer;

            if ($distance < 0) {
                $findings['odometer_mismatch'] = sprintf(
                    'Odometer %s km is lower than the previous fill-up (%s km on %s).',
                    $this->number($expense->odometer, 0), $this->number($previous->odometer, 0), $previous->expense_date->format('d M Y'),
                );
            } elseif ($distance < $settings->fuel_min_km_between_fills) {
                $findings['fills_too_close'] = sprintf(
                    'Only %s km since the previous fill-up on %s.', $this->number($distance, 0), $previous->expense_date->format('d M Y'),
                );
            }
        }

        if (! isset($findings['odometer_mismatch']) && ($mismatch = $this->logbookMismatch($expense))) {
            $findings['odometer_mismatch'] = $mismatch;
        }

        if ($expense->price_per_litre !== null && ($usual = $this->usualPrice($expense)) !== null) {
            $price = (float) $expense->price_per_litre;
            if ($price > $usual * (1 + $settings->fuel_price_tolerance / 100)) {
                $findings['price_out_of_range'] = sprintf(
                    'N$%s per litre against a recent average of N$%s (+%d%%).',
                    $this->number($price), $this->number($usual), round(($price / $usual - 1) * 100),
                );
            }
        }

        if (! $expense->receipt_path) {
            $findings['missing_receipt'] = 'No receipt photo attached.';
        }

        $reviewed = $expense->fuelAlerts()->whereNotNull('reviewed_at')->pluck('type')->all();

        foreach ($findings as $type => $message) {
            if (in_array($type, $reviewed, true)) {
                continue;
            }

            $expense->fuelAlerts()->create([
                'vehicle_id' => $expense->vehicle_id,
                'driver_id' => $expense->driver_id ?? $expense->user_id,
                'type' => $type,
                'message' => $message,
            ]);
        }
    }

    /**
     * Litres added since the previous full fill (this one included) over the km since then.
     * Null for a partial fill, or when there is no earlier full fill to measure from.
     */
    private function consumption(Expense $expense): ?float
    {
        if (! $expense->full_tank || $expense->odometer === null || $expense->litres === null) {
            return null;
        }

        $previousFull = $this->otherFills($expense)
            ->where('full_tank', true)
            ->where('odometer', '<', $expense->odometer)
            ->orderByDesc('odometer')
            ->first();

        if (! $previousFull) {
            return null;
        }

        $distance = (float) $expense->odometer - (float) $previousFull->odometer;
        if ($distance <= 0) {
            return null;
        }

        // Partial top-ups in between count towards this full-to-full measurement.
        $topUps = (float) $this->otherFills($expense)
            ->where('odometer', '>', $previousFull->odometer)
            ->where('odometer', '<', $expense->odometer)
            ->sum('litres');

        return round(($topUps + (float) $expense->litres) / $distance * 100, 2);
    }

    /** The vehicle's expected L/100 km, or the average of its first full-to-full fills. */
    private function baseline(Expense $expense): ?float
    {
        if ($expense->vehicle?->expected_consumption) {
            return (float) $expense->vehicle->expected_consumption;
        }

        $first = $this->otherFills($expense)
            ->whereNotNull('consumption_l_per_100km')
            ->where('odometer', '<', $expense->odometer)
            ->orderBy('odometer')
            ->limit(self::LEARNED_BASELINE_FILLS)
            ->pluck('consumption_l_per_100km');

        // Until the vehicle has that many measurements of its own, there is nothing to compare with.
        return $first->count() === self::LEARNED_BASELINE_FILLS ? round((float) $first->avg(), 2) : null;
    }

    /**
     * The vehicle's expected L/100 km as the report shows it: the set value, or the average of
     * its first full-to-full fills once it has enough of them.
     */
    public function vehicleBaseline(Vehicle $vehicle): ?float
    {
        if ($vehicle->expected_consumption) {
            return (float) $vehicle->expected_consumption;
        }

        $first = Expense::where('vehicle_id', $vehicle->id)
            ->where('category', 'fuel')
            ->where('status', '!=', 'rejected')
            ->whereNotNull('consumption_l_per_100km')
            ->orderBy('odometer')
            ->limit(self::LEARNED_BASELINE_FILLS)
            ->pluck('consumption_l_per_100km');

        return $first->count() === self::LEARNED_BASELINE_FILLS ? round((float) $first->avg(), 2) : null;
    }

    private function hadTrips(Expense $expense, int $windowDays): bool
    {
        return Logbook::where('vehicle_id', $expense->vehicle_id)
            ->whereDate('date', '>=', $expense->expense_date->copy()->subDays($windowDays))
            ->whereDate('date', '<=', $expense->expense_date->copy()->addDays($windowDays))
            ->exists();
    }

    /** The latest earlier fill-up of the same vehicle with an odometer reading. */
    private function previousFill(Expense $expense): ?Expense
    {
        return $this->otherFills($expense)
            ->whereNotNull('odometer')
            ->where(fn ($q) => $q->where('expense_date', '<', $expense->expense_date)
                ->orWhere(fn ($q) => $q->where('expense_date', $expense->expense_date)->where('id', '<', $expense->id)))
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->first();
    }

    /** A reading outside the odometer range the logbook shows for that vehicle on that day. */
    private function logbookMismatch(Expense $expense): ?string
    {
        if (! $expense->vehicle_id || $expense->odometer === null) {
            return null;
        }

        $trips = Logbook::where('vehicle_id', $expense->vehicle_id)
            ->whereDate('date', $expense->expense_date)
            ->get(['start_odometer', 'end_odometer']);

        if ($trips->isEmpty()) {
            return null;
        }

        $low = (float) $trips->min('start_odometer');
        $high = (float) ($trips->max('end_odometer') ?? $trips->max('start_odometer'));
        $reading = (float) $expense->odometer;

        if ($reading >= $low - self::ODOMETER_SLACK_KM && $reading <= $high + self::ODOMETER_SLACK_KM) {
            return null;
        }

        return sprintf(
            'Odometer %s km is outside the logbook range for that day (%s–%s km).',
            $this->number($reading, 0), $this->number($low, 0), $this->number($high, 0),
        );
    }

    /** Average price per litre over the vehicle's recent earlier fill-ups. */
    private function usualPrice(Expense $expense): ?float
    {
        $prices = $this->otherFills($expense)
            ->whereNotNull('price_per_litre')
            ->where('expense_date', '<=', $expense->expense_date)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->limit(self::PRICE_HISTORY)
            ->pluck('price_per_litre');

        return $prices->count() >= self::PRICE_HISTORY_MIN ? (float) $prices->avg() : null;
    }

    /** @return Builder<Expense> The vehicle's other fuel fill-ups that were not rejected. */
    private function otherFills(Expense $expense): Builder
    {
        return Expense::where('vehicle_id', $expense->vehicle_id)
            ->where('category', 'fuel')
            ->where('status', '!=', 'rejected')
            ->whereKeyNot($expense->id);
    }

    private function number(float|string|null $value, int $decimals = 1): string
    {
        return number_format((float) $value, $decimals);
    }
}
