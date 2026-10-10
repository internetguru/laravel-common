<?php

namespace Tests\Unit\Components;

use InternetGuru\LaravelCommon\Support\Palette;
use Tests\TestCase;

class TagCloudTest extends TestCase
{
    public function test_renders_the_tags_as_chips_by_default()
    {
        $html = $this->blade('<x-ig::tag-cloud :tags="[\'Research\', \'Teaching\']" />');

        $html->assertSee('class="tag-cloud"', false);
        $html->assertDontSee('tag-cloud-typography', false);
        $html->assertDontSee('x-data="tagCloud"', false);
        $html->assertDontSee('--tag-size', false);
        $html->assertSee('Research');
        $html->assertSee('Teaching');
    }

    public function test_accepts_a_comma_separated_string_and_drops_the_empty_tags()
    {
        $html = $this->blade('<x-ig::tag-cloud tags="Research, , Teaching" />');

        $html->assertSee('>Research<', false);
        $html->assertSee('>Teaching<', false);
        $this->assertSame(2, substr_count($html->__toString(), 'class="tag"'));
    }

    public function test_the_typographic_cloud_is_laid_out_by_the_alpine_component()
    {
        $html = $this->blade('<x-ig::tag-cloud typography :tags="[\'Research\']" />');

        $html->assertSee('tag-cloud tag-cloud-typography', false);
        $html->assertSee('x-data="tagCloud"', false);
        $html->assertSee('--tag-size:', false);
    }

    public function test_each_tag_takes_the_palette_colour_of_its_position()
    {
        $html = $this->blade('<x-ig::tag-cloud :tags="[\'One\', \'Two\', \'Three\']" />');

        $html->assertSee('--ig-label-dot: ' . Palette::color(0), false);
        $html->assertSee('--ig-label-dot: ' . Palette::color(1), false);
        $html->assertSee('--ig-label-dot: ' . Palette::color(2), false);
    }

    public function test_the_typographic_cloud_colours_its_words_by_the_palette_hue()
    {
        $html = $this->blade('<x-ig::tag-cloud typography :tags="[\'One\', \'Two\']" />');

        $html->assertSee('--tag-hue: ' . Palette::hue(1) . ';', false);
    }

    public function test_renders_nothing_without_tags()
    {
        $html = $this->blade('<x-ig::tag-cloud />');

        $this->assertSame('', trim($html->__toString()));
    }
}
