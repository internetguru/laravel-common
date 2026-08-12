<?php

namespace Tests\Unit\Components;

use InternetGuru\LaravelCommon\View\Components\CardRow;
use Tests\TestCase;

class CardRowTest extends TestCase
{
    public function test_defaults_to_a_carousel_with_paging_controls()
    {
        $html = $this->blade('<x-ig::card-row label="Publications">Cards</x-ig::card-row>');

        $html->assertSee('class="card-row"', false);
        $html->assertSee('x-data="cardRow"', false);
        $html->assertSee('aria-label="Publications"', false);
        $html->assertSee('role="group"', false);
        $html->assertSee('x-ref="track"', false);
        $html->assertSee('aria-label="' . __('ig-common::layouts.card_row.previous') . '"', false);
        $html->assertSee('aria-label="' . __('ig-common::layouts.card_row.next') . '"', false);
        $html->assertSee('Cards');
    }

    public function test_falls_back_to_the_translated_label()
    {
        $html = $this->blade('<x-ig::card-row />');

        $html->assertSee('aria-label="' . __('ig-common::layouts.card_row.label') . '"', false);
    }

    public function test_the_grid_layout_drops_the_carousel_behaviour()
    {
        $html = $this->blade('<x-ig::card-row layout="grid" />');

        $html->assertSee('card-row card-row-grid', false);
        $html->assertDontSee('x-data="cardRow"', false);
        $html->assertDontSee('x-ref="track"', false);
        $html->assertDontSee('card-row-nav', false);
    }

    public function test_the_size_and_centered_props_add_their_modifiers()
    {
        $html = $this->blade('<x-ig::card-row layout="grid" size="narrow" centered />');

        $html->assertSee('card-row-narrow', false);
        $html->assertSee('card-row-centered', false);

        $html = $this->blade('<x-ig::card-row layout="grid" size="wide" />');

        $html->assertSee('card-row-wide', false);
        $html->assertDontSee('card-row-narrow', false);
        $html->assertDontSee('card-row-centered', false);
    }

    public function test_a_tinted_row_declares_a_tint_for_every_slot_of_the_palette()
    {
        $html = $this->blade('<x-ig::card-row label="Research" tinted />');

        $html->assertSee('card-row-tinted', false);

        foreach (range(1, 8) as $index) {
            $html->assertSee("--card-tint-{$index}: hsl(", false);
        }
    }

    public function test_an_untinted_row_declares_no_tints()
    {
        $this->assertSame([], (new CardRow(label: 'Research'))->tints);

        $this->blade('<x-ig::card-row label="Research" />')->assertDontSee('--card-tint-', false);
    }

    public function test_the_palette_is_stable_for_a_label_and_differs_between_labels()
    {
        $first = (new CardRow(label: 'Research', tinted: true))->tints;
        $again = (new CardRow(label: 'Research', tinted: true))->tints;
        $other = (new CardRow(label: 'Publications', tinted: true))->tints;

        $this->assertSame($first, $again);
        $this->assertNotSame($first, $other);
    }

    public function test_neighbouring_tints_stay_well_apart_on_the_wheel()
    {
        $hues = array_map(
            fn (string $tint): int => (int) preg_replace('/\D*(\d+).*/', '$1', explode('hsl(', $tint)[1]),
            (new CardRow(label: 'Research', tinted: true))->tints,
        );

        $this->assertCount(8, $hues);

        // Including the wrap from the last slot back to the first.
        foreach ($hues as $index => $hue) {
            $next = $hues[($index + 1) % 8];
            $distance = min(abs($hue - $next), 360 - abs($hue - $next));

            $this->assertGreaterThan(100, $distance);
        }
    }
}
