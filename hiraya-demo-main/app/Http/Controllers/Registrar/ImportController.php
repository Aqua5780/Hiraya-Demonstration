<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Registrar/Import/Index', [
            'headers' => app(StudentImportService::class)->headers(),
        ]);
    }

    public function template(StudentImportService $importService): StreamedResponse
    {
        $headers = $importService->headers();
        $sample = [
            '2026-1001',
            'Juan',
            'Santos',
            'Dela Cruz',
            '',
            'juan.delacruz@example.com',
            '09171234567',
            '2007-08-15',
            'Male',
            'BSTM',
            '1st Year',
            'Parent Name',
            '09181234567',
            'active',
        ];

        return response()->streamDownload(function () use ($headers, $sample): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, $sample);
            fclose($handle);
        }, 'hiraya-student-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function store(Request $request, StudentImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.config('hiraya.uploads.student_import_max_kb')],
        ]);

        $result = $importService->import($request->file('file'));

        if ($result['errors'] !== []) {
            return back()
                ->with('error', 'The file contains errors. No records were imported.')
                ->with('import_errors', $result['errors']);
        }

        return back()->with('success', "Imported {$result['imported']} student record(s).");
    }
}
