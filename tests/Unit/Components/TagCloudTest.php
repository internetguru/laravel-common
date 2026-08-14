<?php

namespace Tests\Unit\Components;

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

    public function test_neighbouring_tags_are_spread_across_the_colour_wheel()
    {
        $html = $this->blade('<x-ig::tag-cloud :tags="[\'One\', \'Two\', \'Three\']" />');

        $html->assertSee('--tag-hue: 0', false);
        $html->assertSee('--tag-hue: 138', false);
        $html->assertSee('--tag-hue: 275', false);
    }

    public function test_renders_nothing_without_tags()
    {
        $html = $this->blade('<x-ig::tag-cloud />');

        $this->assertSame('', trim($html->__toString()));
    }
}
