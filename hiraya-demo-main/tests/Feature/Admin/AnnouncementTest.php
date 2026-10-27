<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_publish_a_global_announcement_with_an_attachment(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin)->post('/admin/announcements', [
            'title' => 'Enrollment is open',
            'content' => 'Enrollment for the first semester is now open.',
            'status' => 'published',
            'attachment' => UploadedFile::fake()->create('notice.pdf', 120, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');

        $announcement = Announcement::query()->firstOrFail();

        $this->assertSame('global', $announcement->audience_type);
        $this->assertSame('published', $announcement->status);
        $this->assertNotNull($announcement->posted_at);
        Storage::disk('local')->assertExists($announcement->attachment_path);
    }

    public function test_admin_can_save_a_draft(): void
    {
        $this->actingAs($this->admin)->post('/admin/announcements', [
            'title' => 'Draft notice',
            'content' => 'Work in progress.',
            'status' => 'draft',
        ])->assertRedirect();

        $announcement = Announcement::query()->firstOrFail();

        $this->assertSame('draft', $announcement->status);
        $this->assertNull($announcement->posted_at);
    }

    public function test_unsafe_attachment_paths_are_not_served(): void
    {
        $announcement = Announcement::create([
            'author_id' => $this->admin->id,
            'title' => 'Unsafe path',
            'content' => 'Attempted traversal.',
            'audience_type' => 'global',
            'status' => 'published',
            'posted_at' => now(),
            'attachment_path' => '../.env',
            'attachment_name' => 'stolen.txt',
            'attachment_mime' => 'text/plain',
        ]);

        $this->actingAs($this->admin)
            ->get("/announcements/{$announcement->id}/attachment")
            ->assertNotFound();
    }

    public function test_admin_can_update_and_delete_an_announcement(): void
    {
        $announcement = Announcement::create([
            'author_id' => $this->admin->id,
            'title' => 'Old title',
            'content' => 'Old content',
            'audience_type' => 'global',
            'status' => 'draft',
        ]);

        $this->actingAs($this->admin)->patch("/admin/announcements/{$announcement->id}", [
            'title' => 'Updated title',
            'content' => 'Updated content',
            'status' => 'published',
        ])->assertRedirect();

        $this->assertSame('Updated title', $announcement->fresh()->title);
        $this->assertNotNull($announcement->fresh()->posted_at);

        $this->actingAs($this->admin)
            ->delete("/admin/announcements/{$announcement->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }
}
