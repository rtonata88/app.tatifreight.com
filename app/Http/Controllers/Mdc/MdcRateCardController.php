<?php

namespace App\Http\Controllers\Mdc;

use App\Http\Controllers\Controller;
use App\Models\MdcRateCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt components livewire/mdc-rates/{index,create,edit}.
 *
 * The routes require manage-mdc-rates; inside, the old screens additionally
 * checked the vehicle permissions (create/edit/delete-vehicles) and that is kept.
 */
class MdcRateCardController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $status = (string) $request->query('status', 'all');

        $rates = MdcRateCard::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('category_name', 'like', '%'.$search.'%')
                        ->orWhere('notes', 'like', '%'.$search.'%');
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('min_gvm_tonnes')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (MdcRateCard $rate) => [
                'id' => $rate->id,
                'category_name' => $rate->category_name,
                'notes' => $rate->notes,
                'min_gvm_tonnes' => (float) $rate->min_gvm_tonnes,
                'max_gvm_tonnes' => $rate->max_gvm_tonnes !== null ? (float) $rate->max_gvm_tonnes : null,
                'rate_per_100km' => (float) $rate->rate_per_100km,
                'effective_from' => $rate->effective_from?->format('Y-m-d'),
                'effective_to' => $rate->effective_to?->format('Y-m-d'),
                'is_active' => (bool) $rate->is_active,
            ]);

        $user = $request->user();

        return Inertia::render('mdc-rates/index', [
            'mdcRates' => $rates,
            'totalRates' => MdcRateCard::count(),
            'activeRates' => MdcRateCard::where('is_active', true)->count(),
            'filters' => ['search' => $search, 'status' => $status],
            'can' => [
                'create' => $user->can('create-vehicles'),
                'edit' => $user->can('edit-vehicles'),
                'delete' => $user->can('delete-vehicles'),
            ],
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->can('create-vehicles')) {
            return redirect()->route('mdc-rates.index');
        }

        return Inertia::render('mdc-rates/create', [
            'defaultEffectiveFrom' => now()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->can('create-vehicles')) {
            return redirect()->route('mdc-rates.index');
        }

        $validated = $request->validate($this->rules());

        MdcRateCard::create($this->attributes($request, $validated));

        return redirect()->route('mdc-rates.index')->with('success', 'MDC rate card created successfully!');
    }

    public function edit(Request $request, MdcRateCard $mdcRateCard): Response|RedirectResponse
    {
        if (! $request->user()->can('edit-vehicles')) {
            return redirect()->route('mdc-rates.index');
        }

        return Inertia::render('mdc-rates/edit', [
            'mdcRateCard' => [
                'id' => $mdcRateCard->id,
                'category_name' => $mdcRateCard->category_name,
                'min_gvm_tonnes' => $mdcRateCard->min_gvm_tonnes,
                'max_gvm_tonnes' => $mdcRateCard->max_gvm_tonnes ?? '',
                'rate_per_100km' => $mdcRateCard->rate_per_100km,
                'effective_from' => $mdcRateCard->effective_from?->format('Y-m-d'),
                'effective_to' => $mdcRateCard->effective_to?->format('Y-m-d') ?? '',
                'is_active' => (bool) $mdcRateCard->is_active,
                'notes' => $mdcRateCard->notes ?? '',
            ],
        ]);
    }

    public function update(Request $request, MdcRateCard $mdcRateCard): RedirectResponse
    {
        if (! $request->user()->can('edit-vehicles')) {
            return redirect()->route('mdc-rates.index');
        }

        $validated = $request->validate($this->rules());

        $mdcRateCard->update($this->attributes($request, $validated));

        return redirect()->route('mdc-rates.index')->with('success', 'MDC rate card updated successfully!');
    }

    public function toggleStatus(Request $request, MdcRateCard $mdcRateCard): RedirectResponse
    {
        if (! $request->user()->can('edit-vehicles')) {
            return back()->with('error', 'You do not have permission to edit MDC rates');
        }

        $mdcRateCard->update(['is_active' => ! $mdcRateCard->is_active]);

        return back()->with('success', 'MDC rate status updated');
    }

    public function destroy(Request $request, MdcRateCard $mdcRateCard): RedirectResponse
    {
        if (! $request->user()->can('delete-vehicles')) {
            return back()->with('error', 'You do not have permission to delete MDC rates');
        }

        $mdcRateCard->delete();

        return back()->with('success', 'MDC rate deleted successfully');
    }

    /**
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'category_name' => 'required|string|max:255',
            'min_gvm_tonnes' => 'required|numeric|min:0',
            'max_gvm_tonnes' => 'nullable|numeric|gt:min_gvm_tonnes',
            'rate_per_100km' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(Request $request, array $validated): array
    {
        return [
            'category_name' => $validated['category_name'],
            'min_gvm_tonnes' => $validated['min_gvm_tonnes'],
            'max_gvm_tonnes' => ($validated['max_gvm_tonnes'] ?? null) ?: null,
            'rate_per_100km' => $validated['rate_per_100km'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => ($validated['effective_to'] ?? null) ?: null,
            'is_active' => $request->boolean('is_active'),
            'notes' => $validated['notes'] ?? null,
        ];
    }
}
