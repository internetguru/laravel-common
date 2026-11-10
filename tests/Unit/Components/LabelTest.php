<?php

namespace Tests\Unit\Components;

use Illuminate\Support\Facades\Log;
use Illuminate\View\ViewException;
use InternetGuru\LaravelCommon\Exceptions\LabelException;
use InternetGuru\LaravelCommon\Support\LabelGroups;
use InternetGuru\LaravelCommon\Support\Palette;
use InternetGuru\LaravelCommon\View\Components\Label;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

enum Shape
{
    case CIRCLE;
    case SQUARE;
}

class LabelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LabelGroups::flush();
        LabelGroups::register('locations', ['prg', 'brn', 'ost', 'plz']);
        LabelGroups::register('shapes', ['CIRCLE', 'SQUARE']);
    }

    public function test_renders_the_text_as_a_chip()
    {
        $html = $this->blade('<x-ig::label text="Paid" variant="success" />');

        $html->assertSee('class="ig-label"', false);
        $html->assertSee('Paid');
        $html->assertSee('--ig-label-dot:', false);
    }

    public function test_the_slot_is_used_in_place_of_the_text()
    {
        $html = $this->blade('<x-ig::label text="Paid" variant="success">Unpaid</x-ig::label>');

        $html->assertSee('Unpaid');
        $html->assertDontSee('Paid');
    }

    public function test_a_variant_is_drawn_in_its_palette_tone()
    {
        $html = $this->blade('<x-ig::label text="Paid" variant="success" />');

        $html->assertSee('--ig-label-dot: ' . Palette::variant('success'), false);
    }

    public function test_an_explicit_color_wins_over_the_variant()
    {
        $html = $this->blade('<x-ig::label text="Paid" variant="success" color="#ff8800" />');

        $html->assertSee('--ig-label-dot: #ff8800', false);
    }

    public function test_a_key_takes_the_color_of_its_place_in_the_group()
    {
        $html = $this->blade('<x-ig::label text="Ostrava" group="locations" key="ost" />');

        $html->assertSee('--ig-label-dot: ' . LabelGroups::colors('locations')['ost'], false);
        $this->assertSame(
            LabelGroups::colors('locations')['brn'],
            $this->dotColorOf(Label::html('Brno', group: 'locations', key: 'brn')),
        );
    }

    public function test_an_enum_case_stands_for_its_name_as_the_key()
    {
        $this->assertSame(
            LabelGroups::colors('shapes')['SQUARE'],
            $this->dotColorOf(Label::html('Square', group: 'shapes', key: Shape::SQUARE)),
        );
    }

    public function test_a_failing_label_breaks_the_page_in_debug_mode()
    {
        config(['app.debug' => true]);

        $this->expectException(ViewException::class);

        $this->blade('<x-ig::label text="Paid" group="locations" />');
    }

    #[DataProvider('invalidLabels')]
    public function test_an_invalid_value_fails_in_debug_mode(array $arguments, string $message)
    {
        config(['app.debug' => true]);

        $this->expectException(LabelException::class);
        $this->expectExceptionMessage($message);

        new Label('Paid', ...$arguments);
    }

    #[DataProvider('invalidLabels')]
    public function test_an_invalid_value_is_gray_and_logged_in_production(array $arguments, string $message)
    {
        config(['app.debug' => false]);
        Log::shouldReceive('warning')->once()->with($message);

        $html = Label::html('Paid', ...$arguments);

        $this->assertSame(Palette::variant('secondary'), $this->dotColorOf($html));
        $this->assertStringNotContainsString('evil', $html);
        $this->assertStringNotContainsString('expression', $html);
    }

    public static function invalidLabels(): array
    {
        return [
            'nothing' => [[], "Label 'Paid' has no variant, color or group"],
            'color' => [['color' => 'red; background: url(evil)'], "Label 'Paid' has an invalid color: red; background: url(evil)"],
            'color over a valid variant' => [['variant' => 'success', 'color' => 'reed'], "Label 'Paid' has an invalid color: reed"],
            'variant' => [['variant' => 'expression(alert(1))'], "Label 'Paid' has an unknown variant: expression(alert(1))"],
            'group without a key' => [['group' => 'locations'], "Label 'Paid' needs both a group and a key"],
            'key without a group' => [['key' => 'prg'], "Label 'Paid' needs both a group and a key"],
            'unknown group' => [['group' => 'cities', 'key' => 'prg'], "Label 'Paid' has an unknown group: cities"],
            'unknown key' => [['group' => 'locations', 'key' => 'vie'], "Label 'Paid' has a key not in group locations: vie"],
        ];
    }

    public function test_an_icon_stands_in_for_the_dot()
    {
        $html = $this->blade('<x-ig::label text="Admin" variant="danger" icon="fa-solid fa-user-gear" />');

        $html->assertSee('ig-label-icon', false);
        $html->assertSee('<i class="fa-solid fa-user-gear" aria-hidden="true">', false);
    }

    public function test_a_slim_label_is_marked_as_one()
    {
        $html = $this->blade('<x-ig::label text="Hidden" variant="secondary" slim />');

        $html->assertSee('class="ig-label ig-label-slim"', false);
    }

    public function test_a_label_is_not_slim_unless_it_is_asked_for()
    {
        $html = $this->blade('<x-ig::label text="Hidden" variant="secondary" />');

        $html->assertDontSee('ig-label-slim', false);
    }

    public function test_the_label_carries_no_whitespace_after_it()
    {
        $html = Label::html('Paid', variant: 'success');

        $this->assertStringEndsWith('Paid</span>', $html);
    }

    public function test_an_inline_label_leaves_its_line_of_text_untouched()
    {
        $html = $this->blade('Order <x-ig::label text="Paid" variant="success" />.');

        $this->assertStringEndsWith('Paid</span>.', (string) $html);
    }

    private function dotColorOf(string $html): string
    {
        preg_match('/--ig-label-dot: ([^"]+)"/', $html, $matches);

        return $matches[1] ?? '';
    }
}
