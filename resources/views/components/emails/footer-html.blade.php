@php
    $providerLink = sprintf(
        '<a href="https://%s">%s</a>',
        __('ig-common::layouts.provider.www'),
        __('ig-common::layouts.provider.name')
    );
    $url = sprintf(
        '<a href="%s">%s</a>',
        session('currentPage') ?? '-',
        session('currentPage') ?? '-',
    );
@endphp
<hr />
<p>
    <br />
    @lang('ig-common::layouts.email.generated-at', ['url' => $url])<br />
    @lang('ig-common::layouts.email.generator', ['generator' => InternetGuru\LaravelCommon\Support\Helpers::getAppInfo()])<br />
    @lang('ig-common::layouts.email.requested-from', ['ip' => $ip, 'timezone' => $timezone ?? 'n/a'])<br />
</p>
<p>
    @lang('ig-common::layouts.provider', ['link' => $providerLink, 'year' => date('Y')])
</p>
