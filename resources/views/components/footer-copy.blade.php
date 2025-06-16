@php
$serviceLink = sprintf(
    '<a href="https://%s" data-testid="%s">%s</a>',
    __('layouts.provider.www'),
    'provider-link',
    __('layouts.provider.name')
);
@endphp

@lang('layouts.provider', ['link' => $serviceLink, 'year' => date('Y')])
