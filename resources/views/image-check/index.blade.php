@php($sample = 'data:image/png;base64,' . \InternetGuru\LaravelCommon\Http\Middleware\ReportImageProblems::SAMPLE_PNG)
<div class="image-check">
    <p>{{ __('ig-common::layouts.image-check.intro') }}</p>
    <ul>
        <li>{{ __('ig-common::layouts.image-check.ok') }}<br><img src="{{ $sample }}" width="80" height="80" alt="{{ __('ig-common::layouts.image-check.sample') }}" data-testid="image-check-ok"></li>
        <li>{{ __('ig-common::layouts.image-check.low') }}<br><img src="{{ $sample }}" width="80" height="80" alt="" data-testid="image-check-low"></li>
        <li>{{ __('ig-common::layouts.image-check.medium') }}<br><img src="{{ $sample }}" width="80" height="80" data-testid="image-check-medium"></li>
        <li>{{ __('ig-common::layouts.image-check.severe') }}<br><img src="{{ $sample }}" alt="{{ __('ig-common::layouts.image-check.sample') }}" data-testid="image-check-severe"></li>
        <li>{{ __('ig-common::layouts.image-check.critical') }}<br><img src="{{ route('image-check.sample') }}" width="80" height="80" alt="{{ __('ig-common::layouts.image-check.sample') }}" data-testid="image-check-critical"></li>
    </ul>
</div>
