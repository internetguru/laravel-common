@if (auth()->user()?->isAdmin())
    @lang('ig-common::layouts.submit-admin')
@else
    {{ $slot }}
@endif
