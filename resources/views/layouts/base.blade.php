@props([
    'view',
    'prefix' => 'common::',
    'props' => [],
])

<h1>@lang("${prefix}layouts.$view.title")</h1>

@include("$prefix$view", $props)
