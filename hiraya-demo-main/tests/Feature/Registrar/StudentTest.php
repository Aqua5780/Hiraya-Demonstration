<?php

namespace Tests\Feature\Registrar;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    private User $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->registrar = User::query()->where('email', 'registrar@hiraya.test')->firstOrFail();
    }

    public function test_registrar_can_view_and_search_students(): void
    {
        $this->actingAs($this->registrar)
            ->get('/registrar/students')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Registrar/Students/Index')
                ->has('students.data')
                ->where('students.total', 24)
            );

        $this->actingAs($this->registrar)
            ->get('/registrar/students?q=Juan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('students.total', 1)
                ->where('students.data.0.student_number', '2026-0001')
            );
    }

    public function test_registrar_can_view_a_student_record(): void
    {
        $student = Student::query()->where('student_number', '2026-0001')->firstOrFail();

        $this->actingAs($this->registrar)
            ->get("/registrar/students/{$student->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Registrar/Students/Show')
                ->where('student.student_number', '2026-0001')
                ->where('enrollment.units', 15)
            );
    }

    public function test_registrar_can_update_a_student_record_without_changing_the_login_email(): void
    {
        $student = Student::query()->where('student_number', '2026-0002')->firstOrFail();
        $student->load('user');
        $loginEmail = $student->user->email;

        $this->actingAs($this->registrar)->patch(
            "/registrar/students/{$student->id}",
            $this->studentPayload($student, ['first_name' => 'Maria Clara Updated']),
        )->assertRedirect();

        $student->refresh();

        $this->assertSame('Maria Clara Updated', $student->first_name);
        $this->assertSame($loginEmail, $student->user->fresh()->email);
    }

    public function test_registrar_cannot_change_a_student_login_email(): void
    {
        $student = Student::query()->where('student_number', '2026-0002')->firstOrFail();
        $student->load('user');
        $originalStudentEmail = $student->email;
        $originalLoginEmail = $student->user->email;

        $this->actingAs($this->registrar)
            ->patch(
                "/registrar/students/{$student->id}",
                $this->studentPayload($student, ['email' => 'maria.reyes@hiraya.test']),
            )
            ->assertSessionHasErrors('email');

        $student->refresh();

        $this->assertSame($originalStudentEmail, $student->email);
        $this->assertSame($originalLoginEmail, $student->user->fresh()->email);
    }

    public function test_admin_can_change_a_student_login_email(): void
    {
        $student = Student::query()->where('student_number', '2026-0002')->firstOrFail();
        $student->load('user');
        $admin = User::query()->where('email', 'admin@hiraya.test')->firstOrFail();
        $newEmail = 'admin.changed.student@hiraya.test';

        $this->actingAs($admin)
            ->patch(
                "/registrar/students/{$student->id}",
                $this->studentPayload($student, ['email' => $newEmail]),
            )
            ->assertRedirect();

        $student->refresh();

        $this->assertSame($newEmail, $student->email);
        $this->assertSame($newEmail, $student->user->fresh()->email);
    }

    public function test_registrar_can_sort_students_by_section(): void
    {
        $this->actingAs($this->registrar)
            ->get('/registrar/students?sort=section&direction=asc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('students.data', 15));
    }

    public function test_search_input_is_bound_and_does_not_inject_sql(): void
    {
        $payload = urlencode("' OR 1=1 --");

        $this->actingAs($this->registrar)
            ->get("/registrar/students?q={$payload}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('students.total', 0));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function studentPayload(Student $student, array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Maria Clara',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'suffix' => '',
            'email' => $student->email,
            'phone' => '09170000000',
            'address' => 'Taguig City',
            'birth_date' => '2007-08-15',
            'gender' => 'Female',
            'program_id' => $student->program_id,
            'year_level_id' => $student->year_level_id,
            'status' => 'active',
            'guardian_name' => 'Parent',
            'guardian_phone' => '09180000000',
        ], $overrides);
    }
}
