@php
$serviceLink = sprintf(
    '<a href="https://%s" data-testid="%s">%s</a>',
    __('ig-common::layouts.provider.www'),
    'provider-link',
    __('ig-common::layouts.provider.name')
);
@endphp

@lang('ig-common::layouts.provider', ['link' => $serviceLink, 'year' => date('Y')])
