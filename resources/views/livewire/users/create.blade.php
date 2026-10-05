<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Hash;

new #[Layout('components.layouts.app')] class extends Component {

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|email|unique:users,email')]
    public $email = '';

    #[Validate('required|string|min:8')]
    public $password = '';

    #[Validate('required|same:password')]
    public $password_confirmation = '';

    #[Validate('required|exists:roles,name')]
    public $role = '';

    public function with(): array
    {
        return [
            'roles' => Role::all(),
        ];
    }

    public function save()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        $user->assignRole($this->role);

        session()->flash('success', 'User created successfully!');

        return redirect()->route('users.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Create New User</flux:heading>
    </flux:header>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card>
            <flux:heading size="lg">User Information</flux:heading>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Full Name *</flux:label>
                    <flux:input wire:model="name" placeholder="John Doe" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Email Address *</flux:label>
                    <flux:input wire:model="email" type="email" placeholder="user@example.com" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Password *</flux:label>
                    <flux:input wire:model="password" type="password" placeholder="••••••••" />
                    <flux:error name="password" />
                    <flux:description>Minimum 8 characters</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Confirm Password *</flux:label>
                    <flux:input wire:model="password_confirmation" type="password" placeholder="••••••••" />
                    <flux:error name="password_confirmation" />
                </flux:field>
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Role Assignment</flux:heading>

            <div class="mt-6">
                <flux:field>
                    <flux:label>User Role *</flux:label>
                    <flux:select wire:model="role">
                        <option value="">Select a role</option>
                        @foreach($roles as $roleOption)
                            <option value="{{ $roleOption->name }}">
                                {{ ucfirst($roleOption->name) }}
                                ({{ $roleOption->permissions->count() }} permissions)
                            </option>
                        @endforeach
                    </flux:select>
                    <flux:error name="role" />
                    <flux:description>
                        Roles determine what actions a user can perform in the system
                    </flux:description>
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Create User
            </flux:button>

            <flux:button wire:navigate href="{{ route('users.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
