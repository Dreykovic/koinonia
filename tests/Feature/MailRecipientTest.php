<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Http\Controllers\CommunityRequestFormController;
use App\Models\Community;
use App\Models\CommunityRequest;
use App\Models\MailRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use ReflectionMethod;
use Tests\TestCase;

class MailRecipientTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Test ' . $role,
            'email' => $role . '-' . uniqid() . '@example.test',
            'password' => 'password',
            'roles' => [$role],
        ]);
    }

    /**
     * Declenche la notification et renvoie les adresses reellement destinataires.
     * L'envoi immediat (sendNow) contourne Mail::fake(), on ecoute donc MessageSent.
     */
    private function notify(string $type): array
    {
        config(['mail.default' => 'array']);
        $sent = [];
        Event::listen(MessageSent::class, function (MessageSent $e) use (&$sent) {
            foreach ($e->message->getTo() as $address) {
                $sent[] = $address->getAddress();
            }
        });

        $request = CommunityRequest::create([
            'request_type' => $type,
            'email' => 'demandeur@example.test',
            'community_id' => Community::create(['name' => 'Communaute test'])->id,
            'problem_description' => 'Description',
            'purchase_description' => 'Description',
        ]);

        $method = new ReflectionMethod(CommunityRequestFormController::class, 'notifyAdmins');
        $method->setAccessible(true);
        $method->invoke(app(CommunityRequestFormController::class), $request);

        app()->terminate();

        return $sent;
    }

    public function test_admin_can_add_a_recipient(): void
    {
        $this->actingAs($this->user(PlatformRole::ADMIN))
            ->post(route('mail-recipients.store'), ['type' => 'reparation', 'email' => 'tech@example.test', 'name' => 'Tech'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('mail_recipients', ['type' => 'reparation', 'email' => 'tech@example.test', 'active' => true]);
    }

    public function test_duplicate_or_unknown_type_is_rejected(): void
    {
        MailRecipient::create(['type' => 'reparation', 'email' => 'tech@example.test']);
        $admin = $this->user(PlatformRole::ADMIN);

        $this->actingAs($admin)->post(route('mail-recipients.store'), ['type' => 'reparation', 'email' => 'tech@example.test'])
            ->assertSessionHasErrors('email');
        $this->actingAs($admin)->post(route('mail-recipients.store'), ['type' => 'bogus', 'email' => 'x@example.test'])
            ->assertSessionHasErrors('type');
        $this->assertSame(1, MailRecipient::count());
    }

    public function test_admin_can_deactivate_and_delete(): void
    {
        $admin = $this->user(PlatformRole::ADMIN);
        $recipient = MailRecipient::create(['type' => 'reparation', 'email' => 'tech@example.test']);

        $this->actingAs($admin)->put(route('mail-recipients.update', $recipient), ['active' => 0])->assertSessionHas('success');
        $this->assertFalse($recipient->fresh()->active);

        $this->actingAs($admin)->delete(route('mail-recipients.destroy', $recipient))->assertSessionHas('success');
        $this->assertDatabaseMissing('mail_recipients', ['id' => $recipient->id]);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $member = $this->user('member');

        $this->actingAs($member)->get(route('mail-recipients.index'))->assertForbidden();
        $this->actingAs($member)->post(route('mail-recipients.store'), ['type' => 'reparation', 'email' => 'a@example.test'])->assertForbidden();
        $this->assertSame(0, MailRecipient::count());
    }

    public function test_repair_mail_goes_only_to_configured_active_addresses(): void
    {
        $this->user(PlatformRole::ADMIN);
        MailRecipient::create(['type' => 'reparation', 'email' => 'tech@example.test']);
        MailRecipient::create(['type' => 'reparation', 'email' => 'off@example.test', 'active' => false]);

        $this->assertSame(['tech@example.test'], $this->notify('reparation'));
    }

    public function test_repair_mail_falls_back_to_admins_without_active_address(): void
    {
        $admin = $this->user(PlatformRole::ADMIN);
        MailRecipient::create(['type' => 'reparation', 'email' => 'off@example.test', 'active' => false]);

        $this->assertSame([$admin->email], $this->notify('reparation'));
    }

    public function test_purchase_mail_still_goes_to_admins(): void
    {
        $admin = $this->user(PlatformRole::ADMIN);
        MailRecipient::create(['type' => 'reparation', 'email' => 'tech@example.test']);

        $this->assertSame([$admin->email], $this->notify('achat'));
    }
}
