@yield('content')

<x-ig-common::emails.salutation-plain />

@lang('layouts.service', ['link' => __('layouts.service.www')])

@lang('layouts.provider', ['link' => __('layouts.provider.www'), 'year' => date('Y')])
