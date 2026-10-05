<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.app')] class extends Component {

    public User $user;

    #[Validate('required|string|max:255')]
    public $name;

    public $email;

    #[Validate('nullable|string|min:8')]
    public $password = '';

    #[Validate('nullable|same:password')]
    public $password_confirmation = '';

    #[Validate('required|exists:roles,name')]
    public $role;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->role = $this->user->roles->first()?->name ?? '';
    }

    public function with(): array
    {
        return [
            'roles' => Role::all(),
        ];
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->user->id)],
            'password' => 'nullable|string|min:8',
            'password_confirmation' => 'nullable|same:password',
            'role' => 'required|exists:roles,name',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $this->user->update($data);

        $this->user->syncRoles([$this->role]);

        session()->flash('success', 'User updated successfully!');

        return redirect()->route('users.index');
    }
}; ?>

<div>
    <flux:header>
        <flux:heading size="xl">Edit User: {{ $user->name }}</flux:heading>
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
                    <flux:label>New Password</flux:label>
                    <flux:input wire:model="password" type="password" placeholder="Leave blank to keep current" />
                    <flux:error name="password" />
                    <flux:description>Leave blank to keep current password</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Confirm New Password</flux:label>
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
                        Current role: <strong>{{ ucfirst($user->roles->first()?->name ?? 'No role') }}</strong>
                    </flux:description>
                </flux:field>
            </div>
        </flux:card>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update User
            </flux:button>

            <flux:button wire:navigate href="{{ route('users.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
