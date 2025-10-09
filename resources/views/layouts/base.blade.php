@props([
    'view',
    'prefix' => 'common::',
    'props' => [],
    'title' => null,
    'description' => null,
])

@php
    $title = $title ?? __("${prefix}layouts.$view.title");
    $description = $description ?? __("${prefix}layouts.$view.description");
@endphp

<h1>{{ $title }}</h1>
<p class="lead">{{ $description }}</p>

@include("$prefix$view", $props)
