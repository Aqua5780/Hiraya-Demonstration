<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    private const ATTACHMENT_DIRECTORY = 'announcements';

    public function downloadAnnouncement(Request $request, Announcement $announcement): StreamedResponse
    {
        abort_unless($announcement->attachment_path, 404);
        abort_unless($this->canView($request, $announcement), 403);
        abort_unless($this->isSafeStoredPath($announcement->attachment_path), 404);

        return Storage::disk('local')->download(
            $announcement->attachment_path,
            basename($announcement->attachment_name ?? 'attachment'),
            [
                'Content-Type' => $announcement->attachment_mime ?? 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "sandbox; default-src 'none'",
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    private function canView(Request $request, Announcement $announcement): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->isRegistrar() || $announcement->author_id === $user->id) {
            return true;
        }

        if ($announcement->status !== 'published') {
            return false;
        }

        if ($announcement->audience_type === 'global') {
            return true;
        }

        $sectionIds = $announcement->sections()->pluck('sections.id');

        if ($sectionIds->isEmpty()) {
            return false;
        }

        if ($user->isStudent()) {
            return $user->student?->enrollments()
                ->whereIn('section_id', $sectionIds)
                ->where('status', 'enrolled')
                ->exists() ?? false;
        }

        if ($user->isFaculty()) {
            return $user->facultyProfile?->classOfferings()
                ->whereIn('section_id', $sectionIds)
                ->exists() ?? false;
        }

        return false;
    }

    private function isSafeStoredPath(?string $path): bool
    {
        return is_string($path)
            && str_starts_with($path, self::ATTACHMENT_DIRECTORY.'/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\');
    }
}
