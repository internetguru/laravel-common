<span x-data="{ open: false }" data-testid="share-page">
    <a
        href="javascript:void(0)"
        data-testid="share-page-link"
        x-on:click.prevent="open = true"
        {{ $attributes->merge(['class' => $icon ? 'link-ico' : '']) }}
    >@if ($icon)<i class="{{ $icon }}"></i>@endif{{ $slot->isNotEmpty() ? $slot : __('ig-common::layouts.share.link') }}</a>

    <template x-teleport="body">
        <div x-show="open" x-cloak>
            <div
                class="modal fade show"
                tabindex="-1"
                aria-modal="true"
                role="dialog"
                style="display: block;"
                data-testid="share-page-modal"
                x-on:keydown.escape.window="open = false"
                x-on:click.self="open = false"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="modal-title">@if ($icon)<i class="{{ $icon }}"></i> @endif{{ $title }}</h5>
                                <button
                                    type="button"
                                    class="btn-close"
                                    aria-label="{{ __('ig-common::layouts.close') }}"
                                    x-on:click="open = false"
                                ></button>
                            </div>
                            <div class="text-center">
                                {!! $svg !!}
                            </div>
                            <div class="input-group mt-4">
                                <input
                                    type="text"
                                    class="form-control"
                                    data-testid="share-page-url"
                                    readonly
                                    value="{{ $url }}"
                                    x-on:click="$event.target.select()"
                                >
                                <x-ig::copy-url :url="$url" class="btn btn-primary text-nowrap" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>
        </div>
    </template>
</span>
