<?php

use App\Logging\MaskSensitiveData;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

// Formateur commun (LOG_FORMATTER=Monolog\Formatter\JsonFormatter en production).
// JsonFormatter et LineFormatter omettent la stack trace par défaut : on la garde.
$formatter = env('LOG_FORMATTER') ?: null;
$formatterWith = $formatter ? ['includeStacktraces' => true] : [];

// Canal quotidien partagé : même rétention, même niveau et même formateur.
// permission 0666 : sur l'hébergement mutualisé, le site et le cron (utilisateur SSH)
// écrivent dans les mêmes fichiers ; celui qui crée le fichier du jour ne doit pas
// bloquer l'autre (sinon chaque log lève une exception et la page part en 500).
$dailyChannel = fn (string $file, ?string $level = null) => [
    'driver' => 'daily',
    'path' => storage_path("logs/{$file}.log"),
    'level' => $level ?? env('LOG_LEVEL', 'debug'),
    'days' => (int) env('LOG_DAILY_DAYS', 30),
    'formatter' => $formatter,
    'formatter_with' => $formatterWith,
    'permission' => 0666,
    'tap' => [MaskSensitiveData::class],
    'replace_placeholders' => true,
];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            // LOG_STACK : liste de canaux séparés par des virgules (ex. "daily,stderr").
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        // Canaux thématiques : chaque domaine a son fichier, filtrable par request_id.
        'app' => $dailyChannel('app'),
        // Jamais plus strict que info : chaque accès invité / role:* doit laisser une trace (#23),
        // même avec LOG_LEVEL=warning.
        'security' => $dailyChannel('security', env('LOG_LEVEL', 'debug') === 'debug' ? 'debug' : 'info'),
        'mail' => $dailyChannel('mail'),
        'queue' => $dailyChannel('queue'),
        'audit' => $dailyChannel('audit', 'info'),

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'permission' => 0666,
            'tap' => [MaskSensitiveData::class],
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => (int) env('LOG_DAILY_DAYS', 30),
            'formatter' => $formatter,
            'formatter_with' => $formatterWith,
            'permission' => 0666,
            'tap' => [MaskSensitiveData::class],
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

];
