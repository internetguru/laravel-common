<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use InternetGuru\LaravelFeedback\FeedbackServiceProvider;

class Footer extends Component
{
    public const FEEDBACK_FORM_ID = 'feedback-form';

    public const COMPLAINTS_FORM_ID = 'complaints-form';

    public array $feedbackFields;

    public array $complaintsFields;

    /**
     * Whether the optional internetguru/laravel-feedback package is installed.
     */
    public bool $hasFeedback;

    /**
     * @param  array<int, array<string, mixed>>|null  $feedbackFields  Technical feedback field definitions, defaults to message, attachments and email
     * @param  array<int, array<string, mixed>>|null  $complaintsFields  Complaints field definitions, defaults to location, occurred_at, message and email
     * @param  array<int, string>  $complaintsLocations  Location names rendered as a required select, omitted when empty
     */
    public function __construct(
        public ?string $feedbackEmail = null,
        public ?string $feedbackName = null,
        public ?string $feedbackTitle = null,
        public ?string $feedbackSubject = null,
        public ?string $feedbackDescription = null,
        ?array $feedbackFields = null,
        public ?string $complaintsEmail = null,
        public ?string $complaintsName = null,
        public ?string $complaintsTitle = null,
        public ?string $complaintsSubject = null,
        public ?string $complaintsDescription = null,
        ?array $complaintsFields = null,
        array $complaintsLocations = [],
        public string $feedbackIcon = '',
        public string $complaintsIcon = '',
        public bool $share = true,
        public bool $langSwitch = true,
        public bool $generated = false,
    ) {
        $this->hasFeedback = class_exists(FeedbackServiceProvider::class);

        $this->feedbackEmail = $feedbackEmail ?? __('ig-common::layouts.provider.email');
        $this->feedbackName = $feedbackName ?? __('ig-common::layouts.provider.name');
        $this->feedbackTitle = $feedbackTitle ?? __('ig-common::layouts.support.link');
        $this->feedbackSubject = $feedbackSubject ?? config('app.name') . ' ' . __('ig-common::layouts.support.subject');

        $this->complaintsName = $complaintsName ?? config('app.name');
        $this->complaintsTitle = $complaintsTitle ?? __('ig-common::layouts.complaints.link');
        $this->complaintsSubject = $complaintsSubject ?? config('app.name') . ' ' . __('ig-common::layouts.complaints.link');

        $this->feedbackFields = $feedbackFields ?? $this->defaultFeedbackFields();
        $this->complaintsFields = $complaintsFields ?? $this->defaultComplaintsFields($complaintsLocations);
    }

    /**
     * Technical feedback lets users attach screenshots of what went wrong.
     *
     * The translation keys belong to the optional feedback package, so the defaults
     * are only built when it is installed.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function defaultFeedbackFields(): array
    {
        if (! $this->hasFeedback) {
            return [];
        }

        return [
            ['name' => 'message', 'required' => true],
            ['name' => 'attachments'],
            ['name' => 'email', 'label' => __('ig-feedback::fields.email_optional')],
        ];
    }

    /**
     * @param  array<int, string>  $locations
     * @return array<int, array<string, mixed>>
     */
    protected function defaultComplaintsFields(array $locations): array
    {
        $fields = [];

        if ($locations !== []) {
            $options = [['id' => '', 'name' => __('ig-common::layouts.complaints.please_select')]];
            foreach ($locations as $location) {
                $options[] = ['id' => $location, 'name' => $location];
            }
            $fields[] = ['name' => 'location', 'required' => true, 'options' => $options];
        }

        $fields[] = ['name' => 'occurred_at'];
        $fields[] = ['name' => 'message', 'required' => true];
        $fields[] = ['name' => 'email'];

        return $fields;
    }

    public function render(): View
    {
        return view('ig-common::components.footer');
    }
}
