@php
$iconMarkup = match (true) {
    $icon === null => trim(view('ig-common::components.icons.seedling')->render()),
    $icon === '' => '',
    default => sprintf('<i class="%s provider-ico"></i>', $icon),
};
$serviceLink = sprintf(
    '<a href="https://%s"%s data-testid="%s">%s%s</a>',
    __('ig-common::layouts.provider.www'),
    $iconMarkup ? ' class="link-ico"' : '',
    'provider-link',
    $iconMarkup,
    __('ig-common::layouts.provider.name')
);
@endphp

@lang('ig-common::layouts.provider', ['link' => $serviceLink, 'year' => date('Y')])
