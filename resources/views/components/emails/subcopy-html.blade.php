@props([
    'link',
    'text',
])

<hr />

<p>
    @lang('emails.trouble', ['actionText' => $text])<br>
    <span class="break-all">
        <a href="{{ $link }}">{{ $link }}</a>
    </span>
<p>
