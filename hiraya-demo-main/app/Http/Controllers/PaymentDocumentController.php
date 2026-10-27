<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentDocumentController extends Controller
{
    private const PROOF_DIRECTORY = 'payment-proofs';

    public function receipt(Request $request, Payment $payment): Response
    {
        abort_unless($this->canView($request, $payment), 403);
        abort_unless($payment->status === Payment::VERIFIED, 404);

        $payment->load(['student:id,student_number,first_name,middle_name,last_name,suffix', 'academicYear:id,name', 'verifiedBy:id,name']);

        $ledger = LedgerEntry::query()
            ->where('payment_id', $payment->id)
            ->latest()
            ->first();

        return Inertia::render('Payments/Receipt', [
            'payment' => [
                'id' => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'student_number' => $payment->student?->student_number,
                'student_name' => $payment->student?->full_name,
                'amount' => (float) $payment->amount,
                'method' => $payment->methodLabel(),
                'reference_number' => $payment->reference_number,
                'verified_by' => $payment->verifiedBy?->name,
                'verified_at' => $payment->verified_at?->toIso8601String(),
                'balance_after' => $ledger?->balance_after !== null ? (float) $ledger->balance_after : null,
                'academic_year' => $payment->academicYear?->name,
                'remarks' => $payment->remarks,
            ],
        ]);
    }

    public function proof(Request $request, Payment $payment): StreamedResponse
    {
        abort_unless($this->canView($request, $payment), 403);
        abort_unless($this->isSafeStoredPath($payment->proof_path), 404);

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        $mime = in_array($payment->proof_mime, $allowedMimes, true)
            ? $payment->proof_mime
            : 'application/octet-stream';

        return Storage::disk('local')->download(
            $payment->proof_path,
            basename($payment->proof_name ?? 'payment-proof'),
            [
                'Content-Type' => $mime,
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "sandbox; default-src 'none'",
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    private function canView(Request $request, Payment $payment): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->isFinance()) {
            return true;
        }

        return $payment->student?->user_id === $user->id;
    }

    private function isSafeStoredPath(?string $path): bool
    {
        return is_string($path)
            && str_starts_with($path, self::PROOF_DIRECTORY.'/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\');
    }
}
