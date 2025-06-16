@props([
    'method' => 'POST',
    'recaptcha' => app(\InternetGuru\LaravelCommon\Contracts\ReCaptchaInterface::class)->isEnabled(),
    'testid' => null,
])

@php
    if (! $testid) {
        $action = $attributes->get('action', 'no-action');
        $route = Route::getRoutes()->match(request()->create($action));
        $testid = 'external';
        if ($route) {
            $testid = $route->getName();
        }
    }
@endphp

<form method="{{ $method }}" {{ $attributes }} data-testid="form-{{ $testid }}">
    @csrf
    @if ($recaptcha)
        {!! RecaptchaV3::field('store') !!}
    @endif
    {{ $slot }}
</form>
