@props([
    'method' => 'POST',
    'recaptcha' => app(\InternetGuru\LaravelCommon\Contracts\ReCaptchaInterface::class)->isEnabled(),
])
<form method="{{ $method }}" {{ $attributes }}>
    @csrf
    @if ($recaptcha)
        {!! RecaptchaV3::field('store') !!}
    @endif
    {{ $slot }}
</form>
