@props([
    'link',
])
@php
    $link = strpos($link, '?') === false ? $link . '?usp=plain' : $link . '&usp=plain';
@endphp

-- {{ $slot }}
{{ $link }}
