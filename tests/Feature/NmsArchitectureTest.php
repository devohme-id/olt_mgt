<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Domain\Identity\Models\User;

class NmsArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_redirects_guests_to_login()
    {
        $response = $this->get('/');

        // Should redirect to /login
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        // To avoid foreign key issues with roles in the generic factory, just mock actingAs
        $user = User::factory()->make(['id' => '123', 'role_id' => 'abc']);
        
        $response = $this->actingAs($user)->get('/');
        
        // As long as the route resolves and middleware passes
        $response->assertStatus(200);
    }
}
