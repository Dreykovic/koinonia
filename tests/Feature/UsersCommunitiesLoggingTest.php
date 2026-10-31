<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\ActivityLog;
use App\Models\Community;
use App\Models\CommunityRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\VehicleSetting;
use App\Models\VehicleType;
use App\Services\QRCodeService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use RuntimeException;
use Tests\TestCase;

/**
 * Journalisation des modules Utilisateurs, Communautés et Demandes (issue #24) :
 * échec simulé => log error contextuel + message flash ; refus métier => warning ;
 * actions d'administration => canal audit (sans doublon avec ActivityLog).
 */
class UsersCommunitiesLoggingTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $appLog;

    private TestHandler $auditLog;

    private TestHandler $securityLog;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->appLog = $this->captureChannel('app');
        $this->auditLog = $this->captureChannel('audit');
        $this->securityLog = $this->captureChannel('security');

        $this->admin = $this->user(PlatformRole::ADMIN);
    }

    private function captureChannel(string $channel): TestHandler
    {
        $handler = new TestHandler();
        $this->app['log']->channel($channel)->getLogger()->pushHandler($handler);

        return $handler;
    }

    private function user(string $role, array $attributes = []): User
    {
        return User::create($attributes + [
            'name' => 'Test '.$role.' '.uniqid(),
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'password',
            'roles' => [$role],
            'is_active' => true,
        ]);
    }

    private function record(TestHandler $handler, string $message): ?array
    {
        foreach ($handler->getRecords() as $record) {
            if ($record['message'] === $message) {
                return $record->toArray();
            }
        }

        return null;
    }

    private function assertLogged(TestHandler $handler, string $message, Level $level): array
    {
        $record = $this->record($handler, $message);

        $this->assertNotNull($record, "Log \"{$message}\" absent.");
        $this->assertSame($level->value, $record['level']);

        return $record['context'];
    }

    private function communityRequest(Community $community, string $email): CommunityRequest
    {
        return CommunityRequest::create([
            'email' => $email,
            'community_id' => $community->id,
            'request_type' => 'achat',
            'purchase_description' => 'Ampoules',
        ]);
    }

    // ---- Utilisateurs ----

    public function test_user_store_failure_is_logged_and_flashed_without_password(): void
    {
        User::creating(fn () => throw new RuntimeException('db down'));

        $this->actingAs($this->admin)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'Jean Dupont',
                'email' => 'jean.dupont@example.test',
                'password' => 'Secret123!',
                'password_confirmation' => 'Secret123!',
                'roles' => [PlatformRole::MEMBRE],
            ])
            ->assertRedirect(route('users.create'))
            ->assertSessionHas('error')
            ->assertSessionMissing('_old_input.password');

        $context = $this->assertLogged($this->appLog, 'user.store.failed', Level::Error);
        $this->assertSame('j***@example.test', $context['email']);
        $this->assertSame(RuntimeException::class, $context['exception_class']);
        $this->assertStringNotContainsString('Secret123!', json_encode($this->appLog->getRecords()));
    }

    public function test_user_creation_and_role_change_are_audited(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Marie',
            'email' => 'marie@example.test',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'roles' => [PlatformRole::MEMBRE],
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'marie@example.test')->firstOrFail();
        $context = $this->assertLogged($this->auditLog, 'user.created', Level::Info);
        $this->assertSame($user->id, $context['target_user_id']);

        $this->actingAs($this->admin)->put(route('users.update', $user), [
            'name' => 'Marie',
            'email' => 'marie@example.test',
            'roles' => [PlatformRole::MEMBRE, PlatformRole::COMMUNICATION],
        ])->assertRedirect(route('users.index'));

        $context = $this->assertLogged($this->auditLog, 'user.updated', Level::Info);
        $this->assertContains('roles', $context['changed']);
        $this->assertSame([PlatformRole::MEMBRE], $context['old_roles']);
        $this->assertStringNotContainsString('marie@example.test', json_encode($this->auditLog->getRecords()));
    }

    public function test_user_update_failure_is_logged(): void
    {
        $user = $this->user(PlatformRole::MEMBRE);
        User::updating(fn () => throw new RuntimeException('boom'));

        $this->actingAs($this->admin)->put(route('users.update', $user), [
            'name' => 'Nouveau nom',
            'email' => $user->email,
            'roles' => [PlatformRole::MEMBRE],
        ])->assertSessionHas('error');

        $this->assertSame($user->id, $this->assertLogged($this->appLog, 'user.update.failed', Level::Error)['target_user_id']);
        $this->assertNull($this->record($this->auditLog, 'user.updated'));
    }

    public function test_user_destroy_rollback_is_logged_and_activity_log_not_kept(): void
    {
        $user = $this->user(PlatformRole::MEMBRE);
        User::deleting(fn () => throw new RuntimeException('fk violation'));

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $user), ['confirm_name' => $user->name])
            ->assertSessionHas('error');

        $context = $this->assertLogged($this->appLog, 'user.destroy.failed', Level::Error);
        $this->assertTrue($context['rolled_back']);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertSame(0, ActivityLog::where('action', 'user.deleted')->count());
    }

    public function test_user_deletion_is_audited_by_activity_log_only(): void
    {
        $user = $this->user(PlatformRole::MEMBRE);

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $user), ['confirm_name' => $user->name])
            ->assertRedirect(route('users.index'));

        $this->assertSame(1, ActivityLog::where('action', 'user.deleted')->count());
        $this->assertCount(0, $this->auditLog->getRecords());
    }

    public function test_user_business_refusals_are_logged_as_warning(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->admin), ['confirm_name' => $this->admin->name])
            ->assertSessionHas('error');

        $this->assertSame('self', $this->assertLogged($this->appLog, 'user.destroy.refused', Level::Warning)['reason']);

        $this->actingAs($this->admin)->post(route('users.toggle-active', $this->admin))->assertSessionHas('error');
        $this->assertLogged($this->appLog, 'user.toggle_active.refused', Level::Warning);
    }

    public function test_user_toggle_active_failure_and_success(): void
    {
        $user = $this->user(PlatformRole::MEMBRE);

        $this->actingAs($this->admin)->post(route('users.toggle-active', $user))->assertSessionHas('success');
        $this->assertFalse($this->assertLogged($this->auditLog, 'user.active_toggled', Level::Info)['is_active']);

        User::updating(fn () => throw new RuntimeException('boom'));
        $this->actingAs($this->admin)->post(route('users.toggle-active', $user))->assertSessionHas('error');
        $this->assertLogged($this->appLog, 'user.toggle_active.failed', Level::Error);
    }

    // ---- Communautés ----

    public function test_community_failures_are_logged_and_flashed(): void
    {
        Community::creating(fn () => throw new RuntimeException('boom'));

        $this->actingAs($this->admin)
            ->post(route('communities.store'), ['name' => 'Saint-Joseph'])
            ->assertSessionHas('error');

        $this->assertSame('Saint-Joseph', $this->assertLogged($this->appLog, 'community.store.failed', Level::Error)['name']);
    }

    public function test_community_crud_is_audited_and_refusal_is_warning(): void
    {
        $this->actingAs($this->admin)->post(route('communities.store'), ['name' => 'Saint-Paul'])->assertSessionHas('success');
        $community = Community::where('name', 'Saint-Paul')->firstOrFail();
        $this->assertLogged($this->auditLog, 'community.created', Level::Info);

        $this->actingAs($this->admin)
            ->put(route('communities.update', $community), ['name' => 'Saint-Paul II', 'is_active' => 0])
            ->assertSessionHas('success');
        $this->assertContains('name', $this->assertLogged($this->auditLog, 'community.updated', Level::Info)['changed']);

        $this->user(PlatformRole::MEMBRE, ['community_id' => $community->id]);
        $this->actingAs($this->admin)->delete(route('communities.destroy', $community))->assertSessionHas('error');
        $this->assertSame('has_users', $this->assertLogged($this->appLog, 'community.destroy.refused', Level::Warning)['reason']);
    }

    public function test_community_destroy_failure_is_logged(): void
    {
        $community = Community::create(['name' => 'Vide']);
        Community::deleting(fn () => throw new RuntimeException('boom'));

        $this->actingAs($this->admin)->delete(route('communities.destroy', $community))->assertSessionHas('error');

        $this->assertLogged($this->appLog, 'community.destroy.failed', Level::Error);
        $this->assertNull($this->record($this->auditLog, 'community.deleted'));
    }

    // ---- Demandes (administration) ----

    public function test_community_request_update_failure_is_logged(): void
    {
        $request = $this->communityRequest(Community::create(['name' => 'C1']), 'membre@example.test');
        CommunityRequest::updating(fn () => throw new RuntimeException('boom'));

        $this->actingAs($this->admin)
            ->put(route('community-requests.update', $request), ['status' => 'completed'])
            ->assertSessionHas('error');

        $context = $this->assertLogged($this->appLog, 'community_request.update.failed', Level::Error);
        $this->assertSame('completed', $context['new_status']);
        Mail::assertNothingSent();
    }

    public function test_community_request_update_and_export_are_audited(): void
    {
        $request = $this->communityRequest(Community::create(['name' => 'C2']), 'membre@example.test');

        $this->actingAs($this->admin)
            ->put(route('community-requests.update', $request), ['status' => 'in_progress', 'admin_notes' => 'note privee'])
            ->assertSessionHas('success');

        $context = $this->assertLogged($this->auditLog, 'community_request.updated', Level::Info);
        $this->assertSame('in_progress', $context['new_status']);
        $this->assertStringNotContainsString('note privee', json_encode($this->auditLog->getRecords()));

        $this->actingAs($this->admin)->get(route('community-requests.export', ['status' => 'in_progress']))->assertOk();
        $context = $this->assertLogged($this->auditLog, 'community_request.exported', Level::Info);
        $this->assertSame(1, $context['count']);
    }

    public function test_community_request_destroy_failure_is_logged(): void
    {
        $request = $this->communityRequest(Community::create(['name' => 'C3']), 'membre@example.test');
        CommunityRequest::deleting(fn () => throw new RuntimeException('boom'));

        $this->actingAs($this->admin)->delete(route('community-requests.destroy', $request))->assertSessionHas('error');

        $this->assertLogged($this->appLog, 'community_request.destroy.failed', Level::Error);
        $this->assertDatabaseHas('community_requests', ['id' => $request->id]);
    }

    public function test_member_access_to_another_request_is_logged_in_security(): void
    {
        $member = $this->user(PlatformRole::MEMBRE);
        $request = $this->communityRequest(Community::create(['name' => 'C4']), 'autre@example.test');

        $this->actingAs($member)->get(route('community-requests.show', $request))->assertForbidden();

        $this->assertSame($request->id, $this->assertLogged($this->securityLog, 'community_request.access.denied', Level::Warning)['community_request_id']);
    }

    // ---- Formulaire de demande ----

    public function test_form_store_failure_keeps_session_and_is_logged(): void
    {
        $member = $this->user(PlatformRole::MEMBRE);
        $community = Community::create(['name' => 'C5']);
        CommunityRequest::creating(fn () => throw new RuntimeException('boom'));

        $this->actingAs($member)
            ->withSession(['community_request_form' => [
                'community_id' => $community->id,
                'request_type' => 'achat',
                'email' => $member->email,
            ]])
            ->from(route('community-requests.form.step2.purchase'))
            ->post(route('community-requests.form.step2.purchase.post'), ['purchase_description' => 'Chaises'])
            ->assertRedirect(route('community-requests.form.step2.purchase'))
            ->assertSessionHas('error')
            ->assertSessionHas('community_request_form');

        $context = $this->assertLogged($this->appLog, 'community_request.store.failed', Level::Error);
        $this->assertSame('achat', $context['request_type']);
        $this->assertStringNotContainsString($member->email, json_encode($this->appLog->getRecords()));
    }

    public function test_form_success_is_logged_without_email(): void
    {
        $member = $this->user(PlatformRole::MEMBRE);
        $community = Community::create(['name' => 'C6']);

        $this->actingAs($member)
            ->withSession(['community_request_form' => [
                'community_id' => $community->id,
                'request_type' => 'achat',
                'email' => $member->email,
            ]])
            ->post(route('community-requests.form.step2.purchase.post'), ['purchase_description' => 'Chaises'])
            ->assertRedirect(route('community-requests.form.success'));

        $this->assertLogged($this->appLog, 'community_request.created', Level::Info);
        $this->assertStringNotContainsString($member->email, json_encode($this->appLog->getRecords()));
    }

    public function test_form_business_validation_failures_are_warnings(): void
    {
        $member = $this->user(PlatformRole::MEMBRE);

        $this->actingAs($member)
            ->post(route('community-requests.form.step2.purchase.post'), ['purchase_description' => 'x'])
            ->assertRedirect(route('community-requests.form.step1'))
            ->assertSessionHas('error');
        $this->assertSame('step2_purchase_post', $this->assertLogged($this->appLog, 'community_request.form.session_expired', Level::Warning)['step']);

        $community = Community::create(['name' => 'C7']);
        $this->actingAs($member)
            ->withSession(['community_request_form' => [
                'community_id' => $community->id,
                'request_type' => 'reparation',
                'email' => $member->email,
            ]])
            ->post(route('community-requests.form.step2.repair.post'), ['problem_description' => 'Fuite'])
            ->assertSessionHasErrors('repair_location_id');
        $this->assertLogged($this->appLog, 'community_request.form.location_missing', Level::Warning);
    }

    public function test_qr_download_failure_is_logged(): void
    {
        $this->mock(QRCodeService::class)->shouldReceive('downloadPng')->andThrow(new RuntimeException('gd missing'));

        $this->actingAs($this->admin)
            ->from(route('community-requests.index'))
            ->get(route('community-requests.form.qr-download'))
            ->assertRedirect(route('community-requests.index'))
            ->assertSessionHas('error');

        $this->assertLogged($this->appLog, 'community_request.qr.failed', Level::Error);
    }

    // ---- Profils / types de véhicules ----

    public function test_profile_vehicle_type_attach_failure_and_duplicate(): void
    {
        $profile = UserProfile::create(['name' => 'Profil '.uniqid(), 'is_active' => true]);
        $type = VehicleType::create(['name' => 'Type '.uniqid(), 'is_active' => true]);
        $payload = ['user_profile_id' => $profile->id, 'vehicle_type_id' => $type->id];

        $this->actingAs($this->admin)->post(route('user-profile-vehicle-types.store'), $payload)->assertSessionHas('success');
        $this->assertSame($type->id, $this->assertLogged($this->auditLog, 'profile_vehicle_type.attached', Level::Info)['vehicle_type_id']);

        $this->actingAs($this->admin)->post(route('user-profile-vehicle-types.store'), $payload)->assertSessionHas('error');
        $this->assertSame('already_attached', $this->assertLogged($this->appLog, 'profile_vehicle_type.store.refused', Level::Warning)['reason']);

        // Échec SQL simulé sur la table pivot
        DB::listen(function (QueryExecuted $query) {
            if (str_starts_with(strtolower($query->sql), 'delete') && str_contains($query->sql, 'user_profile_vehicle_types')) {
                throw new RuntimeException('pivot locked');
            }
        });

        $this->actingAs($this->admin)
            ->delete(route('user-profile-vehicle-types.destroy', [$profile, $type]))
            ->assertSessionHas('error');
        $this->assertLogged($this->appLog, 'profile_vehicle_type.destroy.failed', Level::Error);
    }

    // ---- Paramètres de réservation ----

    public function test_vehicle_settings_update_is_audited_and_failure_logged(): void
    {
        VehicleSetting::query()->delete();
        VehicleSetting::clearCache();

        $this->actingAs($this->admin)->put(route('admin.vehicle-settings.update'), [
            'opening_hour' => '08:00',
            'closing_hour' => '19:00',
            'max_future_days' => 30,
        ])->assertSessionHas('success');

        $context = $this->assertLogged($this->auditLog, 'vehicle_settings.updated', Level::Info);
        $this->assertSame(30, $context['new']['max_future_days']);
        $this->assertSame('08:00:00', $context['new']['opening_hour']);

        VehicleSetting::updating(fn () => throw new RuntimeException('boom'));

        $this->actingAs($this->admin)->put(route('admin.vehicle-settings.update'), [
            'opening_hour' => '09:00',
            'closing_hour' => '18:00',
            'max_future_days' => 10,
        ])->assertSessionHas('error');

        $this->assertLogged($this->appLog, 'vehicle_settings.update.failed', Level::Error);
    }
}
