<?php

namespace App\Support;

/**
 * Version de l'application exposée au front (meta "app-version") et jointe aux
 * erreurs client. config('app.version') (APP_VERSION) prime si elle est définie ;
 * à défaut, la dernière version annoncée dans ReleaseNotes.
 */
class AppVersion
{
    public static function current(): string
    {
        $configured = config('app.version');

        return is_string($configured) && $configured !== ''
            ? $configured
            : ReleaseNotes::currentVersion();
    }
}
