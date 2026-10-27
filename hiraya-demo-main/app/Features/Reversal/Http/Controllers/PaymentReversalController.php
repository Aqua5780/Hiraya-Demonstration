<?php

namespace App\Features\Reversal\Http\Controllers;

use App\Features\Reversal\Models\PaymentReversal;
use App\Features\Reversal\Services\PaymentReversalService;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReversalController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Deferred/Reversal/Index', [
            'payments' => Payment::query()
                ->with(['student:id,student_number,first_name,last_name'])
                ->where('status', Payment::VERIFIED)
                ->latest('verified_at')
                ->take(50)
                ->get()
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'student_number' => $payment->student?->student_number,
                    'student' => $payment->student?->full_name,
                    'amount' => (float) $payment->amount,
                    'receipt_number' => $payment->receipt_number,
                    'reference_number' => $payment->reference_number,
                    'verified_at' => $payment->verified_at?->toIso8601String(),
                ]),
            'reversals' => PaymentReversal::query()
                ->with(['payment.student:id,student_number,first_name,last_name', 'reversedBy:id,name', 'approvedBy:id,name'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request, PaymentReversalService $reversalService): RedirectResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'exists:payments,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $payment = Payment::query()->findOrFail($validated['payment_id']);
        $reversalService->request($payment, $request->user(), $validated['reason']);

        return back()->with('success', 'Reversal requested.');
    }

    public function approve(Request $request, PaymentReversal $reversal, PaymentReversalService $reversalService): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $reversalService->approve($reversal, $request->user(), $validated['reference'] ?? null);

        return back()->with('success', 'Reversal approved and posted to the ledger.');
    }

    public function reject(Request $request, PaymentReversal $reversal, PaymentReversalService $reversalService): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:500'],
        ]);

        $reversalService->reject($reversal, $request->user(), $validated['remarks']);

        return back()->with('success', 'Reversal rejected.');
    }
}
