<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassOffering;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassController extends Controller
{
    public function index(Request $request): Response
    {
        $faculty = $request->user()->facultyProfile;
        abort_if(! $faculty, 403, 'Your faculty profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();

        $classes = ClassOffering::query()
            ->with([
                'subject:id,code,name,units',
                'section:id,name,program_id,year_level_id',
                'section.program:id,code',
                'section.yearLevel:id,name',
                'schedules',
            ])
            ->withCount(['enrollmentDetails as enrolled_count' => fn ($query) => $query->where('status', 'enrolled')])
            ->where('faculty_id', $faculty->id)
            ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
            ->get()
            ->map(fn (ClassOffering $offering): array => [
                'id' => $offering->id,
                'subject' => $offering->subject?->code,
                'subject_name' => $offering->subject?->name,
                'section' => $offering->section?->name,
                'program' => $offering->section?->program?->code,
                'year_level' => $offering->section?->yearLevel?->name,
                'schedule' => $offering->scheduleLabel(),
                'room' => $offering->room,
                'students' => $offering->enrolled_count,
                'status' => $offering->status,
            ]);

        return Inertia::render('Faculty/Classes/Index', [
            'year' => $year?->only(['id', 'name']),
            'classes' => $classes,
        ]);
    }

    public function show(Request $request, ClassOffering $classOffering): Response
    {
        $faculty = $request->user()->facultyProfile;
        abort_if(! $faculty, 403, 'Your faculty profile is not configured.');
        abort_unless((int) $classOffering->faculty_id === (int) $faculty->id, 403, 'You can only view your own classes.');

        $classOffering->load([
            'academicYear:id,name',
            'subject:id,code,name,units',
            'section:id,name,program_id,year_level_id',
            'section.program:id,code',
            'section.yearLevel:id,name',
            'schedules',
            'enrollmentDetails' => fn ($query) => $query
                ->where('status', 'enrolled')
                ->with(['enrollment.student:id,student_number,first_name,middle_name,last_name,suffix,program_id,year_level_id', 'enrollment.student.program:id,code', 'enrollment.student.yearLevel:id,name', 'grade']),
        ]);

        return Inertia::render('Faculty/Classes/Show', [
            'classOffering' => [
                'id' => $classOffering->id,
                'subject' => $classOffering->subject?->code,
                'subject_name' => $classOffering->subject?->name,
                'units' => (float) ($classOffering->subject?->units ?? 0),
                'section' => $classOffering->section?->name,
                'program' => $classOffering->section?->program?->code,
                'year_level' => $classOffering->section?->yearLevel?->name,
                'schedule' => $classOffering->scheduleLabel(),
                'room' => $classOffering->room,
                'academic_year' => $classOffering->academicYear?->name,
            ],
            'roster' => $classOffering->enrollmentDetails->map(fn ($detail): array => [
                'id' => $detail->id,
                'student_number' => $detail->enrollment?->student?->student_number,
                'name' => $detail->enrollment?->student?->full_name,
                'program' => $detail->enrollment?->student?->program?->code,
                'year_level' => $detail->enrollment?->student?->yearLevel?->name,
                'status' => $detail->status,
                'midterm' => $detail->grade?->midterm !== null ? (float) $detail->grade->midterm : null,
                'final' => $detail->grade?->final !== null ? (float) $detail->grade->final : null,
                'final_rating' => $detail->grade?->final_rating !== null ? (float) $detail->grade->final_rating : null,
                'remarks' => $detail->grade?->remarks,
            ])->values(),
        ]);
    }
}
