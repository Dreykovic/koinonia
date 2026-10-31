<?php

namespace Tests\Feature;

use App\Services\LogReader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use RuntimeException;
use Tests\TestCase;

/**
 * Écriture réelle sur chaque canal thématique (issue #19) : la configuration de
 * config/logging.php est relue avec un storage temporaire, sans TestHandler.
 */
class LogChannelsTest extends TestCase
{
    private const CHANNELS = ['app', 'security', 'mail', 'queue', 'audit'];

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = sys_get_temp_dir().'/koinonia-log-channels-'.uniqid();
        File::ensureDirectoryExists($this->storage.'/logs');
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        $this->setFormatter(null);
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    /**
     * Recharge config/logging.php (chemins sous le storage temporaire, LOG_FORMATTER donné).
     */
    private function loadLoggingConfig(?string $formatter = null): void
    {
        $this->setFormatter($formatter);
        config(['logging' => require config_path('logging.php')]);

        foreach (self::CHANNELS as $channel) {
            app('log')->forgetChannel($channel);
        }
    }

    private function setFormatter(?string $formatter): void
    {
        foreach (['_SERVER', '_ENV'] as $global) {
            if ($formatter === null) {
                unset($GLOBALS[$global]['LOG_FORMATTER']);
            } else {
                $GLOBALS[$global]['LOG_FORMATTER'] = $formatter;
            }
        }
    }

    private function file(string $channel): string
    {
        return $this->storage."/logs/{$channel}-".now()->format('Y-m-d').'.log';
    }

    public function test_each_channel_writes_to_its_daily_file(): void
    {
        $this->loadLoggingConfig();

        foreach (self::CHANNELS as $channel) {
            $logger = Log::channel($channel);
            $this->assertInstanceOf(RotatingFileHandler::class, $logger->getLogger()->getHandlers()[0]);

            $logger->info('probe-'.$channel);

            $this->assertFileExists($this->file($channel));
            $this->assertStringContainsString('probe-'.$channel, file_get_contents($this->file($channel)));
            // Fichier partagé par le site et le cron (deux utilisateurs) : inscriptible par tous.
            $this->assertSame(0666, fileperms($this->file($channel)) & 0777, $channel);
        }
    }

    public function test_json_formatter_writes_decodable_lines_with_stack_trace(): void
    {
        $this->loadLoggingConfig(JsonFormatter::class);

        foreach (self::CHANNELS as $channel) {
            Log::channel($channel)->info('probe-'.$channel);

            $lines = file($this->file($channel), FILE_IGNORE_NEW_LINES);
            $this->assertSame('probe-'.$channel, json_decode(end($lines), true)['message']);
        }

        Log::channel('app')->error('probe.failed', ['exception' => new RuntimeException('boom')]);

        $lines = file($this->file('app'), FILE_IGNORE_NEW_LINES);
        $decoded = json_decode(end($lines), true);
        $this->assertNotEmpty($decoded['context']['exception']['trace']);

        // Entrée lue comme dans la page Journaux : la trace repliable doit être présente.
        $offset = filesize($this->file('app')) - strlen(end($lines)) - 1;
        $entry = (new LogReader($this->storage.'/logs'))->find('app', now()->format('Y-m-d'), $offset);
        $this->assertNotNull($entry['trace']);
        $this->assertStringContainsString('RuntimeException: boom', $entry['exception']);
    }
}
