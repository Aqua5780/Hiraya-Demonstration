<?php

namespace App\Services;

use App\Models\ClassOffering;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassOfferingService
{
    /**
     * Create or update a class offering and its meeting schedule.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function save(array $data, ?ClassOffering $offering = null, ?User $actor = null): ClassOffering
    {
        $data = $this->normalise($data);
        $this->assertNoConflicts($data, $offering?->id);

        return DB::transaction(function () use ($data, $offering, $actor): ClassOffering {
            $isNew = $offering === null;
            $offering ??= new ClassOffering;
            $oldValues = $isNew ? null : $offering->load('schedules')->toArray();

            $offering->fill([
                'academic_year_id' => $data['academic_year_id'],
                'section_id' => $data['section_id'],
                'subject_id' => $data['subject_id'],
                'faculty_id' => $data['faculty_id'],
                'room' => $data['room'],
                'capacity' => $data['capacity'],
                'status' => $data['status'],
            ])->save();

            $offering->schedules()->delete();

            foreach ($data['schedules'] as $schedule) {
                $offering->schedules()->create([
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'room' => $schedule['room'] ?? $data['room'],
                ]);
            }

            AuditLogger::log(
                $isNew ? 'class_offering.created' : 'class_offering.updated',
                'class_offerings',
                $offering->id,
                $oldValues,
                $offering->fresh(['schedules'])->toArray(),
                $actor,
            );

            return $offering->fresh(['section.program', 'section.yearLevel', 'subject', 'faculty.user', 'schedules']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function normalise(array $data): array
    {
        $schedules = collect($data['schedules'] ?? [])
            ->filter(fn (array $schedule): bool => filled($schedule['day_of_week'] ?? null)
                && filled($schedule['start_time'] ?? null)
                && filled($schedule['end_time'] ?? null))
            ->map(function (array $schedule): array {
                $start = (string) $schedule['start_time'];
                $end = (string) $schedule['end_time'];

                if ($start >= $end) {
                    throw ValidationException::withMessages([
                        'schedules' => 'Each class schedule must end after its start time.',
                    ]);
                }

                return [
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $start,
                    'end_time' => $end,
                    'room' => filled($schedule['room'] ?? null) ? trim((string) $schedule['room']) : null,
                ];
            })
            ->values()
            ->all();

        if ($schedules === []) {
            throw ValidationException::withMessages([
                'schedules' => 'Add at least one class meeting schedule.',
            ]);
        }

        $section = Section::query()->find($data['section_id']);

        if (! $section || (int) $section->academic_year_id !== (int) $data['academic_year_id']) {
            throw ValidationException::withMessages([
                'section_id' => 'The selected section does not belong to the selected academic year.',
            ]);
        }

        $data['schedules'] = $schedules;
        $data['room'] = filled($data['room'] ?? null) ? trim((string) $data['room']) : null;
        $data['faculty_id'] = filled($data['faculty_id'] ?? null) ? (int) $data['faculty_id'] : null;
        $data['capacity'] = max(1, (int) ($data['capacity'] ?? 40));
        $data['status'] = $data['status'] ?? 'open';
        $data['status'] = in_array($data['status'], ['open', 'closed'], true)
            ? $data['status']
            : 'open';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function assertNoConflicts(array $data, ?int $ignoreOfferingId = null): void
    {
        $duplicate = ClassOffering::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('section_id', $data['section_id'])
            ->where('subject_id', $data['subject_id'])
            ->when($ignoreOfferingId, fn ($query) => $query->whereKeyNot($ignoreOfferingId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'subject_id' => 'This subject is already offered to the selected section for this academic year.',
            ]);
        }

        foreach ($data['schedules'] as $schedule) {
            $overlaps = ClassSchedule::query()
                ->with('classOffering')
                ->whereHas('classOffering', function ($query) use ($data, $ignoreOfferingId): void {
                    $query->where('academic_year_id', $data['academic_year_id'])
                        ->when($ignoreOfferingId, fn ($query) => $query->whereKeyNot($ignoreOfferingId));
                })
                ->where('day_of_week', $schedule['day_of_week'])
                ->where('start_time', '<', $schedule['end_time'])
                ->where('end_time', '>', $schedule['start_time'])
                ->get();

            foreach ($overlaps as $overlap) {
                $other = $overlap->classOffering;
                $room = $schedule['room'] ?? $data['room'];

                if ((int) $other->section_id === (int) $data['section_id']) {
                    throw ValidationException::withMessages([
                        'schedules' => "The selected section already has a class on {$schedule['day_of_week']} during that time.",
                    ]);
                }

                if ($data['faculty_id'] !== null && (int) $other->faculty_id === (int) $data['faculty_id']) {
                    throw ValidationException::withMessages([
                        'faculty_id' => "The selected faculty member already has a class on {$schedule['day_of_week']} during that time.",
                    ]);
                }

                if ($room !== null && $room !== '' && $overlap->room === $room) {
                    throw ValidationException::withMessages([
                        'schedules' => "{$room} is already in use on {$schedule['day_of_week']} during that time.",
                    ]);
                }
            }
        }
    }
}
