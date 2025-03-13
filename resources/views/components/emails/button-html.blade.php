@props([
    'link',
])

<p style="text-align: center"><a href="{{ $link }}" class="button button-primary" target="_blank" rel="noopener">{{ $slot }}</a></p>
