<?php

namespace App\Features\Reports\Http\Controllers;

use App\Features\Payroll\Models\PayPeriod;
use App\Features\Reports\Services\ReportService;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassOffering;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reportService): Response
    {
        $type = $request->string('type')->toString() ?: 'enrollment';
        $year = AcademicYear::query()->find($request->integer('academic_year_id'))
            ?? AcademicYear::query()->where('is_active', true)->first();
        $data = $this->reportData($type, $year, $request, $reportService);

        return Inertia::render('Deferred/Reports/Index', [
            'type' => $type,
            'filters' => $request->only(['type', 'academic_year_id', 'class_offering_id', 'student_id', 'pay_period_id', 'from', 'to']),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'classOfferings' => ClassOffering::query()->with(['subject:id,code', 'section:id,name'])->latest()->take(50)->get(),
            'students' => Student::query()->orderBy('last_name')->get(['id', 'student_number', 'first_name', 'last_name']),
            'payPeriods' => PayPeriod::query()->latest('start_date')->get(['id', 'name']),
            'data' => $data,
        ]);
    }

    public function export(Request $request, ReportService $reportService): StreamedResponse
    {
        $type = $request->string('type')->toString() ?: 'enrollment';
        $year = AcademicYear::query()->find($request->integer('academic_year_id'))
            ?? AcademicYear::query()->where('is_active', true)->first();
        $rows = $this->reportData($type, $year, $request, $reportService);
        $first = $rows->first();

        if (! $first) {
            $first = ['No records' => 'No records'];
        }

        return response()->streamDownload(function () use ($rows, $first): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($first));

            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, "hiraya-{$type}-report.csv", ['Content-Type' => 'text/csv']);
    }

    private function reportData(string $type, ?AcademicYear $year, Request $request, ReportService $reportService): Collection
    {
        if (! $year) {
            return collect();
        }

        return match ($type) {
            'collections' => $reportService->collectionReport($year, $request->string('from')->toString() ?: null, $request->string('to')->toString() ?: null),
            'class_list' => ($offering = ClassOffering::query()->find($request->integer('class_offering_id')))
                ? $reportService->classList($offering)
                : collect(),
            'ledger' => ($student = Student::query()->find($request->integer('student_id')))
                ? $reportService->studentLedger($student, $year)
                : collect(),
            'payroll' => ($period = PayPeriod::query()->find($request->integer('pay_period_id')))
                ? $reportService->payrollRegister($period)
                : collect(),
            default => $reportService->enrollmentSummary($year),
        };
    }
}
