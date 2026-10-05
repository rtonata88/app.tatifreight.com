<?php

use App\Models\Client;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $classificationFilter = '';
    public string $statusFilter = '';

    public function with(): array
    {
        $query = Client::query()
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('company_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            })
            ->when($this->classificationFilter, function ($q) {
                $q->where('classification', $this->classificationFilter);
            })
            ->when($this->statusFilter !== '', function ($q) {
                $q->where('is_active', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc');

        return [
            'clients' => $query->paginate(10),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingClassificationFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteClient(int $id): void
    {
        if (auth()->user()->can('delete-clients')) {
            Client::findOrFail($id)->delete();

            $this->dispatch('notify',
                type: 'success',
                message: 'Client deleted successfully'
            );
        }
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Client Management</flux:heading>

        @can('create-clients')
            <flux:button wire:navigate href="{{ route('clients.create') }}" icon="plus">
                Add Client
            </flux:button>
        @endcan
    </flux:header>

    <flux:card class="mt-6">
        <div class="space-y-4">
            {{-- Search and Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search clients..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="classificationFilter" placeholder="Filter by type">
                    <option value="">All Types</option>
                    <option value="adhoc">Ad-hoc</option>
                    <option value="contract">Contract</option>
                </flux:select>

                <flux:select wire:model.live="statusFilter" placeholder="Filter by status">
                    <option value="">All Statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </flux:select>
            </div>

            {{-- Clients Table --}}
            <flux:table>
                <flux:columns>
                    <flux:column>Client Name</flux:column>
                    <flux:column>Company</flux:column>
                    <flux:column>Contact</flux:column>
                    <flux:column>Type</flux:column>
                    <flux:column>Credit Limit</flux:column>
                    <flux:column>Status</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($clients as $client)
                        <flux:row :key="$client->id">
                            <flux:cell>
                                <div>
                                    <strong>{{ $client->name }}</strong>
                                    @if($client->company_name)
                                        <div class="text-sm text-gray-500">{{ $client->company_name }}</div>
                                    @endif
                                </div>
                            </flux:cell>

                            <flux:cell>
                                {{ $client->company_name ?: 'N/A' }}
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm">
                                    <div>{{ $client->email }}</div>
                                    @if($client->phone)
                                        <div class="text-gray-500">{{ $client->phone }}</div>
                                    @endif
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <flux:badge
                                    :color="$client->classification === 'contract' ? 'blue' : 'gray'"
                                    size="sm"
                                >
                                    {{ ucfirst($client->classification) }}
                                </flux:badge>
                            </flux:cell>

                            <flux:cell>
                                N${{ number_format($client->credit_limit, 2) }}
                            </flux:cell>

                            <flux:cell>
                                <flux:badge
                                    :color="$client->is_active ? 'green' : 'red'"
                                    size="sm"
                                >
                                    {{ $client->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </flux:cell>

                            <flux:cell>
                                <flux:dropdown position="left" align="end">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal">Actions</flux:button>

                                    <flux:menu>
                                        @can('edit-clients')
                                            <flux:menu.item wire:navigate href="{{ route('clients.edit', $client) }}" icon="pencil">
                                                Edit Client
                                            </flux:menu.item>
                                        @endcan

                                        <flux:menu.item wire:navigate href="{{ route('clients.statement', $client) }}" icon="document-text">
                                            Customer Statement
                                        </flux:menu.item>

                                        @can('view-documents')
                                            <flux:menu.item wire:navigate href="{{ route('clients.documents', $client) }}" icon="folder-open">
                                                Documents
                                            </flux:menu.item>
                                        @endcan

                                        @can('delete-clients')
                                            <flux:menu.separator />
                                            <flux:menu.item 
                                                wire:click="deleteClient({{ $client->id }})" 
                                                wire:confirm="Are you sure you want to delete this client?"
                                                icon="trash"
                                                variant="danger"
                                            >
                                                Delete Client
                                            </flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="7" class="text-center text-gray-500 py-8">
                                No clients found. Add your first client to get started.
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $clients->links() }}
            </div>
        </div>
    </flux:card>
</div>
