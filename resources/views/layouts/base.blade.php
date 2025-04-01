@props([
    'view',
    'prefix' => 'common::',
    'props' => [],
    'title' => null,
])

@if ($title)
    <h1>{{ $title }}</h1>
@else
    <h1>@lang("${prefix}layouts.$view.title")</h1>
@endif

@include("$prefix$view", $props)
