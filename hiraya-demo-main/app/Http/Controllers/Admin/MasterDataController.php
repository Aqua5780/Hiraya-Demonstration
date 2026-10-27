<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FacultyProfile;
use App\Models\Program;
use App\Models\Section;
use App\Models\Subject;
use App\Models\YearLevel;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/MasterData/Index', [
            'programs' => Program::query()->orderBy('level')->orderBy('code')->get(),
            'yearLevels' => YearLevel::query()->orderBy('sort_order')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name', 'is_active']),
            'faculty' => FacultyProfile::query()
                ->with('user:id,name')
                ->orderBy('employee_number')
                ->get()
                ->map(fn (FacultyProfile $profile): array => [
                    'id' => $profile->id,
                    'employee_number' => $profile->employee_number,
                    'name' => $profile->user?->name,
                ]),
            'sections' => Section::query()
                ->with(['program:id,code,name', 'yearLevel:id,name', 'academicYear:id,name', 'adviser.user:id,name'])
                ->orderByDesc('academic_year_id')
                ->orderBy('name')
                ->get(),
            'subjects' => Subject::query()
                ->with(['program:id,code', 'yearLevel:id,name'])
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        $validated = $this->validateProgram($request);
        $program = Program::create($validated);
        AuditLogger::log('program.created', 'programs', $program->id, null, $validated);

        return back()->with('success', 'Program created.');
    }

    public function updateProgram(Request $request, Program $program): RedirectResponse
    {
        $validated = $this->validateProgram($request, $program);
        $oldValues = $program->only(['code', 'name', 'level', 'description', 'status']);
        $program->update($validated);
        AuditLogger::log('program.updated', 'programs', $program->id, $oldValues, $validated);

        return back()->with('success', 'Program updated.');
    }

    public function storeYearLevel(Request $request): RedirectResponse
    {
        $validated = $this->validateYearLevel($request);
        $yearLevel = YearLevel::create($validated);
        AuditLogger::log('year_level.created', 'year_levels', $yearLevel->id, null, $validated);

        return back()->with('success', 'Year level created.');
    }

    public function updateYearLevel(Request $request, YearLevel $yearLevel): RedirectResponse
    {
        $validated = $this->validateYearLevel($request, $yearLevel);
        $oldValues = $yearLevel->only(['name', 'level', 'sort_order']);
        $yearLevel->update($validated);
        AuditLogger::log('year_level.updated', 'year_levels', $yearLevel->id, $oldValues, $validated);

        return back()->with('success', 'Year level updated.');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $validated = $this->validateSection($request);
        $section = Section::create([...$validated, 'status' => 'active']);
        AuditLogger::log('section.created', 'sections', $section->id, null, $validated);

        return back()->with('success', 'Section created.');
    }

    public function updateSection(Request $request, Section $section): RedirectResponse
    {
        $validated = $this->validateSection($request, $section);
        $oldValues = $section->only(['academic_year_id', 'program_id', 'year_level_id', 'name', 'capacity', 'adviser_id', 'status']);
        $section->update($validated);
        AuditLogger::log('section.updated', 'sections', $section->id, $oldValues, $validated);

        return back()->with('success', 'Section updated.');
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $validated = $this->validateSubject($request);
        $subject = Subject::create($validated);
        AuditLogger::log('subject.created', 'subjects', $subject->id, null, $validated);

        return back()->with('success', 'Subject created.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $this->validateSubject($request, $subject);
        $oldValues = $subject->only(['code', 'name', 'units', 'program_id', 'year_level_id', 'type', 'status']);
        $subject->update($validated);
        AuditLogger::log('subject.updated', 'subjects', $subject->id, $oldValues, $validated);

        return back()->with('success', 'Subject updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProgram(Request $request, ?Program $program = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('programs', 'code')->ignore($program?->id)],
            'name' => ['required', 'string', 'max:150'],
            'level' => ['required', 'in:elementary,junior_high,senior_high,college'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateYearLevel(Request $request, ?YearLevel $yearLevel = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('year_levels', 'name')->ignore($yearLevel?->id)],
            'level' => ['required', 'in:elementary,junior_high,senior_high,college'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSection(Request $request, ?Section $section = null): array
    {
        return $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'year_level_id' => ['required', 'exists:year_levels,id'],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sections', 'name')
                    ->where('academic_year_id', $request->input('academic_year_id'))
                    ->where('program_id', $request->input('program_id'))
                    ->where('year_level_id', $request->input('year_level_id'))
                    ->ignore($section?->id),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'adviser_id' => ['nullable', 'exists:faculty_profiles,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('subjects', 'code')->ignore($subject?->id)],
            'name' => ['required', 'string', 'max:150'],
            'units' => ['required', 'numeric', 'min:0.5', 'max:12'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'year_level_id' => ['nullable', 'exists:year_levels,id'],
            'type' => ['required', 'in:core,major,elective'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
