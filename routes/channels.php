<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel public pour le planning des vehicules (par date)
Broadcast::channel('vehicle-planning.{date}', function ($user) {
    return auth()->check();
});

// Channel public pour les repas du dimanche
Broadcast::channel('meal.{mealId}', function ($user) {
    return auth()->check();
});


