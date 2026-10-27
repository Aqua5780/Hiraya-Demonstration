<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Section;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $faculty = $request->user()->facultyProfile;
        abort_if(! $faculty, 403, 'Your faculty profile is not configured.');

        $year = AcademicYear::query()->where('is_active', true)->first();

        $announcements = Announcement::query()
            ->with(['sections:id,name,program_id,year_level_id', 'sections.program:id,code', 'sections.yearLevel:id,name'])
            ->where('author_id', $request->user()->id)
            ->where('audience_type', 'section')
            ->latest('created_at')
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'status' => $announcement->status,
                'posted_at' => $announcement->posted_at?->toIso8601String(),
                'created_at' => $announcement->created_at?->toIso8601String(),
                'attachment_name' => $announcement->attachment_name,
                'sections' => $announcement->sections->map(fn (Section $section): array => [
                    'id' => $section->id,
                    'name' => $section->name,
                    'program' => $section->program?->code,
                    'year_level' => $section->yearLevel?->name,
                ])->values(),
            ]);

        $sections = Section::query()
            ->with(['program:id,code', 'yearLevel:id,name'])
            ->whereHas('classOfferings', fn ($query) => $query
                ->where('faculty_id', $faculty->id)
                ->when($year, fn ($query) => $query->where('academic_year_id', $year->id)))
            ->orderBy('name')
            ->get()
            ->map(fn (Section $section): array => [
                'id' => $section->id,
                'name' => $section->name,
                'program' => $section->program?->code,
                'year_level' => $section->yearLevel?->name,
            ]);

        return Inertia::render('Faculty/Announcements/Index', [
            'year' => $year?->only(['id', 'name']),
            'announcements' => $announcements,
            'sections' => $sections,
        ]);
    }

    public function store(Request $request, AttachmentService $attachmentService): RedirectResponse
    {
        $faculty = $request->user()->facultyProfile;
        abort_if(! $faculty, 403, 'Your faculty profile is not configured.');

        $validated = $this->validateAnnouncement($request, $faculty->id);
        $attachment = $this->storeAttachment($request, $attachmentService);
        $announcement = Announcement::create([
            'author_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'audience_type' => 'section',
            'status' => $validated['status'],
            'posted_at' => $validated['status'] === 'published' ? now() : null,
            ...$attachment,
        ]);
        $announcement->sections()->sync($validated['target_sections']);

        AuditLogger::log('announcement.created', 'announcements', $announcement->id, null, [
            'title' => $announcement->title,
            'sections' => $validated['target_sections'],
        ]);

        return back()->with('success', $validated['status'] === 'published' ? 'Announcement published.' : 'Announcement saved as draft.');
    }

    public function update(Request $request, Announcement $announcement, AttachmentService $attachmentService): RedirectResponse
    {
        abort_if($announcement->audience_type !== 'section', 404);
        abort_unless($announcement->author_id === $request->user()->id, 403);

        $faculty = $request->user()->facultyProfile;
        abort_if(! $faculty, 403, 'Your faculty profile is not configured.');

        $validated = $this->validateAnnouncement($request, $faculty->id);
        $oldValues = $announcement->only(['title', 'content', 'status']);
        $attachment = $this->storeAttachment($request, $attachmentService);

        if ($attachment !== [] && $announcement->attachment_path) {
            $attachmentService->delete($announcement->attachment_path);
        }

        $announcement->fill([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => $validated['status'],
            'posted_at' => $validated['status'] === 'published'
                ? ($announcement->posted_at ?? now())
                : null,
            ...$attachment,
        ])->save();
        $announcement->sections()->sync($validated['target_sections']);

        AuditLogger::log('announcement.updated', 'announcements', $announcement->id, $oldValues, [
            'title' => $announcement->title,
            'sections' => $validated['target_sections'],
        ]);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy(Request $request, Announcement $announcement, AttachmentService $attachmentService): RedirectResponse
    {
        abort_if($announcement->audience_type !== 'section', 404);
        abort_unless($announcement->author_id === $request->user()->id, 403);

        $attachmentService->delete($announcement->attachment_path);
        $announcement->delete();

        AuditLogger::log('announcement.deleted', 'announcements', $announcement->id, ['title' => $announcement->title], null);

        return back()->with('success', 'Announcement deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAnnouncement(Request $request, int $facultyId): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:10000'],
            'status' => ['required', 'in:draft,published'],
            'target_sections' => ['required', 'array', 'min:1'],
            'target_sections.*' => ['integer', 'exists:sections,id'],
            'attachment' => [
                'nullable',
                'file',
                'max:'.config('hiraya.uploads.announcement_max_kb'),
                'mimes:'.implode(',', config('hiraya.uploads.announcement_mimes')),
            ],
        ]);

        $allowed = Section::query()
            ->whereIn('id', $validated['target_sections'])
            ->whereHas('classOfferings', fn ($query) => $query->where('faculty_id', $facultyId))
            ->pluck('id')
            ->all();

        if (count($allowed) !== count(array_unique($validated['target_sections']))) {
            throw ValidationException::withMessages([
                'target_sections' => 'You can only post to sections assigned to your classes.',
            ]);
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function storeAttachment(Request $request, AttachmentService $attachmentService): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        $attachment = $attachmentService->store($request->file('attachment'), 'announcements');

        return [
            'attachment_path' => $attachment['path'],
            'attachment_name' => $attachment['name'],
            'attachment_mime' => $attachment['mime'],
            'attachment_size' => $attachment['size'],
        ];
    }
}
