@props([
    'method' => 'POST',
    'recaptcha' => app(\InternetGuru\LaravelRecaptchaV3\RecaptchaV3::class)->isEnabled(),
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

    $recaptchaAction = str_replace(['.', '-'], '_', $testid) . '_' . substr(uniqid(), -6);
@endphp

<form method="{{ $method }}" {{ $attributes }} data-testid="form-{{ $testid }}">
    @csrf
    @if ($recaptcha)
        @recaptchaField($recaptchaAction)
        @recaptchaScript($recaptchaAction)
    @endif
    {{ $slot }}
</form>
