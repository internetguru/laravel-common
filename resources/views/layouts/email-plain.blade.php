@yield('content')

<x-ig-common::emails.salutation-plain />

<x-ig-common::emails.service-plain />

@lang('layouts.provider', ['link' => __('layouts.provider.www'), 'year' => date('Y')])
