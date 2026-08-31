<?php

namespace InternetGuru\LaravelCommon\Logging;

use Illuminate\Support\Facades\Cache;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Collapses repeats of the same log record into one entry per time window.
 *
 * A single fault - a scanner working through a component, a failing dependency,
 * a bad deploy - otherwise writes the same line hundreds of times and buries
 * everything else in the log.
 *
 * Monolog ships a DeduplicationHandler, but it is built for mail and chat
 * handlers: it buffers every record until the request ends, then passes or
 * discards the whole batch together. On a long-running Octane worker nothing
 * closes the logger between requests, so records would sit in memory instead of
 * reaching the file. This decides per record and writes straight through.
 *
 * The window is held in the cache rather than a temp file, so it is shared by
 * every worker and every app server. Cache failures pass the record through:
 * logging a fault twice is cheaper than losing it.
 */
class DeduplicatingHandler extends HandlerWrapper
{
    /**
     * @param  array<int, Level>  $levels  Levels to collapse, matched exactly. A
     *                                     record at any other level is passed
     *                                     straight through, so listing `Error`
     *                                     does not cover `Critical` and above.
     */
    public function __construct(
        HandlerInterface $handler,
        private readonly array $levels = [Level::Error],
        private readonly int $seconds = 60,
    ) {
        parent::__construct($handler);
    }

    /**
     * The handler this one wraps, so a caller can tell a wrapped handler from a
     * doubly-wrapped one.
     */
    public function getHandler(): HandlerInterface
    {
        return $this->handler;
    }

    public function handle(LogRecord $record): bool
    {
        if (in_array($record->level, $this->levels, true) && $this->isRepeat($record)) {
            return false;
        }

        return $this->handler->handle($record);
    }

    /**
     * @param  array<int, LogRecord>  $records
     */
    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    /**
     * The first record of a window claims the key; every later one within the
     * window finds it taken. Keyed on level and the message's first line, so a
     * multi-line message still collapses with its repeats.
     */
    private function isRepeat(LogRecord $record): bool
    {
        $key = 'ig-common:log-dedup:' . md5($record->level->getName() . ':' . strtok($record->message, "\r\n"));

        try {
            return ! Cache::add($key, true, $this->seconds);
        } catch (\Throwable) {
            return false;
        }
    }
}
