<?php

namespace Tests\Unit\Components;

use InternetGuru\LaravelCommon\Support\QrCode;
use InternetGuru\LaravelCommon\View\Components\ShowQr;
use Tests\TestCase;

class ShowQrTest extends TestCase
{
    public function test_defaults_to_the_current_url()
    {
        $component = new ShowQr;

        $this->assertSame(url()->full(), $component->url);
        $this->assertSame(__('ig-common::layouts.qr.title'), $component->title);
        $this->assertStringStartsWith('<svg', $component->svg);
    }

    public function test_custom_url_and_title_are_used()
    {
        $component = new ShowQr(url: 'https://example.com/page', title: 'Custom title');

        $this->assertSame('https://example.com/page', $component->url);
        $this->assertSame('Custom title', $component->title);
    }

    public function test_renders_link_and_modal_with_qr_code()
    {
        $html = $this->blade('<x-ig::show-qr url="https://example.com/page" />');

        $html->assertSee('data-testid="show-qr-link"', false);
        $html->assertSee('data-testid="show-qr-modal"', false);
        $html->assertSee(__('ig-common::layouts.qr.link'));
        $html->assertSee('https://example.com/page');
        $html->assertSee('<svg', false);
    }

    public function test_slot_overrides_the_link_text()
    {
        $html = $this->blade('<x-ig::show-qr>Scan me</x-ig::show-qr>');

        $html->assertSee('Scan me');
        $html->assertDontSee(__('ig-common::layouts.qr.link'));
    }

    public function test_svg_has_no_xml_prolog_and_encodes_content()
    {
        $svg = QrCode::svg('https://example.com');

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringNotContainsString('<?xml', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }
}
