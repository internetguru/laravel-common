<?php

namespace Tests\Unit\Logging;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InternetGuru\LaravelCommon\Logging\JsonDailyLogger;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class JsonDailyLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('root');
        Storage::disk('root')->put('VERSION', '1.2.3');
        Storage::disk('root')->put('.git/HEAD', 'ref: refs/heads/dev');
        Storage::disk('root')->put('.git/refs/heads/dev', 'abcdef1234567890');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function logContextFor(string $path, array $input): array
    {
        $request = Request::create($path, 'POST', $input, server: [
            'HTTP_REFERER' => 'https://example.com/callback?code=oauth-code',
            'HTTP_USER_AGENT' => 'Test Browser',
            'SERVER_NAME' => 'app.example.com',
        ]);
        $this->app->instance('request', $request);

        $logger = (new JsonDailyLogger)([
            'path' => storage_path('logs/test.log'),
            'days' => 1,
            'level' => 'debug',
        ]);
        $record = new LogRecord(new \DateTimeImmutable, 'test', Level::Info, 'Something happened');
        foreach ($logger->getProcessors() as $processor) {
            $record = $processor($record);
        }

        return $record->extra['context'];
    }

    private function signIn(): void
    {
        $this->actingAs(new GenericUser(['id' => 7, 'name' => 'Jane Doe', 'email' => 'jane@example.com']));
    }

    public function test_keeps_only_identifiers_and_request_info()
    {
        $this->signIn();
        $session = $this->app['session.store'];
        $session->start();

        $context = $this->logContextFor('/settings', ['api_key' => 'sk_live_secret']);

        $this->assertSame(['id' => 7], $context['user']);
        $this->assertSame(substr(hash('sha256', $session->getId()), 0, 12), $context['session']);
        $this->assertSame([
            'url' => 'settings',
            'ip' => '127.0.0.1',
            'http_method' => 'POST',
            'server' => 'app.example.com',
        ], $context['request_info']);
        $this->assertSame('dev', $context['branch']);
        $this->assertSame('abcdef1', $context['commit']);
    }

    public function test_leaves_out_personal_data_and_secrets()
    {
        $this->signIn();
        $session = $this->app['session.store'];
        $session->start();

        $logged = json_encode($this->logContextFor('/settings', [
            'api_key' => 'sk_live_secret',
            'pin' => '483920',
            'email' => 'guest@example.com',
        ]));

        foreach (['sk_live_secret', '483920', 'guest@example.com', 'jane@example.com', 'Jane Doe', $session->getId(), 'oauth-code', 'Test Browser'] as $secret) {
            $this->assertStringNotContainsString($secret, $logged);
        }
    }

    public function test_leaves_out_livewire_form_values()
    {
        $logged = json_encode($this->logContextFor(EndpointResolver::updatePath(), [
            'components' => [[
                'snapshot' => json_encode(['data' => ['phone' => '+420777123456'], 'memo' => ['name' => 'reservation-form']]),
                'updates' => ['note' => 'Allergic to nuts'],
                'calls' => [['method' => 'save', 'params' => ['guest@example.com']]],
            ]],
        ]));

        foreach (['+420777123456', 'Allergic to nuts', 'guest@example.com'] as $secret) {
            $this->assertStringNotContainsString($secret, $logged);
        }
    }

    public function test_has_no_session_hash_without_a_started_session()
    {
        $context = $this->logContextFor('/webhook', []);

        $this->assertNull($context['session']);
        $this->assertSame([], $context['user']);
    }
}
