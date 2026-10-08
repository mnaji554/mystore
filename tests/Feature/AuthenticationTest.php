<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_user_can_register_as_customer_and_receives_welcome_notification(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'أحمد علي', 'email' => 'Ahmed@Example.com', 'phone' => '0555555555',
            'password' => 'Secret123', 'password_confirmation' => 'Secret123',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'ahmed@example.com')->firstOrFail();
        $this->assertSame(Role::CUSTOMER, $user->role->slug);
        $this->assertTrue(Hash::check('Secret123', $user->password));
        $this->assertNotSame('Secret123', $user->password);
        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_registration_validates_input(): void
    {
        $this->customer(['email' => 'taken@example.com']);

        $this->post('/register', ['name' => '', 'email' => 'taken@example.com', 'password' => 'short', 'password_confirmation' => 'other'])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = $this->customer();

        $this->post('/login', ['email' => $user->email, 'password' => 'Password123'])->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_staff_are_redirected_to_admin_after_login(): void
    {
        $admin = $this->staff('admin');

        $this->post('/login', ['email' => $admin->email, 'password' => 'Password123'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_wrong_password_and_disabled_accounts_cannot_login(): void
    {
        $user = $this->customer();
        $disabled = $this->customer(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $disabled->email, 'password' => 'Password123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->customer();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'bad']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'Password123'])->assertStatus(429);
    }

    public function test_disabled_user_session_is_terminated(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->get('/account')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get('/account')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_reset_link_is_sent_without_revealing_accounts(): void
    {
        Notification::fake();
        $user = $this->customer();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status');
        Notification::assertCount(1);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = $this->customer();
        $token = app('auth.password.broker')->createToken($user);

        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email, 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
    }

    public function test_customer_can_update_profile_and_password(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->put('/account/profile', ['name' => 'اسم جديد', 'email' => 'new@example.com', 'phone' => '0501111111'])
            ->assertSessionHasNoErrors();
        $this->assertSame('اسم جديد', $user->fresh()->name);

        $this->put('/account/password', ['current_password' => 'wrong', 'password' => 'Another123', 'password_confirmation' => 'Another123'])
            ->assertSessionHasErrors('current_password');

        $this->put('/account/password', ['current_password' => 'Password123', 'password' => 'Another123', 'password_confirmation' => 'Another123'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Another123', $user->fresh()->password));
    }

    public function test_account_pages_require_authentication(): void
    {
        $this->get('/account')->assertRedirect(route('login'));
        $this->get('/wishlist')->assertRedirect(route('login'));
        $this->get('/account/orders')->assertRedirect(route('login'));
    }
}
