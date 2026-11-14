<?php

namespace Tests\Unit\Components;

use Illuminate\Support\Facades\Log;
use Illuminate\View\ViewException;
use InternetGuru\LaravelCommon\Support\Palette;
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

        $html->assertSee('<h1>Orders</h1>', false);
        $html->assertDontSee('page-heading-desc', false);
    }

    public function test_a_badge_without_a_variant_is_info()
    {
        $html = $this->blade('<x-ig::page-heading heading="Orders" badge="Note">Text</x-ig::page-heading>');

        $html->assertSee('fa-circle-info', false);
        $html->assertSee('--ig-label-dot: ' . Palette::variant('info'), false);
    }

    public function test_a_description_without_a_variant_has_no_icon()
    {
        $html = $this->blade('<x-ig::page-heading heading="Orders">Text</x-ig::page-heading>');

        $html->assertSee('<p class="ig-page-heading-desc" data-testid="page-heading-desc">Text</p>', false);
    }

    public function test_a_description_under_a_badge_without_a_variant_has_no_icon()
    {
        $html = $this->blade('<x-ig::page-heading heading="Orders" badge="Note" :tooltip="false">Text</x-ig::page-heading>');

        $html->assertSee('fa-circle-info', false);
        $html->assertSee('<p class="ig-page-heading-desc" data-testid="page-heading-desc">Text</p>', false);
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

    public function test_a_badge_slot_stands_above_the_heading_in_place_of_the_label()
    {
        $html = (string) $this->blade('<x-ig::page-heading heading="Order 1"><x-slot:badge><span class="status">Paid</span></x-slot:badge></x-ig::page-heading>');

        $this->assertLessThan(strpos($html, '<h1'), strpos($html, '<span class="status">Paid</span>'));
        $this->assertStringNotContainsString('ig-label', $html);
        $this->assertStringNotContainsString('page-heading-desc', $html);
    }

    public function test_a_badge_slot_leaves_the_description_out()
    {
        $html = $this->blade("<x-ig::page-heading heading=\"Order 1\"><x-slot:badge>Paid</x-slot:badge>\n    Paid in full.</x-ig::page-heading>");

        $html->assertDontSee('Paid in full.');
    }

    public function test_a_badge_without_tooltip_writes_the_description_below_the_heading()
    {
        $html = (string) $this->blade('<x-ig::page-heading heading="Redemption" badge="Pending" :tooltip="false">Check the account first.</x-ig::page-heading>');

        $this->assertLessThan(strpos($html, '<h1'), strpos($html, 'data-testid="page-heading-badge"'));
        $this->assertLessThan(strpos($html, 'data-testid="page-heading-desc"'), strpos($html, '<h1'));
        $this->assertStringNotContainsString('data-bs-toggle', $html);
    }

    public function test_an_htmlable_heading_keeps_its_markup()
    {
        $html = $this->blade('<x-ig::page-heading :heading="new Illuminate\Support\HtmlString(\'Reservation <tt>ABC</tt>\')" />');

        $html->assertSee('<h1>Reservation <tt>ABC</tt></h1>', false);
    }

    public function test_a_string_heading_is_escaped()
    {
        $html = $this->blade('<x-ig::page-heading heading="Fish &amp; <b>chips</b>" />');

        $html->assertDontSee('<b>chips</b>', false);
    }

    public function test_the_description_can_take_a_variant_of_its_own()
    {
        $html = $this->blade('<x-ig::page-heading heading="Redemption" badge="Pending" variant="warning" description-variant="info" :tooltip="false">Check the account first.</x-ig::page-heading>');

        $html->assertSee('--ig-label-dot: ' . Palette::variant('warning'), false);
        $html->assertSee('ig-page-heading-desc-info', false);
        $html->assertDontSee('ig-page-heading-desc-warning', false);
    }

    public function test_the_description_follows_the_badge_variant_by_default()
    {
        $html = $this->blade('<x-ig::page-heading heading="Redemption" badge="Pending" variant="warning" :tooltip="false">Check the account first.</x-ig::page-heading>');

        $html->assertSee('ig-page-heading-desc-warning', false);
    }
}
