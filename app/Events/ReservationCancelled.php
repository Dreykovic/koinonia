<?php

namespace App\Events;

use App\Models\VehicleReservation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $reservationId;
    public int $vehicleId;
    public string $date;

    public function __construct(VehicleReservation $reservation)
    {
        $this->reservationId = $reservation->id;
        $this->vehicleId = $reservation->vehicle_id;
        $this->date = $reservation->reservation_date->format('Y-m-d');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('vehicle-planning.' . $this->date),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->reservationId,
            'vehicle_id' => $this->vehicleId,
        ];
    }
}
