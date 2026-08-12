<?php

namespace Tests\Unit\Components;

use InternetGuru\LaravelCommon\View\Components\Card;
use Tests\TestCase;

class CardTest extends TestCase
{
    public function test_renders_the_title_and_subtitle()
    {
        $html = $this->blade('<x-ig::card title="Reduced balls" subtitle="Prague, 2026" />');

        $html->assertSee('<h4>Reduced balls</h4>', false);
        $html->assertSee('<p class="lead">Prague, 2026</p>', false);
    }

    public function test_the_heading_level_can_be_given()
    {
        $html = $this->blade('<x-ig::card title="Reduced balls" :level="2" />');

        $html->assertSee('<h2>Reduced balls</h2>', false);
    }

    public function test_the_heading_level_is_kept_within_the_levels_that_exist()
    {
        $this->assertSame(1, (new Card(level: 0))->level);
        $this->assertSame(6, (new Card(level: 9))->level);
        $this->assertSame(4, (new Card)->level);
    }

    public function test_renders_no_heading_without_a_title()
    {
        $html = $this->blade('<x-ig::card />');

        $html->assertDontSee('<h4>', false);
        $html->assertDontSee('class="lead"', false);
        $html->assertDontSee('badge', false);
    }

    public function test_renders_a_single_badge_with_its_kind()
    {
        $html = $this->blade('<x-ig::card badge="Preprint" badge-type="preprint" />');

        $html->assertSee('<span class="badge badge-preprint">Preprint</span>', false);
    }

    public function test_renders_every_badge_of_an_array()
    {
        $html = $this->blade('<x-ig::card :badge="[\'Article\', \'Award\']" />');

        $html->assertSee('<span class="badge">Article</span>', false);
        $html->assertSee('<span class="badge">Award</span>', false);
    }

    public function test_a_link_covers_the_card_and_carries_a_default_label()
    {
        $html = $this->blade('<x-ig::card link="https://example.com/paper" />');

        $html->assertSee('card card-linked', false);
        $html->assertSee('href="https://example.com/paper"', false);
        $html->assertSee('aria-label="' . __('ig-common::layouts.card.open') . '"', false);
    }

    public function test_the_link_label_can_be_given()
    {
        $html = $this->blade('<x-ig::card link="https://example.com" link-label="Read the paper" />');

        $html->assertSee('aria-label="Read the paper"', false);
    }

    public function test_renders_no_action_without_a_link()
    {
        $html = $this->blade('<x-ig::card title="Plain" />');

        $html->assertDontSee('card-linked', false);
        $html->assertDontSee('card-action', false);
    }

    public function test_the_gray_flag_puts_the_card_on_a_grey_surface()
    {
        $html = $this->blade('<x-ig::card gray />');

        $html->assertSee('card card-gray', false);
    }

    public function test_the_gray_flag_accepts_a_string()
    {
        $this->assertTrue((new Card(gray: 'true'))->gray);
        $this->assertFalse((new Card(gray: 'false'))->gray);
        $this->assertFalse((new Card)->gray);
    }

    public function test_the_slot_and_extra_attributes_are_kept()
    {
        $html = $this->blade('<x-ig::card class="card-highlight" data-testid="card">Body</x-ig::card>');

        $html->assertSee('Body');
        $html->assertSee('card card-highlight', false);
        $html->assertSee('data-testid="card"', false);
    }
}
