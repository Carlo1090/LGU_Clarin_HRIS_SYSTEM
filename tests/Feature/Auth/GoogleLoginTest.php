<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Features;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.google', [
        'client_id' => 'test-client',
        'client_secret' => 'test-secret',
        'redirect' => 'http://localhost/auth/google/callback',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'example-token']),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
            'email' => 'employee@gmail.com',
            'email_verified' => true,
        ]),
    ]);
});

test('google signs in an existing verified account with matching callback state', function () {
    $user = User::factory()->for(Role::factory()->state(['name' => 'HRMO Head']))->create(['email' => 'employee@gmail.com']);
    $this->get(route('google.redirect'))->assertRedirect();
    $state = session('google_login.state');
    $this->get(route('google.callback', ['state' => $state, 'code' => 'google-code']))
        ->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('google rejects both pending and active employees with verified email', function (bool $pending) {
    $factory = User::factory()->for(Role::factory()->state(['name' => 'Employee']));
    if ($pending) {
        $factory = $factory->state(['password' => null]);
    }
    $user = $factory->create(['email' => 'employee@gmail.com']);

    $this->get(route('google.redirect'))->assertRedirect();
    $this->get(route('google.callback', ['state' => session('google_login.state'), 'code' => 'google-code']))
        ->assertRedirect(route('login'))->assertSessionHasErrors('google');
    $this->assertGuest();
    if ($pending) {
        expect($user->fresh()->password)->toBeNull();
    }
})->with([true, false]);

test('google does not exchange a code with mismatched state or create accounts', function () {
    $this->get(route('google.redirect'))->assertRedirect();
    $this->get(route('google.callback', ['state' => 'unrelated', 'code' => 'google-code']))
        ->assertRedirect(route('login'))->assertSessionHasErrors('google');
    Http::assertNothingSent();

    $this->get(route('google.redirect'))->assertRedirect();
    $this->get(route('google.callback', ['state' => session('google_login.state'), 'code' => 'google-code']))
        ->assertRedirect(route('login'))->assertSessionHasErrors('google');
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('google preserves the existing authenticator code challenge', function () {
    config()->set('fortify.features', [Features::twoFactorAuthentication(['confirm' => true])]);
    $user = User::factory()->for(Role::factory()->state(['name' => 'HR Officer']))->withTwoFactor()->create(['email' => 'employee@gmail.com']);
    $this->get(route('google.redirect'))->assertRedirect();
    $this->get(route('google.callback', ['state' => session('google_login.state'), 'code' => 'google-code']))
        ->assertRedirect(route('two-factor.login'))->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});
