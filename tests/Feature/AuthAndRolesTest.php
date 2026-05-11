<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_can_register_as_regular_user(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'is_admin' => false,
        ]);
    }

    public function test_password_reset_request_page_is_enabled(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('Восстановление пароля');
    }

    public function test_regular_user_cannot_access_filament(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/web-panel');

        $response->assertForbidden();
    }

    public function test_admin_user_can_access_filament(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $response = $this->actingAs($user)->get('/web-panel');

        $response->assertOk();
    }
}
