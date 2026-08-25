<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * /register is school self-registration, not a generic "create any account" form — see
 * RegisteredUserController and tests/Feature/SchoolRegistrationTest.php for the full flow
 * (pending review, trial, admin role, etc). Kept minimal here since that file already covers it.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_school_admins_can_register(): void
    {
        Role::findOrCreate('admin');

        $response = $this->post('/register', [
            'school_name' => 'Test School',
            'school_address' => 'Kampala, Uganda',
            'subdomain' => 'test-school',
            'admin_name' => 'Test User',
            'admin_email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
