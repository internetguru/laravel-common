@props([
    'link',
    'text',
])

<hr />

<p>
    @lang('ig-common::messages.email.trouble', ['actionText' => $text])<br>
    <span class="break-all">
        <a href="{{ $link }}">{{ $link }}</a>
    </span>
<p>
