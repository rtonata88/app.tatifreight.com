<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The fuel price and the fuel alert thresholds (App\Services\FuelChecks), kept on CompanySetting.
 */
class FuelSettingController extends Controller
{
    /** Fuel alert thresholds (App\Services\FuelChecks). */
    private const THRESHOLDS = ['fuel_consumption_tolerance', 'fuel_min_km_between_fills', 'fuel_price_tolerance', 'fuel_trip_window_days'];

    public function edit(): Response
    {
        $settings = CompanySetting::get();

        return Inertia::render('settings/fuel', [
            'settings' => [
                ...collect(self::THRESHOLDS)->mapWithKeys(fn ($field) => [$field => $settings->{$field}])->all(),
                'fuel_price_per_litre' => $settings->fuel_price_per_litre !== null ? (float) $settings->fuel_price_per_litre : null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fuel_consumption_tolerance' => 'nullable|integer|min:1|max:100',
            'fuel_min_km_between_fills' => 'nullable|integer|min:0|max:5000',
            'fuel_price_tolerance' => 'nullable|integer|min:1|max:100',
            'fuel_trip_window_days' => 'nullable|integer|min:0|max:14',
            'fuel_price_per_litre' => 'nullable|numeric|min:0.01|max:1000',
        ]);

        $settings = CompanySetting::get();

        $data = [];
        foreach (self::THRESHOLDS as $field) {
            // A threshold left out or cleared keeps its current value.
            $data[$field] = $validated[$field] ?? $settings->{$field};
        }
        // Clearing the price is allowed: fill-ups then take the price the driver enters.
        $data['fuel_price_per_litre'] = $validated['fuel_price_per_litre'] ?? null;

        $settings->update($data);

        return back()->with('success', 'Fuel settings updated successfully!');
    }
}
