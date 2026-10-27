<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\AttachmentService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $announcements = Announcement::query()
            ->with('author:id,name')
            ->where('audience_type', 'global')
            ->latest('created_at')
            ->paginate(10)
            ->through(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'status' => $announcement->status,
                'author' => $announcement->author?->name,
                'posted_at' => $announcement->posted_at?->toIso8601String(),
                'created_at' => $announcement->created_at?->toIso8601String(),
                'attachment_name' => $announcement->attachment_name,
                'attachment_size' => $announcement->attachment_size,
            ]);

        return Inertia::render('Admin/Announcements/Index', [
            'announcements' => $announcements,
        ]);
    }

    public function store(Request $request, AttachmentService $attachmentService): RedirectResponse
    {
        $validated = $this->validateAnnouncement($request);
        $attachment = $this->storeAttachment($request, $attachmentService);
        $status = $validated['status'];

        $announcement = Announcement::create([
            'author_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'audience_type' => 'global',
            'status' => $status,
            'posted_at' => $status === 'published' ? now() : null,
            ...$attachment,
        ]);

        AuditLogger::log('announcement.created', 'announcements', $announcement->id, null, [
            'title' => $announcement->title,
            'status' => $announcement->status,
        ]);

        return back()->with('success', $status === 'published' ? 'Announcement published.' : 'Announcement saved as draft.');
    }

    public function update(Request $request, Announcement $announcement, AttachmentService $attachmentService): RedirectResponse
    {
        abort_if($announcement->audience_type !== 'global', 404);
        abort_unless($request->user()->isAdmin() || $announcement->author_id === $request->user()->id, 403);

        $validated = $this->validateAnnouncement($request);
        $oldValues = $announcement->only(['title', 'content', 'status']);
        $attachment = $this->storeAttachment($request, $attachmentService);

        if ($attachment !== [] && $announcement->attachment_path) {
            $attachmentService->delete($announcement->attachment_path);
        }

        $status = $validated['status'];
        $announcement->fill([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => $status,
            'posted_at' => $status === 'published'
                ? ($announcement->posted_at ?? now())
                : null,
            ...$attachment,
        ])->save();

        AuditLogger::log('announcement.updated', 'announcements', $announcement->id, $oldValues, [
            'title' => $announcement->title,
            'status' => $announcement->status,
        ]);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement, AttachmentService $attachmentService): RedirectResponse
    {
        abort_if($announcement->audience_type !== 'global', 404);

        $attachmentService->delete($announcement->attachment_path);
        $announcement->delete();

        AuditLogger::log('announcement.deleted', 'announcements', $announcement->id, ['title' => $announcement->title], null);

        return back()->with('success', 'Announcement deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:10000'],
            'status' => ['required', 'in:draft,published'],
            'attachment' => [
                'nullable',
                'file',
                'max:'.config('hiraya.uploads.announcement_max_kb'),
                'mimes:'.implode(',', config('hiraya.uploads.announcement_mimes')),
            ],
        ]);
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
