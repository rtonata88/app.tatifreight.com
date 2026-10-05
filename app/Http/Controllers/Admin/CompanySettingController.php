<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyBankAccount;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt component livewire/settings/company.
 */
class CompanySettingController extends Controller
{
    /** Text fields saved from the form, in the old component's order. */
    private const FIELDS = [
        'company_name', 'vat_number', 'registration_number', 'email', 'phone', 'secondary_phone',
        'address', 'city', 'postal_code', 'country', 'website', 'invoice_footer', 'quote_footer',
        'bank_name', 'bank_branch', 'account_name', 'account_number', 'branch_code', 'swift_code',
    ];

    public function edit(): Response
    {
        $settings = CompanySetting::get();

        return Inertia::render('settings/company', [
            'settings' => [
                ...collect(self::FIELDS)->mapWithKeys(fn ($field) => [$field => $settings->{$field}])->all(),
                'logo_url' => $settings->logo_path ? Storage::url($settings->logo_path) : null,
                'signature_url' => $settings->signature_path ? Storage::url($settings->signature_path) : null,
            ],
            'bankAccounts' => CompanyBankAccount::active()->map(fn (CompanyBankAccount $account) => [
                'id' => $account->id,
                'bank_name' => $account->bank_name,
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'branch_name' => $account->branch_name,
                'branch_code' => $account->branch_code,
                'swift_code' => $account->swift_code,
                'currency' => $account->currency,
                'is_primary' => $account->is_primary,
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
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

        $data = collect(self::FIELDS)->mapWithKeys(fn ($field) => [$field => $validated[$field] ?? null])->all();
        // `country` is NOT NULL in the table; Livewire saved a cleared field as '' (Inertia sends null).
        $data['country'] ??= '';

        if ($request->hasFile('logo_upload')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $data['logo_path'] = $request->file('logo_upload')->store('company', 'public');
        }

        if ($request->hasFile('signature_upload')) {
            if ($settings->signature_path) {
                Storage::disk('public')->delete($settings->signature_path);
            }
            $data['signature_path'] = $request->file('signature_upload')->store('company', 'public');
        }

        $settings->update($data);

        return back()->with('success', 'Company settings updated successfully!');
    }

    public function destroyLogo(): RedirectResponse
    {
        $settings = CompanySetting::get();

        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->update(['logo_path' => null]);

            return back()->with('success', 'Logo deleted successfully');
        }

        return back();
    }

    public function destroySignature(): RedirectResponse
    {
        $settings = CompanySetting::get();

        if ($settings->signature_path) {
            Storage::disk('public')->delete($settings->signature_path);
            $settings->update(['signature_path' => null]);

            return back()->with('success', 'Signature deleted successfully');
        }

        return back();
    }
}
