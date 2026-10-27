<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\LedgerEntry;
use App\Models\Program;
use App\Models\Section;
use App\Models\Student;
use App\Models\YearLevel;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $activeYear = AcademicYear::query()->where('is_active', true)->first();
        $filters = $request->only(['q', 'program_id', 'year_level_id', 'section_id', 'status', 'sort', 'direction']) + [
            'q' => '',
            'program_id' => '',
            'year_level_id' => '',
            'section_id' => '',
            'status' => '',
            'sort' => 'name',
            'direction' => 'asc',
        ];
        $sort = in_array($filters['sort'] ?? 'name', ['name', 'student_number', 'year_level', 'section'], true)
            ? ($filters['sort'] ?? 'name')
            : 'name';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $query = Student::query()
            ->with([
                'program:id,code,name',
                'yearLevel:id,name',
                'user:id,email,status',
                'enrollments' => fn ($query) => $query
                    ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
                    ->with('section:id,name,program_id,year_level_id'),
            ])
            ->when($filters['q'] ?? null, function ($query, $value): void {
                $query->where(function ($query) use ($value): void {
                    $query->where('student_number', 'like', "%{$value}%")
                        ->orWhere('first_name', 'like', "%{$value}%")
                        ->orWhere('middle_name', 'like', "%{$value}%")
                        ->orWhere('last_name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                });
            })
            ->when($filters['program_id'] ?? null, fn ($query, $value) => $query->where('program_id', $value))
            ->when($filters['year_level_id'] ?? null, fn ($query, $value) => $query->where('year_level_id', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['section_id'] ?? null, function ($query, $value) use ($activeYear): void {
                $query->whereHas('enrollments', function ($query) use ($value, $activeYear): void {
                    $query->where('section_id', $value)
                        ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id));
                });
            });

        if ($sort === 'year_level') {
            $query->orderBy(
                YearLevel::query()->select('sort_order')->whereColumn('year_levels.id', 'students.year_level_id')->limit(1),
                $direction,
            );
        } elseif ($sort === 'section') {
            $query->orderBy(
                Section::query()
                    ->select('sections.name')
                    ->join('enrollments', 'sections.id', '=', 'enrollments.section_id')
                    ->whereColumn('enrollments.student_id', 'students.id')
                    ->when($activeYear, fn ($query) => $query->where('enrollments.academic_year_id', $activeYear->id))
                    ->limit(1),
                $direction,
            );
        } elseif ($sort === 'student_number') {
            $query->orderBy('student_number', $direction);
        } else {
            $query->orderBy('last_name', $direction)->orderBy('first_name', $direction);
        }

        $students = $query
            ->paginate(15)
            ->withQueryString()
            ->through(function (Student $student): array {
                $enrollment = $student->enrollments->first();

                return [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'name' => $student->full_name,
                    'email' => $student->email,
                    'program' => $student->program?->code,
                    'program_name' => $student->program?->name,
                    'year_level' => $student->yearLevel?->name,
                    'section' => $enrollment?->section?->name,
                    'status' => $student->status,
                    'account_status' => $student->user?->status,
                    'balance' => $enrollment ? (float) $enrollment->balance : null,
                    'enrolled' => $enrollment !== null,
                ];
            });

        return Inertia::render('Registrar/Students/Index', [
            'students' => $students,
            'filters' => $filters,
            'currentYear' => $activeYear?->only(['id', 'name']),
            'programs' => Program::query()->orderBy('code')->get(['id', 'code', 'name']),
            'yearLevels' => YearLevel::query()->orderBy('sort_order')->get(['id', 'name']),
            'sections' => Section::query()
                ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(Student $student): Response
    {
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        $enrollment = $activeYear
            ? Enrollment::query()
                ->with([
                    'section.program:id,code',
                    'section.yearLevel:id,name',
                    'details.classOffering.subject:id,code,name,units',
                    'details.classOffering.faculty.user:id,name',
                    'details.grade',
                ])
                ->where('student_id', $student->id)
                ->where('academic_year_id', $activeYear->id)
                ->first()
            : null;

        $student->load(['program:id,code,name', 'yearLevel:id,name', 'user:id,email,status,last_login_at']);

        return Inertia::render('Registrar/Students/Show', [
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'name' => $student->full_name,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'suffix' => $student->suffix,
                'email' => $student->email,
                'phone' => $student->phone,
                'address' => $student->address,
                'birth_date' => $student->birth_date?->toDateString(),
                'gender' => $student->gender,
                'status' => $student->status,
                'program' => $student->program?->code,
                'program_name' => $student->program?->name,
                'year_level' => $student->yearLevel?->name,
                'guardian_name' => $student->guardian_name,
                'guardian_phone' => $student->guardian_phone,
                'account_status' => $student->user?->status,
                'last_login_at' => $student->user?->last_login_at?->toIso8601String(),
            ],
            'currentYear' => $activeYear?->only(['id', 'name']),
            'enrollment' => $enrollment ? [
                'id' => $enrollment->id,
                'status' => $enrollment->status,
                'section' => $enrollment->section?->name,
                'program' => $enrollment->section?->program?->code,
                'year_level' => $enrollment->section?->yearLevel?->name,
                'units' => (float) $enrollment->total_units,
                'assessed_amount' => (float) $enrollment->assessed_amount,
                'balance' => (float) $enrollment->balance,
                'classes' => $enrollment->details->map(fn ($detail): array => [
                    'id' => $detail->id,
                    'subject' => $detail->classOffering?->subject?->code,
                    'subject_name' => $detail->classOffering?->subject?->name,
                    'faculty' => $detail->classOffering?->faculty?->user?->name,
                    'midterm' => $detail->grade?->midterm !== null ? (float) $detail->grade->midterm : null,
                    'final' => $detail->grade?->final !== null ? (float) $detail->grade->final : null,
                    'final_rating' => $detail->grade?->final_rating !== null ? (float) $detail->grade->final_rating : null,
                    'remarks' => $detail->grade?->remarks,
                ])->values(),
            ] : null,
            'payments' => $student->payments()
                ->where('academic_year_id', $activeYear?->id)
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->methodLabel(),
                    'reference_number' => $payment->reference_number,
                    'status' => $payment->status,
                    'receipt_number' => $payment->receipt_number,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ]),
            'ledger' => $activeYear
                ? LedgerEntry::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $activeYear->id)
                    ->latest()
                    ->take(10)
                    ->get()
                    ->map(fn (LedgerEntry $entry): array => [
                        'id' => $entry->id,
                        'type' => $entry->type,
                        'description' => $entry->description,
                        'debit' => (float) $entry->debit,
                        'credit' => (float) $entry->credit,
                        'balance_after' => (float) $entry->balance_after,
                        'created_at' => $entry->created_at?->toIso8601String(),
                    ])
                : [],
        ]);
    }

    public function edit(Request $request, Student $student): Response
    {
        $student->load(['program:id,code,name', 'yearLevel:id,name', 'user:id,email,status']);

        return Inertia::render('Registrar/Students/Edit', [
            'student' => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'suffix' => $student->suffix,
                'email' => $student->email,
                'phone' => $student->phone,
                'address' => $student->address,
                'birth_date' => $student->birth_date?->toDateString(),
                'gender' => $student->gender,
                'status' => $student->status,
                'program_id' => $student->program_id,
                'year_level_id' => $student->year_level_id,
                'guardian_name' => $student->guardian_name,
                'guardian_phone' => $student->guardian_phone,
                'account_status' => $student->user?->status,
            ],
            'programs' => Program::query()->orderBy('code')->get(['id', 'code', 'name']),
            'yearLevels' => YearLevel::query()->orderBy('sort_order')->get(['id', 'name']),
            'canEditLoginEmail' => $request->user()->isAdmin(),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $emailRules = ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student->user_id)];

        if ($student->user) {
            array_unshift($emailRules, 'required');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:30'],
            'program_id' => ['required', 'exists:programs,id'],
            'year_level_id' => ['required', 'exists:year_levels,id'],
            'status' => ['required', 'in:active,inactive,graduated'],
            'guardian_name' => ['nullable', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $isAdmin = $request->user()->isAdmin();

        if (! $isAdmin && $student->user && $validated['email'] !== $student->user->email) {
            throw ValidationException::withMessages([
                'email' => 'Only an administrator can change a student login email.',
            ]);
        }

        $oldValues = $student->only([
            'first_name', 'middle_name', 'last_name', 'suffix', 'email', 'phone', 'address',
            'birth_date', 'gender', 'program_id', 'year_level_id', 'status', 'guardian_name', 'guardian_phone',
        ]);

        DB::transaction(function () use ($student, $validated, $isAdmin): void {
            $student->update($validated);

            if ($isAdmin && $student->user && ($validated['email'] ?? null) !== null && $validated['email'] !== $student->user->email) {
                $student->user->forceFill([
                    'email' => $validated['email'],
                    'email_verified_at' => null,
                ])->save();
            }
        });

        AuditLogger::log('student.updated', 'students', $student->id, $oldValues, $validated);

        return redirect()->route('registrar.students.show', $student)->with('success', 'Student record updated.');
    }
}
