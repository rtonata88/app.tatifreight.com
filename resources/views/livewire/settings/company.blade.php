<?php

use App\Models\CompanySetting;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Storage;

new #[Layout('components.layouts.app')] class extends Component {
    use WithFileUploads;

    public $company_name;
    public $vat_number;
    public $registration_number;
    public $email;
    public $phone;
    public $secondary_phone;
    public $address;
    public $city;
    public $postal_code;
    public $country;
    public $website;
    public $logo_upload = null;
    public $current_logo;
    public $signature_upload = null;
    public $current_signature;
    public $invoice_footer;
    public $quote_footer;
    public $bank_name;
    public $bank_branch;
    public $account_name;
    public $account_number;
    public $branch_code;
    public $swift_code;

    public function mount()
    {
        $settings = CompanySetting::get();

        $this->company_name = $settings->company_name;
        $this->vat_number = $settings->vat_number;
        $this->registration_number = $settings->registration_number;
        $this->email = $settings->email;
        $this->phone = $settings->phone;
        $this->secondary_phone = $settings->secondary_phone;
        $this->address = $settings->address;
        $this->city = $settings->city;
        $this->postal_code = $settings->postal_code;
        $this->country = $settings->country;
        $this->website = $settings->website;
        $this->current_logo = $settings->logo_path;
        $this->current_signature = $settings->signature_path;
        $this->invoice_footer = $settings->invoice_footer;
        $this->quote_footer = $settings->quote_footer;
        $this->bank_name = $settings->bank_name;
        $this->bank_branch = $settings->bank_branch;
        $this->account_name = $settings->account_name;
        $this->account_number = $settings->account_number;
        $this->branch_code = $settings->branch_code;
        $this->swift_code = $settings->swift_code;
    }

    public function save()
    {
        $this->validate([
            'company_name' => 'required|string|max:255',
            'vat_number' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'secondary_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'logo_upload' => 'nullable|image|max:2048',
            'signature_upload' => 'nullable|image|max:2048',
            'invoice_footer' => 'nullable|string',
            'quote_footer' => 'nullable|string',
            'bank_name' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:100',
            'branch_code' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:50',
        ]);

        $settings = CompanySetting::get();

        $data = [
            'company_name' => $this->company_name,
            'vat_number' => $this->vat_number,
            'registration_number' => $this->registration_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'secondary_phone' => $this->secondary_phone,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'website' => $this->website,
            'invoice_footer' => $this->invoice_footer,
            'quote_footer' => $this->quote_footer,
            'bank_name' => $this->bank_name,
            'bank_branch' => $this->bank_branch,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'branch_code' => $this->branch_code,
            'swift_code' => $this->swift_code,
        ];

        // Handle logo upload
        if ($this->logo_upload) {
            // Delete old logo
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $data['logo_path'] = $this->logo_upload->store('company', 'public');
            $this->current_logo = $data['logo_path'];
        }

        // Handle signature upload
        if ($this->signature_upload) {
            // Delete old signature
            if ($settings->signature_path) {
                Storage::disk('public')->delete($settings->signature_path);
            }

            $data['signature_path'] = $this->signature_upload->store('company', 'public');
            $this->current_signature = $data['signature_path'];
        }

        $settings->update($data);

        $this->dispatch('notify',
            type: 'success',
            message: 'Company settings updated successfully!'
        );
    }

    public function deleteLogo()
    {
        $settings = CompanySetting::get();

        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->update(['logo_path' => null]);
            $this->current_logo = null;

            $this->dispatch('notify',
                type: 'success',
                message: 'Logo deleted successfully'
            );
        }
    }

    public function deleteSignature()
    {
        $settings = CompanySetting::get();

        if ($settings->signature_path) {
            Storage::disk('public')->delete($settings->signature_path);
            $settings->update(['signature_path' => null]);
            $this->current_signature = null;

            $this->dispatch('notify',
                type: 'success',
                message: 'Signature deleted successfully'
            );
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Company Settings</flux:heading>
        <flux:subheading>Manage your company information, branding, and document settings</flux:subheading>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        {{-- Company Information --}}
        <flux:card>
            <flux:heading size="lg">Company Information</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Company Name *</flux:label>
                    <flux:input wire:model="company_name" placeholder="TAATI Transport" />
                    <flux:error name="company_name" />
                </flux:field>

                <flux:field>
                    <flux:label>VAT Number</flux:label>
                    <flux:input wire:model="vat_number" placeholder="123456789" />
                    <flux:error name="vat_number" />
                    <flux:description>Your VAT registration number</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Registration Number</flux:label>
                    <flux:input wire:model="registration_number" placeholder="REG123456" />
                    <flux:error name="registration_number" />
                    <flux:description>Business registration number</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input wire:model="email" type="email" placeholder="info@taati.com.na" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Phone</flux:label>
                    <flux:input wire:model="phone" placeholder="+264 61 123 4567" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Secondary Phone</flux:label>
                    <flux:input wire:model="secondary_phone" placeholder="+264 61 987 6543" />
                    <flux:error name="secondary_phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Website</flux:label>
                    <flux:input wire:model="website" type="url" placeholder="https://taati.com.na" />
                    <flux:error name="website" />
                </flux:field>
            </div>
        </flux:card>

        {{-- Address Information --}}
        <flux:card>
            <flux:heading size="lg">Address Information</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field class="md:col-span-2">
                    <flux:label>Street Address</flux:label>
                    <flux:textarea wire:model="address" rows="2" placeholder="123 Main Street, Industrial Area" />
                    <flux:error name="address" />
                </flux:field>

                <flux:field>
                    <flux:label>City</flux:label>
                    <flux:input wire:model="city" placeholder="Windhoek" />
                    <flux:error name="city" />
                </flux:field>

                <flux:field>
                    <flux:label>Postal Code</flux:label>
                    <flux:input wire:model="postal_code" placeholder="9000" />
                    <flux:error name="postal_code" />
                </flux:field>

                <flux:field>
                    <flux:label>Country</flux:label>
                    <flux:input wire:model="country" placeholder="Namibia" />
                    <flux:error name="country" />
                </flux:field>
            </div>
        </flux:card>

        {{-- Company Logo --}}
        <flux:card>
            <flux:heading size="lg">Company Logo</flux:heading>
            <flux:subheading>Upload your company logo (will appear on PDFs and documents)</flux:subheading>

            <div class="mt-6">
                @if($current_logo)
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Current Logo:</p>
                        <div class="relative inline-block">
                            <img src="{{ Storage::url($current_logo) }}" class="max-w-xs max-h-32 border border-gray-200 rounded">
                            <flux:button
                                type="button"
                                wire:click="deleteLogo"
                                wire:confirm="Are you sure you want to delete the logo?"
                                variant="danger"
                                size="sm"
                                class="mt-2"
                            >
                                Delete Logo
                            </flux:button>
                        </div>
                    </div>
                @endif

                <flux:field>
                    <flux:label>{{ $current_logo ? 'Replace Logo' : 'Upload Logo' }}</flux:label>
                    <flux:input wire:model="logo_upload" type="file" accept="image/*" />
                    <flux:error name="logo_upload" />
                    <flux:description>Recommended: PNG or JPG, max 2MB, transparent background preferred</flux:description>
                </flux:field>

                @if ($logo_upload)
                    <div class="mt-4">
                        <p class="text-sm text-gray-600 mb-2">New Logo Preview:</p>
                        <img src="{{ $logo_upload->temporaryUrl() }}" class="max-w-xs max-h-32 border border-gray-200 rounded">
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Company Signature --}}
        <flux:card>
            <flux:heading size="lg">Authorized Signature</flux:heading>
            <flux:subheading>Upload authorized signature image (will appear on invoices and official documents)</flux:subheading>

            <div class="mt-6">
                @if($current_signature)
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">Current Signature:</p>
                        <div class="relative inline-block">
                            <img src="{{ Storage::url($current_signature) }}" class="max-w-xs max-h-24 border border-gray-200 rounded bg-white p-2">
                            <flux:button
                                type="button"
                                wire:click="deleteSignature"
                                wire:confirm="Are you sure you want to delete the signature?"
                                variant="danger"
                                size="sm"
                                class="mt-2"
                            >
                                Delete Signature
                            </flux:button>
                        </div>
                    </div>
                @endif

                <flux:field>
                    <flux:label>{{ $current_signature ? 'Replace Signature' : 'Upload Signature' }}</flux:label>
                    <flux:input wire:model="signature_upload" type="file" accept="image/*" />
                    <flux:error name="signature_upload" />
                    <flux:description>Recommended: PNG with transparent background, max 2MB. Signature of authorized person.</flux:description>
                </flux:field>

                @if ($signature_upload)
                    <div class="mt-4">
                        <p class="text-sm text-gray-600 mb-2">New Signature Preview:</p>
                        <img src="{{ $signature_upload->temporaryUrl() }}" class="max-w-xs max-h-24 border border-gray-200 rounded bg-white p-2">
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Banking Details --}}
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Banking Details</flux:heading>
                    <flux:subheading>Bank account information for payments (will appear on quotes and invoices)</flux:subheading>
                </div>
                <flux:button type="button" wire:navigate href="{{ route('settings.bank-accounts') }}" variant="primary" icon="plus">
                    Manage Bank Accounts
                </flux:button>
            </div>

            <div class="mt-6">
                @php
                    $bankAccounts = \App\Models\CompanyBankAccount::active();
                @endphp

                @if($bankAccounts->count() > 0)
                    <div class="space-y-4">
                        @foreach($bankAccounts as $account)
                            <div class="border border-gray-200 rounded-lg p-4 {{ $account->is_primary ? 'bg-blue-50 border-blue-300' : '' }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h4 class="font-semibold text-gray-900">{{ $account->bank_name }}</h4>
                                            @if($account->is_primary)
                                                <span class="px-2 py-0.5 text-xs font-semibold bg-blue-600 text-white rounded">Primary</span>
                                            @endif
                                        </div>
                                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-600">
                                            <div><strong>Account Name:</strong> {{ $account->account_name }}</div>
                                            <div><strong>Account No:</strong> {{ $account->account_number }}</div>
                                            @if($account->branch_name)
                                                <div><strong>Branch:</strong> {{ $account->branch_name }}</div>
                                            @endif
                                            @if($account->branch_code)
                                                <div><strong>Branch Code:</strong> {{ $account->branch_code }}</div>
                                            @endif
                                            @if($account->swift_code)
                                                <div><strong>SWIFT:</strong> {{ $account->swift_code }}</div>
                                            @endif
                                            <div><strong>Currency:</strong> {{ $account->currency }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p class="mb-4">No bank accounts configured yet.</p>
                        <flux:button type="button" wire:navigate href="{{ route('settings.bank-accounts') }}" variant="primary">
                            Add Your First Bank Account
                        </flux:button>
                    </div>
                @endif
            </div>
        </flux:card>

        {{-- Document Footers --}}
        <flux:card>
            <flux:heading size="lg">Document Footers</flux:heading>
            <flux:subheading>Custom footer text for quotes and invoices</flux:subheading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Quote Footer</flux:label>
                    <flux:textarea wire:model="quote_footer" rows="3" placeholder="Thank you for your business!" />
                    <flux:error name="quote_footer" />
                    <flux:description>Appears at the bottom of quote PDFs</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Invoice Footer</flux:label>
                    <flux:textarea wire:model="invoice_footer" rows="3" placeholder="Payment terms: Net 30 days..." />
                    <flux:error name="invoice_footer" />
                    <flux:description>Appears at the bottom of invoice PDFs</flux:description>
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Save Settings
            </flux:button>
        </div>
    </form>
</div>
