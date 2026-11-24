<?php

namespace InternetGuru\LaravelCommon\Logging;

use Illuminate\Http\Request;
use InternetGuru\LaravelCommon\Support\Helpers;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class JsonDailyLogger
{
    /** @param array<string, mixed> $config */
    public function __invoke(array $config): Logger
    {
        $handler = new RotatingFileHandler($config['path'], $config['days'], $config['level']);
        $handler->setFormatter(new RedactingJsonFormatter);

        $logger = new Logger('json_daily');
        $logger->pushHandler($handler);
        $logger->pushProcessor(new RedactSecrets);

        if (! app()->bound('auth')) {
            return $logger;
        }

        $logger->pushProcessor(fn (LogRecord $record) => $this->addRequestContext($record));

        return $logger;
    }

    /**
     * Identifies who and what a line is about without carrying personal data
     * or secrets: no names, e-mail addresses, request input or raw session id.
     */
    private function addRequestContext(LogRecord $record): LogRecord
    {
        $request = app(Request::class);
        $app = Helpers::getAppInfoArray();

        $record->extra['context']['user'] = auth()->check() ? ['id' => auth()->id()] : [];
        $record->extra['context']['session'] = $this->sessionHash();
        $record->extra['context']['branch'] = $app['branch'];
        $record->extra['context']['commit'] = $app['commit'];
        $record->extra['context']['request_info'] = [
            'url' => $request->path(),
            'ip' => $request->ip(),
            'http_method' => $request->method(),
            'server' => $request->server('SERVER_NAME'),
        ];

        return $record;
    }

    /** Lets lines of one visit be grouped without the id that would sign in as it. */
    private function sessionHash(): ?string
    {
        if (! app()->bound('session.store') || ! session()->isStarted()) {
            return null;
        }

        return substr(hash('sha256', session()->getId()), 0, 12);
    }
}
