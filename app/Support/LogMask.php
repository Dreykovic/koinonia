<?php

namespace App\Support;

/**
 * Masquage des données personnelles avant écriture dans les logs.
 */
class LogMask
{
    /**
     * Masque la partie locale d'une adresse : "jean.dupont@example.com" => "j***@example.com".
     * Une valeur sans "@" est entièrement masquée ; null reste null.
     */
    public static function email(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }

        $at = strrpos($email, '@');

        if ($at === false || $at === 0) {
            return '***';
        }

        return mb_substr($email, 0, 1).'***'.substr($email, $at);
    }
}
