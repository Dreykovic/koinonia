<?php

/**
 * Point d'entrée du cron OVH (le formulaire n'accepte qu'un chemin de script, sans arguments).
 * Équivalent de : php artisan schedule:run
 */

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

define('LARAVEL_START', microtime(true));

chdir(__DIR__);

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$input = new ArrayInput(['command' => 'schedule:run']);

$status = $kernel->handle($input, new ConsoleOutput);

$kernel->terminate($input, $status);

exit($status);
