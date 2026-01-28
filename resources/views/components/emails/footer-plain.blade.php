-- 

@lang('ig-common::layouts.email.generated-at', ['url' => $url ?? config('app.url')])

@lang('ig-common::layouts.email.generator', ['generator' => InternetGuru\LaravelCommon\Support\Helpers::getAppInfo()])

@lang('ig-common::layouts.email.requested-from', ['ip' => $ip, 'timezone' => $timezone ?? 'n/a'])
@if ($noreplyMessage)

{{ $noreplyMessage }}@endif


@lang('ig-common::layouts.provider', ['link' => __('ig-common::layouts.provider.www'), 'year' => date('Y')])

