<?php

namespace Tests\Unit\Traits;

use InternetGuru\LaravelCommon\Traits\WatchesForChanges;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class WatchesForChangesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        WatchedCounter::$value = 1;
        WatchedCounter::$fingerprintReads = 0;
    }

    public function test_an_unchanged_fingerprint_sends_no_markup()
    {
        $component = Livewire::test(WatchedCounter::class)->call('watchRefresh');

        $this->assertArrayNotHasKey('html', $component->effects);
    }

    public function test_a_changed_fingerprint_renders_the_component()
    {
        $component = Livewire::test(WatchedCounter::class)->assertSee('Value 1');

        WatchedCounter::$value = 2;

        $component->call('watchRefresh')
            ->assertSee('Value 2')
            ->assertSet('changedCalls', 1);
        $this->assertArrayHasKey('html', $component->effects);
    }

    public function test_the_render_stores_the_new_fingerprint_so_the_next_check_is_quiet_again()
    {
        $component = Livewire::test(WatchedCounter::class);
        WatchedCounter::$value = 2;
        $component->call('watchRefresh');

        $component->call('watchRefresh');

        $this->assertArrayNotHasKey('html', $component->effects);
    }

    public function test_the_fingerprint_is_read_once_per_request()
    {
        $component = Livewire::test(WatchedCounter::class);
        WatchedCounter::$value = 2;
        WatchedCounter::$fingerprintReads = 0;

        $component->call('watchRefresh');

        $this->assertSame(1, WatchedCounter::$fingerprintReads);
    }

    public function test_typed_values_sent_with_an_unchanged_check_still_render()
    {
        $component = Livewire::test(WatchedCounter::class);

        $component->update(
            calls: [['method' => 'watchRefresh', 'params' => [], 'metadata' => []]],
            updates: ['note' => 'typed'],
        );

        $this->assertArrayHasKey('html', $component->effects);
        $component->assertSee('Note typed')->assertSet('changedCalls', 0);
    }

    public function test_a_call_bundled_after_an_unchanged_check_still_renders()
    {
        $component = Livewire::test(WatchedCounter::class);

        $component->update(calls: [
            ['method' => 'watchRefresh', 'params' => [], 'metadata' => []],
            ['method' => 'rename', 'params' => ['clicked'], 'metadata' => []],
        ]);

        $component->assertSee('Note clicked');
    }

    public function test_a_renderless_call_bundled_with_an_unchanged_check_renders_nothing()
    {
        $component = Livewire::test(WatchedCounter::class);

        $component->update(calls: [
            ['method' => 'watchRefresh', 'params' => [], 'metadata' => []],
            ['method' => 'ping', 'params' => [], 'metadata' => []],
        ]);

        $this->assertArrayNotHasKey('html', $component->effects);
    }

    public function test_the_stored_fingerprint_cannot_be_set_from_the_browser()
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(WatchedCounter::class)->set('watchedFingerprint', 'forged');
    }
}

class WatchedCounter extends Component
{
    use WatchesForChanges;

    public static int $value = 1;

    public static int $fingerprintReads = 0;

    public int $changedCalls = 0;

    public string $note = '';

    public function rename(string $note): void
    {
        $this->note = $note;
    }

    #[Renderless]
    public function ping(): void {}

    protected function watchFingerprint(): string
    {
        self::$fingerprintReads++;

        return (string) self::$value;
    }

    protected function watchChanged(): void
    {
        $this->changedCalls++;
    }

    public function render(): string
    {
        return '<div>Value ' . self::$value . ' Note ' . $this->note . '</div>';
    }
}
