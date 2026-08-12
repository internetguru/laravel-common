@php
    $attributes = $attributes->class([
        'card-row',
        'card-row-grid' => ! $isCarousel,
        'card-row-tinted' => $isTinted,
        'card-row-narrow' => $size === 'narrow',
        'card-row-wide' => $size === 'wide',
        'card-row-centered' => $isCentered,
    ]);

    if ($tints !== []) {
        $attributes = $attributes->style($tints);
    }
@endphp

<div
    {{ $attributes }}
    @if ($isCarousel)
        x-data="cardRow"
        x-on:resize.window.debounce.100="update()"
        x-bind:class="{
            'card-row-shadow-start': overflowing && !atStart,
            'card-row-shadow-end': overflowing && !atEnd,
        }"
    @endif
    role="group"
    aria-label="{{ $label }}"
>
    <div
        class="card-row-track"
        @if ($isCarousel)
            x-ref="track"
            x-on:scroll.passive="onScroll()"
            tabindex="0"
            x-on:keydown.left.prevent="page(-1)"
            x-on:keydown.right.prevent="page(1)"
        @endif
    >
        {{ $slot }}
    </div>

    @if ($isCarousel)
        <div class="card-row-nav" x-show="overflowing" x-cloak>
            <button
                type="button"
                class="card-row-btn"
                aria-label="{{ __('ig-common::layouts.card_row.previous') }}"
                x-bind:disabled="atStart"
                x-on:click="page(-1)"
            >
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button
                type="button"
                class="card-row-btn"
                aria-label="{{ __('ig-common::layouts.card_row.next') }}"
                x-bind:disabled="atEnd"
                x-on:click="page(1)"
            >
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    @endif
</div>
