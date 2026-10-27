<?php

namespace Tests\Feature\Student;

use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\EnrollmentDetail;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;

    private Student $student;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->studentUser = User::query()->where('email', 'student@hiraya.test')->firstOrFail();
        $this->student = $this->studentUser->student;
        $this->year = AcademicYear::query()->where('is_active', true)->firstOrFail();
    }

    public function test_student_pages_render(): void
    {
        $this->actingAs($this->studentUser)->get('/student/schedule')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Schedule/Index'));
        $this->actingAs($this->studentUser)->get('/student/classes')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Classes/Index'));
        $this->actingAs($this->studentUser)->get('/student/grades')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Grades/Index'));
        $this->actingAs($this->studentUser)->get('/student/announcements')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Announcements/Index'));
        $this->actingAs($this->studentUser)->get('/student/payments')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Payments/Index'));
    }

    public function test_student_can_view_a_class_roster_only_when_enrolled(): void
    {
        $detail = EnrollmentDetail::query()
            ->whereHas('enrollment', fn ($query) => $query
                ->where('student_id', $this->student->id)
                ->where('academic_year_id', $this->year->id))
            ->firstOrFail();

        $this->actingAs($this->studentUser)
            ->get("/student/classes/{$detail->class_offering_id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Classes/Show')
                ->has('roster')
            );

        $other = ClassOffering::query()
            ->where('academic_year_id', $this->year->id)
            ->whereKeyNot($detail->class_offering_id)
            ->whereDoesntHave('enrollmentDetails', fn ($query) => $query
                ->whereHas('enrollment', fn ($query) => $query->where('student_id', $this->student->id)))
            ->firstOrFail();

        $this->actingAs($this->studentUser)
            ->get("/student/classes/{$other->id}")
            ->assertForbidden();
    }

    public function test_student_grades_page_contains_the_encoded_grades(): void
    {
        $this->actingAs($this->studentUser)
            ->get('/student/grades')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('grades')
                ->where('average', 89)
            );
    }
}
