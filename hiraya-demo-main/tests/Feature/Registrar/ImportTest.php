<?php

namespace Tests\Feature\Registrar;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    private User $registrar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->registrar = User::query()->where('email', 'registrar@hiraya.test')->firstOrFail();
    }

    public function test_template_can_be_downloaded(): void
    {
        $response = $this->actingAs($this->registrar)->get('/registrar/import/template');

        $response->assertOk();
        $this->assertStringContainsString('student_number,first_name', $response->streamedContent());
    }

    public function test_valid_csv_imports_students(): void
    {
        $csv = implode("\n", [
            'student_number,first_name,middle_name,last_name,suffix,email,phone,birth_date,gender,program_code,year_level,guardian_name,guardian_phone,status',
            '2026-9001,Test,Middle,Student,,test.student@hiraya.test,09170000000,2007-08-15,Male,BSTM,1st Year,Parent,09180000000,active',
        ]);

        $this->actingAs($this->registrar)->post('/registrar/import', [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'student_number' => '2026-9001',
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);
    }

    public function test_invalid_headers_are_reported(): void
    {
        $csv = "student_number,name\n2026-9002,Test Student\n";

        $this->actingAs($this->registrar)->post('/registrar/import', [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertRedirect()->assertSessionHas('import_errors');

        $this->assertDatabaseMissing('students', ['student_number' => '2026-9002']);
    }

    public function test_invalid_rows_prevent_the_import(): void
    {
        $csv = implode("\n", [
            'student_number,first_name,middle_name,last_name,suffix,email,phone,birth_date,gender,program_code,year_level,guardian_name,guardian_phone,status',
            '2026-0001,Duplicate,Student,,,duplicate@hiraya.test,,,,BSTM,1st Year,,,active',
            '2026-9003,Valid,Student,,,valid@hiraya.test,,,,UNKNOWN,1st Year,,,active',
        ]);

        $this->actingAs($this->registrar)->post('/registrar/import', [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertRedirect()->assertSessionHas('import_errors');

        $this->assertDatabaseMissing('students', ['student_number' => '2026-9003']);
        $this->assertSame(24, Student::query()->count());
    }
}
