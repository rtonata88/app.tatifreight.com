<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Replaces the Volt components livewire/users/{index,create,edit}.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->query('search', '');
        $role = (string) $request->query('role', '');

        // Same query as before: the search's orWhere is not grouped, exactly like the old component.
        $users = User::with('roles')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            })
            ->when($role, function ($q) use ($role) {
                $q->whereHas('roles', fn ($query) => $query->where('name', $role));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values(),
                'created_at' => $user->created_at?->format('Y-m-d'),
                'is_self' => $user->id === $request->user()->id,
            ]);

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => ['search' => $search, 'role' => $role],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('users/edit', [
            'roles' => $this->roleOptions(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? '',
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'password_confirmation' => 'nullable|same:password',
            'role' => 'required|exists:roles,name',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully');
    }

    /**
     * Role options with their permission counts, as the old select showed them.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function roleOptions()
    {
        return Role::withCount('permissions')->get()->map(fn (Role $role) => [
            'name' => $role->name,
            'permissions_count' => $role->permissions_count,
        ]);
    }
}
