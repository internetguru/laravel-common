@yield('content')

@lang('ig-common::messages.email.regards'),
{{ config('app.name') }}
{{ config('app.url') }}

@lang('layouts.service', ['link' => __('layouts.service.www')])

@lang('layouts.provider', ['link' => __('layouts.provider.www'), 'year' => date('Y')])
