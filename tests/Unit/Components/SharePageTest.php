<?php

namespace Tests\Unit\Components;

use InternetGuru\LaravelCommon\Support\QrCode;
use InternetGuru\LaravelCommon\View\Components\SharePage;
use Tests\TestCase;

class SharePageTest extends TestCase
{
    public function test_defaults_to_the_current_url()
    {
        $component = new SharePage;

        $this->assertSame(url()->full(), $component->url);
        $this->assertSame(__('ig-common::layouts.share.title'), $component->title);
        $this->assertStringStartsWith('<svg', $component->svg);
    }

    public function test_custom_url_and_title_are_used()
    {
        $component = new SharePage(url: 'https://example.com/page', title: 'Custom title');

        $this->assertSame('https://example.com/page', $component->url);
        $this->assertSame('Custom title', $component->title);
    }

    public function test_renders_link_and_modal_with_qr_code_and_url_field()
    {
        $html = $this->blade('<x-ig::share-page url="https://example.com/page" />');

        $html->assertSee('data-testid="share-page-link"', false);
        $html->assertSee('data-testid="share-page-modal"', false);
        $html->assertSee(__('ig-common::layouts.share.link'));
        $html->assertSee('<svg', false);
        $html->assertSee('data-testid="share-page-url"', false);
        $html->assertSee('value="https://example.com/page"', false);
        $html->assertSee('$event.target.select()', false);
    }

    public function test_modal_contains_the_copy_link()
    {
        $html = $this->blade('<x-ig::share-page url="https://example.com/page" />');

        $html->assertSee('data-testid="copy-url"', false);
        $html->assertSee(__('ig-common::layouts.copy_url.link'));
    }

    public function test_icon_is_rendered_in_the_link_and_the_modal_title()
    {
        $html = $this->blade('<x-ig::share-page />');

        $html->assertSee('class="link-ico"', false);
        $html->assertSee('<i class="fa-solid fa-fw fa-share"></i>', false);
        $html->assertSee(
            '<h5 class="modal-title"><i class="fa-solid fa-fw fa-share"></i> ' . __('ig-common::layouts.share.title') . '</h5>',
            false
        );
    }

    public function test_link_icon_can_be_omitted()
    {
        $html = $this->blade('<x-ig::share-page icon="" />');

        $html->assertDontSee('link-ico', false);
        $html->assertDontSee('fa-share', false);
    }

    public function test_slot_overrides_the_link_text()
    {
        $html = $this->blade('<x-ig::share-page title="Custom title">Scan me</x-ig::share-page>');

        $html->assertSee('Scan me');
        $html->assertDontSee(__('ig-common::layouts.share.link'));
    }

    public function test_svg_has_no_xml_prolog_and_encodes_content()
    {
        $svg = QrCode::svg('https://example.com');

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringNotContainsString('<?xml', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }
}
