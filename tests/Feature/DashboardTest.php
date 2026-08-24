<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * One request per role, just asserting a 200 — every dashboard view was touched during the UI
 * polish pass (docs/HARDENING_TODO.md), and this was previously completely untested (flagged by
 * the original audit), so a Blade syntax error in any of them would otherwise go unnoticed.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('roleProvider')]
    public function test_dashboard_renders_for_role(string $role): void
    {
        Role::findOrCreate($role);
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->assignRole($role);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public static function roleProvider(): array
    {
        return [
            ['admin'], ['teacher'], ['parent'], ['learner'],
            ['nurse'], ['hr'], ['bursar'], ['librarian'],
        ];
    }
}
