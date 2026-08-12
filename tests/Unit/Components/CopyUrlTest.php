<?php

namespace Tests\Unit\Components;

use InternetGuru\LaravelCommon\View\Components\CopyUrl;
use Tests\TestCase;

class CopyUrlTest extends TestCase
{
    public function test_defaults_to_the_current_url()
    {
        $component = new CopyUrl;

        $this->assertSame(url()->full(), $component->url);
    }

    public function test_renders_link_with_the_given_url_and_error_message()
    {
        $html = $this->blade('<x-ig::copy-url url="https://example.com/page" />');

        $html->assertSee('data-testid="copy-url"', false);
        $html->assertSee('href="https://example.com/page"', false);
        $html->assertSee(__('ig-common::layouts.copy_url.link'));
        $html->assertSee(__('ig-common::layouts.copy_url.error'));
    }

    public function test_renders_both_the_default_and_the_copied_icon()
    {
        $html = $this->blade('<x-ig::copy-url />');

        $html->assertSee('fa-copy" x-show="! copied"', false);
        $html->assertSee('fa-check" x-show="copied"', false);
    }

    public function test_icons_can_be_omitted()
    {
        $html = $this->blade('<x-ig::copy-url icon="" />');

        $html->assertDontSee('<i class=', false);
    }

    public function test_slot_overrides_the_link_text()
    {
        $html = $this->blade('<x-ig::copy-url>Duplicate the link</x-ig::copy-url>');

        $html->assertSee('Duplicate the link');
        $html->assertDontSee(__('ig-common::layouts.copy_url.link'));
    }
}
