@props([
    'method' => 'POST',
    'recaptcha' => true,
])
<form method="{{ $method }}" {{ $attributes }}>
    @csrf
    @if ($recaptcha)
        {!! RecaptchaV3::field('store') !!}
    @endif
    {{ $slot }}
</form>
