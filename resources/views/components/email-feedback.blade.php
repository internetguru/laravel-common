@php
    $body = urlencode (
        __('ig-common::layouts.support.header') . "\n\n\n"
        . __('ig-common::layouts.support.footer') . "\n"
        . Helpers::getAppInfo() . "\n"
        . url()->current() . "\n"
    );
    $subject = urlencode(__('ig-common::layouts.support.subject'));
@endphp
<a href="mailto:@lang('layouts.provider.email')?body={{ $emailBody }}&subject={{ $subject }}">@lang('ig-common::layouts.support.link')</a>
