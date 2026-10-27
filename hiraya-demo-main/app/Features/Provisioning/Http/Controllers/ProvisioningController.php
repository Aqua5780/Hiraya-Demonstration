<?php

namespace App\Features\Provisioning\Http\Controllers;

use App\Features\Provisioning\Models\AccountProvisioning;
use App\Features\Provisioning\Services\StudentAccountProvisioningService;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProvisioningController extends Controller
{
    public function index(StudentAccountProvisioningService $provisioningService): Response
    {
        $students = Student::query()
            ->with(['program:id,code', 'yearLevel:id,name'])
            ->whereNull('user_id')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'student_number' => $student->student_number,
                'name' => $student->full_name,
                'program' => $student->program?->code,
                'year_level' => $student->yearLevel?->name,
                'preview_email' => $provisioningService->previewEmail($student),
            ]);

        return Inertia::render('Deferred/Provisioning/Index', [
            'students' => $students,
            'logs' => AccountProvisioning::query()
                ->with(['student:id,student_number,first_name,last_name', 'createdBy:id,name'])
                ->latest()
                ->take(50)
                ->get(),
        ]);
    }

    public function store(Request $request, StudentAccountProvisioningService $provisioningService): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        $result = $provisioningService->provision($student, $request->user());

        if (config('hiraya.demo_mode')) {
            $request->session()->flash('demo_activation_url', $result['activation_url']);
        }

        return back()->with('success', "Account provisioned for {$result['provisioning']->email}.");
    }
}
