<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\FacultyProfile;
use App\Models\Section;
use App\Models\Subject;
use App\Services\ClassOfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassOfferingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['academic_year_id', 'section_id', 'faculty_id', 'status', 'q']);

        $offerings = ClassOffering::query()
            ->with([
                'academicYear:id,name',
                'section:id,name,program_id,year_level_id,academic_year_id',
                'section.program:id,code',
                'section.yearLevel:id,name',
                'subject:id,code,name,units',
                'faculty:id,user_id,employee_number',
                'faculty.user:id,name',
                'schedules',
            ])
            ->withCount(['enrollmentDetails as enrolled_count' => fn ($query) => $query->where('status', 'enrolled')])
            ->when($filters['academic_year_id'] ?? null, fn ($query, $value) => $query->where('academic_year_id', $value))
            ->when($filters['section_id'] ?? null, fn ($query, $value) => $query->where('section_id', $value))
            ->when($filters['faculty_id'] ?? null, fn ($query, $value) => $query->where('faculty_id', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['q'] ?? null, function ($query, $value): void {
                $query->where(function ($query) use ($value): void {
                    $query->whereHas('subject', function ($query) use ($value): void {
                        $query->where('code', 'like', "%{$value}%")->orWhere('name', 'like', "%{$value}%");
                    })->orWhereHas('section', function ($query) use ($value): void {
                        $query->where('name', 'like', "%{$value}%");
                    });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ClassOffering $offering): array => [
                'id' => $offering->id,
                'academic_year' => $offering->academicYear?->name,
                'academic_year_id' => $offering->academic_year_id,
                'section_id' => $offering->section_id,
                'section' => $offering->section?->name,
                'program' => $offering->section?->program?->code,
                'year_level' => $offering->section?->yearLevel?->name,
                'subject_id' => $offering->subject_id,
                'subject' => $offering->subject?->code,
                'subject_name' => $offering->subject?->name,
                'units' => (float) ($offering->subject?->units ?? 0),
                'faculty_id' => $offering->faculty_id,
                'faculty' => $offering->faculty?->user?->name,
                'room' => $offering->room,
                'capacity' => $offering->capacity,
                'enrolled_count' => $offering->enrolled_count,
                'status' => $offering->status,
                'schedules' => $offering->schedules->map(fn ($schedule): array => [
                    'id' => $schedule->id,
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'room' => $schedule->room,
                    'label' => $schedule->label(),
                ])->values(),
                'schedule_label' => $offering->scheduleLabel(),
            ]);

        return Inertia::render('Admin/ClassOfferings/Index', [
            'offerings' => $offerings,
            'filters' => $filters,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name', 'is_active']),
            'sections' => Section::query()
                ->with(['program:id,code', 'yearLevel:id,name'])
                ->join('programs', 'programs.id', '=', 'sections.program_id')
                ->join('year_levels', 'year_levels.id', '=', 'sections.year_level_id')
                ->orderBy('programs.code')
                ->orderBy('year_levels.sort_order')
                ->orderBy('sections.name')
                ->select('sections.*')
                ->get()
                ->map(fn (Section $section): array => [
                    'id' => $section->id,
                    'academic_year_id' => $section->academic_year_id,
                    'program' => $section->program?->code,
                    'year_level' => $section->yearLevel?->name,
                    'name' => $section->name,
                ]),
            'subjects' => Subject::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name', 'units']),
            'faculty' => FacultyProfile::query()
                ->with('user:id,name')
                ->orderBy('employee_number')
                ->get()
                ->map(fn (FacultyProfile $profile): array => [
                    'id' => $profile->id,
                    'employee_number' => $profile->employee_number,
                    'name' => $profile->user?->name,
                ]),
        ]);
    }

    public function store(Request $request, ClassOfferingService $classOfferingService): RedirectResponse
    {
        $validated = $this->validateOffering($request);
        $classOfferingService->save($validated, null, $request->user());

        return back()->with('success', 'Class offering created.');
    }

    public function update(Request $request, ClassOffering $classOffering, ClassOfferingService $classOfferingService): RedirectResponse
    {
        $validated = $this->validateOffering($request);
        $classOfferingService->save($validated, $classOffering, $request->user());

        return back()->with('success', 'Class offering updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateOffering(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'faculty_id' => ['nullable', 'exists:faculty_profiles,id'],
            'room' => ['nullable', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'status' => ['required', 'in:open,closed'],
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week' => ['required', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.room' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
