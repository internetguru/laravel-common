@props([
    'link',
])

@php
    $link = strpos($link, '?') === false ? $link . '?usp=button' : $link . '&usp=button';
@endphp

<p style="text-align: center"><a href="{{ $link }}" class="button button-primary" target="_blank" rel="noopener">{{ $slot }}</a></p>
