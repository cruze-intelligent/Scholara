<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_publish_a_notice(): void
    {
        Role::findOrCreate('admin');

        $school = School::factory()->create();
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('notices.store'), [
            'title' => 'Sports day',
            'body' => 'Sports day is next Friday.',
            'audience' => 'all',
        ])->assertRedirect(route('notices.index'));

        $this->assertDatabaseHas('notices', ['title' => 'Sports day', 'published_at' => null]);

        $notice = \App\Models\Notice::first();
        $this->actingAs($admin)->patch(route('notices.publish', $notice))->assertRedirect();

        $this->assertNotNull($notice->fresh()->published_at);
    }

    public function test_parent_cannot_create_notices(): void
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $parent = User::factory()->create(['school_id' => $school->id]);
        $parent->assignRole('parent');

        $this->actingAs($parent)->get(route('notices.index'))->assertForbidden();
    }

    public function test_author_can_edit_their_own_unpublished_notice(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->post(route('notices.store'), [
            'title' => 'Draft', 'body' => 'Original body', 'audience' => 'all',
        ]);
        $notice = \App\Models\Notice::first();

        $this->actingAs($teacher)->put(route('notices.update', $notice), [
            'title' => 'Fixed title', 'body' => 'Original body', 'audience' => 'all',
        ])->assertRedirect(route('notices.index'));

        $this->assertSame('Fixed title', $notice->fresh()->title);
    }

    public function test_cannot_edit_a_published_notice(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)->post(route('notices.store'), [
            'title' => 'Live', 'body' => 'Body', 'audience' => 'all', 'publish' => '1',
        ]);
        $notice = \App\Models\Notice::first();

        $this->actingAs($teacher)->get(route('notices.edit', $notice))->assertStatus(422);
    }

    public function test_non_author_cannot_edit_someone_elses_notice(): void
    {
        Role::findOrCreate('teacher');
        $school = School::factory()->create();
        $author = User::factory()->create(['school_id' => $school->id]);
        $author->assignRole('teacher');
        $other = User::factory()->create(['school_id' => $school->id]);
        $other->assignRole('teacher');

        $this->actingAs($author)->post(route('notices.store'), [
            'title' => 'Mine', 'body' => 'Body', 'audience' => 'all',
        ]);
        $notice = \App\Models\Notice::first();

        $this->actingAs($other)->get(route('notices.edit', $notice))->assertForbidden();
    }

    public function test_admin_can_delete_any_notice_author_can_delete_their_own(): void
    {
        Role::findOrCreate('teacher');
        Role::findOrCreate('admin');
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->assignRole('teacher');
        $admin = User::factory()->create(['school_id' => $school->id]);
        $admin->assignRole('admin');

        $this->actingAs($teacher)->post(route('notices.store'), [
            'title' => 'To delete', 'body' => 'Body', 'audience' => 'all',
        ]);
        $notice = \App\Models\Notice::first();

        $this->actingAs($admin)->delete(route('notices.destroy', $notice))->assertRedirect();

        $this->assertDatabaseMissing('notices', ['id' => $notice->id]);
    }
}
