<div class="messages-wrapper">
    <div>
        <div class="toast-wrapper" style="
            z-index: 1050;
            --bs-success-rgb: 38, 50, 56;
        ">
            <div class="toast-container">
                @foreach ($messages as $index => $message)
                    <div wire:key="message-{{ time() }}" data-index="{{ $index }}">
                        <x-ig::message
                            type="{{ $message['type'] }}"
                            message="{!! $message['content'] !!}"
                            class="message-item"
                            wire:click="removeMessage({{ $index }})"
                        />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@script
    <script>
        document.addEventListener('livewire:initialized', function () {
            initToasts();

            // Re-init toasts whenever messages are updated
            Livewire.hook('morph.updated', ({ el }) => {
                if (el.classList.contains('messages-wrapper')) {
                    setTimeout(() => {
                        initToasts();
                    }, 100);
                }
            });

            function initToasts() {
                document.querySelectorAll('.toast').forEach(function (toast) {
                    initToast(toast);
                    toast.addEventListener('hidden.bs.toast', function () {
                        console.log('Toast hidden:', toast);
                        const index = toast.parentNode.getAttribute('data-index');
                        @this.removeMessageQuietly(parseInt(index));
                    });
                });
            }

            function initToast(toast) {
                var bsToast = new Toast(toast);

                if (toast.classList.contains('bg-success')) {
                    const index = toast.parentNode.getAttribute('data-index');
                    @this.removeMessageQuietly(parseInt(index));
                }

                bsToast.show();
            }
        });
    </script>
@endscript
