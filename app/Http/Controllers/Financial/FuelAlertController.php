<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FuelAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FuelAlertController extends Controller
{
    /** A reviewer marks a fuel alert as followed up, with a short note for the owner. */
    public function review(Request $request, FuelAlert $fuelAlert): RedirectResponse
    {
        $validated = $request->validate(['review_note' => 'required|string|max:500']);

        $fuelAlert->update([
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'],
        ]);

        return back()->with('success', 'Fuel alert marked as reviewed.');
    }
}
