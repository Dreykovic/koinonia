<?php

namespace App\Support;

/**
 * Numéro de tentative du job en cours, renseigné par QueueMonitoringServiceProvider
 * (JobProcessing) et effacé à la fin du job. Le failed() d'un mailable ne reçoit que
 * l'exception : c'est ici qu'il lit le nombre réel de tentatives faites par le worker.
 */
class CurrentJob
{
    private static ?int $attempts = null;

    public static function start(int $attempts): void
    {
        self::$attempts = $attempts;
    }

    public static function attempts(): ?int
    {
        return self::$attempts;
    }

    public static function clear(): void
    {
        self::$attempts = null;
    }
}
