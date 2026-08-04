@if ($hasFeedback)
    @if ($complaintsEmail)
        <livewire:ig-feedback
            :id="InternetGuru\LaravelCommon\View\Components\Footer::COMPLAINTS_FORM_ID"
            :email="$complaintsEmail"
            :name="$complaintsName"
            :subject="$complaintsSubject"
            :title="$complaintsTitle"
            :description="$complaintsDescription"
            :fields="$complaintsFields"
        />
    @endif

    <livewire:ig-feedback
        :id="InternetGuru\LaravelCommon\View\Components\Footer::FEEDBACK_FORM_ID"
        :email="$feedbackEmail"
        :name="$feedbackName"
        :subject="$feedbackSubject"
        :title="$feedbackTitle"
        :description="$feedbackDescription"
    />
@endif

<footer {{ $attributes->merge(['class' => 'container-fluid']) }} data-testid="footer">
    {{ $slot }}

    <div>
        @if ($langSwitch)
            <x-ig::lang-switch />
        @endif

        <ul class="list-inline" data-testid="footer-tools">
            @if ($share)
                <li class="list-inline-item"><x-ig::share-page /></li>
            @endif
            @if ($hasFeedback)
                @if ($complaintsEmail)
                    <li class="list-inline-item">
                        <x-ig-feedback::link
                            :form-id="InternetGuru\LaravelCommon\View\Components\Footer::COMPLAINTS_FORM_ID"
                            :class="$complaintsIcon ? 'link-ico' : ''"
                        >@if ($complaintsIcon)<i class="{{ $complaintsIcon }}"></i>@endif{{ $complaintsTitle }}</x-ig-feedback::link>
                    </li>
                @endif
                <li class="list-inline-item">
                    <x-ig-feedback::link
                        :form-id="InternetGuru\LaravelCommon\View\Components\Footer::FEEDBACK_FORM_ID"
                        :class="$feedbackIcon ? 'link-ico' : ''"
                    >@if ($feedbackIcon)<i class="{{ $feedbackIcon }}"></i>@endif{{ $feedbackTitle }}</x-ig-feedback::link>
                </li>
            @endif
        </ul>

        <ul class="list-inline">
            <li class="list-inline-item"><x-ig::footer-copy /></li>
        </ul>

        @if ($generated)
            <ul class="list-inline" data-testid="footer-generated">
                <li class="list-inline-item">@lang('ig-common::layouts.generated', [
                    'datetime' => \Carbon\Carbon::now()->toDisplayTimezone()->dateTimeForHumans(),
                    'timezone' => config('app.display_timezone'),
                ])</li>
            </ul>
        @endif
    </div>
</footer>
