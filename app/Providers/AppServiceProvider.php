<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Hébergement mutualisé (docs/deploy-ovh.md, 6 bis) : le site et le cron tournent sous
        // deux utilisateurs qui partagent storage/. Sans ce umask, un dossier de cache ou un
        // fichier créé par l'un (0755 / 0644) bloque les écritures de l'autre (page 500).
        if ($this->app->isProduction()) {
            umask(0000);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Paginator::useBootstrapFive();

        //
    }
}
