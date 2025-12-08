<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dossier_id' => 'required|exists:dossiers,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'method' => 'required|string',
            'reference' => 'nullable|string|max:255',
            'status' => 'required|in:paid,pending,partial',
        ]);

        Payment::create($validated);

        return Redirect::back()->with('success', 'Règlement ajouté.');
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'method' => 'required|string',
            'reference' => 'nullable|string|max:255',
            'status' => 'required|in:paid,pending,partial',
        ]);

        $payment->update($validated);

        return Redirect::back()->with('success', 'Règlement mis à jour.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return Redirect::back()->with('success', 'Règlement supprimé.');
    }
}