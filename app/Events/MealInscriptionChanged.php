<?php

namespace App\Events;

use App\Models\SundayMeal;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MealInscriptionChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SundayMeal $meal;
    public string $action; // 'inscribed' or 'cancelled'

    public function __construct(SundayMeal $meal, string $action)
    {
        $this->meal = $meal;
        $this->action = $action;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('meal.' . $this->meal->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'meal_id' => $this->meal->id,
            'action' => $this->action,
            'remaining_places' => $this->meal->remaining_places,
            'confirmed_count' => $this->meal->confirmed_count,
            'is_full' => $this->meal->remaining_places <= 0,
        ];
    }
}



