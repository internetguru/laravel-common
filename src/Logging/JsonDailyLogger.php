<?php

namespace InternetGuru\LaravelCommon\Logging;

use Illuminate\Http\Request;
use InternetGuru\LaravelCommon\Support\Helpers;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class JsonDailyLogger
{
    /** @param array<string, mixed> $config */
    public function __invoke(array $config): Logger
    {
        $handler = new RotatingFileHandler($config['path'], $config['days'], $config['level']);
        $handler->setFormatter(new JsonFormatter);

        $logger = new Logger('json_daily');
        $logger->pushHandler($handler);

        if (! app()->bound('auth')) {
            return $logger;
        }

        // Add custom processor for request input
        $logger->pushProcessor(function (LogRecord $record) {
            // user info
            $record->extra['context']['user'] = [];
            if (auth()->check()) {
                $user = auth()->user();
                $record->extra['context']['user'] = [
                    'id' => $user?->id,
                    'name' => $user?->name,
                    'email' => $user?->email,
                ];
            }
            // request input
            $record->extra['context']['request_input'] = [];
            $request = app(Request::class);
            $inputs = $request->except('password', '_token', 'g-recaptcha-response');
            // remove 'snapshot' from livewire request
            if ($request->path() == 'livewire/update') {
                foreach ($inputs['components'] as $key => $component) {
                    if (isset($component['snapshot'])) {
                        unset($inputs['components'][$key]['snapshot']);
                    }
                    if (count($inputs['components'][$key]) == 1) {
                        unset($inputs['components'][$key]);
                    }
                }
                if (count($inputs['components']) == 0) {
                    return $record;
                }
            }
            if ($inputs) {
                $record->extra['context']['request_input'] = $inputs;
            }
            // session id
            $record->extra['context']['session_id'] = session()->getId();
            // software info
            $app = Helpers::getAppInfoArray();
            $record->extra['context']['branch'] = $app['branch'];
            $record->extra['context']['commit'] = $app['commit'];
            // request info
            $record->extra['context']['request_info'] = [];
            $info = [
                'url' => $request->path(),
                'ip' => $request->ip(),
                'http_method' => $request->method(),
                'server' => $request->server('SERVER_NAME'),
                'referrer' => $request->server('HTTP_REFERER'),
                'user_agent' => $request->userAgent(),
            ];
            $record->extra['context']['request_info'] = $info;

            return $record;
        });

        return $logger;
    }
}
