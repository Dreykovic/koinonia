<?php

return [
    'platform' => [
        'admin',
        'communication',
        'gestion_plateforme',
        'autre',
    ],
];
/**
 * 
 * 
 * 
 * 
 * 
 $allowedRoles = config('roles.platform');

@foreach(config('roles.platform') as $role)
    <label>
        <input type="checkbox" name="roles[]" value="{{ $role }}">
        {{ ucfirst($role) }}
    </label>
@endforeach

*

*/

