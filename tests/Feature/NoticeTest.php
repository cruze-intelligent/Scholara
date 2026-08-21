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
}
