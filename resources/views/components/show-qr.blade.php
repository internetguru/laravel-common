<span x-data="{ open: false }" data-testid="show-qr">
    <a
        href="javascript:void(0)"
        data-testid="show-qr-link"
        x-on:click.prevent="open = true"
        {{ $attributes }}
    >@if ($icon)<i class="{{ $icon }}"></i> @endif{{ $slot->isNotEmpty() ? $slot : __('ig-common::layouts.qr.link') }}</a>

    <template x-teleport="body">
        <div x-show="open" x-cloak>
            <div
                class="modal fade show"
                tabindex="-1"
                aria-modal="true"
                role="dialog"
                style="display: block;"
                data-testid="show-qr-modal"
                x-on:keydown.escape.window="open = false"
                x-on:click.self="open = false"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="modal-title">{{ $title }}</h5>
                                <button
                                    type="button"
                                    class="btn-close"
                                    aria-label="{{ __('ig-common::layouts.close') }}"
                                    x-on:click="open = false"
                                ></button>
                            </div>
                            <div class="text-center">
                                {!! $svg !!}
                                <p class="mb-0 text-break"><small>{{ $url }}</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        </div>
    </template>
</span>
