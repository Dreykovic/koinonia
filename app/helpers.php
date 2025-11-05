<?php
function roleMiddleware(array $roles): string
{
    return 'role:' . implode(',', $roles);
}





