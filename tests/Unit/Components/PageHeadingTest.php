<?php

namespace Tests\Unit\Components;

use Illuminate\Support\Facades\Log;
use Illuminate\View\ViewException;
use Tests\TestCase;

class PageHeadingTest extends TestCase
{
    public function test_a_badge_stands_above_the_heading_with_the_description_as_its_tooltip()
    {
        $html = (string) $this->blade('<x-ig::page-heading heading="Orders" badge="Restricted" variant="warning">You see your own &amp; related orders.</x-ig::page-heading>');

        $this->assertLessThan(strpos($html, '<h1'), strpos($html, 'data-testid="page-heading-badge"'));
        $this->assertStringContainsString('data-bs-toggle="tooltip"', $html);
        $this->assertStringContainsString('data-bs-title="You see your own &amp; related orders."', $html);
        $this->assertStringContainsString('fa-triangle-exclamation', $html);
        $this->assertStringNotContainsString('page-heading-desc', $html);
    }

    public function test_a_badge_without_a_description_has_no_tooltip()
    {
        $html = $this->blade('<x-ig::page-heading heading="Orders" badge="Restricted" />');

        $html->assertSee('Restricted');
        $html->assertDontSee('data-bs-toggle', false);
    }

    public function test_without_a_badge_the_description_follows_the_heading()
    {
        $html = (string) $this->blade('<x-ig::page-heading heading="Orders" variant="success">All paid.</x-ig::page-heading>');

        $this->assertLessThan(strpos($html, 'data-testid="page-heading-desc"'), strpos($html, '<h1'));
        $this->assertStringContainsString('ig-page-heading-desc-success', $html);
        $this->assertStringNotContainsString('page-heading-badge', $html);
    }

    public function test_an_empty_description_renders_the_heading_only()
    {
        $html = $this->blade("<x-ig::page-heading heading=\"Orders\">\n    </x-ig::page-heading>");

        $html->assertSee('>Orders</h1>', false);
        $html->assertDontSee('page-heading-desc', false);
    }

    public function test_the_variant_defaults_to_info()
    {
        $badge = $this->blade('<x-ig::page-heading heading="Orders" badge="Note">Text</x-ig::page-heading>');
        $desc = $this->blade('<x-ig::page-heading heading="Orders">Text</x-ig::page-heading>');

        $badge->assertSee('fa-circle-info', false);
        $desc->assertSee('ig-page-heading-desc-info', false);
    }

    public function test_an_icon_replaces_the_badge_icon_of_the_variant()
    {
        $html = $this->blade('<x-ig::page-heading heading="Orders" badge="Restricted" variant="warning" icon="fa-solid fa-shield-halved" />');

        $html->assertSee('fa-shield-halved', false);
        $html->assertDontSee('fa-triangle-exclamation', false);
    }

    public function test_an_unknown_variant_is_an_error_in_debug_mode()
    {
        config(['app.debug' => true]);

        $this->expectException(ViewException::class);

        $this->blade('<x-ig::page-heading heading="Orders" variant="nope">Text</x-ig::page-heading>');
    }

    public function test_an_unknown_variant_falls_back_to_info_outside_debug_mode()
    {
        config(['app.debug' => false]);
        Log::spy();

        $html = $this->blade('<x-ig::page-heading heading="Orders" variant="nope">Text</x-ig::page-heading>');

        $html->assertSee('ig-page-heading-desc-info', false);
        Log::shouldHaveReceived('warning')->once();
    }
}
