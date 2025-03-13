@yield('content')

@lang('emails.regards'),
{{ config('app.name') . ' ' . settings('common.company_name') }}
{{ config('app.url') }}

@lang('layouts.service', ['link' => __('layouts.service.www')])

@lang('layouts.provider', ['link' => __('layouts.provider.www'), 'year' => date('Y')])
