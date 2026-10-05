<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';

    public function with(): array
    {
        $query = User::with('roles')
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->roleFilter, function ($q) {
                $q->whereHas('roles', function($query) {
                    $query->where('name', $this->roleFilter);
                });
            })
            ->orderBy('created_at', 'desc');

        return [
            'users' => $query->paginate(15),
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function deleteUser(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->dispatch('notify',
                type: 'error',
                message: 'You cannot delete your own account'
            );
            return;
        }

        $user->delete();

        $this->dispatch('notify',
            type: 'success',
            message: 'User deleted successfully'
        );
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">User Management</flux:heading>

        <flux:button wire:navigate href="{{ route('users.create') }}" icon="plus">
            Add User
        </flux:button>
    </flux:header>

    <flux:card class="mt-6">
        <div class="space-y-4">
            {{-- Search and Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name or email..."
                    icon="magnifying-glass"
                />

                <flux:select wire:model.live="roleFilter" placeholder="Filter by role">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="dispatcher">Dispatcher</option>
                    <option value="accountant">Accountant</option>
                    <option value="driver">Driver</option>
                </flux:select>
            </div>

            {{-- Users Table --}}
            <flux:table>
                <flux:columns>
                    <flux:column>Name</flux:column>
                    <flux:column>Email</flux:column>
                    <flux:column>Role(s)</flux:column>
                    <flux:column>Created</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($users as $user)
                        <flux:row :key="$user->id">
                            <flux:cell>
                                <strong>{{ $user->name }}</strong>
                            </flux:cell>

                            <flux:cell>
                                {{ $user->email }}
                            </flux:cell>

                            <flux:cell>
                                <div class="flex gap-1 flex-wrap">
                                    @forelse($user->roles as $role)
                                        <flux:badge
                                            :color="match($role->name) {
                                                'admin' => 'red',
                                                'manager' => 'blue',
                                                'accountant' => 'green',
                                                'dispatcher' => 'purple',
                                                'driver' => 'gray',
                                                default => 'gray'
                                            }"
                                            size="sm"
                                        >
                                            {{ ucfirst($role->name) }}
                                        </flux:badge>
                                    @empty
                                        <span class="text-sm text-gray-500">No role</span>
                                    @endforelse
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="text-sm text-gray-600">
                                    {{ $user->created_at->format('d M Y') }}
                                </div>
                            </flux:cell>

                            <flux:cell>
                                <div class="flex gap-2">
                                    <flux:button
                                        wire:navigate
                                        href="{{ route('users.edit', $user) }}"
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil"
                                    >
                                        Edit
                                    </flux:button>

                                    @if($user->id !== auth()->id())
                                        <flux:button
                                            wire:click="deleteUser({{ $user->id }})"
                                            wire:confirm="Are you sure you want to delete this user?"
                                            size="sm"
                                            variant="danger"
                                            icon="trash"
                                        >
                                            Delete
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="5" class="text-center text-gray-500 py-8">
                                No users found.
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </flux:card>
</div>
