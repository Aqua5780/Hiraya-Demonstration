<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\ClassSchedule;
use App\Models\FacultyProfile;
use App\Models\FeeStructure;
use App\Models\Program;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = AcademicYear::query()->updateOrCreate(
            ['name' => 'SY 2026-2027'],
            [
                'start_date' => '2026-06-01',
                'end_date' => '2027-03-31',
                'enrollment_start' => '2026-10-01',
                'enrollment_end' => '2026-11-30',
                'status' => 'active',
                'is_active' => true,
            ],
        );

        AcademicYear::query()->updateOrCreate(
            ['name' => 'SY 2025-2026'],
            [
                'start_date' => '2025-06-02',
                'end_date' => '2026-03-31',
                'enrollment_start' => '2025-05-01',
                'enrollment_end' => '2025-06-15',
                'status' => 'completed',
                'is_active' => false,
            ],
        );

        $programs = [
            ['code' => 'BSTM', 'name' => 'Bachelor of Science in Tourism Management', 'level' => Program::COLLEGE],
            ['code' => 'BSEntrep', 'name' => 'Bachelor of Science in Entrepreneurship', 'level' => Program::COLLEGE],
            ['code' => 'BSOA', 'name' => 'Bachelor of Science in Office Administration', 'level' => Program::COLLEGE],
            ['code' => 'BPA', 'name' => 'Bachelor in Public Administration', 'level' => Program::COLLEGE],
            ['code' => 'ABM', 'name' => 'Accountancy, Business and Management', 'level' => Program::SENIOR_HIGH],
            ['code' => 'HUMSS', 'name' => 'Humanities and Social Sciences', 'level' => Program::SENIOR_HIGH],
            ['code' => 'GAS', 'name' => 'General Academic Strand', 'level' => Program::SENIOR_HIGH],
            ['code' => 'TVL-HE', 'name' => 'Technical-Vocational-Livelihood - Home Economics', 'level' => Program::SENIOR_HIGH],
        ];

        foreach ($programs as $program) {
            Program::query()->updateOrCreate(['code' => $program['code']], $program);
        }

        $yearLevels = [
            ['name' => 'Kinder', 'level' => Program::ELEMENTARY, 'sort_order' => 0],
            ['name' => 'Grade 1', 'level' => Program::ELEMENTARY, 'sort_order' => 1],
            ['name' => 'Grade 2', 'level' => Program::ELEMENTARY, 'sort_order' => 2],
            ['name' => 'Grade 3', 'level' => Program::ELEMENTARY, 'sort_order' => 3],
            ['name' => 'Grade 4', 'level' => Program::ELEMENTARY, 'sort_order' => 4],
            ['name' => 'Grade 5', 'level' => Program::ELEMENTARY, 'sort_order' => 5],
            ['name' => 'Grade 6', 'level' => Program::ELEMENTARY, 'sort_order' => 6],
            ['name' => 'Grade 7', 'level' => Program::JUNIOR_HIGH, 'sort_order' => 7],
            ['name' => 'Grade 8', 'level' => Program::JUNIOR_HIGH, 'sort_order' => 8],
            ['name' => 'Grade 9', 'level' => Program::JUNIOR_HIGH, 'sort_order' => 9],
            ['name' => 'Grade 10', 'level' => Program::JUNIOR_HIGH, 'sort_order' => 10],
            ['name' => 'Grade 11', 'level' => Program::SENIOR_HIGH, 'sort_order' => 11],
            ['name' => 'Grade 12', 'level' => Program::SENIOR_HIGH, 'sort_order' => 12],
            ['name' => '1st Year', 'level' => Program::COLLEGE, 'sort_order' => 13],
            ['name' => '2nd Year', 'level' => Program::COLLEGE, 'sort_order' => 14],
            ['name' => '3rd Year', 'level' => Program::COLLEGE, 'sort_order' => 15],
            ['name' => '4th Year', 'level' => Program::COLLEGE, 'sort_order' => 16],
        ];

        foreach ($yearLevels as $yearLevel) {
            YearLevel::query()->updateOrCreate(['name' => $yearLevel['name']], $yearLevel);
        }

        $faculty = [
            ['name' => 'Maria Santos', 'department' => 'Information Technology', 'email' => 'faculty@hiraya.test'],
            ['name' => 'Jose Rizal', 'department' => 'Business Education', 'email' => 'faculty2@hiraya.test'],
            ['name' => 'Andres Bonifacio', 'department' => 'Tourism and Hospitality', 'email' => 'faculty3@hiraya.test'],
            ['name' => 'Gabriela Silang', 'department' => 'Education', 'email' => 'faculty4@hiraya.test'],
            ['name' => 'Emilio Aguinaldo', 'department' => 'Public Administration', 'email' => 'faculty5@hiraya.test'],
            ['name' => 'Melchora Aquino', 'department' => 'General Education', 'email' => 'faculty6@hiraya.test'],
            ['name' => 'Apolinario Mabini', 'department' => 'Mathematics', 'email' => 'faculty7@hiraya.test'],
            ['name' => 'Gregoria de Jesus', 'department' => 'Languages', 'email' => 'faculty8@hiraya.test'],
            ['name' => 'Lapu-Lapu', 'department' => 'Physical Education', 'email' => 'faculty9@hiraya.test'],
            ['name' => 'Teresa Magbanua', 'department' => 'Social Sciences', 'email' => 'faculty10@hiraya.test'],
            ['name' => 'Francisco Baltazar', 'department' => 'Humanities', 'email' => 'faculty11@hiraya.test'],
        ];

        $facultyRole = Role::query()->where('slug', Role::FACULTY)->firstOrFail();
        $facultyProfiles = [];

        foreach ($faculty as $index => $person) {
            $user = User::query()->updateOrCreate(
                ['email' => $person['email']],
                [
                    'role_id' => $facultyRole->id,
                    'name' => $person['name'],
                    'password' => 'Password123!',
                    'status' => 'active',
                ],
            );

            $facultyProfiles[] = FacultyProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_number' => 'EMP-2026-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'department' => $person['department'],
                    'employment_status' => 'full_time',
                ],
            );
        }

        $sectionSpecs = [
            ['program' => 'BSTM', 'year' => '1st Year', 'name' => '1-A', 'room' => 'Room 301'],
            ['program' => 'BSTM', 'year' => '1st Year', 'name' => '1-B', 'room' => 'Room 302'],
            ['program' => 'BSTM', 'year' => '2nd Year', 'name' => '2-A', 'room' => 'Room 303'],
            ['program' => 'BSEntrep', 'year' => '1st Year', 'name' => '1-A', 'room' => 'Room 304'],
            ['program' => 'BSOA', 'year' => '1st Year', 'name' => '1-A', 'room' => 'Room 305'],
            ['program' => 'BPA', 'year' => '1st Year', 'name' => '1-A', 'room' => 'Room 306'],
            ['program' => 'ABM', 'year' => 'Grade 11', 'name' => '11-A', 'room' => 'Room 401'],
            ['program' => 'HUMSS', 'year' => 'Grade 11', 'name' => '11-A', 'room' => 'Room 402'],
            ['program' => 'GAS', 'year' => 'Grade 11', 'name' => '11-A', 'room' => 'Room 403'],
            ['program' => 'TVL-HE', 'year' => 'Grade 11', 'name' => '11-A', 'room' => 'Room 404'],
        ];

        $sections = [];

        foreach ($sectionSpecs as $index => $spec) {
            $program = Program::query()->where('code', $spec['program'])->firstOrFail();
            $yearLevel = YearLevel::query()->where('name', $spec['year'])->firstOrFail();

            $sections[] = Section::query()->updateOrCreate(
                [
                    'academic_year_id' => $activeYear->id,
                    'program_id' => $program->id,
                    'year_level_id' => $yearLevel->id,
                    'name' => $spec['name'],
                ],
                [
                    'capacity' => 40,
                    'adviser_id' => $facultyProfiles[$index % 10]->id,
                    'status' => 'active',
                ],
            );
        }

        $subjects = [
            ['code' => 'GEC101', 'name' => 'Understanding the Self', 'units' => 3, 'program' => null, 'type' => 'core'],
            ['code' => 'GEC102', 'name' => 'Purposive Communication', 'units' => 3, 'program' => null, 'type' => 'core'],
            ['code' => 'GEC103', 'name' => 'Mathematics in the Modern World', 'units' => 3, 'program' => null, 'type' => 'core'],
            ['code' => 'TM101', 'name' => 'Fundamentals of Tourism and Hospitality', 'units' => 3, 'program' => 'BSTM', 'type' => 'major'],
            ['code' => 'TM102', 'name' => 'Tourism Geography and Culture', 'units' => 3, 'program' => 'BSTM', 'type' => 'major'],
            ['code' => 'TM103', 'name' => 'Sustainable Tourism Elective', 'units' => 3, 'program' => 'BSTM', 'type' => 'elective'],
            ['code' => 'ENT101', 'name' => 'Introduction to Entrepreneurship', 'units' => 3, 'program' => 'BSEntrep', 'type' => 'major'],
            ['code' => 'ENT102', 'name' => 'Business Management', 'units' => 3, 'program' => 'BSEntrep', 'type' => 'major'],
            ['code' => 'OA101', 'name' => 'Office Administration Fundamentals', 'units' => 3, 'program' => 'BSOA', 'type' => 'major'],
            ['code' => 'OA102', 'name' => 'Business Communication', 'units' => 3, 'program' => 'BSOA', 'type' => 'major'],
            ['code' => 'PA101', 'name' => 'Introduction to Public Administration', 'units' => 3, 'program' => 'BPA', 'type' => 'major'],
            ['code' => 'PA102', 'name' => 'Governance and Public Policy', 'units' => 3, 'program' => 'BPA', 'type' => 'major'],
            ['code' => 'ORALCOMM', 'name' => 'Oral Communication in Context', 'units' => 3, 'program' => null, 'type' => 'core'],
            ['code' => 'GENMATH', 'name' => 'General Mathematics', 'units' => 3, 'program' => null, 'type' => 'core'],
            ['code' => 'EAPP', 'name' => 'English for Academic and Professional Purposes', 'units' => 3, 'program' => null, 'type' => 'core'],
            ['code' => 'PR1', 'name' => 'Practical Research 1', 'units' => 3, 'program' => null, 'type' => 'core'],
            ['code' => 'PEH11', 'name' => 'Physical Education and Health 1', 'units' => 2, 'program' => null, 'type' => 'core'],
        ];

        foreach ($subjects as $subject) {
            $program = $subject['program'] ? Program::query()->where('code', $subject['program'])->first() : null;

            Subject::query()->updateOrCreate(
                ['code' => $subject['code']],
                [
                    'name' => $subject['name'],
                    'units' => $subject['units'],
                    'program_id' => $program?->id,
                    'year_level_id' => null,
                    'type' => $subject['type'],
                    'status' => 'active',
                ],
            );
        }

        $subjectCodes = [
            'BSTM' => ['GEC101', 'GEC102', 'GEC103', 'TM101', 'TM102'],
            'BSEntrep' => ['GEC101', 'GEC102', 'GEC103', 'ENT101', 'ENT102'],
            'BSOA' => ['GEC101', 'GEC102', 'GEC103', 'OA101', 'OA102'],
            'BPA' => ['GEC101', 'GEC102', 'GEC103', 'PA101', 'PA102'],
            'ABM' => ['ORALCOMM', 'GENMATH', 'EAPP', 'PR1', 'PEH11'],
            'HUMSS' => ['ORALCOMM', 'GENMATH', 'EAPP', 'PR1', 'PEH11'],
            'GAS' => ['ORALCOMM', 'GENMATH', 'EAPP', 'PR1', 'PEH11'],
            'TVL-HE' => ['ORALCOMM', 'GENMATH', 'EAPP', 'PR1', 'PEH11'],
        ];

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($sections as $sectionIndex => $section) {
            $programCode = $section->program->code;
            $codes = $subjectCodes[$programCode];

            foreach ($codes as $subjectIndex => $code) {
                $subject = Subject::query()->where('code', $code)->firstOrFail();
                $facultyIndex = ($sectionIndex + $subjectIndex) % 10;

                $offering = ClassOffering::query()->updateOrCreate(
                    [
                        'academic_year_id' => $activeYear->id,
                        'section_id' => $section->id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'faculty_id' => $facultyProfiles[$facultyIndex]->id,
                        'room' => $sectionSpecs[$sectionIndex]['room'],
                        'capacity' => 40,
                        'status' => 'open',
                    ],
                );

                $offering->schedules()->delete();
                ClassSchedule::create([
                    'class_offering_id' => $offering->id,
                    'day_of_week' => $days[$subjectIndex],
                    'start_time' => '08:00:00',
                    'end_time' => '09:30:00',
                    'room' => $sectionSpecs[$sectionIndex]['room'],
                ]);
            }
        }

        $firstSection = $sections[0];
        $elective = Subject::query()->where('code', 'TM103')->firstOrFail();

        $electiveOffering = ClassOffering::query()->updateOrCreate(
            [
                'academic_year_id' => $activeYear->id,
                'section_id' => $firstSection->id,
                'subject_id' => $elective->id,
            ],
            [
                'faculty_id' => $facultyProfiles[10]->id,
                'room' => 'Room 301',
                'capacity' => 40,
                'status' => 'open',
            ],
        );

        $electiveOffering->schedules()->delete();
        ClassSchedule::create([
            'class_offering_id' => $electiveOffering->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'room' => 'Room 301',
        ]);

        $fees = [
            ['program' => 'BSTM', 'year' => '1st Year', 'tuition' => 28000, 'misc' => 4500],
            ['program' => 'BSTM', 'year' => '2nd Year', 'tuition' => 28000, 'misc' => 4500],
            ['program' => 'BSEntrep', 'year' => '1st Year', 'tuition' => 26000, 'misc' => 4000],
            ['program' => 'BSOA', 'year' => '1st Year', 'tuition' => 24000, 'misc' => 4000],
            ['program' => 'BPA', 'year' => '1st Year', 'tuition' => 24000, 'misc' => 4000],
            ['program' => 'ABM', 'year' => 'Grade 11', 'tuition' => 18000, 'misc' => 3000],
            ['program' => 'HUMSS', 'year' => 'Grade 11', 'tuition' => 18000, 'misc' => 3000],
            ['program' => 'GAS', 'year' => 'Grade 11', 'tuition' => 18000, 'misc' => 3000],
            ['program' => 'TVL-HE', 'year' => 'Grade 11', 'tuition' => 18000, 'misc' => 3000],
        ];

        foreach ($fees as $fee) {
            $program = Program::query()->where('code', $fee['program'])->firstOrFail();
            $year = YearLevel::query()->where('name', $fee['year'])->firstOrFail();

            FeeStructure::query()->updateOrCreate(
                [
                    'academic_year_id' => $activeYear->id,
                    'program_id' => $program->id,
                    'year_level_id' => $year->id,
                ],
                [
                    'name' => 'Standard Fees',
                    'tuition_amount' => $fee['tuition'],
                    'misc_amount' => $fee['misc'],
                    'total_amount' => $fee['tuition'] + $fee['misc'],
                    'status' => 'active',
                ],
            );
        }
    }
}
