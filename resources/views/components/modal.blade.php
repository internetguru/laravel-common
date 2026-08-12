<div id="{{ $id }}" {{ $attributes->merge(['class' => 'ig-modal' . ($open ? '' : ' d-none')]) }}>
    <div
        class="modal fade show"
        tabindex="-1"
        aria-modal="true"
        role="dialog"
        @if ($title) aria-labelledby="{{ $id }}-label" @endif
        style="display: block;"
        onclick="if (event.target === this) { window.igModal.close('{{ $id }}'); }"
    >
        <div class="modal-dialog{{ $centered ? ' modal-dialog-centered' : '' }}">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="modal-title" id="{{ $id }}-label">{{ $title }}</h5>
                        <button
                            type="button"
                            class="btn-close"
                            aria-label="{{ __('ig-common::layouts.close') }}"
                            onclick="window.igModal.close('{{ $id }}')"
                        ></button>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
</div>

@include('ig-common::components.partials.modal-script')

<script>window.igModal.register(@js($id), @js((object) array_filter(['hash' => $hash, 'wire' => $wireOpen])));</script>
