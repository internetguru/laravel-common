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
        // Resolve the live Messages component for a given toast element.
        // Never close over @this: it goes stale across wire:navigate.
        function igMessagesComponent(toast) {
            const root = toast.closest('[wire\\:id]');
            return root ? window.Livewire.find(root.getAttribute('wire:id')) : null;
        }

        function igInitToast(toast) {
            if (toast.dataset.igInit) {
                return;
            }
            toast.dataset.igInit = '1';

            toast.addEventListener('hidden.bs.toast', function () {
                const component = igMessagesComponent(toast);
                const index = toast.parentNode.getAttribute('data-index');
                component?.removeMessageQuietly(parseInt(index));
            });

            new Toast(toast).show();

            if (toast.classList.contains('bg-success')) {
                const component = igMessagesComponent(toast);
                const index = toast.parentNode.getAttribute('data-index');
                component?.removeMessageQuietly(parseInt(index));
            }
        }

        function igInitToasts() {
            document.querySelectorAll('.messages-wrapper .toast').forEach(igInitToast);
        }

        // Bind global listeners only once, regardless of how many times this
        // component (re)mounts across navigations.
        if (! window.igMessagesToastsBound) {
            window.igMessagesToastsBound = true;

            document.addEventListener('livewire:navigated', igInitToasts);

            Livewire.hook('morph.updated', ({ el }) => {
                if (el.classList && el.classList.contains('messages-wrapper')) {
                    setTimeout(igInitToasts, 100);
                }
            });
        }

        igInitToasts();
    </script>
@endscript
