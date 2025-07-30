@props([
    'method' => 'POST',
    'recaptcha' => app(\InternetGuru\LaravelCommon\Contracts\ReCaptchaInterface::class)->isEnabled(),
    'testid' => null,
])

@php
    if (! isset($attributes['action'])) {
        $testid = 'no-action';
    } elseif (! $testid) {
        try {
            $action = $attributes->get('action');
            $route = Route::getRoutes()->match(request()->create($action, $method));
            $testid = 'external';
            if ($route) {
                $testid = $route->getName();
            }
        } catch (\Exception $e) {
            // If we can't determine the route, we will use 'external' as a fallback
            $testid = 'external';
        }
    }
@endphp

<form method="{{ $method }}" {{ $attributes }} data-testid="form-{{ $testid }}">
    @csrf
    <div wire:ignore.self>
    @if ($recaptcha)
        {!! RecaptchaV3::field('store') !!}
    @endif
    </div>
    {{ $slot }}
</form>
