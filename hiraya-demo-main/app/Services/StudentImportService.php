<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Student;
use App\Models\YearLevel;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentImportService
{
    /**
     * @return array<int, string>
     */
    public function headers(): array
    {
        return [
            'student_number',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'email',
            'phone',
            'birth_date',
            'gender',
            'program_code',
            'year_level',
            'guardian_name',
            'guardian_phone',
            'status',
        ];
    }

    /**
     * @return array{imported: int, errors: array<int, array{row: int, column: string, message: string}>}
     */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return [
                'imported' => 0,
                'errors' => [['row' => 0, 'column' => 'file', 'message' => 'The file could not be read.']],
            ];
        }

        $headerRow = fgetcsv($handle, 0, ',', '"', '\\');
        $headerRow = $this->normaliseHeader($headerRow ?: []);
        $expected = $this->headers();
        $missing = array_values(array_diff($expected, $headerRow));

        if ($missing !== []) {
            fclose($handle);

            return [
                'imported' => 0,
                'errors' => [[
                    'row' => 1,
                    'column' => 'header',
                    'message' => 'Missing column(s): '.implode(', ', $missing).'.',
                ]],
            ];
        }

        $programs = Program::query()->get()->keyBy(fn (Program $program): string => Str::lower($program->code));
        $yearLevels = YearLevel::query()->get()->keyBy(fn (YearLevel $yearLevel): string => Str::lower($yearLevel->name));
        $errors = [];
        $rows = [];
        $seenNumbers = [];
        $seenEmails = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $line++;

            if ($row === [null] || $row === []) {
                continue;
            }

            $record = array_combine($headerRow, array_pad(array_slice($row, 0, count($headerRow)), count($headerRow), null));

            if (! is_array($record)) {
                $errors[] = ['row' => $line, 'column' => 'row', 'message' => 'The row has an invalid number of columns.'];

                continue;
            }

            $record = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $record);
            $studentNumber = (string) ($record['student_number'] ?? '');
            $firstName = (string) ($record['first_name'] ?? '');
            $lastName = (string) ($record['last_name'] ?? '');
            $programCode = Str::lower((string) ($record['program_code'] ?? ''));
            $yearLevelName = Str::lower((string) ($record['year_level'] ?? ''));
            $email = Str::lower((string) ($record['email'] ?? ''));

            if ($studentNumber === '') {
                $errors[] = ['row' => $line, 'column' => 'student_number', 'message' => 'Student number is required.'];
            } elseif (isset($seenNumbers[$studentNumber])) {
                $errors[] = ['row' => $line, 'column' => 'student_number', 'message' => 'Student number is duplicated in this file.'];
            } elseif (Student::query()->where('student_number', $studentNumber)->exists()) {
                $errors[] = ['row' => $line, 'column' => 'student_number', 'message' => 'Student number already exists.'];
            }

            $seenNumbers[$studentNumber] = true;

            if ($firstName === '') {
                $errors[] = ['row' => $line, 'column' => 'first_name', 'message' => 'First name is required.'];
            }

            if ($lastName === '') {
                $errors[] = ['row' => $line, 'column' => 'last_name', 'message' => 'Last name is required.'];
            }

            $program = $programs->get($programCode);

            if (! $program) {
                $errors[] = ['row' => $line, 'column' => 'program_code', 'message' => 'Program code was not found.'];
            }

            $yearLevel = $yearLevels->get($yearLevelName);

            if (! $yearLevel) {
                $errors[] = ['row' => $line, 'column' => 'year_level', 'message' => 'Year level was not found.'];
            }

            if ($email !== '') {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = ['row' => $line, 'column' => 'email', 'message' => 'Email address is invalid.'];
                } elseif (isset($seenEmails[$email]) || Student::query()->where('email', $email)->exists()) {
                    $errors[] = ['row' => $line, 'column' => 'email', 'message' => 'Email address is duplicated.'];
                }
            }

            $seenEmails[$email] = true;

            $birthDate = null;

            if (filled($record['birth_date'] ?? null)) {
                try {
                    $birthDate = Carbon::parse($record['birth_date'])->toDateString();
                } catch (\Throwable) {
                    $errors[] = ['row' => $line, 'column' => 'birth_date', 'message' => 'Birth date is not a valid date.'];
                }
            }

            $status = Str::lower((string) ($record['status'] ?? 'active'));

            if (! in_array($status, ['active', 'inactive', 'graduated'], true)) {
                $errors[] = ['row' => $line, 'column' => 'status', 'message' => 'Status must be active, inactive, or graduated.'];
            }

            $rows[] = [
                'student_number' => $studentNumber,
                'first_name' => $firstName,
                'middle_name' => $this->nullable($record['middle_name'] ?? null),
                'last_name' => $lastName,
                'suffix' => $this->nullable($record['suffix'] ?? null),
                'email' => $email !== '' ? $email : null,
                'phone' => $this->nullable($record['phone'] ?? null),
                'birth_date' => $birthDate,
                'gender' => $this->nullable($record['gender'] ?? null),
                'program_id' => $program?->id,
                'year_level_id' => $yearLevel?->id,
                'status' => $status,
                'guardian_name' => $this->nullable($record['guardian_name'] ?? null),
                'guardian_phone' => $this->nullable($record['guardian_phone'] ?? null),
            ];
        }

        fclose($handle);

        if ($errors !== []) {
            return ['imported' => 0, 'errors' => $errors];
        }

        if ($rows === []) {
            return [
                'imported' => 0,
                'errors' => [['row' => 2, 'column' => 'row', 'message' => 'The file has no student rows.']],
            ];
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                Student::create($row);
            }
        });

        AuditLogger::log('student.imported', 'students', null, null, ['count' => count($rows)]);

        return ['imported' => count($rows), 'errors' => []];
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array<int, string>
     */
    private function normaliseHeader(array $row): array
    {
        return array_map(function ($value): string {
            $value = is_string($value) ? $value : '';

            return Str::of($value)->replace("\xEF\xBB\xBF", '')->trim()->lower()->toString();
        }, $row);
    }

    private function nullable(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return filled($value) ? (string) $value : null;
    }
}
