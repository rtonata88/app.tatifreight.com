<?php

use App\Models\Client;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {

    public Client $client;

    #[Validate('required|string|max:255')]
    public $name;

    #[Validate('nullable|string|max:255')]
    public $company_name;

    #[Validate('required|email')]
    public $email;

    #[Validate('nullable|string|max:20')]
    public $phone;

    #[Validate('nullable|string|max:20')]
    public $secondary_phone;

    #[Validate('nullable|string')]
    public $address;

    #[Validate('nullable|string|max:100')]
    public $city;

    #[Validate('nullable|string|max:100')]
    public $region;

    #[Validate('nullable|string|max:20')]
    public $postal_code;

    #[Validate('required|in:adhoc,contract')]
    public $classification;

    #[Validate('nullable|string|max:50')]
    public $tax_number;

    #[Validate('nullable|numeric|min:0')]
    public $credit_limit;

    #[Validate('required|integer|min:0')]
    public $payment_terms_days;

    #[Validate('nullable|string')]
    public $notes;

    #[Validate('required|boolean')]
    public $is_active;

    public function mount(Client $client)
    {
        $this->client = $client;

        $this->name = $this->client->name;
        $this->company_name = $this->client->company_name;
        $this->email = $this->client->email;
        $this->phone = $this->client->phone;
        $this->secondary_phone = $this->client->secondary_phone;
        $this->address = $this->client->address;
        $this->city = $this->client->city;
        $this->region = $this->client->province;
        $this->postal_code = $this->client->postal_code;
        $this->classification = $this->client->classification;
        $this->tax_number = $this->client->tax_number;
        $this->credit_limit = $this->client->credit_limit;
        $this->payment_terms_days = $this->client->payment_terms_days;
        $this->notes = $this->client->notes;
        $this->is_active = $this->client->is_active;
    }

    public function save()
    {
        $this->validate();

        $this->client->update([
            'name' => $this->name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'secondary_phone' => $this->secondary_phone,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->region,
            'postal_code' => $this->postal_code,
            'classification' => $this->classification,
            'tax_number' => $this->tax_number,
            'credit_limit' => $this->credit_limit,
            'payment_terms_days' => $this->payment_terms_days,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ]);

        session()->flash('success', 'Client updated successfully!');

        return redirect()->route('clients.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Edit Client: {{ $client->name }}</flux:heading>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">Basic Information</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Contact Name *</flux:label>
                    <flux:input wire:model="name" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Company Name</flux:label>
                    <flux:input wire:model="company_name" />
                    <flux:error name="company_name" />
                </flux:field>

                <flux:field>
                    <flux:label>Email Address *</flux:label>
                    <flux:input wire:model="email" type="email" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Phone Number</flux:label>
                    <flux:input wire:model="phone" type="tel" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Secondary Phone</flux:label>
                    <flux:input wire:model="secondary_phone" type="tel" />
                    <flux:error name="secondary_phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Tax Number (VAT)</flux:label>
                    <flux:input wire:model="tax_number" />
                    <flux:error name="tax_number" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Address Information</flux:heading>

            <div class="mt-6 grid grid-cols-1 gap-6">
                <flux:field>
                    <flux:label>Street Address</flux:label>
                    <flux:textarea wire:model="address" rows="3" />
                    <flux:error name="address" />
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <flux:field>
                        <flux:label>City</flux:label>
                        <flux:input wire:model="city" />
                        <flux:error name="city" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Region</flux:label>
                        <flux:select wire:model="region">
                            <option value="">Select Region</option>
                            <option value="Erongo">Erongo</option>
                            <option value="Hardap">Hardap</option>
                            <option value="Karas">Karas</option>
                            <option value="Kavango East">Kavango East</option>
                            <option value="Kavango West">Kavango West</option>
                            <option value="Khomas">Khomas</option>
                            <option value="Kunene">Kunene</option>
                            <option value="Ohangwena">Ohangwena</option>
                            <option value="Omaheke">Omaheke</option>
                            <option value="Omusati">Omusati</option>
                            <option value="Oshana">Oshana</option>
                            <option value="Oshikoto">Oshikoto</option>
                            <option value="Otjozondjupa">Otjozondjupa</option>
                            <option value="Zambezi">Zambezi</option>
                        </flux:select>
                        <flux:error name="province" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Postal Code</flux:label>
                        <flux:input wire:model="postal_code" />
                        <flux:error name="postal_code" />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Client Classification & Terms</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Client Type *</flux:label>
                    <flux:select wire:model="classification">
                        <option value="adhoc">Ad-hoc (Pay per booking)</option>
                        <option value="contract">Contract (Long-term agreement)</option>
                    </flux:select>
                    <flux:error name="classification" />
                </flux:field>

                <flux:field>
                    <flux:label>Credit Limit (N$)</flux:label>
                    <flux:input wire:model="credit_limit" type="number" step="0.01" />
                    <flux:error name="credit_limit" />
                </flux:field>

                <flux:field>
                    <flux:label>Payment Terms (Days) *</flux:label>
                    <flux:input wire:model="payment_terms_days" type="number" />
                    <flux:error name="payment_terms_days" />
                </flux:field>

                <flux:field>
                    <flux:label>Status *</flux:label>
                    <flux:select wire:model="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </flux:select>
                    <flux:error name="is_active" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Additional Notes</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="4" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update Client
            </flux:button>

            <flux:button wire:navigate href="{{ route('clients.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
