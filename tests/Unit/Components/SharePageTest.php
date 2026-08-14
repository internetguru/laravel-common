<?php

namespace Tests\Unit\Components;

use Illuminate\Support\Str;
use InternetGuru\LaravelCommon\Support\QrCode;
use InternetGuru\LaravelCommon\View\Components\SharePage;
use Tests\TestCase;

class SharePageTest extends TestCase
{
    public function test_defaults_to_the_current_url()
    {
        $component = new SharePage;

        $this->assertSame(url()->full(), $component->url);
        $this->assertSame(Str::slug(__('ig-common::layouts.share.title')), $component->id);
        $this->assertSame(__('ig-common::layouts.share.title'), $component->title);
        $this->assertStringStartsWith('<svg', $component->svg);
    }

    public function test_custom_url_and_title_are_used()
    {
        $component = new SharePage(url: 'https://example.com/page', title: 'Custom title');

        $this->assertSame('https://example.com/page', $component->url);
        $this->assertSame('Custom title', $component->title);
        $this->assertSame('custom-title', $component->id);
    }

    public function test_the_id_can_be_given()
    {
        $this->assertSame('menu-qr', (new SharePage(id: 'menu-qr'))->id);
    }

    public function test_renders_link_and_modal_with_qr_code_and_url_field()
    {
        $html = $this->blade('<x-ig::share-page url="https://example.com/page" />');

        $html->assertSee('data-testid="share-page-link"', false);
        $html->assertSee('data-testid="share-page-modal"', false);
        $html->assertSee(__('ig-common::layouts.share.link'));
        $html->assertSee('<svg', false);
        $html->assertSee('data-testid="share-page-url"', false);
        $html->assertSee('>https://example.com/page</div>', false);
    }

    public function test_modal_is_opened_with_plain_javascript_and_can_be_linked_to()
    {
        $html = $this->blade('<x-ig::share-page url="https://example.com/page" title="Share page" />');

        $html->assertSee('window.igModal.open(\'share-page\')', false);
        $html->assertSee('id="share-page"', false);
        $html->assertSee('share-page', false);
        $html->assertSee('data-testid="ig-modal-script"', false);
        $html->assertDontSee('x-teleport', false);
        $html->assertDontSee('x-show="open"', false);
    }

    public function test_modal_contains_the_copy_link()
    {
        $html = $this->blade('<x-ig::share-page url="https://example.com/page" />');

        $html->assertSee('data-testid="copy-url"', false);
        $html->assertSee('fa-copy', false);
        $html->assertSee(__('ig-common::layouts.copy_url.link'));
    }

    public function test_icon_is_rendered_in_the_link()
    {
        $html = $this->blade('<x-ig::share-page />');

        $html->assertSee('class="link-ico"', false);
        $html->assertSee('<i class="fa-regular fa-fw fa-share-from-square"></i>', false);
        $html->assertSee('class="modal-title"', false);
    }

    public function test_link_icon_can_be_omitted()
    {
        $html = $this->blade('<x-ig::share-page icon="" />');

        $html->assertSee('data-testid="share-page-link"', false);
        $html->assertDontSee('fa-share-from-square', false);
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

    public function test_svg_truncates_oversized_content()
    {
        $svg = QrCode::svg('https://example.com/?q='.str_repeat('a', 5000));

        $this->assertStringStartsWith('<svg', $svg);
    }

    public function test_component_renders_with_an_oversized_url()
    {
        $component = new SharePage(url: 'https://example.com/?q='.str_repeat('a', 5000));

        $this->assertStringStartsWith('<svg', $component->svg);
    }
}
