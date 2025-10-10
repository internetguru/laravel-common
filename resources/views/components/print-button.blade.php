@props([
    'selector' => '#print-content',
    'text' => __('ig-common::layouts.print'),
    'icon' => 'fa-solid fa-fw fa-print',
    'buttonClass' => 'btn btn-ico btn-outline-primary',
])

<button
    x-data="print"
    x-on:click="printElement('{{ $selector }}')"
    {{ $attributes->merge(['class' => $buttonClass]) }}
>
    <i class="{{ $icon }}"></i>{{ $text }}
</button>
