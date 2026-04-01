@php
    $providerLink = sprintf(
        '<a href="https://%s">%s</a>',
        __('ig-common::layouts.provider.www'),
        __('ig-common::layouts.provider.name')
    );
    $domain = config('app.url');
    $domainLink = sprintf('<a href="%s?usp=gen">%s</a>', $domain, $domain);
@endphp
<hr />
<p>
    @if ($refNumber)
        @lang('ig-common::layouts.email.reference', ['ref' => '<samp>' . $refNumber . '</samp>'])<br />
    @endif
    @if ($noreplyMessage)
        {{ $noreplyMessage }}<br />
    @endif
    @lang('ig-common::layouts.email.generated-at', ['url' => $domainLink])<br />
    @lang('ig-common::layouts.email.generator', ['generator' => '<samp>' . InternetGuru\LaravelCommon\Support\Helpers::getAppInfo() . '</samp>'])<br />
    @lang('ig-common::layouts.email.requested-from', ['ip' => '<samp>' . $ip . '</samp>', 'timezone' => '<samp>' . ($timezone ?? 'n/a') . '</samp>'])
</p>
<p>
    @lang('ig-common::layouts.provider', ['link' => $providerLink, 'year' => date('Y')])
</p>
