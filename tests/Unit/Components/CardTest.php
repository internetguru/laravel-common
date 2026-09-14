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

    public function test_a_pdf_link_is_marked_with_its_file_type()
    {
        $html = $this->blade('<x-ig::card link="https://example.com/paper.pdf" />');

        $html->assertSee('<span class="card-format" aria-hidden="true">PDF</span>', false);
        $html->assertSee('aria-label="' . __('ig-common::layouts.card.open') . ' (PDF)"', false);
    }

    public function test_the_file_type_is_read_off_the_path_rather_than_the_query_string()
    {
        $this->assertSame('PDF', (new Card(link: 'https://example.com/paper.pdf?v=2#page=3'))->format);
        $this->assertNull((new Card(link: 'https://example.com/papers?file=paper.pdf'))->format);
    }

    public function test_a_file_served_without_an_extension_is_recognised_by_its_folder()
    {
        $this->assertSame('PDF', (new Card(link: 'https://arxiv.org/pdf/2607.21166'))->format);
        $this->assertNull((new Card(link: 'https://arxiv.org/abs/2607.21166'))->format);
    }

    public function test_the_button_label_does_not_repeat_a_file_type_it_already_names()
    {
        $html = $this->blade('<x-ig::card link="https://example.com/cv.pdf" link-label="Download CV in PDF" />');

        $html->assertSee('aria-label="Download CV in PDF"', false);
        $html->assertDontSee('Download CV in PDF (PDF)', false);
    }

    public function test_the_file_type_stands_beside_the_button_rather_than_inside_it()
    {
        $html = $this->blade('<x-ig::card link="https://example.com/paper.pdf" />');

        $this->assertMatchesRegularExpression(
            '/<span class="card-format" aria-hidden="true">PDF<\/span>\s*<a class="card-action"/',
            $html->__toString()
        );
    }

    public function test_a_web_page_link_is_left_unmarked()
    {
        $html = $this->blade('<x-ig::card link="https://example.com/seminar.html" />');

        $html->assertDontSee('card-format', false);
        $html->assertSee('aria-label="' . __('ig-common::layouts.card.open') . '"', false);
    }

    public function test_the_file_type_can_be_given_or_suppressed()
    {
        $this->assertSame('SLIDES', (new Card(link: 'https://example.com/talk', format: 'SLIDES'))->format);
        $this->assertNull((new Card(link: 'https://example.com/paper.pdf', format: ''))->format);
    }

    public function test_the_slot_and_extra_attributes_are_kept()
    {
        $html = $this->blade('<x-ig::card class="card-highlight" data-testid="card">Body</x-ig::card>');

        $html->assertSee('Body');
        $html->assertSee('card card-highlight', false);
        $html->assertSee('data-testid="card"', false);
    }
}
