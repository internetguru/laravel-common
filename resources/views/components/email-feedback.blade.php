@php
    $emailBody = rawurlencode(__('ig-common::layouts.support.body', [
        'version' => Helpers::getAppInfo(),
        'url' => url()->current(),
    ]));
@endphp
<a href="mailto:@lang('layouts.provider.email')?body={{ $emailBody }}&subject=@lang('ig-common::layouts.support.subject')">@lang('ig-common::layouts.support.link')</a>
