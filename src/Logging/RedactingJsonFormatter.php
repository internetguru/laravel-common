<?php

namespace InternetGuru\LaravelCommon\Logging;

use Monolog\Formatter\JsonFormatter;
use Throwable;

class RedactingJsonFormatter extends JsonFormatter
{
    /** @return array<array-key, mixed> */
    protected function normalizeException(Throwable $e, int $depth = 0): array
    {
        $data = parent::normalizeException($e, $depth);

        if (isset($data['message']) && is_string($data['message'])) {
            $data['message'] = RedactSecrets::in($data['message']);
        }

        return $data;
    }
}
