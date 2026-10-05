<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Replaces the Volt component livewire/settings/bank-accounts (list + add/edit modal).
 */
class BankAccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/bank-accounts', [
            'bankAccounts' => CompanyBankAccount::orderBy('is_primary', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn (CompanyBankAccount $account) => [
                    'id' => $account->id,
                    'bank_name' => $account->bank_name,
                    'account_name' => $account->account_name,
                    'account_number' => $account->account_number,
                    'branch_name' => $account->branch_name,
                    'branch_code' => $account->branch_code,
                    'swift_code' => $account->swift_code,
                    'currency' => $account->currency,
                    'is_primary' => $account->is_primary,
                    'is_active' => $account->is_active,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // If setting as primary, unset other primary accounts
        if ($data['is_primary']) {
            CompanyBankAccount::where('is_primary', true)->update(['is_primary' => false]);
        }

        // If this is the first account, make it primary
        if (CompanyBankAccount::count() === 0) {
            $data['is_primary'] = true;
        }

        CompanyBankAccount::create($data);

        return back()->with('success', 'Bank account added successfully!');
    }

    public function update(Request $request, CompanyBankAccount $bankAccount): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['is_primary']) {
            CompanyBankAccount::where('is_primary', true)->update(['is_primary' => false]);
        }

        $bankAccount->update($data);

        return back()->with('success', 'Bank account updated successfully!');
    }

    public function setPrimary(CompanyBankAccount $bankAccount): RedirectResponse
    {
        CompanyBankAccount::where('is_primary', true)->update(['is_primary' => false]);
        $bankAccount->update(['is_primary' => true]);

        return back()->with('success', 'Primary account updated!');
    }

    public function toggleActive(CompanyBankAccount $bankAccount): RedirectResponse
    {
        // Prevent deactivating primary account
        if ($bankAccount->is_primary && $bankAccount->is_active) {
            return back()->with('error', 'Cannot deactivate primary account. Set another account as primary first.');
        }

        $bankAccount->update(['is_active' => ! $bankAccount->is_active]);

        return back()->with('success', $bankAccount->is_active ? 'Account activated!' : 'Account deactivated!');
    }

    public function destroy(CompanyBankAccount $bankAccount): RedirectResponse
    {
        // Prevent deleting primary account
        if ($bankAccount->is_primary) {
            return back()->with('error', 'Cannot delete primary account. Set another account as primary first.');
        }

        $bankAccount->delete();

        return back()->with('success', 'Bank account deleted!');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:100',
            'branch_name' => 'nullable|string|max:255',
            'branch_code' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:50',
            'currency' => 'required|string|max:10',
        ]);

        return [
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'branch_name' => $validated['branch_name'] ?? null,
            'branch_code' => $validated['branch_code'] ?? null,
            'swift_code' => $validated['swift_code'] ?? null,
            'currency' => $validated['currency'],
            'is_primary' => $request->boolean('is_primary'),
        ];
    }
}
