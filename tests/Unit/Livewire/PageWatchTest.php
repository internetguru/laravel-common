<?php

namespace Tests\Unit\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use InternetGuru\LaravelCommon\Contracts\HasPageFingerprint;
use InternetGuru\LaravelCommon\Livewire\PageWatch;
use InternetGuru\LaravelCommon\Support\WatchedPage;
use InvalidArgumentException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class PageWatchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('watched_orders', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->timestamp('paid_from')->nullable();
            $table->timestamps();
        });
        WatchedClock::$value = 'a';
    }

    public function test_a_page_that_registered_nothing_renders_no_watcher()
    {
        $html = Blade::render('<x-ig::page-watch />');

        $this->assertStringNotContainsString('igPageWatch', $html);
    }

    public function test_a_page_that_registered_a_model_mounts_the_watcher()
    {
        WatchedPage::add(WatchedOrder::create(['status' => 'unpaid']));

        $html = Blade::render('<x-ig::page-watch />');

        $this->assertStringContainsString('igPageWatch', $html);
    }

    public function test_an_unchanged_page_reports_no_change()
    {
        $order = WatchedOrder::create(['status' => 'unpaid']);

        Livewire::test(PageWatch::class, $this->watchOf($order))
            ->call('watchCheck')
            ->assertReturned(['changed' => false, 'message' => null]);
    }

    public function test_an_updated_model_counts_as_a_change()
    {
        $order = WatchedOrder::create(['status' => 'unpaid']);
        $component = Livewire::test(PageWatch::class, $this->watchOf($order));

        $this->travel(1)->minute();
        $order->update(['status' => 'paid']);

        $component->call('watchCheck')->assertReturned(fn (array $result) => $result['changed'] === true);
    }

    public function test_a_change_made_after_the_page_rendered_but_before_the_watcher_mounted_counts()
    {
        $order = WatchedOrder::create(['status' => 'unpaid']);
        $watch = $this->watchOf($order);

        $this->travel(1)->minute();
        WatchedOrder::whereKey($order->getKey())->update(['status' => 'paid', 'updated_at' => now()]);

        Livewire::test(PageWatch::class, $watch)
            ->call('watchCheck')
            ->assertReturned(fn (array $result) => $result['changed'] === true);
    }

    public function test_a_deleted_model_counts_as_a_change()
    {
        $order = WatchedOrder::create(['status' => 'unpaid']);
        $component = Livewire::test(PageWatch::class, $this->watchOf($order));

        $order->delete();

        $component->call('watchCheck')->assertReturned(fn (array $result) => $result['changed'] === true);
    }

    public function test_a_state_that_changes_with_time_alone_counts_through_the_model_fingerprint()
    {
        $order = WatchedOrder::create(['status' => 'unpaid', 'paid_from' => now()->addMinute()]);
        $component = Livewire::test(PageWatch::class, $this->watchOf($order));

        $this->travel(2)->minutes();

        $component->call('watchCheck')->assertReturned(fn (array $result) => $result['changed'] === true);
    }

    public function test_a_static_callable_source_counts_its_result()
    {
        $sources = $this->watchOf([WatchedClock::class, 'read', ['x']]);
        $component = Livewire::test(PageWatch::class, $sources);

        WatchedClock::$value = 'b';

        $component->call('watchCheck')->assertReturned(fn (array $result) => $result['changed'] === true);
    }

    public function test_a_callable_message_is_read_when_the_change_is_found()
    {
        $order = WatchedOrder::create(['status' => 'unpaid']);
        $component = Livewire::test(PageWatch::class, [...$this->watchOf($order), 'message' => (function () {
            WatchedPage::message([WatchedClock::class, 'read', ['said ']]);

            return app(WatchedPage::class)->customMessage();
        })()]);

        WatchedClock::$value = 'later';
        $this->travel(1)->minute();
        $order->update(['status' => 'paid']);

        $component->call('watchCheck')->assertReturned(fn (array $result) => $result['message'] === 'said later');
    }

    public function test_a_closure_cannot_be_watched()
    {
        $this->expectException(InvalidArgumentException::class);

        WatchedPage::add([fn () => 'x', 'method']);
    }

    public function test_callable_arguments_must_be_scalar()
    {
        $this->expectException(InvalidArgumentException::class);

        WatchedPage::add([WatchedClock::class, 'read', [['nested']]]);
    }

    public function test_reload_flashes_the_page_message_and_returns_to_the_full_address()
    {
        $order = WatchedOrder::create(['status' => 'unpaid']);

        $component = Livewire::withQueryParams(['tab' => 'orders'])
            ->test(PageWatch::class, [...$this->watchOf($order), 'message' => 'Payment received']);
        $url = $component->get('url');
        $component->call('watchReload')->assertRedirect($url);

        $this->assertStringEndsWith('?tab=orders', $url);
        $this->assertSame('Payment received', session('success'));
    }

    public function test_the_watched_sources_cannot_be_changed_from_the_browser()
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(PageWatch::class, ['sources' => [], 'fingerprint' => ''])->set('sources', []);
    }

    /** @return array{sources: list<array<string, mixed>>, fingerprint: string} */
    private function watchOf(Model|array $source): array
    {
        $page = new WatchedPage;
        app()->instance(WatchedPage::class, $page);
        WatchedPage::add($source);

        return ['sources' => $page->sources(), 'fingerprint' => $page->fingerprint()];
    }
}

class WatchedOrder extends Model implements HasPageFingerprint
{
    protected $table = 'watched_orders';

    protected $guarded = [];

    protected $casts = ['paid_from' => 'datetime'];

    public function pageFingerprint(): string
    {
        return $this->paid_from instanceof Carbon && $this->paid_from->isPast() ? 'paid' : 'waiting';
    }
}

class WatchedClock
{
    public static string $value = 'a';

    public static function read(string $prefix): string
    {
        return $prefix . self::$value;
    }
}
