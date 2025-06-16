@php
    $body = urlencode (
        __('ig-common::layouts.support.header') . "\n\n\n"
        . __('ig-common::layouts.support.footer') . "\n"
        . Helpers::getAppInfo() . "\n"
        . url()->current() . "\n"
    );
    $subject = urlencode(__('ig-common::layouts.support.subject'));
@endphp
<a href="mailto:@lang('layouts.provider.email')?body={{ $body }}&subject={{ $subject }}" data-testid="support-link">
    @lang('ig-common::layouts.support.link')
</a>
