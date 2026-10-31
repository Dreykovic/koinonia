<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Models\User;
use App\Support\ReleaseNotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsNewTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?string $seen = null): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'password',
            'roles' => [$role],
            'whats_new_seen_version' => $seen,
        ]);
    }

    public function test_whats_new_is_shown_on_first_page_after_update(): void
    {
        $this->actingAs($this->user(PlatformRole::MEMBRE))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="whatsNewModal"', false)
            ->assertSee('Le menu du jour en tete de page')
            ->assertDontSee('Rotation automatique');
    }

    public function test_admin_items_are_only_shown_to_admins(): void
    {
        $this->actingAs($this->user(PlatformRole::ADMIN))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Journaux consultables');
    }

    public function test_admin_only_release_does_not_hide_previous_release_from_members(): void
    {
        $member = $this->user(PlatformRole::MEMBRE, '2026.10.26');

        $this->assertNull(ReleaseNotes::pendingFor($member));

        $this->actingAs($this->user(PlatformRole::MEMBRE))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Le menu du jour en tete de page')
            ->assertDontSee('Journaux consultables');
    }

    public function test_whats_new_is_shown_only_once(): void
    {
        $user = $this->user(PlatformRole::MEMBRE);

        $this->actingAs($user)
            ->postJson(route('whats-new.seen'))
            ->assertNoContent();

        $this->assertSame(ReleaseNotes::currentVersion(), $user->fresh()->whats_new_seen_version);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="whatsNewModal"', false);
    }

    public function test_user_who_saw_the_current_version_is_not_bothered(): void
    {
        $this->actingAs($this->user(PlatformRole::MEMBRE, ReleaseNotes::currentVersion()))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="whatsNewModal"', false);
    }

    public function test_user_who_saw_an_older_version_sees_the_new_one(): void
    {
        $this->actingAs($this->user(PlatformRole::MEMBRE, '2026.01.01'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="whatsNewModal"', false);
    }
}
