@php
    $providerLink = sprintf(
        '<a href="https://%s">%s</a>',
        __('ig-common::layouts.provider.www'),
        __('ig-common::layouts.provider.name')
    );
@endphp
<hr />
<p>
    @lang('ig-common::layouts.provider', ['link' => $providerLink, 'year' => date('Y')])
</p>
