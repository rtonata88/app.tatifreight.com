<?php

use App\Models\CompanyBankAccount;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {

    public $showModal = false;
    public $editingId = null;
    
    public $bank_name = '';
    public $account_name = '';
    public $account_number = '';
    public $branch_name = '';
    public $branch_code = '';
    public $swift_code = '';
    public $currency = 'NAD';
    public $is_primary = false;

    public function with(): array
    {
        return [
            'bankAccounts' => CompanyBankAccount::orderBy('is_primary', 'desc')
                ->orderBy('created_at', 'desc')
                ->get(),
        ];
    }

    public function openModal()
    {
        $this->reset(['bank_name', 'account_name', 'account_number', 'branch_name', 'branch_code', 'swift_code', 'currency', 'is_primary', 'editingId']);
        $this->currency = 'NAD';
        $this->showModal = true;
    }

    public function editAccount($id)
    {
        $account = CompanyBankAccount::findOrFail($id);
        
        $this->editingId = $account->id;
        $this->bank_name = $account->bank_name;
        $this->account_name = $account->account_name;
        $this->account_number = $account->account_number;
        $this->branch_name = $account->branch_name;
        $this->branch_code = $account->branch_code;
        $this->swift_code = $account->swift_code;
        $this->currency = $account->currency;
        $this->is_primary = $account->is_primary;
        
        $this->showModal = true;
    }

    public function saveAccount()
    {
        $this->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'branch_name' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:50',
            'currency' => 'required|string|max:10',
        ]);

        $data = [
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'branch_name' => $this->branch_name,
            'branch_code' => $this->branch_code,
            'swift_code' => $this->swift_code,
            'currency' => $this->currency,
            'is_primary' => $this->is_primary,
        ];

        // If setting as primary, unset other primary accounts
        if ($this->is_primary) {
            CompanyBankAccount::where('is_primary', true)->update(['is_primary' => false]);
        }

        if ($this->editingId) {
            CompanyBankAccount::find($this->editingId)->update($data);
            $message = 'Bank account updated successfully!';
        } else {
            // If this is the first account, make it primary
            if (CompanyBankAccount::count() === 0) {
                $data['is_primary'] = true;
            }
            CompanyBankAccount::create($data);
            $message = 'Bank account added successfully!';
        }

        $this->dispatch('notify', type: 'success', message: $message);
        $this->showModal = false;
    }

    public function setPrimary($id)
    {
        CompanyBankAccount::where('is_primary', true)->update(['is_primary' => false]);
        CompanyBankAccount::find($id)->update(['is_primary' => true]);

        $this->dispatch('notify', type: 'success', message: 'Primary account updated!');
    }

    public function toggleActive($id)
    {
        $account = CompanyBankAccount::find($id);
        
        // Prevent deactivating primary account
        if ($account->is_primary && $account->is_active) {
            $this->dispatch('notify', type: 'error', message: 'Cannot deactivate primary account. Set another account as primary first.');
            return;
        }

        $account->update(['is_active' => !$account->is_active]);
        
        $message = $account->is_active ? 'Account activated!' : 'Account deactivated!';
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function deleteAccount($id)
    {
        $account = CompanyBankAccount::find($id);
        
        // Prevent deleting primary account
        if ($account->is_primary) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete primary account. Set another account as primary first.');
            return;
        }

        $account->delete();
        $this->dispatch('notify', type: 'success', message: 'Bank account deleted!');
    }
}; ?>

<div>
    <flux:header>
        <div>
            <flux:heading size="xl">Bank Accounts</flux:heading>
            <flux:subheading>Manage your company's bank accounts for payments</flux:subheading>
        </div>

        <div class="flex gap-3">
            <flux:button wire:navigate href="{{ route('settings.company') }}" variant="ghost" icon="arrow-left">
                Back to Settings
            </flux:button>
            <flux:button wire:click="openModal" variant="primary" icon="plus">
                Add Bank Account
            </flux:button>
        </div>
    </flux:header>

    {{-- Bank Accounts List --}}
    <div class="mt-6 space-y-4">
        @forelse($bankAccounts as $account)
            <flux:card class="{{ $account->is_primary ? 'border-2 border-blue-500' : '' }}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-3">
                            <h3 class="text-lg font-semibold">{{ $account->bank_name }}</h3>
                            
                            @if($account->is_primary)
                                <span class="px-2 py-1 text-xs font-semibold bg-blue-600 text-white rounded">Primary</span>
                            @endif
                            
                            @if(!$account->is_active)
                                <span class="px-2 py-1 text-xs font-semibold bg-gray-400 text-white rounded">Inactive</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 text-xs mb-1">Account Name</p>
                                <p class="font-medium">{{ $account->account_name }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-1">Account Number</p>
                                <p class="font-medium">{{ $account->account_number }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-xs mb-1">Currency</p>
                                <p class="font-medium">{{ $account->currency }}</p>
                            </div>
                            @if($account->branch_name)
                                <div>
                                    <p class="text-gray-500 text-xs mb-1">Branch</p>
                                    <p class="font-medium">{{ $account->branch_name }}</p>
                                </div>
                            @endif
                            @if($account->branch_code)
                                <div>
                                    <p class="text-gray-500 text-xs mb-1">Branch Code</p>
                                    <p class="font-medium">{{ $account->branch_code }}</p>
                                </div>
                            @endif
                            @if($account->swift_code)
                                <div>
                                    <p class="text-gray-500 text-xs mb-1">SWIFT Code</p>
                                    <p class="font-medium">{{ $account->swift_code }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-2 ml-4">
                        @if(!$account->is_primary)
                            <flux:button wire:click="setPrimary({{ $account->id }})" size="sm" variant="ghost">
                                Set as Primary
                            </flux:button>
                        @endif
                        
                        <flux:button wire:click="editAccount({{ $account->id }})" size="sm" variant="ghost" icon="pencil">
                            Edit
                        </flux:button>

                        <flux:button 
                            wire:click="toggleActive({{ $account->id }})" 
                            size="sm" 
                            variant="ghost"
                        >
                            {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                        </flux:button>

                        @if(!$account->is_primary)
                            <flux:button 
                                wire:click="deleteAccount({{ $account->id }})" 
                                wire:confirm="Are you sure you want to delete this bank account?"
                                size="sm" 
                                variant="danger" 
                                icon="trash"
                            >
                                Delete
                            </flux:button>
                        @endif
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card class="text-center py-12">
                <p class="text-gray-500 mb-4">No bank accounts configured yet.</p>
                <flux:button wire:click="openModal" variant="primary">
                    Add Your First Bank Account
                </flux:button>
            </flux:card>
        @endforelse
    </div>

    {{-- Add/Edit Modal --}}
    <flux:modal wire:model="showModal">
        <form wire:submit="saveAccount">
            <flux:heading size="lg">{{ $editingId ? 'Edit' : 'Add' }} Bank Account</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Bank Name *</flux:label>
                    <flux:input wire:model="bank_name" placeholder="Standard Bank Namibia" />
                    <flux:error name="bank_name" />
                </flux:field>

                <flux:field>
                    <flux:label>Currency *</flux:label>
                    <flux:select wire:model="currency">
                        <option value="NAD">NAD - Namibian Dollar</option>
                        <option value="ZAR">ZAR - South African Rand</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="GBP">GBP - British Pound</option>
                    </flux:select>
                    <flux:error name="currency" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Account Name *</flux:label>
                    <flux:input wire:model="account_name" placeholder="Tati Investment CC" />
                    <flux:error name="account_name" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Account Number *</flux:label>
                    <flux:input wire:model="account_number" placeholder="6000 6755 290" />
                    <flux:error name="account_number" />
                </flux:field>

                <flux:field>
                    <flux:label>Branch Name</flux:label>
                    <flux:input wire:model="branch_name" placeholder="Katutura" />
                    <flux:error name="branch_name" />
                </flux:field>

                <flux:field>
                    <flux:label>Branch Code</flux:label>
                    <flux:input wire:model="branch_code" placeholder="082 972" />
                    <flux:error name="branch_code" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>SWIFT Code</flux:label>
                    <flux:input wire:model="swift_code" placeholder="SBNMNANX" />
                    <flux:error name="swift_code" />
                    <flux:description>For international transfers</flux:description>
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:checkbox wire:model="is_primary" label="Set as Primary Account" />
                    <flux:description>Primary account will be displayed on invoices and quotes</flux:description>
                </flux:field>
            </div>

            <div class="mt-6 flex gap-3">
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Update' : 'Add' }} Account
                </flux:button>
                <flux:button type="button" wire:click="showModal = false" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

