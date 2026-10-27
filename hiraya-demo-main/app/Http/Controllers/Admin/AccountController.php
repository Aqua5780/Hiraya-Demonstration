<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivationToken;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\AccountInvitationService;
use App\Services\AuditLogger;
use App\Services\StudentAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request, StudentAccountService $studentAccountService): Response
    {
        $filters = $request->only(['q', 'role', 'status']);

        $users = User::query()
            ->with(['role:id,name,slug', 'facultyProfile:id,user_id,employee_number', 'student:id,user_id,student_number'])
            ->when($filters['q'] ?? null, function ($query, $value): void {
                $query->where(function ($query) use ($value): void {
                    $query->where('name', 'like', "%{$value}%")->orWhere('email', 'like', "%{$value}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, $value) => $query->whereHas('role', fn ($query) => $query->where('slug', $value)))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->orderBy('role_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->role?->name,
                'role_slug' => $user->role?->slug,
                'employee_number' => $user->facultyProfile?->employee_number,
                'student_number' => $user->student?->student_number,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Accounts/Index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::query()
                ->whereIn('slug', [Role::ADMIN, Role::REGISTRAR, Role::FACULTY, Role::FINANCE])
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'allRoles' => Role::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'studentsWithoutAccounts' => Student::query()
                ->with(['program:id,code', 'yearLevel:id,name'])
                ->whereNull('user_id')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->map(fn (Student $student): array => [
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'name' => $student->full_name,
                    'program' => $student->program?->code,
                    'year_level' => $student->yearLevel?->name,
                    'preview_email' => $studentAccountService->previewEmail($student),
                ]),
            'studentsWithoutAccountsTotal' => Student::query()->whereNull('user_id')->count(),
        ]);
    }

    public function invite(Request $request, AccountInvitationService $invitationService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in([Role::ADMIN, Role::REGISTRAR, Role::FACULTY, Role::FINANCE])],
            'department' => ['nullable', 'string', 'max:120'],
        ]);

        $invitation = $invitationService->invite($validated, $request->user());

        if (config('hiraya.demo_mode')) {
            $request->session()->flash('demo_activation_url', $invitation['activation_url']);
        }

        return back()->with('success', "Invitation created for {$invitation['user']->email}.");
    }

    public function resend(Request $request, User $user, AccountInvitationService $invitationService): RedirectResponse
    {
        $invitation = $invitationService->resend($user, $request->user());

        if (config('hiraya.demo_mode')) {
            $request->session()->flash('demo_activation_url', $invitation['activation_url']);
        }

        return back()->with('success', "A new activation link was generated for {$user->email}.");
    }

    public function storeStudentAccount(Request $request, StudentAccountService $studentAccountService): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        $result = $studentAccountService->create($student, $request->user());

        if (config('hiraya.demo_mode')) {
            $request->session()->flash('demo_activation_url', $result['activation_url']);
        }

        return back()->with('success', "Student account created for {$result['user']->email}.");
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStudent(), 403, 'Only student passwords can be changed from this screen.');

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $oldValues = $user->only(['status', 'failed_attempts']);

        $user->forceFill([
            'password' => $validated['password'],
            'status' => 'active',
            'failed_attempts' => 0,
            'locked_until' => null,
            'remember_token' => null,
        ])->save();

        ActivationToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        DB::table('sessions')->where('user_id', $user->id)->delete();

        AuditLogger::log('account.student_password_changed', 'users', $user->id, $oldValues, [
            'status' => 'active',
        ], $request->user());

        return back()->with('success', "Password updated for {$user->name}.");
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        abort_if($user->id === $request->user()->id, 422, 'You cannot change the status of your own account.');

        if ($user->status === 'pending') {
            return back()->with('error', 'Pending accounts must be activated through their invitation link.');
        }

        $oldStatus = $user->status;

        $user->forceFill([
            'status' => $validated['status'],
            'failed_attempts' => 0,
            'locked_until' => null,
        ])->save();

        if ($validated['status'] === 'inactive') {
            $user->forceFill(['remember_token' => null])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        AuditLogger::log('account.status_updated', 'users', $user->id, ['status' => $oldStatus], ['status' => $user->status], $request->user());

        return back()->with('success', 'Account status updated.');
    }
}
