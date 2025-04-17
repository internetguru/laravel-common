@php
$serviceLink = sprintf(
    '<a href="https://%s">%s</a>',
    __('layouts.provider.www'),
    __('layouts.provider.name')
);
@endphp

@lang('layouts.provider', ['link' => $serviceLink, 'year' => date('Y')])
