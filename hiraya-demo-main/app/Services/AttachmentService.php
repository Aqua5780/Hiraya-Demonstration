<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    /**
     * @return array{path: string, name: string, mime: string, size: int}
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $path = $file->store($directory, 'local');

        return [
            'path' => $path,
            'name' => basename($file->getClientOriginalName()),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
        ];
    }

    public function delete(?string $path): void
    {
        if ($path && ! str_contains($path, '..') && ! str_starts_with($path, '/') && ! str_contains($path, '\\')) {
            Storage::disk('local')->delete($path);
        }
    }
}
