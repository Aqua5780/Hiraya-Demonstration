<?php

namespace Tests\Feature\Finance;

use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinancePaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $financeUser;

    private User $studentUser;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->financeUser = User::query()->where('email', 'finance@hiraya.test')->firstOrFail();
        $this->studentUser = User::query()->where('email', 'student@hiraya.test')->firstOrFail();
        $this->student = $this->studentUser->student;
    }

    public function test_finance_can_view_the_verification_queue(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/finance/payments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finance/Payments/Index')
                ->where('pendingCount', 1)
                ->has('payments.data')
            );
    }

    public function test_finance_can_verify_a_pending_payment_and_update_the_ledger(): void
    {
        $payment = Payment::query()->where('status', Payment::PENDING)->firstOrFail();

        $this->actingAs($this->financeUser)
            ->post("/finance/payments/{$payment->id}/verify")
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment->refresh();
        $this->assertSame(Payment::VERIFIED, $payment->status);
        $this->assertNotNull($payment->receipt_number);

        $this->assertDatabaseHas('ledger_entries', [
            'payment_id' => $payment->id,
            'type' => 'payment',
            'credit' => 5000,
        ]);

        $enrollment = Enrollment::query()
            ->where('student_id', $this->student->id)
            ->firstOrFail();
        $this->assertEquals(17500.0, (float) $enrollment->balance);
    }

    public function test_a_payment_cannot_be_verified_twice(): void
    {
        $payment = Payment::query()->where('status', Payment::PENDING)->firstOrFail();

        $this->actingAs($this->financeUser)
            ->post("/finance/payments/{$payment->id}/verify")
            ->assertRedirect();

        $this->actingAs($this->financeUser)
            ->post("/finance/payments/{$payment->id}/verify")
            ->assertSessionHasErrors('payment');

        $this->assertSame(1, LedgerEntry::query()->where('payment_id', $payment->id)->count());
    }

    public function test_a_verified_payment_cannot_be_rejected(): void
    {
        $payment = Payment::query()->where('status', Payment::PENDING)->firstOrFail();

        $this->actingAs($this->financeUser)
            ->post("/finance/payments/{$payment->id}/verify")
            ->assertRedirect();

        $this->actingAs($this->financeUser)
            ->post("/finance/payments/{$payment->id}/reject", ['remarks' => 'Too late'])
            ->assertSessionHasErrors('payment');

        $this->assertSame(Payment::VERIFIED, $payment->fresh()->status);
    }

    public function test_finance_can_reject_a_pending_payment(): void
    {
        $payment = Payment::create([
            'student_id' => $this->student->id,
            'academic_year_id' => Enrollment::query()->where('student_id', $this->student->id)->value('academic_year_id'),
            'amount' => 1000,
            'method' => 'gcash',
            'reference_number' => 'REJECT-TEST-001',
            'status' => Payment::PENDING,
        ]);

        $this->actingAs($this->financeUser)
            ->post("/finance/payments/{$payment->id}/reject", [
                'remarks' => 'Reference could not be verified.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment->refresh();

        $this->assertSame(Payment::REJECTED, $payment->status);
        $this->assertSame('Reference could not be verified.', $payment->remarks);
        $this->assertDatabaseMissing('ledger_entries', ['payment_id' => $payment->id]);
    }

    public function test_finance_can_post_a_walk_in_payment(): void
    {
        $this->actingAs($this->financeUser)
            ->post('/finance/payments', [
                'student_id' => $this->student->id,
                'amount' => 1000,
                'method' => 'cash',
                'reference_number' => 'WALK-IN-TEST-001',
                'remarks' => 'Paid at the finance office.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $payment = Payment::query()->where('reference_number', 'WALK-IN-TEST-001')->firstOrFail();

        $this->assertSame(Payment::VERIFIED, $payment->status);
        $this->assertNotNull($payment->receipt_number);
        $this->assertDatabaseHas('ledger_entries', [
            'payment_id' => $payment->id,
            'credit' => 1000,
        ]);
    }

    public function test_finance_can_view_student_ledgers_and_receipts(): void
    {
        $this->actingAs($this->financeUser)
            ->get('/finance/accounts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Finance/Accounts/Index'));

        $this->actingAs($this->financeUser)
            ->get("/finance/accounts/{$this->student->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Finance/Accounts/Show'));

        $payment = Payment::query()->where('status', Payment::VERIFIED)->firstOrFail();

        $this->actingAs($this->financeUser)
            ->get("/payments/{$payment->id}/receipt")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Payments/Receipt'));
    }

    public function test_student_cannot_access_finance_pages(): void
    {
        $this->actingAs($this->studentUser)
            ->get('/finance/payments')
            ->assertForbidden();
    }
}
