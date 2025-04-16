<div class="messages-wrapper">
    <div>
        <div class="toast-wrapper" style="
            z-index: 1050;
            --bs-success-rgb: 38, 50, 56;
        ">
            <div class="toast-container">
                @foreach ($messages as $index => $message)
                    <div wire:key="message-{{ $index }}">
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
            console.log('Livewire initialized');

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
                });
            }

            function initToast(toast) {
                var bsToast = new Toast(toast);

                toast.addEventListener('hidden.bs.toast', function() {
                    // Find the index from the wire:key attribute
                    const parentEl = toast.closest('[wire\\:key^="message-"]');
                    if (parentEl) {
                        const keyAttr = parentEl.getAttribute('wire:key');
                        const index = keyAttr.replace('message-', '');
                        // Call the Livewire removeMessage method
                        @this.removeMessage(parseInt(index));
                    }
                });

                bsToast.show();
            }
        });
    </script>
@endscript
