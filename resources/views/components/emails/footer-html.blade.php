<hr />
@php
    $providerLink = sprintf(
        '<a href="https://%s">%s</a>',
        __('layouts.provider.www'),
        __('layouts.provider.name')
    );
@endphp
<p>
    <x-ig-common::emails.service />
    @lang('layouts.provider', ['link' => $providerLink, 'year' => date('Y')])
</p>
