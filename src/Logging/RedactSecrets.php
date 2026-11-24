<?php

namespace InternetGuru\LaravelCommon\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class RedactSecrets implements ProcessorInterface
{
    public const MASK = '[redacted]';

    private const SECRET_QUERY_PARAMETER = '/([?&](?:[\w.\-]*(?:key|token|secret|password|signature)|code|pin)=)[^&\s"\'`#<>]+/i';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(message: self::in($record->message));
    }

    public static function in(string $text): string
    {
        return preg_replace(self::SECRET_QUERY_PARAMETER, '$1' . self::MASK, $text) ?? $text;
    }
}
