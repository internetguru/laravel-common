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
    {{--
        iOS positions `fixed` elements against the layout viewport, so as soon as the
        visual viewport is zoomed or shifted — a pinch, or Safari zooming in on a focused
        field — the dialog and its backdrop cover only part of what the reader can see and
        look shifted up the screen. These custom properties, kept in sync below, pin both
        to the visible area instead. Without `visualViewport` they stay unset and the
        Bootstrap defaults apply.
    --}}
    <style data-testid="ig-modal-style">
        .ig-modal .modal,
        .ig-modal .modal-backdrop {
            top: var(--ig-modal-top, 0);
            left: var(--ig-modal-left, 0);
            width: var(--ig-modal-width, 100vw);
            height: var(--ig-modal-height, 100vh);
        }
    </style>

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
            // Pin the open modal to the visible area rather than to the layout viewport,
            // which is where `position: fixed` puts it once the page is zoomed.
            syncViewport() {
                const root = document.documentElement;
                const viewport = window.visualViewport;
                const offsets = viewport && document.querySelector('.ig-modal:not(.d-none)')
                    ? {
                        '--ig-modal-top': viewport.offsetTop + 'px',
                        '--ig-modal-left': viewport.offsetLeft + 'px',
                        '--ig-modal-width': viewport.width + 'px',
                        '--ig-modal-height': viewport.height + 'px',
                    }
                    : null;

                ['--ig-modal-top', '--ig-modal-left', '--ig-modal-width', '--ig-modal-height'].forEach((name) => {
                    if (offsets) {
                        root.style.setProperty(name, offsets[name]);
                    } else {
                        root.style.removeProperty(name);
                    }
                });
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
                    this.syncViewport();

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

                // A modal rendered open never passes through the observer above
                if (open) {
                    this.syncViewport();
                }
            },
        };

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                window.igModal.closeAll();
            }
        });

        // A dialog is rendered wherever it fits the page, which is often outside the form it
        // belongs to - and there the browser never offers implicit submission, so Enter in a
        // field does nothing. Send it to the dialog's own submit button instead.
        //
        // A dialog that sits inside its form is left to the browser: nothing is prevented
        // here unless a button was actually found, so native submission still applies.
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' || event.defaultPrevented || event.isComposing) {
                return;
            }
            const field = event.target;
            // Enter belongs to the control itself on these: a newline, a press, a choice.
            if (! (field instanceof HTMLInputElement) || ['checkbox', 'radio', 'button', 'submit', 'reset'].includes(field.type)) {
                return;
            }
            const modal = field.closest('.ig-modal');
            if (! modal || modal.classList.contains('d-none')) {
                return;
            }
            const submit = modal.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');
            if (! submit) {
                return;
            }
            event.preventDefault();
            submit.click();
        });

        if (window.visualViewport) {
            const syncViewport = () => window.igModal.syncViewport();
            window.visualViewport.addEventListener('resize', syncViewport);
            window.visualViewport.addEventListener('scroll', syncViewport);
        }
    </script>
@endonce
