<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'can' => [
                'create' => $user->can('create-expenses'),
                'edit' => $user->can('edit-expenses'),
                'delete' => $user->can('delete-expenses'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('expenses/create', [
            ...$this->formOptions(withBookingDate: true),
            'defaults' => ['expense_date' => now()->format('Y-m-d')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $receiptPath = null;
        if ($request->hasFile('receipt_upload')) {
            $receiptPath = $request->file('receipt_upload')->store(self::RECEIPT_PATH, self::RECEIPT_DISK);
        }

        Expense::create([
            'vehicle_id' => ($validated['vehicle_id'] ?? null) ?: null,
            'booking_id' => ($validated['booking_id'] ?? null) ?: null,
            'user_id' => $request->user()->id,
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'],
            'receipt_path' => $receiptPath,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense submitted successfully!');
    }

    public function show(Request $request, Expense $expense): Response
    {
        $expense->load(['vehicle.vehicleType', 'booking.client', 'user', 'approvedBy']);
        $user = $request->user();

        return Inertia::render('expenses/show', [
            'expense' => [
                'id' => $expense->id,
                'amount' => (float) $expense->amount,
                'status' => $expense->status,
                'category' => $expense->category,
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
            ],
            'can' => [
                'edit' => $user->can('edit-expenses'),
                'delete' => $user->can('delete-expenses'),
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
        ]);

        $data = [
            'vehicle_id' => ($validated['vehicle_id'] ?? null) ?: null,
            'booking_id' => ($validated['booking_id'] ?? null) ?: null,
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'],
            'notes' => $validated['notes'] ?? null,
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

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully!');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $expense->delete();

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

    public function downloadReceipt(Expense $expense): BinaryFileResponse|RedirectResponse
    {
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

            return back()->with('success', 'Receipt deleted successfully');
        }

        return back();
    }

    /**
     * Validation shared by create and edit (edit adds status).
     *
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'category' => 'required|in:fuel,maintenance,repairs,tolls,insurance,licenses,wages,other',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:500',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'receipt_upload' => 'nullable|image|max:2048',
            'notes' => 'nullable|string',
        ];
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
        return [
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
}
