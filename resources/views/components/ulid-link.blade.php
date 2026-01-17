@props([
    'url',
    'title',
    'content',
])

<a href="{{ $url }}" title="{{ $title }}">{!! $content !!}</a>
