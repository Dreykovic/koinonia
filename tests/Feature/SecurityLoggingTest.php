<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Http\Controllers\AuthController;
use App\Models\AccountRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Tests\TestCase;

class SecurityLoggingTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $security;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->security = new TestHandler();
        $this->app['log']->channel('security')->getLogger()->pushHandler($this->security);
    }

    private function user(array $roles = [PlatformRole::MEMBRE], array $attributes = []): User
    {
        return User::create($attributes + [
            'name' => 'Test',
            'email' => 'sec-'.uniqid().'@example.test',
            'password' => 'secret-password',
            'roles' => $roles,
            'is_active' => true,
        ]);
    }

    private function record(string $message): ?LogRecord
    {
        return collect($this->security->getRecords())->first(fn (LogRecord $r) => $r->message === $message);
    }

    private function assertNoRecordContains(string $needle): void
    {
        foreach ($this->security->getRecords() as $record) {
            $this->assertStringNotContainsString($needle, json_encode($record->context), "Fuite dans {$record->message}");
        }
    }

    // ---------- Connexion ----------

    public function test_failed_login_is_logged_with_masked_email_and_without_password(): void
    {
        $user = $this->user(attributes: ['email' => 'jean.dupont@example.test']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong-pass-123'])
            ->assertSessionHasErrors('email');

        $record = $this->record('auth.login.failed');
        $this->assertNotNull($record);
        $this->assertSame('Warning', $record->level->name);
        $this->assertSame('invalid_credentials', $record->context['reason']);
        $this->assertSame($user->id, $record->context['user_id']);
        $this->assertSame('j***@example.test', $record->context['email']);
        $this->assertNoRecordContains('wrong-pass-123');
        $this->assertNoRecordContains('jean.dupont');
    }

    public function test_login_on_unknown_or_inactive_account_is_logged(): void
    {
        $this->post(route('login'), ['email' => 'ghost@example.test', 'password' => 'whatever'])
            ->assertSessionHasErrors('email');

        $this->assertSame('unknown_account', $this->record('auth.login.failed')->context['reason']);

        $inactive = $this->user(attributes: ['is_active' => false]);
        $this->security->clear();

        $this->post(route('login'), ['email' => $inactive->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');

        $record = $this->record('auth.login.failed');
        $this->assertSame('inactive_account', $record->context['reason']);
        $this->assertSame($inactive->id, $record->context['user_id']);
    }

    public function test_successful_login_and_logout_are_logged(): void
    {
        $user = $this->user();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect(route('dashboard'));

        $login = $this->record('auth.login.succeeded');
        $this->assertSame('Info', $login->level->name);
        $this->assertSame($user->id, $login->context['user_id']);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertSame($user->id, $this->record('auth.logout')->context['user_id']);
        $this->assertNoRecordContains('secret-password');
    }

    public function test_too_many_failed_logins_trigger_a_logged_lockout(): void
    {
        $user = $this->user();

        for ($i = 0; $i < AuthController::MAX_LOGIN_ATTEMPTS; $i++) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'bad']);
        }
        $this->assertNull($this->record('auth.lockout'));

        // Même le bon mot de passe est refusé pendant le blocage.
        $this->post(route('login'), ['email' => $user->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $record = $this->record('auth.lockout');
        $this->assertNotNull($record);
        $this->assertSame('Warning', $record->level->name);
        $this->assertStringStartsWith(substr($user->email, 0, 1).'***@', $record->context['email']);
    }

    // ---------- Accès ----------

    public function test_check_role_refusal_is_logged_with_user_route_and_required_roles(): void
    {
        $member = $this->user();

        $this->actingAs($member)->get(route('users.index'))->assertForbidden();

        $record = $this->record('auth.access.denied');
        $this->assertNotNull($record);
        $this->assertSame('Warning', $record->level->name);
        $this->assertSame(['admin'], $record->context['required_roles']);
        $this->assertSame($member->id, $record->context['user_id']);
        $this->assertSame([PlatformRole::MEMBRE], $record->context['roles']);
        $this->assertSame('users.index', $record->context['route']);
    }

    public function test_check_role_with_several_roles_logs_all_of_them(): void
    {
        $this->actingAs($this->user())->get(route('bulletin.index'))->assertForbidden();

        $this->assertSame(['admin', 'communication'], $this->record('auth.access.denied')->context['required_roles']);
    }

    public function test_unauthenticated_access_is_logged_at_info_level(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $record = $this->record('auth.unauthenticated');
        $this->assertNotNull($record);
        $this->assertSame('Info', $record->level->name);
        $this->assertSame('dashboard', $record->context['route']);
    }

    // ---------- Mot de passe oublié ----------

    public function test_reset_link_for_unknown_account_is_logged(): void
    {
        $this->post(route('password.email'), ['email' => 'nobody@example.test'])
            ->assertSessionHasErrors('email');

        $record = $this->record('auth.password.reset_link.failed');
        $this->assertSame('n***@example.test', $record->context['email']);
        $this->assertSame(Password::INVALID_USER, $record->context['status']);
    }

    public function test_reset_form_never_logs_the_token(): void
    {
        $token = 'tok'.str_repeat('a', 40);

        $this->get(route('password.reset', ['token' => $token, 'email' => 'jean@example.test']))->assertOk();

        $this->assertNotNull($this->record('auth.password.reset_form'));
        $this->assertNoRecordContains($token);
        $this->assertNoRecordContains('jean@');
    }

    public function test_password_reset_failure_and_success_are_logged(): void
    {
        $user = $this->user();

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertSessionHasErrors('email');

        $this->assertSame(Password::INVALID_TOKEN, $this->record('auth.password.reset.failed')->context['status']);

        $token = Password::createToken($user);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertRedirect(route('login'));

        $this->assertSame($user->id, $this->record('auth.password.reset')->context['user_id']);
        $this->assertNoRecordContains($token);
        $this->assertNoRecordContains('new-password-1');
    }

    // ---------- Demandes de compte ----------

    public function test_account_request_submission_is_logged(): void
    {
        $this->post(route('account-request.store'), ['name' => 'Paul', 'email' => 'paul.martin@example.test'])
            ->assertRedirect(route('account-request.success'));

        $record = $this->record('account_request.submitted');
        $this->assertSame('p***@example.test', $record->context['email']);
        $this->assertNotNull($record->context['account_request_id']);
    }

    public function test_invalid_account_request_submission_is_logged(): void
    {
        $existing = $this->user();

        $this->post(route('account-request.store'), ['name' => 'Paul', 'email' => $existing->email])
            ->assertSessionHasErrors('email');

        $record = $this->record('account_request.submit.invalid');
        $this->assertSame(['email'], $record->context['fields']);
        $this->assertNoRecordContains($existing->email);
    }

    public function test_account_request_rejection_is_logged_without_notes(): void
    {
        $admin = $this->user([PlatformRole::ADMIN]);
        $accountRequest = AccountRequest::create(['name' => 'Paul', 'email' => 'paul@example.test']);

        $this->actingAs($admin)
            ->post(route('account-requests.reject', $accountRequest), ['admin_notes' => 'Note privée confidentielle'])
            ->assertRedirect(route('account-requests.index'));

        $record = $this->record('account_request.rejected');
        $this->assertSame($accountRequest->id, $record->context['account_request_id']);
        $this->assertSame($admin->id, $record->context['user_id']);
        $this->assertTrue($record->context['has_notes']);
        $this->assertNoRecordContains('confidentielle');

        // Rejet d'une demande déjà traitée : refus tracé.
        $this->actingAs($admin)->post(route('account-requests.reject', $accountRequest));

        $skipped = $this->record('account_request.reject.skipped');
        $this->assertSame('already_processed', $skipped->context['reason']);
        $this->assertSame('rejected', $skipped->context['status']);
    }

    public function test_account_request_rejection_by_a_member_is_denied_and_logged(): void
    {
        $accountRequest = AccountRequest::create(['name' => 'Paul', 'email' => 'paul@example.test']);

        $this->actingAs($this->user())
            ->post(route('account-requests.reject', $accountRequest))
            ->assertForbidden();

        $this->assertSame('account-requests.reject', $this->record('auth.access.denied')->context['route']);
        $this->assertSame('pending', $accountRequest->fresh()->status);
    }

    public function test_account_request_approval_is_logged(): void
    {
        $admin = $this->user([PlatformRole::ADMIN]);
        $accountRequest = AccountRequest::create(['name' => 'Paul', 'email' => 'paul@example.test']);

        $this->actingAs($admin)->post(route('account-requests.approve', $accountRequest))
            ->assertRedirect(route('account-requests.index'));

        $record = $this->record('account_request.approved');
        $created = User::where('email', 'paul@example.test')->first();
        $this->assertSame($created->id, $record->context['created_user_id']);
        $this->assertSame(['account_request_id', 'created_user_id', 'email'], array_values(array_intersect(
            array_keys($record->context),
            ['account_request_id', 'created_user_id', 'email', 'password', 'temporary_password'],
        )));
    }

    // ---------- Profil ----------

    public function test_profile_password_change_with_wrong_current_password_is_logged(): void
    {
        $user = $this->user();

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'not-my-password',
            'password' => 'Another-pass-123',
            'password_confirmation' => 'Another-pass-123',
        ])->assertSessionHasErrors('current_password');

        $record = $this->record('profile.password.change_failed');
        $this->assertSame('wrong_current_password', $record->context['reason']);
        $this->assertNoRecordContains('not-my-password');
        $this->assertNoRecordContains('Another-pass-123');

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'secret-password',
            'password' => 'Another-pass-123',
            'password_confirmation' => 'Another-pass-123',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($this->record('profile.password.changed'));
    }

    public function test_profile_email_change_is_logged_masked(): void
    {
        $user = $this->user(attributes: ['email' => 'old.address@example.test']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Test',
            'email' => 'new.address@example.test',
        ])->assertSessionHasNoErrors();

        $record = $this->record('profile.email.changed');
        $this->assertSame('o***@example.test', $record->context['old_email']);
        $this->assertSame('n***@example.test', $record->context['new_email']);
    }

    public function test_profile_page_shows_the_authenticated_user(): void
    {
        $this->user(attributes: ['name' => 'Premier Utilisateur']);
        $me = $this->user(attributes: ['name' => 'Moi Connecte']);

        $this->actingAs($me)->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Moi Connecte')
            ->assertDontSee('Premier Utilisateur');
    }
}
