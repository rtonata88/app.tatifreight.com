<?php

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            // The wordmark in the rail and on the sign-in screens: the uploaded logo, else the name.
            'company' => function () {
                $settings = CompanySetting::first();

                return [
                    'name' => $settings?->company_name ?: config('app.name'),
                    'logo_url' => $settings?->logo_path ? Storage::disk('public')->url($settings->logo_path) : null,
                ];
            },
            'auth' => [
                'user' => $user,
                // Permission and role names drive what the sidebar and pages show.
                // Every action is still authorised again on the server.
                'permissions' => fn () => $user ? $user->getAllPermissions()->pluck('name')->values() : [],
                'roles' => fn () => $user ? $user->getRoleNames()->values() : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
