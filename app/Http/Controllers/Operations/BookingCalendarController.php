<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt component livewire/bookings/calendar.
 * The month grid itself is built in React from the bookings sent here.
 */
class BookingCalendarController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $requested = (string) $request->query('date', '');
        try {
            $date = $requested !== '' ? Carbon::parse($requested) : now();
        } catch (\Throwable) {
            $date = now();
        }

        $selectedVehicle = (string) $request->query('vehicle', '');

        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        $bookings = Booking::with(['client', 'vehicle.vehicleType', 'driver', 'createdBy', 'invoice'])
            // Grouped so the vehicle filter applies to every booking in the month
            // (the Volt query left these ORs ungrouped, see the conversion report).
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->when($selectedVehicle, fn ($q) => $q->where('vehicle_id', $selectedVehicle))
            ->orderBy('start_date')
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'status' => $booking->status,
                // Y-m-d for placing the booking in the grid, d M Y / d M Y, H:i for display.
                'start_date' => $booking->start_date?->format('Y-m-d'),
                'end_date' => $booking->end_date?->format('Y-m-d'),
                'start_display' => $booking->start_date?->format('d M Y, H:i'),
                'end_display' => $booking->end_date?->format('d M Y, H:i'),
                'duration_days' => (int) round($booking->start_date->diffInDays($booking->end_date)) + 1,
                'distance_km' => $booking->distance_km !== null ? (float) $booking->distance_km : null,
                'pickup_location' => $booking->pickup_location,
                'notes' => $booking->notes,
                'created_at' => $booking->created_at?->format('d M Y, H:i'),
                'created_by' => $booking->createdBy?->name,
                'client' => [
                    'name' => $booking->client?->name,
                    'company_name' => $booking->client?->company_name,
                    'phone' => $booking->client?->phone,
                    'email' => $booking->client?->email,
                ],
                'vehicle' => [
                    'reg_number' => $booking->vehicle?->reg_number,
                    'type' => $booking->vehicle?->vehicleType?->name,
                    'make' => $booking->vehicle?->make,
                    'model' => $booking->vehicle?->model,
                ],
                'driver' => $booking->driver ? [
                    'name' => $booking->driver->name,
                    'email' => $booking->driver->email,
                ] : null,
                'invoice' => $booking->invoice ? [
                    'total' => (float) $booking->invoice->total,
                    'status' => $booking->invoice->status,
                ] : null,
            ]);

        return Inertia::render('bookings/calendar', [
            'date' => $date->format('Y-m-d'),
            'today' => now()->format('Y-m-d'),
            'bookings' => $bookings,
            'vehicles' => Vehicle::with('vehicleType')->orderBy('reg_number')->get()
                ->map(fn (Vehicle $vehicle) => [
                    'value' => $vehicle->id,
                    'label' => $vehicle->reg_number.' - '.$vehicle->vehicleType?->name,
                ]),
            'filters' => ['vehicle' => $selectedVehicle],
            'can' => [
                'create' => $request->user()->can('create-bookings'),
                'edit' => $request->user()->can('edit-bookings'),
            ],
        ]);
    }
}
