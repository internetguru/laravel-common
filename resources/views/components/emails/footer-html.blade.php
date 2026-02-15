@php
    $providerLink = sprintf(
        '<a href="https://%s">%s</a>',
        __('ig-common::layouts.provider.www'),
        __('ig-common::layouts.provider.name')
    );
    $rawUrl = $url ?? config('app.url');
    $url = sprintf(
        '<a href="%s?usp=gen">%s</a>',
        $rawUrl,
        $rawUrl,
    );
@endphp
<hr />
<p>
    @lang('ig-common::layouts.email.reference', ['ref' => $refNumber])<br />
    @lang('ig-common::layouts.email.generated-at', ['url' => $url])<br />
    @lang('ig-common::layouts.email.generator', ['generator' => InternetGuru\LaravelCommon\Support\Helpers::getAppInfo()])<br />
    @lang('ig-common::layouts.email.requested-from', ['ip' => $ip, 'timezone' => $timezone ?? 'n/a'])
    @if ($noreplyMessage)
        <br />{{ $noreplyMessage }}
    @endif
</p>
<p>
    @lang('ig-common::layouts.provider', ['link' => $providerLink, 'year' => date('Y')])
</p>
