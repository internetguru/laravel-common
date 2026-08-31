<?php

namespace Tests\Unit\Logging;

use Illuminate\Support\Facades\Log;
use InternetGuru\LaravelCommon\Logging\DeduplicateRepeatedRecords;
use InternetGuru\LaravelCommon\Logging\DeduplicatingHandler;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Tests\TestCase;

class LogDeduplicationTest extends TestCase
{
    private TestHandler $records;

    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->records = new TestHandler;
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new DeduplicatingHandler($this->records, [Level::Error, Level::Debug], 60));
    }

    public function test_a_burst_of_the_same_error_is_written_once()
    {
        for ($i = 0; $i < 50; $i++) {
            $this->logger->error('Undefined array key 8');
        }

        $this->assertCount(1, $this->records->getRecords());
    }

    public function test_distinct_errors_are_all_written()
    {
        $this->logger->error('Undefined array key 8');
        $this->logger->error('Unsupported operand types: array - int');

        $this->assertCount(2, $this->records->getRecords());
    }

    public function test_the_same_message_at_a_different_level_is_written()
    {
        $this->logger->error('Database is read-only');
        $this->logger->critical('Database is read-only');

        $this->assertCount(2, $this->records->getRecords());
    }

    public function test_every_listed_level_is_collapsed()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->logger->debug('Discarded malformed Livewire payload: [currentYear] holds a integer');
        }

        $this->assertCount(1, $this->records->getRecords());
    }

    /**
     * Levels are matched exactly, so a level that is not configured is passed
     * through however often it repeats - including the ones either side of a
     * listed level.
     */
    public function test_an_unlisted_level_is_never_collapsed()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->logger->warning('Slow query');
            $this->logger->critical('Queue worker died');
            $this->logger->info('Reservation created');
        }

        $this->assertCount(15, $this->records->getRecords());
    }

    public function test_a_multi_line_message_collapses_with_its_repeats()
    {
        $this->logger->error("Boom\n#0 /app/foo.php(1)");
        $this->logger->error("Boom\n#0 /app/bar.php(9)");

        $this->assertCount(1, $this->records->getRecords());
    }

    public function test_the_window_expires()
    {
        $logger = new Logger('short');
        $logger->pushHandler(new DeduplicatingHandler($this->records, [Level::Error], 1));

        $logger->error('Undefined array key 8');
        $this->travel(2)->seconds();
        $logger->error('Undefined array key 8');

        $this->assertCount(2, $this->records->getRecords());
    }

    public function test_a_cache_failure_lets_the_record_through()
    {
        config(['cache.default' => 'no-such-store']);

        $this->logger->error('Undefined array key 8');
        $this->logger->error('Undefined array key 8');

        $this->assertCount(2, $this->records->getRecords());
    }

    public function test_the_configured_levels_reach_the_handler()
    {
        config(['ig-common.log_deduplication.levels' => ['warning', ' notice ']]);

        $logger = new Logger('configured');
        (new DeduplicateRepeatedRecords)->__invoke(
            new \Illuminate\Log\Logger($logger->pushHandler($this->records)),
        );

        $logger->warning('Slow query');
        $logger->warning('Slow query');
        $logger->error('Undefined array key 8');
        $logger->error('Undefined array key 8');

        // The warning collapses, the unlisted error does not.
        $this->assertCount(3, $this->records->getRecords());
    }

    public function test_every_configured_channel_is_tapped()
    {
        $taps = config('logging.channels.single.tap');

        $this->assertContains(DeduplicateRepeatedRecords::class, $taps);
    }

    public function test_the_resolved_channel_wraps_its_handlers()
    {
        $handlers = Log::channel('single')->getLogger()->getHandlers();

        $this->assertNotEmpty($handlers);

        foreach ($handlers as $handler) {
            $this->assertInstanceOf(DeduplicatingHandler::class, $handler);
        }
    }

    /**
     * A stack collects the handler instances of its sub-channels, which the tap
     * has already wrapped. Wrapping them again would let the outer handler claim
     * the window and the inner one read it back as a repeat, dropping the lot.
     */
    public function test_a_stack_channel_does_not_double_wrap_its_sub_channel_handlers()
    {
        config(['logging.channels.stacked' => [
            'driver' => 'stack',
            'channels' => ['single'],
        ]]);

        foreach (Log::channel('stacked')->getLogger()->getHandlers() as $handler) {
            $this->assertInstanceOf(DeduplicatingHandler::class, $handler);
            $this->assertNotInstanceOf(DeduplicatingHandler::class, $handler->getHandler());
        }
    }
}
