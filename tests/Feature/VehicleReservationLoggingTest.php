<?php

namespace Tests\Feature;

use App\Enums\PlatformRole;
use App\Events\ReservationCancelled;
use App\Events\ReservationCreated;
use App\Models\RepairLocation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleReservation;
use App\Models\VehicleType;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Mail;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Tests\TestCase;

/**
 * Journalisation des modules Vehicules, Reservations et Lieux de reparation (#26).
 */
class VehicleReservationLoggingTest extends TestCase
{
    use RefreshDatabase;

    private TestHandler $appLog;

    private TestHandler $securityLog;

    private TestHandler $auditLog;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);

        $this->appLog = $this->captureChannel('app');
        $this->securityLog = $this->captureChannel('security');
        $this->auditLog = $this->captureChannel('audit');
    }

    private function captureChannel(string $channel): TestHandler
    {
        $handler = new TestHandler();
        $this->app['log']->channel($channel)->getLogger()->pushHandler($handler);

        return $handler;
    }

    private function record(TestHandler $handler, string $message): ?LogRecord
    {
        return collect($handler->getRecords())->firstWhere('message', $message);
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'password',
            'roles' => [$role],
        ]);
    }

    private function vehicle(): Vehicle
    {
        $type = VehicleType::create(['name' => 'type-'.uniqid(), 'is_active' => true]);

        return Vehicle::create([
            'name' => 'Voiture '.uniqid(),
            'vehicle_type_id' => $type->id,
            'capacity' => 5,
            'is_available' => true,
        ]);
    }

    private function reservation(Vehicle $vehicle, User $user, array $attributes = []): VehicleReservation
    {
        return VehicleReservation::create($attributes + [
            'vehicle_id' => $vehicle->id,
            'user_id' => $user->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'approved',
        ]);
    }

    /**
     * Driver de broadcast qui echoue toujours (Pusher injoignable).
     */
    private function failingBroadcaster(): void
    {
        Mail::fake();
        Broadcast::extend('failing', fn () => new class extends Broadcaster
        {
            public function auth($request)
            {
            }

            public function validAuthenticationResponse($request, $result)
            {
            }

            public function broadcast(array $channels, $event, array $payload = [])
            {
                throw new BroadcastException('Pusher injoignable');
            }
        });
        config([
            'broadcasting.default' => 'failing',
            'broadcasting.connections.failing' => ['driver' => 'failing'],
        ]);
    }

    public function test_reservation_conflict_is_logged_with_slot_context(): void
    {
        $admin = $this->user(PlatformRole::ADMIN);
        $vehicle = $this->vehicle();
        $this->reservation($vehicle, $admin);

        $this->actingAs($admin)->postJson('/api/v1/reservations', [
            'vehicle_id' => $vehicle->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'start_time' => '09:30',
            'end_time' => '11:00',
        ])->assertStatus(422);

        $record = $this->record($this->appLog, 'reservation.conflict');
        $this->assertNotNull($record);
        $this->assertSame('Warning', $record->level->name);
        $this->assertSame('store', $record->context['action']);
        $this->assertSame('overlap', $record->context['reason']);
        $this->assertSame($vehicle->id, $record->context['vehicle_id']);
        $this->assertSame('09:30', $record->context['start_time']);
    }

    public function test_update_conflict_is_logged_with_reservation_context(): void
    {
        $admin = $this->user(PlatformRole::ADMIN);
        $vehicle = $this->vehicle();
        $this->reservation($vehicle, $admin);
        $other = $this->reservation($vehicle, $admin, ['start_time' => '11:00', 'end_time' => '12:00']);

        $this->actingAs($admin)->putJson("/api/v1/reservations/{$other->id}", [
            'start_time' => '09:30',
            'end_time' => '12:00',
        ])->assertStatus(422);

        $record = $this->record($this->appLog, 'reservation.conflict');
        $this->assertSame('update', $record->context['action']);
        $this->assertSame($other->id, $record->context['reservation_id']);
        $this->assertSame('09:30', $record->context['requested_start_time']);
    }

    public function test_broadcast_failure_on_store_does_not_break_the_request_and_is_logged(): void
    {
        $this->failingBroadcaster();
        $admin = $this->user(PlatformRole::ADMIN);
        $vehicle = $this->vehicle();

        $response = $this->actingAs($admin)->postJson('/api/v1/reservations', [
            'vehicle_id' => $vehicle->id,
            'reservation_date' => now()->addDay()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:00',
        ])->assertCreated();

        $reservationId = $response->json('reservation.id');
        $this->assertDatabaseHas('vehicle_reservations', ['id' => $reservationId]);

        $record = $this->record($this->appLog, 'broadcast.failed');
        $this->assertNotNull($record);
        $this->assertSame(ReservationCreated::class, $record->context['event']);
        $this->assertSame($reservationId, $record->context['reservation_id']);
        $this->assertSame($vehicle->id, $record->context['vehicle_id']);
        $this->assertSame($admin->id, $record->context['owner_id']);
        $this->assertNotNull($this->record($this->appLog, 'reservation.created'));
    }

    public function test_broadcast_failure_on_cancel_does_not_break_the_request_and_is_logged(): void
    {
        $this->failingBroadcaster();
        $member = $this->user(PlatformRole::MEMBRE);
        $reservation = $this->reservation($this->vehicle(), $member);

        $this->actingAs($member)->deleteJson("/api/v1/reservations/{$reservation->id}")->assertOk();

        $this->assertDatabaseMissing('vehicle_reservations', ['id' => $reservation->id]);
        $this->assertSame(ReservationCancelled::class, $this->record($this->appLog, 'broadcast.failed')->context['event']);

        $cancelled = $this->record($this->appLog, 'reservation.cancelled');
        $this->assertSame($reservation->id, $cancelled->context['reservation_id']);
        $this->assertFalse($cancelled->context['by_admin']);
    }

    public function test_cancel_refusals_are_logged(): void
    {
        $owner = $this->user(PlatformRole::MEMBRE);
        $intruder = $this->user(PlatformRole::MEMBRE);
        $vehicle = $this->vehicle();
        $upcoming = $this->reservation($vehicle, $owner);
        $past = $this->reservation($vehicle, $owner, ['reservation_date' => now()->subDays(2)->toDateString()]);

        $this->actingAs($intruder)->deleteJson("/api/v1/reservations/{$upcoming->id}")->assertForbidden();
        $denied = $this->record($this->securityLog, 'reservation.access.denied');
        $this->assertSame('cancel', $denied->context['action']);
        $this->assertSame($owner->id, $denied->context['owner_id']);

        $this->actingAs($owner)->deleteJson("/api/v1/reservations/{$past->id}")->assertStatus(422);
        $this->assertSame($past->id, $this->record($this->appLog, 'reservation.cancel.refused')->context['reservation_id']);
    }

    public function test_admin_rejection_is_audited(): void
    {
        $admin = $this->user(PlatformRole::ADMIN);
        $reservation = $this->reservation($this->vehicle(), $this->user(PlatformRole::MEMBRE), ['status' => 'pending']);

        $this->actingAs($admin)->post(route('reservations.reject', $reservation))->assertRedirect();

        $record = $this->record($this->auditLog, 'reservation.rejected');
        $this->assertSame($reservation->id, $record->context['reservation_id']);
        $this->assertSame('pending', $record->context['previous_status']);
        $this->assertSame('rejected', $record->context['status']);
    }

    public function test_vehicle_deletion_refusal_and_deletion_are_logged(): void
    {
        $admin = $this->user(PlatformRole::ADMIN);
        $vehicle = $this->vehicle();
        $this->reservation($vehicle, $admin);

        $this->actingAs($admin)->delete(route('vehicles.destroy', $vehicle))->assertRedirect();
        $refused = $this->record($this->appLog, 'vehicle.delete.refused');
        $this->assertSame($vehicle->id, $refused->context['vehicle_id']);
        $this->assertSame(1, $refused->context['future_reservations']);

        $free = $this->vehicle();
        $this->actingAs($admin)->delete(route('vehicles.destroy', $free))->assertRedirect(route('vehicles.index'));
        $this->assertSame($free->id, $this->record($this->auditLog, 'vehicle.deleted')->context['vehicle_id']);
    }

    public function test_repair_location_changes_are_audited(): void
    {
        $admin = $this->user(PlatformRole::ADMIN);

        $this->actingAs($admin)->post(route('repair-locations.store'), ['name' => 'Garage '.uniqid()])->assertRedirect();
        $location = RepairLocation::latest('id')->first();
        $this->assertSame($location->id, $this->record($this->auditLog, 'repair_location.created')->context['repair_location_id']);

        $this->actingAs($admin)->delete(route('repair-locations.destroy', $location))->assertRedirect();
        $this->assertSame($location->id, $this->record($this->auditLog, 'repair_location.deleted')->context['repair_location_id']);
    }
}
