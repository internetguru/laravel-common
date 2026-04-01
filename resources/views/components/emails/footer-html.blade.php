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
        @lang('ig-common::layouts.email.reference', ['ref' => '<code>' . $refNumber . '</code>'])<br />
    @endif
    @if ($noreplyMessage)
        {{ $noreplyMessage }}<br />
    @endif
    @lang('ig-common::layouts.email.generated-at', ['url' => $domainLink])<br />
    @lang('ig-common::layouts.email.generator', ['generator' => '<code>' . InternetGuru\LaravelCommon\Support\Helpers::getAppInfo() . '</code>'])<br />
    @lang('ig-common::layouts.email.requested-from', ['ip' => '<code>' . $ip . '</code>', 'timezone' => '<code>' . ($timezone ?? 'n/a') . '</code>'])
</p>
<p>
    @lang('ig-common::layouts.provider', ['link' => $providerLink, 'year' => date('Y')])
</p>
