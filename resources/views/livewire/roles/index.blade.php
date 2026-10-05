<?php

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {

    public function with(): array
    {
        return [
            'roles' => Role::withCount('users', 'permissions')->get(),
            'permissions' => Permission::all()->groupBy(function($permission) {
                // Group permissions by module (e.g., view-vehicles -> vehicles)
                $parts = explode('-', $permission->name);
                return count($parts) > 1 ? $parts[1] : 'other';
            }),
        ];
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Roles & Permissions</flux:heading>
    </flux:header>

    <div class="mt-6 space-y-6">
        {{-- Roles Overview --}}
        <flux:card>
            <flux:heading size="lg">System Roles</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($roles as $role)
                    <div class="p-6 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-gradient-to-br {{ match($role->name) {
                        'admin' => 'from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20',
                        'manager' => 'from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20',
                        'accountant' => 'from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20',
                        'dispatcher' => 'from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20',
                        'driver' => 'from-gray-50 to-gray-100 dark:from-gray-900/20 dark:to-gray-800/20',
                        default => 'from-gray-50 to-gray-100'
                    } }}">
                        <div class="text-center">
                            <flux:badge
                                :color="match($role->name) {
                                    'admin' => 'red',
                                    'manager' => 'blue',
                                    'accountant' => 'green',
                                    'dispatcher' => 'purple',
                                    'driver' => 'gray',
                                    default => 'gray'
                                }"
                                size="lg"
                                class="mb-3"
                            >
                                {{ ucfirst($role->name) }}
                            </flux:badge>
                            <div class="mt-4">
                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $role->users_count }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $role->users_count === 1 ? 'User' : 'Users' }}</p>
                            </div>
                            <div class="mt-2">
                                <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $role->permissions_count }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Permissions</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- Permissions Matrix --}}
        <flux:card>
            <flux:heading size="lg">Permissions Matrix</flux:heading>
            <flux:subheading>Overview of permissions assigned to each role</flux:subheading>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider sticky left-0 bg-zinc-50 dark:bg-zinc-800">
                                Module
                            </th>
                            @foreach($roles as $role)
                                <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    {{ ucfirst($role->name) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($permissions as $module => $modulePermissions)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100 sticky left-0 bg-white dark:bg-zinc-900">
                                    {{ ucfirst(str_replace('-', ' ', $module)) }}
                                    <div class="text-xs text-gray-500">
                                        {{ $modulePermissions->count() }} permission{{ $modulePermissions->count() > 1 ? 's' : '' }}
                                    </div>
                                </td>
                                @foreach($roles as $role)
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @php
                                            $rolePermissions = $role->permissions->pluck('name');
                                            $hasAll = $modulePermissions->every(fn($p) => $rolePermissions->contains($p->name));
                                            $hasNone = $modulePermissions->every(fn($p) => !$rolePermissions->contains($p->name));
                                            $hasCount = $modulePermissions->filter(fn($p) => $rolePermissions->contains($p->name))->count();
                                        @endphp

                                        @if($hasAll)
                                            <flux:badge color="green" size="sm">Full Access</flux:badge>
                                        @elseif($hasNone)
                                            <flux:badge color="red" size="sm">No Access</flux:badge>
                                        @else
                                            <flux:badge color="yellow" size="sm">
                                                Partial ({{ $hasCount }}/{{ $modulePermissions->count() }})
                                            </flux:badge>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>

        {{-- Detailed Permissions --}}
        <flux:card>
            <flux:heading size="lg">Detailed Permissions</flux:heading>

            <div class="mt-6 space-y-4">
                @foreach($permissions as $module => $modulePermissions)
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">
                            {{ ucfirst(str_replace('-', ' ', $module)) }}
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach($modulePermissions as $permission)
                                <div class="text-sm">
                                    <code class="px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded text-xs">
                                        {{ $permission->name }}
                                    </code>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
</div>
