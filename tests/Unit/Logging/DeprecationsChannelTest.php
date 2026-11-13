<?php

namespace Tests\Unit\Logging;

use Illuminate\Log\LogManager;
use Monolog\Handler\NullHandler;
use Tests\TestCase;

class DeprecationsChannelTest extends TestCase
{
    /**
     * Config to set after it is loaded but before the providers register,
     * which is when an application's config/logging.php is in place.
     *
     * @var array<string, mixed>
     */
    private array $loggingConfig = [];

    private string $emergencyLog;

    protected function setUp(): void
    {
        $this->emergencyLog = sys_get_temp_dir() . '/ig-common-emergency-' . uniqid() . '.log';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        @unlink($this->emergencyLog);

        parent::tearDown();
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('logging.channels.emergency.path', $this->emergencyLog);
        $app['config']->set($this->loggingConfig);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function bootWithLoggingConfig(array $config): void
    {
        $this->loggingConfig = $config;
        $this->refreshApplication();
    }

    public function test_the_channel_drops_deprecations_by_default()
    {
        $this->assertSame(NullHandler::class, config('logging.channels.deprecations.handler'));
    }

    public function test_the_channel_follows_the_configured_deprecations_channel()
    {
        $this->bootWithLoggingConfig(['logging.deprecations.channel' => 'single']);

        $this->assertSame('single', config('logging.channels.deprecations.driver'));
        $this->assertSame(config('logging.channels.single.path'), config('logging.channels.deprecations.path'));
    }

    public function test_the_legacy_string_option_names_the_channel()
    {
        $this->bootWithLoggingConfig(['logging.deprecations' => 'single']);

        $this->assertSame('single', config('logging.channels.deprecations.driver'));
    }

    public function test_a_channel_the_application_defines_is_kept()
    {
        $this->bootWithLoggingConfig(['logging.channels.deprecations' => ['driver' => 'errorlog']]);

        $this->assertSame('errorlog', config('logging.channels.deprecations.driver'));
    }

    public function test_a_request_config_clone_resolves_the_channel_without_the_emergency_logger()
    {
        $this->app->instance('config', clone $this->app['config']);
        $this->app->make(LogManager::class)->setApplication($this->app);

        $this->app->make(LogManager::class)->channel('deprecations')->warning('Deprecated in test');

        $this->assertFileDoesNotExist($this->emergencyLog);
    }
}
