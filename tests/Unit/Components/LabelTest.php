<?php

namespace Tests\Unit\Components;

use InternetGuru\LaravelCommon\View\Components\Label;
use Tests\TestCase;

class LabelTest extends TestCase
{
    public function test_renders_the_text_as_a_chip()
    {
        $html = $this->blade('<x-ig::label text="Paid" />');

        $html->assertSee('class="ig-label"', false);
        $html->assertSee('Paid');
        $html->assertSee('--ig-label-dot:', false);
    }

    public function test_the_slot_is_used_in_place_of_the_text()
    {
        $html = $this->blade('<x-ig::label text="Paid">Unpaid</x-ig::label>');

        $html->assertSee('Unpaid');
        $html->assertDontSee('Paid');
    }

    public function test_a_variant_names_a_theme_colour()
    {
        $html = $this->blade('<x-ig::label text="Paid" variant="success" />');

        $html->assertSee('--ig-label-dot: var(--bs-success)', false);
    }

    public function test_an_unknown_variant_falls_back_to_a_derived_colour()
    {
        $html = $this->blade('<x-ig::label text="Paid" variant="expression(alert(1))" />');

        $html->assertDontSee('expression', false);
        $html->assertSee('--ig-label-dot: hsl(', false);
    }

    public function test_an_explicit_colour_wins_over_the_variant()
    {
        $html = $this->blade('<x-ig::label text="Paid" variant="success" color="#ff8800" />');

        $html->assertSee('--ig-label-dot: #ff8800', false);
    }

    public function test_a_colour_that_is_not_a_colour_is_dropped()
    {
        $html = $this->blade('<x-ig::label text="Paid" color="red; background: url(evil)" />');

        $html->assertDontSee('evil', false);
        $html->assertSee('--ig-label-dot: hsl(', false);
    }

    public function test_a_value_keeps_its_colour_and_differs_from_its_neighbours()
    {
        $this->assertSame(Label::html('Prague'), Label::html('Prague'));
        $this->assertNotSame(
            $this->dotColorOf(Label::html('Prague')),
            $this->dotColorOf(Label::html('Brno')),
        );
    }

    public function test_the_seed_colours_the_label_in_place_of_the_text()
    {
        $this->assertSame(
            $this->dotColorOf(Label::html('Paid', seed: 'paid')),
            $this->dotColorOf(Label::html('Bezahlt', seed: 'paid')),
        );
    }

    public function test_an_icon_stands_in_for_the_dot()
    {
        $html = $this->blade('<x-ig::label text="Admin" icon="fa-solid fa-user-gear" />');

        $html->assertSee('ig-label-icon', false);
        $html->assertSee('<i class="fa-solid fa-user-gear" aria-hidden="true">', false);
    }

    public function test_a_slim_label_is_marked_as_one()
    {
        $html = $this->blade('<x-ig::label text="Hidden" slim />');

        $html->assertSee('class="ig-label ig-label-slim"', false);
    }

    public function test_a_label_is_not_slim_unless_it_is_asked_for()
    {
        $html = $this->blade('<x-ig::label text="Hidden" />');

        $html->assertDontSee('ig-label-slim', false);
    }

    private function dotColorOf(string $html): string
    {
        preg_match('/--ig-label-dot: ([^"]+)"/', $html, $matches);

        return $matches[1] ?? '';
    }
}
