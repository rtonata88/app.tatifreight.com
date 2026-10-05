<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\RateCard;
use App\Models\VehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt components livewire/rate-cards/{index,create,edit}.
 */
class RateCardController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $rateType = (string) $request->query('rate_type', '');
        $vehicleType = (string) $request->query('vehicle_type', '');

        $rateCards = RateCard::with(['vehicleType', 'client'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('client', function ($c) use ($search) {
                            $c->where('name', 'like', '%'.$search.'%')
                                ->orWhere('company_name', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('vehicleType', fn ($v) => $v->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($rateType, fn ($q) => $q->where('rate_type', $rateType))
            ->when($vehicleType, fn ($q) => $q->where('vehicle_type_id', $vehicleType))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (RateCard $card) => [
                'id' => $card->id,
                'name' => $card->name,
                'includes_mdc' => (bool) $card->includes_mdc,
                'vehicle_type' => $card->vehicleType?->name,
                'client' => $card->client ? [
                    'name' => $card->client->name,
                    'company_name' => $card->client->company_name,
                ] : null,
                'rate_type' => $card->rate_type,
                'rate' => (float) $card->rate,
                'effective_from' => $card->effective_from?->format('Y-m-d'),
                'effective_to' => $card->effective_to?->format('Y-m-d'),
                'is_active' => (bool) $card->is_active,
            ]);

        $user = $request->user();

        return Inertia::render('rate-cards/index', [
            'rateCards' => $rateCards,
            'stats' => [
                'active' => RateCard::where('is_active', true)->count(),
                'inactive' => RateCard::where('is_active', false)->count(),
                'client_specific' => RateCard::whereNotNull('client_id')->count(),
                'general' => RateCard::whereNull('client_id')->count(),
            ],
            'vehicleTypes' => $this->vehicleTypeOptions(),
            'filters' => ['search' => $search, 'rate_type' => $rateType, 'vehicle_type' => $vehicleType],
            'can' => [
                'create' => $user->can('create-rate-cards'),
                'edit' => $user->can('edit-rate-cards'),
                'delete' => $user->can('delete-rate-cards'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('rate-cards/create', [
            ...$this->formOptions(),
            'defaults' => ['effective_from' => now()->format('Y-m-d')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        RateCard::create($this->attributes($validated));

        return redirect()->route('rate-cards.index')->with('success', 'Rate card created successfully!');
    }

    public function edit(RateCard $rateCard): Response
    {
        return Inertia::render('rate-cards/edit', [
            ...$this->formOptions(),
            'rateCard' => [
                'id' => $rateCard->id,
                'name' => $rateCard->name,
                'vehicle_type_id' => $rateCard->vehicle_type_id,
                'client_id' => $rateCard->client_id,
                'rate_type' => $rateCard->rate_type,
                'rate' => $rateCard->rate,
                'includes_mdc' => (bool) $rateCard->includes_mdc,
                'effective_from' => $rateCard->effective_from?->format('Y-m-d'),
                'effective_to' => $rateCard->effective_to?->format('Y-m-d'),
                'is_active' => (bool) $rateCard->is_active,
                'notes' => $rateCard->notes,
                'created_at' => $rateCard->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $rateCard->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function update(Request $request, RateCard $rateCard): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $rateCard->update($this->attributes($validated));

        return redirect()->route('rate-cards.index')->with('success', 'Rate card updated successfully!');
    }

    public function toggleActive(RateCard $rateCard): RedirectResponse
    {
        $rateCard->update(['is_active' => ! $rateCard->is_active]);

        return back()->with('success', 'Rate card status updated');
    }

    public function destroy(RateCard $rateCard): RedirectResponse
    {
        $rateCard->delete();

        return back()->with('success', 'Rate card deleted successfully');
    }

    /**
     * @return array<string, string>
     */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'client_id' => 'nullable|exists:clients,id',
            'rate_type' => 'required|in:hourly,daily,per_km,tonnage,load_specific',
            'rate' => 'required|numeric|min:0.01',
            'includes_mdc' => 'boolean',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        return [
            'vehicle_type_id' => $validated['vehicle_type_id'],
            'client_id' => ($validated['client_id'] ?? null) ?: null,
            'name' => $validated['name'],
            'rate_type' => $validated['rate_type'],
            'rate' => $validated['rate'],
            'includes_mdc' => (bool) ($validated['includes_mdc'] ?? false),
            'effective_from' => $validated['effective_from'],
            'effective_to' => ($validated['effective_to'] ?? null) ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{value: int, label: string}>
     */
    private function vehicleTypeOptions()
    {
        return VehicleType::orderBy('name')->get()
            ->map(fn (VehicleType $type) => ['value' => $type->id, 'label' => $type->name]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'vehicleTypes' => $this->vehicleTypeOptions(),
            'clients' => Client::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Client $client) => [
                    'value' => $client->id,
                    'label' => $client->name.($client->company_name ? ' ('.$client->company_name.')' : ''),
                ]),
        ];
    }
}
