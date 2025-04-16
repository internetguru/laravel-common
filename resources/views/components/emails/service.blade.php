@php
$serviceLink = sprintf(
    '<a href="https://%s"><img src="https://%s"/>%s</a>',
    __('layouts.service.www'),
    __('layouts.service.www') . '/favicon.ico',
    __('layouts.service.name')
);
@endphp

@lang('layouts.service', ['link' => $serviceLink])<br/>
