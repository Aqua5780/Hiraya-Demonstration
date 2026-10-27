<?php

namespace Tests\Feature\Student;

use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $newStudent;

    private AcademicYear $year;

    private array $offeringIds;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->newStudent = User::query()->where('email', 'newstudent@hiraya.test')->firstOrFail();
        $this->year = AcademicYear::query()->where('is_active', true)->firstOrFail();

        $section = Section::query()
            ->where('name', '1-A')
            ->whereHas('program', fn ($query) => $query->where('code', 'BSTM'))
            ->firstOrFail();

        $this->offeringIds = ClassOffering::query()
            ->where('section_id', $section->id)
            ->whereHas('subject', fn ($query) => $query->where('code', '!=', 'TM103'))
            ->pluck('id')
            ->all();
    }

    public function test_unenrolled_student_can_view_enrollment_options(): void
    {
        $this->actingAs($this->newStudent)
            ->get('/student/enrollment')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Enrollment/Index')
                ->where('canEnroll', true)
                ->has('offerings')
            );
    }

    public function test_student_can_enroll_in_classes(): void
    {
        $this->actingAs($this->newStudent)->post('/student/enrollment', [
            'classes' => $this->offeringIds,
        ])->assertRedirect()->assertSessionHas('success');

        $student = Student::query()->where('email', 'newstudent@hiraya.test')->firstOrFail();
        $enrollment = Enrollment::query()->where('student_id', $student->id)->firstOrFail();

        $this->assertSame('enrolled', $enrollment->status);
        $this->assertEquals(15.0, (float) $enrollment->total_units);
        $this->assertEquals(32500.0, (float) $enrollment->assessed_amount);
        $this->assertDatabaseHas('ledger_entries', [
            'student_id' => $student->id,
            'type' => 'charge',
        ]);
        $this->assertSame(5, $enrollment->details()->count());
    }

    public function test_overlapping_classes_are_rejected(): void
    {
        $section = Section::query()
            ->where('name', '1-A')
            ->whereHas('program', fn ($query) => $query->where('code', 'BSTM'))
            ->firstOrFail();

        $overlapping = ClassOffering::query()
            ->where('section_id', $section->id)
            ->whereHas('subject', fn ($query) => $query->whereIn('code', ['GEC101', 'TM103']))
            ->pluck('id')
            ->all();

        $this->actingAs($this->newStudent)->post('/student/enrollment', [
            'classes' => $overlapping,
        ])->assertSessionHasErrors('classes');

        $student = Student::query()->where('email', 'newstudent@hiraya.test')->firstOrFail();
        $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);
    }

    public function test_student_cannot_enroll_when_window_is_closed(): void
    {
        $this->year->update(['enrollment_end' => now()->subDay()->toDateString()]);

        $this->actingAs($this->newStudent)->post('/student/enrollment', [
            'classes' => $this->offeringIds,
        ])->assertSessionHasErrors('classes');
    }

    public function test_already_enrolled_student_cannot_enroll_again(): void
    {
        $student = User::query()->where('email', 'student@hiraya.test')->firstOrFail();

        $this->actingAs($student)->post('/student/enrollment', [
            'classes' => $this->offeringIds,
        ])->assertSessionHasErrors('classes');
    }
}
