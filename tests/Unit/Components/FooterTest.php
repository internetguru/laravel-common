<?php

namespace Tests\Unit\Components;

use InternetGuru\LaravelCommon\View\Components\Footer;
use Tests\TestCase;

class FooterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('languages', ['en' => 'English', 'cs' => 'Česky']);
    }

    public function test_defaults_are_taken_from_provider_translations()
    {
        $footer = new Footer;

        $this->assertSame(__('ig-common::layouts.provider.email'), $footer->feedbackEmail);
        $this->assertSame(__('ig-common::layouts.provider.name'), $footer->feedbackName);
        $this->assertSame(__('ig-common::layouts.support.link'), $footer->feedbackTitle);
        $this->assertSame(
            config('app.name') . ' ' . __('ig-common::layouts.support.subject'),
            $footer->feedbackSubject
        );
        $this->assertNull($footer->complaintsEmail);
    }

    public function test_default_feedback_fields_are_empty_without_the_feedback_package()
    {
        $footer = new Footer;

        $this->assertFalse($footer->hasFeedback);
        $this->assertSame([], $footer->feedbackFields);
    }

    public function test_explicit_feedback_fields_are_used_as_is()
    {
        $fields = [['name' => 'message', 'required' => true], ['name' => 'attachments']];

        $footer = new Footer(feedbackFields: $fields);

        $this->assertSame($fields, $footer->feedbackFields);
    }

    public function test_default_complaints_fields_omit_location_without_locations()
    {
        $footer = new Footer(complaintsEmail: 'complaints@example.com');

        $this->assertSame(
            ['occurred_at', 'message', 'email'],
            array_column($footer->complaintsFields, 'name')
        );
        $this->assertSame(config('app.name'), $footer->complaintsName);
    }

    public function test_default_complaints_fields_include_location_select_with_locations()
    {
        $footer = new Footer(
            complaintsEmail: 'complaints@example.com',
            complaintsLocations: ['Restaurant A', 'Restaurant B'],
        );

        $location = $footer->complaintsFields[0];

        $this->assertSame('location', $location['name']);
        $this->assertTrue($location['required']);
        $this->assertSame(
            ['', 'Restaurant A', 'Restaurant B'],
            array_column($location['options'], 'id')
        );
        $this->assertSame(__('ig-common::layouts.complaints.please_select'), $location['options'][0]['name']);
    }

    public function test_explicit_complaints_fields_are_used_as_is()
    {
        $fields = [['name' => 'message', 'required' => true]];

        $footer = new Footer(complaintsEmail: 'complaints@example.com', complaintsFields: $fields);

        $this->assertSame($fields, $footer->complaintsFields);
    }

    public function test_renders_tools_and_copyright()
    {
        $html = $this->blade('<x-ig::footer />');

        $html->assertSee('data-testid="footer"', false);
        $html->assertSee('data-testid="share-page"', false);
        $html->assertSee('data-testid="lang-switch"', false);
        $html->assertSee(__('ig-common::layouts.provider.name'));
    }

    public function test_the_copyright_line_carries_the_years_without_a_copyright_sign()
    {
        $html = $this->blade('<x-ig::footer />');

        $html->assertSee('2022–' . date('Y'), false);
        $html->assertDontSee('©', false);
        $html->assertSee(__('ig-common::layouts.provider.name'));
    }

    public function test_inquiry_defaults_are_taken_from_provider_translations()
    {
        $footer = new Footer;

        $this->assertSame(__('ig-common::layouts.provider.email'), $footer->inquiryEmail);
        $this->assertSame(__('ig-common::layouts.provider.name'), $footer->inquiryName);
        $this->assertSame(__('ig-common::layouts.inquiry.title'), $footer->inquiryTitle);
        $this->assertSame(__('ig-common::layouts.inquiry.link'), $footer->inquiryLink);
        $this->assertSame(__('ig-common::layouts.inquiry.description'), $footer->inquiryDescription);
        $this->assertSame(
            config('app.name') . ' ' . __('ig-common::layouts.inquiry.subject'),
            $footer->inquirySubject
        );
    }

    public function test_default_inquiry_fields_are_empty_without_the_feedback_package()
    {
        $footer = new Footer;

        $this->assertFalse($footer->hasFeedback);
        $this->assertSame([], $footer->inquiryFields);
    }

    public function test_explicit_inquiry_fields_are_used_as_is()
    {
        $fields = [['name' => 'fullname', 'required' => true]];

        $footer = new Footer(inquiryFields: $fields);

        $this->assertSame($fields, $footer->inquiryFields);
    }

    public function test_tools_can_be_disabled()
    {
        $html = $this->blade('<x-ig::footer :share="false" :lang-switch="false" />');

        $html->assertDontSee('data-testid="share-page"', false);
        $html->assertDontSee('data-testid="lang-switch"', false);
    }

    public function test_share_and_provider_links_have_an_icon()
    {
        $html = $this->blade('<x-ig::footer />');

        $html->assertSee('<i class="fa-solid fa-fw fa-share-nodes"></i>', false);
        $html->assertSee('provider-ico', false);
        $html->assertSee('class="fa-group"', false);
        $html->assertSee('class="fa-secondary"', false);
    }

    public function test_the_inquiry_link_is_omitted_without_the_feedback_package()
    {
        $html = $this->blade('<x-ig::footer />');

        $html->assertDontSee('data-testid="footer-inquiry-link"', false);
        $html->assertDontSee(__('ig-common::layouts.inquiry.link'));
    }

    public function test_slot_content_is_rendered()
    {
        $html = $this->blade('<x-ig::footer>Custom footer content</x-ig::footer>');

        $html->assertSee('Custom footer content');
    }

    public function test_generated_line_is_opt_in()
    {
        $this->app['config']->set('app.display_timezone', 'Europe/Prague');

        $this->blade('<x-ig::footer />')
            ->assertDontSee('data-testid="footer-generated"', false);

        $this->blade('<x-ig::footer :generated="true" />')
            ->assertSee('data-testid="footer-generated"', false)
            ->assertSee('Europe/Prague');
    }

    public function test_feedback_forms_are_omitted_without_the_feedback_package()
    {
        $html = $this->blade('<x-ig::footer :complaints-email="$email" />', ['email' => 'complaints@example.com']);

        $this->assertFalse((new Footer)->hasFeedback);
        $html->assertDontSee('ig-feedback', false);
        $html->assertDontSee(__('ig-common::layouts.complaints.link'));
        $html->assertDontSee(__('ig-common::layouts.support.link'));
    }

    public function test_feedback_field_definitions_are_skipped_without_the_feedback_package()
    {
        $this->assertNull(config('ig-feedback.names.location'));
        $this->assertNull(config('ig-feedback.names.occurred_at'));
    }
}
