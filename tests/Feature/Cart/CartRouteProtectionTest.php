<?php

namespace Tests\Feature\Cart;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login_when_accessing_cart(): void
    {
        $response = $this->get(route('cart.index'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_unauthenticated_user_sees_login_message_after_redirect(): void
    {
        $response = $this->get(route('cart.index'));

        $response->assertRedirect(route('login', absolute: false));
        
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
    }

    public function test_authenticated_user_can_access_cart(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
    }

    public function test_after_login_user_is_redirected_back_to_intended_cart_page(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->get(route('cart.index'));

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('cart.index', absolute: false));
    }

    public function test_cart_route_stores_intended_url_in_session(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->get(route('cart.index'));

        $this->assertTrue(session()->has('url.intended'));
        $intendedUrl = session('url.intended');
        $this->assertStringContainsString('/cart', $intendedUrl);
    }
}
