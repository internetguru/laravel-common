<?php

namespace Tests\Unit\Components;

use Tests\TestCase;

class InputTest extends TestCase
{
    public function test_empty_date_input_is_marked_empty()
    {
        $this->withViewErrors([]);

        $html = $this->blade('<x-ig::input type="date" name="starts_at" value="">Starts</x-ig::input>');

        $html->assertSee('data-empty', false);
    }

    public function test_filled_date_input_is_not_marked_empty()
    {
        $this->withViewErrors([]);

        $html = $this->blade('<x-ig::input type="date" name="starts_at" value="2026-09-23">Starts</x-ig::input>');

        $html->assertDontSee('data-empty', false);
    }

    public function test_empty_text_input_is_not_marked_empty()
    {
        $this->withViewErrors([]);

        $html = $this->blade('<x-ig::input type="text" name="title" value="">Title</x-ig::input>');

        $html->assertDontSee('data-empty', false);
    }

    public function test_tooltip_marker_follows_the_label_text()
    {
        $this->withViewErrors([]);

        $html = $this->blade('<x-ig::input type="text" name="title" tooltip="Not printed on the card">Title</x-ig::input>');

        $html->assertSeeInOrder(['<label for="title">', 'Title', 'class="input-tooltip"', 'data-bs-title="Not printed on the card"', 'fa-regular fa-circle-question', '</label>'], false);
    }

    public function test_checkbox_tooltip_marker_follows_the_label_text()
    {
        $this->withViewErrors([]);

        $html = $this->blade('<x-ig::input type="checkbox" name="agree" tooltip="Required once">Agree</x-ig::input>');

        $html->assertSeeInOrder(['Agree', 'class="input-tooltip"', 'data-bs-title="Required once"', '</label>'], false);
    }

    public function test_input_without_tooltip_has_no_marker()
    {
        $this->withViewErrors([]);

        $html = $this->blade('<x-ig::input type="text" name="title">Title</x-ig::input>');

        $html->assertDontSee('input-tooltip', false);
    }

    public function test_select_arrow_is_outside_the_label()
    {
        $this->withViewErrors([]);

        $html = $this->blade('<x-ig::input type="select" name="lang" :options="[\'cs\', \'en\']">Language</x-ig::input>');

        $html->assertSee('<label for="lang">Language</label>', false);
        $html->assertSee('<span class="select-arrow" aria-hidden="true">▼</span>', false);
    }

    public function test_only_a_text_like_field_gets_a_clear_button()
    {
        $this->withViewErrors([]);

        $this->blade('<x-ig::input type="text" name="title">Title</x-ig::input>')
            ->assertSee('x-data="clearable"', false);
        $this->blade('<x-ig::input type="select" name="lang" :options="[\'cs\']">Language</x-ig::input>')
            ->assertDontSee('clearable', false);
    }
}
