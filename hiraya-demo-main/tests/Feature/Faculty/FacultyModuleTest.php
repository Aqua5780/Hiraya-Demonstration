<?php

namespace Tests\Feature\Faculty;

use App\Models\Announcement;
use App\Models\ClassOffering;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FacultyModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $facultyUser;

    private int $facultyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->facultyUser = User::query()->where('email', 'faculty@hiraya.test')->firstOrFail();
        $this->facultyId = $this->facultyUser->facultyProfile->id;
    }

    public function test_faculty_pages_render(): void
    {
        $this->actingAs($this->facultyUser)->get('/faculty/schedule')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Faculty/Schedule/Index'));
        $this->actingAs($this->facultyUser)->get('/faculty/classes')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Faculty/Classes/Index'));
        $this->actingAs($this->facultyUser)->get('/faculty/grades')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Faculty/Grades/Index'));
        $this->actingAs($this->facultyUser)->get('/faculty/announcements')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Faculty/Announcements/Index'));
    }

    public function test_faculty_can_view_their_class_roster(): void
    {
        $offering = $this->ownOffering();

        $this->actingAs($this->facultyUser)
            ->get("/faculty/classes/{$offering->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Faculty/Classes/Show')
                ->where('classOffering.id', $offering->id)
            );
    }

    public function test_faculty_can_encode_grades_for_their_own_class(): void
    {
        $offering = $this->ownOffering();
        $detail = $offering->enrollmentDetails()->where('status', 'enrolled')->firstOrFail();

        $this->actingAs($this->facultyUser)->post("/faculty/grades/{$offering->id}", [
            'grades' => [
                [
                    'enrollment_detail_id' => $detail->id,
                    'midterm' => 90,
                    'final' => 92,
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $grade = Grade::query()->where('enrollment_detail_id', $detail->id)->firstOrFail();

        $this->assertEquals(91.0, (float) $grade->final_rating);
        $this->assertSame('Passed', $grade->remarks);
    }

    public function test_faculty_cannot_access_another_faculty_grade_sheet(): void
    {
        $other = ClassOffering::query()->where('faculty_id', '!=', $this->facultyId)->firstOrFail();

        $this->actingAs($this->facultyUser)
            ->get("/faculty/grades/{$other->id}")
            ->assertForbidden();
    }

    public function test_faculty_can_post_an_announcement_to_their_own_section(): void
    {
        $section = $this->ownOffering()->section;

        $this->actingAs($this->facultyUser)->post('/faculty/announcements', [
            'title' => 'Class reminder',
            'content' => 'Bring your project next meeting.',
            'status' => 'published',
            'target_sections' => [$section->id],
        ])->assertRedirect()->assertSessionHas('success');

        $announcement = Announcement::query()->where('title', 'Class reminder')->firstOrFail();

        $this->assertSame('section', $announcement->audience_type);
        $this->assertDatabaseHas('announcement_sections', [
            'announcement_id' => $announcement->id,
            'section_id' => $section->id,
        ]);
    }

    public function test_faculty_cannot_post_to_an_unassigned_section(): void
    {
        $ownSectionIds = ClassOffering::query()
            ->where('faculty_id', $this->facultyId)
            ->pluck('section_id');

        $otherSection = Section::query()->whereNotIn('id', $ownSectionIds)->firstOrFail();

        $this->actingAs($this->facultyUser)->post('/faculty/announcements', [
            'title' => 'Wrong section',
            'content' => 'This should not be allowed.',
            'status' => 'published',
            'target_sections' => [$otherSection->id],
        ])->assertSessionHasErrors('target_sections');
    }

    private function ownOffering(): ClassOffering
    {
        return ClassOffering::query()
            ->where('faculty_id', $this->facultyId)
            ->whereHas('enrollmentDetails')
            ->with(['section', 'enrollmentDetails'])
            ->firstOrFail();
    }
}
