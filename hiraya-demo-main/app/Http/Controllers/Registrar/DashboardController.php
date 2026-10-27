<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $year = AcademicYear::query()->where('is_active', true)->first();

        $stats = [
            'students' => Student::query()->count(),
            'enrolled' => $year ? Enrollment::query()->where('academic_year_id', $year->id)->where('status', 'enrolled')->count() : 0,
            'programs' => Program::query()->where('status', 'active')->count(),
            'unassigned' => Student::query()->whereNull('program_id')->count(),
        ];

        $recentStudents = Student::query()
            ->with(['program:id,code,name', 'yearLevel:id,name'])
            ->latest()
            ->take(6)
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'name' => $student->full_name,
                'program' => $student->program?->code,
                'year_level' => $student->yearLevel?->name,
                'status' => $student->status,
            ]);

        $recentEnrollments = $year
            ? Enrollment::query()
                ->with(['student:id,first_name,last_name,student_number', 'section:id,name'])
                ->where('academic_year_id', $year->id)
                ->latest('enrolled_at')
                ->take(6)
                ->get()
                ->map(fn (Enrollment $enrollment): array => [
                    'id' => $enrollment->id,
                    'student' => $enrollment->student?->full_name,
                    'student_number' => $enrollment->student?->student_number,
                    'section' => $enrollment->section?->name,
                    'units' => (float) $enrollment->total_units,
                    'balance' => (float) $enrollment->balance,
                    'enrolled_at' => $enrollment->enrolled_at?->toIso8601String(),
                ])
            : collect();

        return Inertia::render('Registrar/Dashboard', [
            'year' => $year?->only(['id', 'name']),
            'stats' => $stats,
            'recentStudents' => $recentStudents,
            'recentEnrollments' => $recentEnrollments,
        ]);
    }
}
