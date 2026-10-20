<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FuelAlert;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FuelChecks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Replaces the Volt components livewire/expenses/{index,create,show,edit}.
 */
class ExpenseController extends Controller
{
    /** Receipts live on the public disk under this folder (unchanged from the Livewire version). */
    private const RECEIPT_DISK = 'public';

    private const RECEIPT_PATH = 'expenses/receipts';

    private const ODOMETER_PHOTO_PATH = 'expenses/odometer-photos';

    public function __construct(private FuelChecks $fuelChecks) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $isDriver = $user->hasRole('driver');

        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', '');
        $category = (string) $request->query('category', '');

        $expenses = Expense::with(['vehicle', 'booking', 'user', 'approvedBy'])
            // Drivers only see the expenses they submitted.
            ->when($isDriver, fn ($q) => $q->where('user_id', $user->id))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('description', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('reg_number', 'like', '%'.$search.'%'));
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('expense_date', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Expense $expense) => [
                'id' => $expense->id,
                'expense_date' => $expense->expense_date?->format('Y-m-d'),
                'category' => $expense->category,
                'description' => $expense->description,
                'booking_number' => $expense->booking_id ? $expense->booking?->booking_number : null,
                'vehicle' => $expense->vehicle?->reg_number,
                'submitted_by' => $expense->user?->name,
                'amount' => (float) $expense->amount,
                'status' => $expense->status,
                'approved_by' => $expense->approvedBy?->name,
                'approved_at' => $expense->approved_at?->format('Y-m-d H:i:s'),
                'has_receipt' => $this->receiptExists($expense),
            ]);

        $statsQuery = Expense::query()->when($isDriver, fn ($q) => $q->where('user_id', $user->id));

        return Inertia::render('expenses/index', [
            'expenses' => $expenses,
            'stats' => [
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                'approved' => (clone $statsQuery)->where('status', 'approved')->count(),
                'rejected' => (clone $statsQuery)->where('status', 'rejected')->count(),
                'total_pending' => (float) (clone $statsQuery)->where('status', 'pending')->sum('amount'),
                'total_approved' => (float) (clone $statsQuery)->where('status', 'approved')->sum('amount'),
            ],
            'filters' => ['search' => $search, 'status' => $status, 'category' => $category],
            'categories' => ExpenseCategory::options(),
            'can' => [
                'create' => $user->can('create-expenses'),
                'edit' => $user->can('edit-expenses'),
                'delete' => $user->can('delete-expenses'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('expenses/create', [
            ...$this->formOptions(withBookingDate: true),
            'defaults' => [
                'expense_date' => now()->format('Y-m-d'),
                // A driver filling up is usually recording their own fill-up.
                'driver_id' => $user->hasRole('driver') ? $user->id : null,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            // A fill-up needs its slip; other expenses may follow without one.
            'receipt_upload' => 'nullable|required_if:category,fuel|image|max:2048',
        ], self::MESSAGES);

        $receiptPath = null;
        if ($request->hasFile('receipt_upload')) {
            $receiptPath = $request->file('receipt_upload')->store(self::RECEIPT_PATH, self::RECEIPT_DISK);
        }

        $expense = Expense::create([
            ...$this->attributes($validated),
            'user_id' => $request->user()->id,
            'receipt_path' => $receiptPath,
            'status' => 'pending',
        ]);
        $this->storeOdometerPhoto($request, $expense);
        $this->fuelChecks->check($expense);

        return redirect()->route('expenses.index')->with('success', 'Expense submitted successfully!');
    }

    public function show(Request $request, Expense $expense): Response
    {
        $this->ensureDriverOwns($request, $expense);
        $expense->load(['vehicle.vehicleType', 'booking.client', 'user', 'driver', 'approvedBy', 'fuelAlerts.reviewedBy']);
        $user = $request->user();

        return Inertia::render('expenses/show', [
            'expense' => [
                'id' => $expense->id,
                'amount' => (float) $expense->amount,
                'status' => $expense->status,
                'category' => $expense->category,
                'category_label' => ExpenseCategory::where('value', $expense->category)->value('label') ?? Str::headline($expense->category),
                'description' => $expense->description,
                'expense_date' => $expense->expense_date?->format('Y-m-d'),
                'notes' => $expense->notes,
                'vehicle' => $expense->vehicle ? [
                    'id' => $expense->vehicle->id,
                    'reg_number' => $expense->vehicle->reg_number,
                    'type' => $expense->vehicle->vehicleType?->name,
                ] : null,
                'booking' => $expense->booking ? [
                    'id' => $expense->booking->id,
                    'booking_number' => $expense->booking->booking_number,
                    'client' => $expense->booking->client
                        ? ($expense->booking->client->company_name ?: $expense->booking->client->name)
                        : null,
                ] : null,
                'submitted_by' => $expense->user?->name,
                'created_at' => $expense->created_at?->format('Y-m-d H:i:s'),
                'approved_by' => $expense->approvedBy?->name,
                'approved_at' => $expense->approved_at?->format('Y-m-d H:i:s'),
                ...$this->receiptProps($expense),
                'fuel' => $expense->category === 'fuel' ? [
                    'driver' => $expense->driver?->name,
                    'litres' => $expense->litres !== null ? (float) $expense->litres : null,
                    'price_per_litre' => $expense->price_per_litre !== null ? (float) $expense->price_per_litre : null,
                    'odometer' => $expense->odometer !== null ? (float) $expense->odometer : null,
                    'full_tank' => $expense->full_tank,
                    'station' => $expense->station,
                    'consumption' => $expense->consumption_l_per_100km !== null ? (float) $expense->consumption_l_per_100km : null,
                    'expected_consumption' => $expense->vehicle?->expected_consumption !== null ? (float) $expense->vehicle->expected_consumption : null,
                    'odometer_photo_url' => $expense->odometer_photo_path ? Storage::disk(self::RECEIPT_DISK)->url($expense->odometer_photo_path) : null,
                ] : null,
            ],
            // Fuel alerts are for reviewers; drivers do not see them.
            'fuelAlerts' => $user->can('approve-expenses')
                ? $expense->fuelAlerts->sortBy('id')->map(fn (FuelAlert $alert) => [
                    'id' => $alert->id,
                    'label' => $alert->label(),
                    'message' => $alert->message,
                    'reviewed' => $alert->reviewed_at !== null,
                    'reviewed_by' => $alert->reviewedBy?->name,
                    'reviewed_at' => $alert->reviewed_at?->format('Y-m-d H:i'),
                    'review_note' => $alert->review_note,
                ])->values()
                : [],
            'can' => [
                'edit' => $user->can('edit-expenses'),
                'delete' => $user->can('delete-expenses'),
                'reviewFuelAlerts' => $user->can('approve-expenses'),
            ],
        ]);
    }

    public function edit(Expense $expense): Response
    {
        $expense->load(['vehicle', 'booking', 'user', 'approvedBy']);

        return Inertia::render('expenses/edit', [
            ...$this->formOptions(withBookingDate: false),
            'expense' => [
                'id' => $expense->id,
                'category' => $expense->category,
                'amount' => $expense->amount,
                'expense_date' => $expense->expense_date?->format('Y-m-d'),
                'description' => $expense->description,
                'vehicle_id' => $expense->vehicle_id,
                'booking_id' => $expense->booking_id,
                'notes' => $expense->notes,
                'status' => $expense->status,
                'submitted_by' => $expense->user?->name,
                'created_at' => $expense->created_at?->format('Y-m-d H:i:s'),
                'approved_by_id' => $expense->approved_by,
                'approved_by' => $expense->approvedBy?->name,
                'approved_at' => $expense->approved_at?->format('Y-m-d H:i:s'),
                'driver_id' => $expense->driver_id,
                'litres' => $expense->litres,
                'price_per_litre' => $expense->price_per_litre,
                'odometer' => $expense->odometer,
                'full_tank' => $expense->full_tank,
                'station' => $expense->station,
                'odometer_photo_url' => $expense->odometer_photo_path ? Storage::disk(self::RECEIPT_DISK)->url($expense->odometer_photo_path) : null,
                // The edit screen showed the stored receipt whenever a path was set.
                'receipt_path' => $expense->receipt_path,
                'receipt_url' => $expense->receipt_path ? Storage::disk(self::RECEIPT_DISK)->url($expense->receipt_path) : null,
            ],
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            'status' => 'required|in:pending,approved,rejected',
        ], self::MESSAGES);

        $data = [
            ...$this->attributes($validated, $expense),
            'status' => $validated['status'],
        ];

        if ($request->hasFile('receipt_upload')) {
            // Replace the old receipt.
            if ($expense->receipt_path) {
                Storage::disk(self::RECEIPT_DISK)->delete($expense->receipt_path);
            }

            $data['receipt_path'] = $request->file('receipt_upload')->store(self::RECEIPT_PATH, self::RECEIPT_DISK);
        }

        // Record who approved/rejected when the status changes to one of those.
        if ($validated['status'] !== $expense->status && in_array($validated['status'], ['approved', 'rejected'])) {
            $data['approved_by'] = $request->user()->id;
            $data['approved_at'] = now();
        }

        $expense->update($data);
        $this->storeOdometerPhoto($request, $expense);
        $this->fuelChecks->check($expense);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully!');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $expense->delete();
        // Expenses are soft-deleted, so their alerts are cleared here rather than by the foreign key.
        $expense->fuelAlerts()->delete();

        // From the details page the old screen went back to the list; from the list it stayed put.
        if ($request->input('redirect') === 'index') {
            return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully');
        }

        return back()->with('success', 'Expense deleted successfully');
    }

    public function approve(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Expense approved successfully');
    }

    public function reject(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Expense rejected');
    }

    public function downloadReceipt(Request $request, Expense $expense): BinaryFileResponse|RedirectResponse
    {
        $this->ensureDriverOwns($request, $expense);

        if (! $this->receiptExists($expense)) {
            return back()->with('error', 'Receipt file not found');
        }

        return response()->download(
            Storage::disk(self::RECEIPT_DISK)->path($expense->receipt_path),
            basename($expense->receipt_path)
        );
    }

    public function destroyReceipt(Expense $expense): RedirectResponse
    {
        if ($expense->receipt_path) {
            Storage::disk(self::RECEIPT_DISK)->delete($expense->receipt_path);
            $expense->update(['receipt_path' => null]);
            $this->fuelChecks->check($expense);

            return back()->with('success', 'Receipt deleted successfully');
        }

        return back();
    }

    /**
     * Adds an expense category from the "New category" dialog beside the category field on the
     * expense form, and answers with it as a select option.
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate(['label' => 'required|string|max:100']);

        $label = trim($validated['label']);
        $value = Str::slug($label, '_');

        $exists = ExpenseCategory::where('value', $value)
            ->orWhereRaw('LOWER(label) = ?', [mb_strtolower($label)])
            ->exists();

        if ($value === '' || $exists) {
            throw ValidationException::withMessages([
                'label' => $value === '' ? 'Use at least one letter or number.' : 'This category already exists.',
            ]);
        }

        $category = ExpenseCategory::create(['value' => $value, 'label' => $label]);

        return response()->json(['category' => ['value' => $category->value, 'label' => $category->label]], 201);
    }

    /** Messages for the fill-up fields, worded as the form asks. */
    private const MESSAGES = [
        'full_tank.required_if' => 'Say whether you filled the tank until the pump clicked off.',
        'odometer.required_if' => 'Enter the odometer reading at the pump.',
    ];

    /**
     * Validation shared by create and edit (edit adds status).
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'category' => ['required', Rule::exists('expense_categories', 'value')],
            // A fill-up's amount is litres × price, and its description can be left to the station.
            'amount' => 'nullable|required_unless:category,fuel|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'nullable|required_unless:category,fuel|string|max:500',
            'vehicle_id' => 'nullable|required_if:category,fuel|exists:vehicles,id',
            'driver_id' => 'nullable|exists:users,id',
            'litres' => 'nullable|required_if:category,fuel|numeric|min:0.01|max:10000',
            // Ignored once a fuel price is set in Company settings (see fuelPrice()).
            'price_per_litre' => 'nullable|numeric|min:0.01|max:1000',
            'odometer' => 'nullable|required_if:category,fuel|numeric|min:0',
            'full_tank' => 'nullable|required_if:category,fuel|boolean',
            'station' => 'nullable|required_if:category,fuel|string|max:255',
            'odometer_photo_upload' => 'nullable|image|max:2048',
            'booking_id' => 'nullable|exists:bookings,id',
            'receipt_upload' => 'nullable|image|max:2048',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Columns saved from the create and edit forms. Fuel fields are kept only on fuel expenses,
     * whose amount is litres × price per litre.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated, ?Expense $expense = null): array
    {
        $fuel = $validated['category'] === 'fuel';
        $litres = $fuel ? (float) $validated['litres'] : null;
        $price = $fuel ? $this->fuelPrice($validated, $expense) : null;
        $station = $fuel ? trim((string) $validated['station']) : null;

        return [
            'vehicle_id' => ($validated['vehicle_id'] ?? null) ?: null,
            'booking_id' => ($validated['booking_id'] ?? null) ?: null,
            'driver_id' => $fuel ? (($validated['driver_id'] ?? null) ?: null) : null,
            'category' => $validated['category'],
            // Rounded twice so 210.5 × 21.45 = 4515.225 becomes 4515.23, not 4515.22 from float error.
            'amount' => $fuel ? round(round($litres * $price, 6), 2) : $validated['amount'],
            'litres' => $litres,
            'price_per_litre' => $price,
            'odometer' => $fuel ? $validated['odometer'] : null,
            'full_tank' => $fuel ? (bool) $validated['full_tank'] : null,
            'station' => $station,
            'expense_date' => $validated['expense_date'],
            'description' => ($validated['description'] ?? null) ?: sprintf('Fuel: %s L at %s', number_format($litres, 2), $station),
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * The price a fill-up is charged at. A new fill-up takes the price set in Company settings,
     * whatever was typed, so drivers cannot change it; only with no price set is the entered one
     * used. Editing (managers only) may correct it, else keeps the price it was saved with.
     */
    private function fuelPrice(array $validated, ?Expense $expense): float
    {
        $entered = $validated['price_per_litre'] ?? null;
        $setting = CompanySetting::get()->fuel_price_per_litre;

        $price = $expense
            ? ($entered ?? $expense->price_per_litre ?? $setting)
            : ($setting ?? $entered);

        if ($price === null) {
            throw ValidationException::withMessages([
                'price_per_litre' => 'Enter the price per litre, or ask a manager to set the fuel price in Company settings.',
            ]);
        }

        return (float) $price;
    }

    private function storeOdometerPhoto(Request $request, Expense $expense): void
    {
        if (! $request->hasFile('odometer_photo_upload')) {
            return;
        }

        if ($expense->odometer_photo_path) {
            Storage::disk(self::RECEIPT_DISK)->delete($expense->odometer_photo_path);
        }

        $expense->update(['odometer_photo_path' => $request->file('odometer_photo_upload')->store(self::ODOMETER_PHOTO_PATH, self::RECEIPT_DISK)]);
    }

    private function receiptExists(Expense $expense): bool
    {
        return $expense->receipt_path && Storage::disk(self::RECEIPT_DISK)->exists($expense->receipt_path);
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptProps(Expense $expense): array
    {
        $exists = $this->receiptExists($expense);
        $extension = $exists ? strtolower(pathinfo($expense->receipt_path, PATHINFO_EXTENSION)) : null;

        return [
            'has_receipt' => $exists,
            'receipt_url' => $exists ? Storage::disk(self::RECEIPT_DISK)->url($expense->receipt_path) : null,
            'receipt_extension' => $extension,
            'receipt_is_image' => $exists && in_array($extension, ['jpg', 'jpeg', 'png', 'gif']),
        ];
    }

    /**
     * Vehicle and booking options for the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(bool $withBookingDate): array
    {
        $fuelPrice = CompanySetting::get()->fuel_price_per_litre;

        return [
            'categories' => ExpenseCategory::options(),
            // Shown read-only on new fill-ups; null lets the driver enter it.
            'fuelPrice' => $fuelPrice !== null ? (float) $fuelPrice : null,
            // whereHas rather than User::role(), which throws when no driver role exists yet.
            'drivers' => User::whereHas('roles', fn ($q) => $q->where('name', 'driver'))->orderBy('name')->get()
                ->map(fn (User $driver) => ['value' => $driver->id, 'label' => $driver->name]),
            'vehicles' => Vehicle::with('vehicleType')->orderBy('reg_number')->get()
                ->map(fn (Vehicle $vehicle) => [
                    'value' => $vehicle->id,
                    'label' => $vehicle->reg_number.' - '.$vehicle->vehicleType?->name,
                ]),
            'bookings' => Booking::with(['client', 'vehicle'])
                ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn (Booking $booking) => [
                    'value' => $booking->id,
                    'label' => $booking->booking_number.' - '.$booking->client?->name
                        .($withBookingDate && $booking->start_date ? ' ('.$booking->start_date->format('d M Y').')' : ''),
                ]),
        ];
    }

    /**
     * Drivers only see their own expenses in the list; stop them opening other
     * people's expenses or receipts by changing the URL.
     */
    private function ensureDriverOwns(Request $request, Expense $expense): void
    {
        $user = $request->user();

        abort_if($user->hasRole('driver') && $expense->user_id !== $user->id, 403);
    }
}
