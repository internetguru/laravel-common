<?php

namespace InternetGuru\LaravelCommon\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\Logger as Monolog;

/**
 * Log channel tap that wraps each of the channel's handlers in a
 * DeduplicatingHandler.
 *
 * Registered against every configured channel by CommonServiceProvider, so it
 * needs no change to an application's logging config. LogManager applies taps
 * in get(), which covers `custom` driver channels too - createCustomDriver()
 * alone does not.
 */
class DeduplicateRepeatedRecords
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (! $monolog instanceof Monolog) {
            return;
        }

        $levels = $this->configuredLevels();

        if ($levels === []) {
            return;
        }

        $seconds = (int) config('ig-common.log_deduplication.seconds', 60);

        // A stack channel collects the handler instances of its sub-channels,
        // which this tap has already wrapped. Wrapping them a second time would
        // let the outer handler claim the window and the inner one read it back
        // as a repeat, dropping every record.
        $monolog->setHandlers(array_map(
            fn (HandlerInterface $handler): HandlerInterface => $handler instanceof DeduplicatingHandler
                ? $handler
                : new DeduplicatingHandler($handler, $levels, $seconds),
            $monolog->getHandlers(),
        ));
    }

    /**
     * @return array<int, Level>
     */
    private function configuredLevels(): array
    {
        $levels = [];

        foreach ((array) config('ig-common.log_deduplication.levels', []) as $level) {
            if (is_string($level)) {
                $level = trim($level);
            }

            if ($level === '' || $level === null) {
                continue;
            }

            $levels[] = Monolog::toMonologLevel($level);
        }

        return $levels;
    }
}
