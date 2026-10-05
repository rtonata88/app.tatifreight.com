<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Client;
use App\Models\CompanyBankAccount;
use App\Models\Quote;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt components livewire/quotes/{index,create,edit}.
 * PDF view/download stay in App\Http\Controllers\QuoteController.
 */
class QuoteManagementController extends Controller
{
    /** South African VAT, as hard-coded in the old create/edit components. */
    public const TAX_RATE = 15;

    public const STATUSES = ['draft', 'sent', 'approved', 'rejected', 'expired'];

    public const UNITS = ['trip', 'day', 'hour', 'km', 'tonne', 'load', 'pallet', 'container', 'cbm', 'item', 'week', 'month'];

    public const DEFAULT_TERMS = "1. Quote valid for 30 days from issue date\n2. Payment terms as per agreement\n3. Prices subject to change based on fuel levy\n4. Booking confirmation required 24 hours in advance";

    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $user = $request->user();

        $quotes = Quote::with(['client', 'createdBy', 'booking'])
            ->when($search, function ($q) use ($search) {
                // Same (un-grouped) where/orWhereHas as the old component.
                $q->where('quote_number', 'like', '%'.$search.'%')
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('company_name', 'like', '%'.$search.'%');
                    });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Quote $quote) => [
                'id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'version' => (int) $quote->version,
                'client_name' => $quote->client?->name,
                'client_company' => $quote->client?->company_name,
                'created_by' => $quote->createdBy?->name,
                'valid_until' => $quote->valid_until?->format('Y-m-d'),
                'valid_until_past' => (bool) $quote->valid_until?->isPast(),
                'created_at' => $quote->created_at?->format('Y-m-d'),
                'subtotal' => (float) $quote->subtotal,
                'total' => (float) $quote->total,
                'status' => $quote->status,
                'has_booking' => $quote->booking !== null,
            ]);

        return Inertia::render('quotes/index', [
            'quotes' => $quotes,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'draft' => Quote::where('status', 'draft')->count(),
                'sent' => Quote::where('status', 'sent')->count(),
                'approved' => Quote::where('status', 'approved')->count(),
                'rejected' => Quote::where('status', 'rejected')->count(),
                'expired' => Quote::where('status', 'expired')->count(),
            ],
            'can' => [
                'view' => $user->can('view-quotes'),
                'create' => $user->can('create-quotes'),
                'edit' => $user->can('edit-quotes'),
                'delete' => $user->can('delete-quotes'),
                'createBookings' => $user->can('create-bookings'),
            ],
        ]);
    }

    public function create(): Response
    {
        $primaryAccount = CompanyBankAccount::primary();

        return Inertia::render('quotes/create', [
            'clients' => $this->clientOptions(),
            'vehicles' => $this->vehicleOptions(
                Vehicle::with('vehicleType')->whereIn('status', ['available', 'in_use', 'maintenance'])
            ),
            'bankAccounts' => CompanyBankAccount::active()->map(fn (CompanyBankAccount $account) => [
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'is_primary' => (bool) $account->is_primary,
            ])->values(),
            'defaults' => [
                'valid_until' => now()->addDays(30)->format('Y-m-d'),
                'company_bank_account_id' => $primaryAccount?->id,
                'terms_conditions' => self::DEFAULT_TERMS,
            ],
            'taxRate' => self::TAX_RATE,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_bank_account_id' => 'nullable|exists:company_bank_accounts,id',
            'description' => 'nullable|string',
            'valid_until' => 'required|date',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            ...$this->itemRules(),
        ]);

        $items = $validated['items'] ?? [];
        if (empty($items)) {
            return back()->with('error', 'Please add at least one line item');
        }

        [$lines, $totals] = $this->calculate($items);

        DB::transaction(function () use ($validated, $lines, $totals, $request) {
            $quote = Quote::create([
                'quote_number' => $this->nextQuoteNumber(),
                'client_id' => $validated['client_id'],
                'company_bank_account_id' => $validated['company_bank_account_id'] ?? null,
                'created_by' => $request->user()->id,
                'version' => 1,
                'status' => 'draft',
                'valid_until' => $validated['valid_until'],
                'description' => $validated['description'] ?? null,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
                ...$totals,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $quote->lineItems()->create($line);
            }
        });

        return redirect()->route('quotes.index')->with('success', 'Quote created successfully!');
    }

    public function edit(Quote $quote): Response
    {
        $quote->load(['lineItems', 'createdBy']);

        return Inertia::render('quotes/edit', [
            'quote' => [
                'id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'client_id' => $quote->client_id,
                'description' => $quote->description,
                'valid_until' => $quote->valid_until?->format('Y-m-d'),
                'terms_conditions' => $quote->terms_conditions,
                'notes' => $quote->notes,
                'status' => $quote->status,
                'subtotal' => (float) $quote->subtotal,
                'tax_amount' => (float) $quote->tax_amount,
                'total' => (float) $quote->total,
                'created_at' => $quote->created_at?->format('d M Y, H:i'),
                'created_by' => $quote->createdBy?->name,
                'sent_at' => $quote->sent_at?->format('d M Y, H:i'),
                'approved_at' => $quote->approved_at?->format('d M Y, H:i'),
                'line_items' => $quote->lineItems->map(fn ($item) => [
                    'id' => $item->id,
                    'vehicle_id' => $item->vehicle_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'amount' => (float) $item->amount,
                    'unit' => $item->unit,
                ])->values(),
            ],
            'hasBooking' => $quote->booking()->exists(),
            'clients' => $this->clientOptions(),
            // The old edit screen only offered available vehicles.
            'vehicles' => $this->vehicleOptions(Vehicle::with('vehicleType')->where('status', 'available')),
            'taxRate' => self::TAX_RATE,
            'can' => [
                'view' => request()->user()->can('view-quotes'),
            ],
        ]);
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'description' => 'nullable|string',
            'valid_until' => 'required|date',
            'terms_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,approved,rejected,expired',
            ...$this->itemRules(),
            'items.*.id' => 'nullable|integer',
        ]);

        $items = $validated['items'] ?? [];
        if (empty($items)) {
            return back()->with('error', 'Please add at least one line item');
        }

        [$lines, $totals] = $this->calculate($items);

        DB::transaction(function () use ($quote, $validated, $items, $lines, $totals) {
            $quote->update([
                'client_id' => $validated['client_id'],
                'status' => $validated['status'],
                'valid_until' => $validated['valid_until'],
                'description' => $validated['description'] ?? null,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
                ...$totals,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Lines removed in the form are deleted (the old screen deleted them as soon as
            // the row was removed); existing lines are updated, new ones created.
            $keepIds = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $quote->lineItems()->whereNotIn('id', $keepIds)->delete();

            foreach ($items as $i => $item) {
                $existing = ! empty($item['id']) ? $quote->lineItems()->whereKey($item['id'])->first() : null;

                if ($existing) {
                    $existing->update($lines[$i]);
                } else {
                    $quote->lineItems()->create($lines[$i]);
                }
            }
        });

        return redirect()->route('quotes.index')->with('success', 'Quote updated successfully!');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return back()->with('success', 'Quote deleted successfully');
    }

    public function markAsSent(Quote $quote): RedirectResponse
    {
        $quote->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', 'Quote marked as sent');
    }

    public function markAsApproved(Quote $quote): RedirectResponse
    {
        $quote->update(['status' => 'approved', 'approved_at' => now()]);

        return back()->with('success', 'Quote approved');
    }

    public function markAsRejected(Quote $quote): RedirectResponse
    {
        $quote->update(['status' => 'rejected']);

        return back()->with('success', 'Quote rejected');
    }

    public function markAsExpired(Quote $quote): RedirectResponse
    {
        $quote->update(['status' => 'expired']);

        return back()->with('success', 'Quote marked as expired');
    }

    public function duplicate(Request $request, Quote $quote): RedirectResponse
    {
        $quote->load('lineItems');

        DB::transaction(function () use ($quote, $request) {
            // Same fields as the old duplicateQuote() (bank account was not copied).
            $newQuote = Quote::create([
                'quote_number' => $this->nextQuoteNumber(),
                'client_id' => $quote->client_id,
                'created_by' => $request->user()->id,
                'version' => 1,
                'status' => 'draft',
                'valid_until' => now()->addDays(30),
                'description' => $quote->description,
                'terms_conditions' => $quote->terms_conditions,
                'subtotal' => $quote->subtotal,
                'tax_amount' => $quote->tax_amount,
                'total' => $quote->total,
                'notes' => $quote->notes,
            ]);

            foreach ($quote->lineItems as $item) {
                $newQuote->lineItems()->create([
                    'vehicle_id' => $item->vehicle_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                    'unit' => $item->unit,
                ]);
            }
        });

        return back()->with('success', 'Quote duplicated successfully');
    }

    /**
     * Index: stays on the list. Edit page sends open_booking=1 and is taken to the
     * new booking, like the old edit component's convertToBooking().
     */
    public function convertToBooking(Request $request, Quote $quote): RedirectResponse
    {
        if (! $request->user()->can('create-bookings')) {
            return back()->with('error', 'You do not have permission to create bookings');
        }

        $quote->load('lineItems');

        if ($quote->status !== 'approved') {
            return back()->with('error', 'Only approved quotes can be converted to bookings');
        }

        if ($quote->booking()->exists()) {
            return back()->with('error', 'This quote has already been converted to a booking');
        }

        $vehicleItem = $quote->lineItems->whereNotNull('vehicle_id')->first();

        if (! $vehicleItem) {
            return back()->with('error', 'Quote must have at least one line item with a vehicle to convert to booking');
        }

        $lastBooking = Booking::withTrashed()->latest('id')->first(); // include deleted bookings: numbers are unique
        $nextNumber = $lastBooking ? (int) substr($lastBooking->booking_number, 4) + 1 : 1;
        $bookingNumber = 'BKG-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

        $booking = Booking::create([
            'booking_number' => $bookingNumber,
            'client_id' => $quote->client_id,
            'vehicle_id' => $vehicleItem->vehicle_id,
            'quote_id' => $quote->id,
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDays(1),
            'notes' => $quote->description,
        ]);

        $message = 'Booking created successfully from quote!';

        if ($request->boolean('open_booking')) {
            $target = Route::has('bookings.edit')
                ? route('bookings.edit', $booking->id)
                : url('bookings/'.$booking->id.'/edit');

            return redirect()->to($target)->with('success', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Line item rules. The old components had no rules for line items (only error slots
     * for lineItems.*.vehicle_id / description); these keep bad rows from hitting the DB.
     *
     * @return array<string, string>
     */
    private function itemRules(): array
    {
        return [
            'items' => 'nullable|array',
            'items.*.vehicle_id' => 'nullable|exists:vehicles,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.unit' => 'required|in:'.implode(',', self::UNITS),
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric',
        ];
    }

    /**
     * Server-side version of calculateLineAmount() / calculateTotals():
     * amount = qty × unit price, VAT = subtotal × 15%, total = subtotal + VAT.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: array{subtotal: float, tax_amount: float, total: float}}
     */
    public static function calculate(array $items): array
    {
        $lines = [];
        $subtotal = 0.0;

        foreach (array_values($items) as $i => $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $amount = $quantity * $unitPrice;
            $subtotal += $amount;

            $lines[$i] = [
                'vehicle_id' => ($item['vehicle_id'] ?? null) ?: null,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'unit' => $item['unit'] ?? 'trip',
            ];
        }

        $taxAmount = $subtotal * (self::TAX_RATE / 100);

        return [$lines, [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ]];
    }

    /** QT-000001, QT-000002 … based on the latest quote, as before. */
    private function nextQuoteNumber(): string
    {
        $lastQuote = Quote::withTrashed()->latest('id')->first(); // include deleted quotes: numbers are unique
        $nextNumber = $lastQuote ? (int) substr($lastQuote->quote_number, 3) + 1 : 1;

        return 'QT-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function clientOptions()
    {
        return Client::where('is_active', true)->orderBy('name')->get()->map(fn (Client $client) => [
            'value' => $client->id,
            'label' => $client->name.($client->company_name ? ' ('.$client->company_name.')' : ''),
        ]);
    }

    /**
     * Vehicles for the line item picker, with what loadVehicleRate() used.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Vehicle>  $query
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function vehicleOptions($query)
    {
        return $query->orderBy('reg_number')->get()->map(fn (Vehicle $vehicle) => [
            'id' => $vehicle->id,
            'reg_number' => $vehicle->reg_number,
            'type_name' => $vehicle->vehicleType?->name,
            'base_rate_daily' => $vehicle->vehicleType?->base_rate_daily !== null ? (float) $vehicle->vehicleType->base_rate_daily : null,
        ]);
    }
}
