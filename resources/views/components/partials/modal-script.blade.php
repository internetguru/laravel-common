@once
    {{--
        Generic show/hide for modals whose markup is already on the page: the wrapper carries
        the `ig-modal` class and is hidden with `d-none`. Inline and framework-free, so a modal
        opens on the first click without waiting for a bundle, Alpine.js or Livewire to load.

        Every modal registers itself and is watched for class changes, so it behaves the same
        whether it was toggled here or by a Livewire re-render: the body class, the URL hash,
        the mirrored Livewire property and the bubbling `ig-modal-opened` / `ig-modal-closed`
        events all follow the wrapper.
    --}}
    <script data-testid="ig-modal-script">
        window.igModal = {
            open(id) {
                document.getElementById(id)?.classList.remove('d-none');
            },
            close(id) {
                document.getElementById(id)?.classList.add('d-none');
            },
            closeAll() {
                document.querySelectorAll('.ig-modal:not(.d-none)').forEach((modal) => this.close(modal.id));
            },
            isOpen(id) {
                const modal = document.getElementById(id);

                return !! modal && ! modal.classList.contains('d-none');
            },
            whenLivewireReady(callback) {
                if (window.Livewire) {
                    callback();

                    return;
                }
                document.addEventListener('livewire:init', callback, { once: true });
            },
            // Options: `hash` deep-links the modal, `wire` names the Livewire property
            // mirroring the open state.
            register(id, options = {}) {
                const modal = document.getElementById(id);
                if (! modal || modal.dataset.igModal) {
                    return;
                }
                modal.dataset.igModal = '1';

                let open = this.isOpen(id);
                const sync = () => {
                    if (this.isOpen(id) === open) {
                        return;
                    }
                    open = ! open;
                    document.body.classList.toggle('modal-open', !! document.querySelector('.ig-modal:not(.d-none)'));

                    if (options.hash) {
                        if (open) {
                            window.location.hash = options.hash;
                        } else if (window.location.hash === '#' + options.hash) {
                            history.replaceState(null, null, ' ');
                        }
                    }

                    if (options.wire) {
                        // Keep the server in sync so a later re-render does not undo the change.
                        this.whenLivewireReady(() => {
                            const root = modal.closest('[wire\\:id]');
                            const component = root ? window.Livewire.find(root.getAttribute('wire:id')) : null;
                            if (component && component.get(options.wire) !== open) {
                                component.set(options.wire, open, false);
                            }
                        });
                    }

                    modal.dispatchEvent(new CustomEvent(open ? 'ig-modal-opened' : 'ig-modal-closed', { bubbles: true }));
                };

                new MutationObserver(sync).observe(modal, { attributes: true, attributeFilter: ['class'] });

                if (options.hash && window.location.hash === '#' + options.hash) {
                    this.open(id);
                }
            },
        };

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                window.igModal.closeAll();
            }
        });
    </script>
@endonce
