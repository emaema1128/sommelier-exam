<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $email = 'taro@example.com', bool $verified = true, string $id = 'google-123'): void
    {
        $googleUser = (new SocialiteUser)
            ->setRaw(['sub' => $id, 'email' => $email, 'email_verified' => $verified])
            ->map(['id' => $id, 'name' => 'Taro', 'email' => $email]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
    }

    public function test_redirects_to_google()
    {
        config(['services.google.client_id' => 'test-client']);

        $response = $this->get(route('google.redirect'));

        $response->assertRedirectContains('accounts.google.com');
    }

    public function test_new_user_is_registered_and_logged_in()
    {
        $this->fakeGoogleUser();

        $response = $this->get(route('google.callback'));

        $user = User::where('email', 'taro@example.com')->firstOrFail();
        $this->assertSame('google-123', $user->google_id);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_existing_email_user_is_linked_instead_of_duplicated()
    {
        $existing = User::factory()->create(['email' => 'taro@example.com']);
        $this->fakeGoogleUser();

        $this->get(route('google.callback'));

        $this->assertSame(1, User::count());
        $this->assertSame('google-123', $existing->fresh()->google_id);
        $this->assertAuthenticatedAs($existing);
    }

    public function test_returning_user_is_found_by_google_id_even_if_email_changed()
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'google_id' => 'google-123']);
        $this->fakeGoogleUser(email: 'new@example.com');

        $this->get(route('google.callback'));

        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_google_email_is_rejected()
    {
        $existing = User::factory()->create(['email' => 'taro@example.com']);
        $this->fakeGoogleUser(verified: false);

        $response = $this->get(route('google.callback'));

        $this->assertGuest();
        $this->assertNull($existing->fresh()->google_id);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_failed_google_callback_redirects_back_to_login()
    {
        Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException);

        $response = $this->get(route('google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_google_only_user_cannot_log_in_with_a_password()
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'google-123']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => '']);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }
}
