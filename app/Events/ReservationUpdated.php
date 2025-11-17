<?php

namespace App\Events;

use App\Models\VehicleReservation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public VehicleReservation $reservation;

    public function __construct(VehicleReservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('vehicle-planning.' . $this->reservation->reservation_date->format('Y-m-d')),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->reservation->id,
            'vehicle_id' => $this->reservation->vehicle_id,
            'vehicle_name' => $this->reservation->vehicle->name,
            'user_id' => $this->reservation->user_id,
            'user_name' => $this->reservation->user->name,
            'start_time' => $this->reservation->start_time,
            'end_time' => $this->reservation->end_time,
            'destination' => $this->reservation->destination,
            'status' => $this->reservation->status,
        ];
    }
}

