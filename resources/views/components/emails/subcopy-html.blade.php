@props([
    'link',
    'text',
])
@php
    $link = strpos($link, '?') === false ? $link . '?usp=plain' : $link . '&usp=plain';
@endphp

<hr />

<p>
    @lang('ig-common::messages.email.trouble', ['actionText' => $text])<br>
    <span class="break-all">
        <a href="{{ $link }}">{{ $link }}</a>
    </span>
<p>
