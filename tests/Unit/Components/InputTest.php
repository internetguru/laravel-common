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
}
