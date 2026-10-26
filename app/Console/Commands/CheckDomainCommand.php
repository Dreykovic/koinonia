<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckDomainCommand extends Command
{
    protected $signature = 'domain:check';

    protected $description = 'Verify domain-related configuration (APP_URL, cookies, proxies) after a domain change';

    public function handle(): int
    {
        $url = (string) config('app.url');
        $parts = parse_url($url) ?: [];
        $host = $parts['host'] ?? '';
        $scheme = $parts['scheme'] ?? '';
        $problems = [];
        $warnings = [];

        if (in_array($host, ['', 'localhost', '127.0.0.1'], true)) {
            $problems[] = "APP_URL ({$url}) pointe vers une adresse locale : les liens des e-mails et des taches planifiees seront faux.";
        }

        if (app()->environment('production') && $scheme !== 'https') {
            $problems[] = 'APP_URL doit etre en https en production.';
        }

        if (app()->environment('production') && config('app.debug')) {
            $problems[] = 'APP_DEBUG est actif en production.';
        }

        $sessionDomain = config('session.domain');
        if ($sessionDomain && $host && !str_ends_with($host, ltrim($sessionDomain, '.'))) {
            $problems[] = "SESSION_DOMAIN ({$sessionDomain}) ne correspond pas a l'hote de APP_URL ({$host}) : les sessions seront perdues.";
        }

        if ($scheme === 'https' && config('session.secure') === false) {
            $warnings[] = 'SESSION_SECURE_COOKIE est desactive alors que le site est en https.';
        }

        if ($scheme === 'https' && config('session.secure') === null) {
            $warnings[] = 'SESSION_SECURE_COOKIE n\'est pas defini : recommande a true en https.';
        }

        if (!env('TRUSTED_PROXIES')) {
            $warnings[] = 'TRUSTED_PROXIES non defini : a renseigner si le site est derriere un proxy/CDN (sinon le https peut ne pas etre detecte).';
        }

        $stateful = (array) config('sanctum.stateful');
        if ($host && !collect($stateful)->contains(fn ($d) => str_starts_with($d, $host))) {
            $warnings[] = "SANCTUM_STATEFUL_DOMAINS ne contient pas {$host} (utile seulement si l'API est utilisee en SPA).";
        }

        $this->table(['Parametre', 'Valeur'], [
            ['APP_ENV', app()->environment()],
            ['APP_URL', $url],
            ['Exemple de lien (login)', route('login')],
            ['SESSION_DOMAIN', $sessionDomain ?: '(non defini : cookie lie a l\'hote)'],
            ['SESSION_SECURE_COOKIE', var_export(config('session.secure'), true)],
            ['TRUSTED_PROXIES', env('TRUSTED_PROXIES') ?: '(non defini)'],
            ['MAIL_FROM_ADDRESS', config('mail.from.address')],
        ]);

        foreach ($problems as $p) {
            $this->error('✘ ' . $p);
        }
        foreach ($warnings as $w) {
            $this->warn('! ' . $w);
        }

        if (!$problems && !$warnings) {
            $this->info('Configuration de domaine coherente.');
        }

        return $problems ? self::FAILURE : self::SUCCESS;
    }
}
