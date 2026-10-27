<?php

namespace Tests\Feature\Student;

use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->studentUser = User::query()->where('email', 'student@hiraya.test')->firstOrFail();
    }

    public function test_student_can_view_their_payment_account(): void
    {
        $this->actingAs($this->studentUser)
            ->get('/student/payments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Payments/Index')
                ->where('balance', 22500)
                ->has('payments')
                ->has('ledger')
            );
    }

    public function test_student_can_submit_a_payment_for_verification(): void
    {
        Storage::fake('local');

        $this->actingAs($this->studentUser)->post('/student/payments', [
            'amount' => 1000,
            'method' => 'gcash',
            'reference_number' => 'GCASH-TEST-001',
            'proof' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');

        $payment = Payment::query()->where('reference_number', 'GCASH-TEST-001')->firstOrFail();

        $this->assertSame(Payment::PENDING, $payment->status);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('local')->assertExists($payment->proof_path);
        $this->assertNull($payment->receipt_number);
    }

    public function test_payment_cannot_exceed_the_remaining_balance(): void
    {
        Storage::fake('local');

        $this->actingAs($this->studentUser)->post('/student/payments', [
            'amount' => 99999,
            'method' => 'gcash',
            'reference_number' => 'GCASH-OVER',
            'proof' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('payments', ['reference_number' => 'GCASH-OVER']);
    }

    public function test_proof_of_payment_is_required(): void
    {
        $this->actingAs($this->studentUser)->post('/student/payments', [
            'amount' => 1000,
            'method' => 'gcash',
            'reference_number' => 'GCASH-NO-PROOF',
        ])->assertSessionHasErrors('proof');
    }

    public function test_proof_is_served_as_a_hardened_download(): void
    {
        Storage::fake('local');

        $this->actingAs($this->studentUser)->post('/student/payments', [
            'amount' => 500,
            'method' => 'gcash',
            'reference_number' => 'GCASH-DOWNLOAD-001',
            'proof' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $payment = Payment::query()->where('reference_number', 'GCASH-DOWNLOAD-001')->firstOrFail();

        $response = $this->actingAs($this->studentUser)->get("/payments/{$payment->id}/proof");

        $response->assertOk();
        $this->assertStringContainsString('attachment', strtolower($response->headers->get('content-disposition') ?? ''));
        $this->assertSame('nosniff', $response->headers->get('x-content-type-options'));
        $this->assertStringContainsString('sandbox', $response->headers->get('content-security-policy') ?? '');
    }

    public function test_executable_or_script_proof_files_are_rejected(): void
    {
        $this->actingAs($this->studentUser)->post('/student/payments', [
            'amount' => 500,
            'method' => 'gcash',
            'reference_number' => 'GCASH-SCRIPT-001',
            'proof' => UploadedFile::fake()->create('shell.php', 100, 'application/x-php'),
        ])->assertSessionHasErrors('proof');

        $this->assertDatabaseMissing('payments', ['reference_number' => 'GCASH-SCRIPT-001']);
    }

    public function test_student_can_view_their_own_receipt_and_proof(): void
    {
        Storage::fake('local');
        $payment = $this->studentUser->student->payments()
            ->where('status', Payment::VERIFIED)
            ->firstOrFail();

        $this->actingAs($this->studentUser)
            ->get("/payments/{$payment->id}/receipt")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payments/Receipt')
                ->where('payment.receipt_number', $payment->receipt_number)
            );

        $this->actingAs($this->studentUser)->post('/student/payments', [
            'amount' => 500,
            'method' => 'gcash',
            'reference_number' => 'GCASH-PROOF-001',
            'proof' => UploadedFile::fake()->create('proof.png', 50, 'image/png'),
        ]);

        $pending = Payment::query()->where('reference_number', 'GCASH-PROOF-001')->firstOrFail();

        $this->actingAs($this->studentUser)->get("/payments/{$pending->id}/proof")->assertOk();
    }

    public function test_another_student_cannot_view_the_receipt(): void
    {
        $payment = Payment::query()->where('status', Payment::VERIFIED)->firstOrFail();
        $other = User::query()->where('email', 'newstudent@hiraya.test')->firstOrFail();

        $this->actingAs($other)
            ->get("/payments/{$payment->id}/receipt")
            ->assertForbidden();
    }

    public function test_another_student_cannot_view_the_payment_proof(): void
    {
        $payment = Payment::query()->where('status', Payment::VERIFIED)->firstOrFail();
        $other = User::query()->where('email', 'newstudent@hiraya.test')->firstOrFail();

        $this->actingAs($other)
            ->get("/payments/{$payment->id}/proof")
            ->assertForbidden();
    }

    public function test_unsafe_payment_proof_paths_are_not_served(): void
    {
        $payment = Payment::query()->where('status', Payment::VERIFIED)->firstOrFail();
        $payment->forceFill(['proof_path' => '../.env'])->save();

        $this->actingAs($this->studentUser)
            ->get("/payments/{$payment->id}/proof")
            ->assertNotFound();
    }
}
