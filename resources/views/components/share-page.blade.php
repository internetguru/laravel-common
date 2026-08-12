@php
    $modalId = 'share-page-' . substr(md5($url . $title), 0, 12);
@endphp

<span data-testid="share-page">
    <a
        href="javascript:void(0)"
        data-testid="share-page-link"
        onclick="window.igModal.open('{{ $modalId }}'); return false;"
        {{ $attributes->merge(['class' => $icon ? 'link-ico' : '']) }}
    >@if ($icon)<i class="{{ $icon }}"></i>@endif{{ $slot->isNotEmpty() ? $slot : __('ig-common::layouts.share.link') }}</a>

    <x-ig::modal :id="$modalId" :title="$title" centered data-testid="share-page-modal">
        <div class="text-center">
            {!! $svg !!}
        </div>
        <div class="mt-4">
            <x-ig::copy-url :url="$url" class="text-nowrap link-ico" />
        </div>
        {{-- On its own line and wrapping, so long URLs stay readable in full. --}}
        <div class="text-break mt-2" style="word-break: break-all;" data-testid="share-page-url">{{ $url }}</div>
    </x-ig::modal>
</span>
