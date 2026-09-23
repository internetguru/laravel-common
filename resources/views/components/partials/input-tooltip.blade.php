{{-- The tooltip hangs on the span rather than on the icon: Font Awesome swaps the <i> for an <svg>
     of its own, which would strand a tooltip bound to it. A click is kept from the label around it,
     which would otherwise move focus to the field instead of showing the tooltip. --}}
<span
    class="input-tooltip"
    tabindex="0"
    role="button"
    aria-label="{{ $text }}"
    data-bs-toggle="tooltip"
    data-bs-title="{{ $text }}"
    x-data
    x-on:click.prevent
><i class="fa-solid fa-circle-info" aria-hidden="true"></i></span>
