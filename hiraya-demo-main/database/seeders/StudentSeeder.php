<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Program;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Models\YearLevel;
use App\Services\EnrollmentService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function __construct(
        private readonly EnrollmentService $enrollmentService,
        private readonly PaymentService $paymentService,
    ) {}

    public function run(): void
    {
        $year = AcademicYear::query()->where('is_active', true)->firstOrFail();
        $studentRole = Role::query()->where('slug', Role::STUDENT)->firstOrFail();

        $specs = [
            ['Juan', 'Santos', 'Dela Cruz', 'student@hiraya.test', 'BSTM', '1st Year', '1-A', true],
            ['Maria Clara', 'Santos', 'Reyes', 'newstudent@hiraya.test', 'BSTM', '1st Year', '1-A', false],
            ['Josefa', 'Llanes', 'Escoda', 'student3@hiraya.test', 'BSTM', '1st Year', '1-A', true],
            ['Antonio', 'Nacional', 'Luna', 'student4@hiraya.test', 'BSTM', '1st Year', '1-A', true],
            ['Gregorio', 'Hilario', 'del Pilar', 'student5@hiraya.test', 'BSTM', '1st Year', '1-A', true],
            ['Marcela', 'Marino', 'Agoncillo', 'student6@hiraya.test', 'BSTM', '1st Year', '1-A', true],
            ['Diego', 'Andaya', 'Silang', 'student7@hiraya.test', 'BSTM', '1st Year', '1-A', true],
            ['Trinidad', 'Perez', 'Tecson', 'student8@hiraya.test', 'BSTM', '1st Year', '1-A', true],
            ['Emilio', 'Dizon', 'Jacinto', 'student9@hiraya.test', 'BSTM', '1st Year', '1-B', true],
            ['Leonor', 'Valenzuela', 'Rivera', 'student10@hiraya.test', 'BSTM', '1st Year', '1-B', true],
            ['Isabelo', 'Florentino', 'de los Reyes', 'student11@hiraya.test', 'BSTM', '1st Year', '1-B', true],
            ['Paz', 'Marquez', 'Benitez', 'student12@hiraya.test', 'BSTM', '1st Year', '1-B', true],
            ['Carlos', 'Pequeno', 'Romulo', 'student13@hiraya.test', 'BSTM', '2nd Year', '2-A', true],
            ['Liza', 'Macuja', 'Elizalde', 'student14@hiraya.test', 'BSTM', '2nd Year', '2-A', true],
            ['Fernando', 'Cueto', 'Amorsolo', 'student15@hiraya.test', 'BSEntrep', '1st Year', '1-A', true],
            ['Francisca', 'Reyes', 'Aquino', 'student16@hiraya.test', 'BSEntrep', '1st Year', '1-A', true],
            ['Guillermo', 'Estrella', 'Tolentino', 'student17@hiraya.test', 'BSOA', '1st Year', '1-A', true],
            ['Lucio', 'San', 'Pedro', 'student18@hiraya.test', 'BSOA', '1st Year', '1-A', true],
            ['Nicanor', 'Santa', 'Abelardo', 'student19@hiraya.test', 'BPA', '1st Year', '1-A', true],
            ['Bienvenido', 'Lumbera', 'Santos', 'student20@hiraya.test', 'BPA', '1st Year', '1-A', true],
            ['Edith', 'Tiempo', 'Lopez', 'student21@hiraya.test', 'ABM', 'Grade 11', '11-A', true],
            ['F. Sionil', 'Jose', 'Reyes', 'student22@hiraya.test', 'HUMSS', 'Grade 11', '11-A', true],
            ['Nick', 'Joaquin', 'Reyes', 'student23@hiraya.test', 'GAS', 'Grade 11', '11-A', true],
            ['Paz', 'Latorena', 'Cruz', 'student24@hiraya.test', 'TVL-HE', 'Grade 11', '11-A', true],
        ];

        $students = [];

        foreach ($specs as $index => $spec) {
            [$firstName, $middleName, $lastName, $email, $programCode, $yearName, $sectionName, $shouldEnroll] = $spec;

            $program = Program::query()->where('code', $programCode)->firstOrFail();
            $yearLevel = YearLevel::query()->where('name', $yearName)->firstOrFail();

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'role_id' => $studentRole->id,
                    'name' => trim("{$firstName} {$middleName} {$lastName}"),
                    'password' => 'Password123!',
                    'status' => 'active',
                ],
            );

            $student = Student::query()->updateOrCreate(
                ['student_number' => '2026-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => '0917-'.str_pad((string) (2000000 + $index), 7, '0', STR_PAD_LEFT),
                    'address' => 'Central Bicutan, Taguig City',
                    'birth_date' => $program->level === Program::SENIOR_HIGH ? '2009-05-'.str_pad((string) (($index % 27) + 1), 2, '0', STR_PAD_LEFT) : '2007-08-'.str_pad((string) (($index % 27) + 1), 2, '0', STR_PAD_LEFT),
                    'gender' => $index % 2 === 0 ? 'Male' : 'Female',
                    'program_id' => $program->id,
                    'year_level_id' => $yearLevel->id,
                    'status' => 'active',
                    'guardian_name' => 'Parent of '.$firstName.' '.$lastName,
                    'guardian_phone' => '0918-'.str_pad((string) (3000000 + $index), 7, '0', STR_PAD_LEFT),
                ],
            );

            $students[] = ['student' => $student, 'enroll' => $shouldEnroll, 'program' => $programCode, 'year' => $yearName, 'section' => $sectionName];
        }

        $allOfferings = ClassOffering::query()
            ->with('subject')
            ->where('academic_year_id', $year->id)
            ->get();

        foreach ($students as $entry) {
            if (! $entry['enroll']) {
                continue;
            }

            $section = Section::query()
                ->where('academic_year_id', $year->id)
                ->where('program_id', Program::query()->where('code', $entry['program'])->value('id'))
                ->where('year_level_id', YearLevel::query()->where('name', $entry['year'])->value('id'))
                ->where('name', $entry['section'])
                ->firstOrFail();

            $offeringIds = $allOfferings
                ->where('section_id', $section->id)
                ->filter(fn (ClassOffering $offering): bool => $offering->subject?->code !== 'TM103')
                ->pluck('id')
                ->all();

            $this->enrollmentService->enroll($entry['student'], $year, $offeringIds, null, true);
        }

        $demo = $students[0]['student'];
        $finance = User::query()->where('email', 'finance@hiraya.test')->firstOrFail();

        $demoEnrollment = Enrollment::query()
            ->where('student_id', $demo->id)
            ->where('academic_year_id', $year->id)
            ->firstOrFail();

        $this->paymentService->postDirect($demo, $year, [
            'amount' => 10000,
            'method' => 'gcash',
            'reference_number' => 'GCASH-2026-10001',
            'remarks' => 'Initial partial payment.',
        ], $finance);

        $this->paymentService->submit($demo, $year, [
            'amount' => 5000,
            'method' => 'bank_transfer',
            'reference_number' => 'BDO-2026-55001',
        ], $demo->user);

        $gradeScores = [
            [88, 90],
            [85, 87],
            [91, 93],
        ];

        $demoEnrollment->load('details.classOffering.faculty');

        foreach ($demoEnrollment->details->take(3)->values() as $index => $detail) {
            [$midterm, $final] = $gradeScores[$index];

            Grade::query()->updateOrCreate(
                ['enrollment_detail_id' => $detail->id],
                [
                    'faculty_id' => $detail->classOffering?->faculty_id,
                    'midterm' => $midterm,
                    'final' => $final,
                    'final_rating' => round(($midterm + $final) / 2, 2),
                    'remarks' => round(($midterm + $final) / 2, 2) >= 75 ? 'Passed' : 'Failed',
                    'status' => 'submitted',
                    'encoded_at' => now()->subDays(3),
                ],
            );
        }

        foreach (array_slice($students, 2, 4) as $entry) {
            $enrollment = Enrollment::query()
                ->where('student_id', $entry['student']->id)
                ->where('academic_year_id', $year->id)
                ->with('details.classOffering')
                ->first();

            if (! $enrollment) {
                continue;
            }

            foreach ($enrollment->details->take(2) as $detail) {
                Grade::query()->updateOrCreate(
                    ['enrollment_detail_id' => $detail->id],
                    [
                        'faculty_id' => $detail->classOffering?->faculty_id,
                        'midterm' => 86,
                        'final' => 88,
                        'final_rating' => 87,
                        'remarks' => 'Passed',
                        'status' => 'submitted',
                        'encoded_at' => now()->subDays(4),
                    ],
                );
            }
        }
    }
}
