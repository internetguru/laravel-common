@once
    {{--
        Generic show/hide for modals whose markup is already on the page: the wrapper carries
        the `ig-modal` class and is hidden with `d-none`. Inline and framework-free, so a modal
        opens on the first click without waiting for a bundle, Alpine.js or Livewire to load.

        Every modal registers itself and is watched for class changes, so it behaves the same
        whether it was toggled here or by a Livewire re-render: the body class, the URL hash,
        the mirrored Livewire property, the keyboard focus and the bubbling `ig-modal-opened` /
        `ig-modal-closed` events all follow the wrapper.
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

        /* The dialog layer covers the screen, so no scroll over it reaches the page behind. */
        .ig-modal .modal {
            overscroll-behavior: contain;
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
            returnTargets: new WeakMap(),
            focusInside(modal) {
                if (modal.contains(document.activeElement)) {
                    return;
                }
                const autofocus = this.focusables(modal).find((element) => element.hasAttribute('autofocus'));
                if (autofocus) {
                    autofocus.focus();
                } else {
                    // The dialog rather than its first field, which would open a phone's keyboard
                    modal.querySelector('.modal')?.focus({ preventScroll: true });
                }
            },
            restoreFocus(modal) {
                const active = document.activeElement;
                const stranded = ! active || active === document.body || !! active.closest('.ig-modal.d-none');
                if (! stranded) {
                    return;
                }
                let target = this.returnTargets.get(modal);
                const visited = new Set([modal]);
                // A target inside a modal closed along with this one hands over to that modal's own target
                for (let closed = target?.closest?.('.ig-modal.d-none'); closed && ! visited.has(closed); closed = target?.closest?.('.ig-modal.d-none')) {
                    visited.add(closed);
                    target = this.returnTargets.get(closed);
                }
                if (target?.isConnected && target !== document.body) {
                    target.focus();
                }
            },
            focusables(modal) {
                const candidates = [...modal.querySelectorAll('a[href], area[href], button, input, select, textarea, summary, iframe, audio[controls], video[controls], [tabindex], [contenteditable]:not([contenteditable="false"])')]
                    .filter((element) => ! element.matches(':disabled')
                        && element.tabIndex >= 0
                        && element.type !== 'hidden'
                        && ! element.closest('[hidden], .d-none, [inert]')
                        && (element.checkVisibility?.({ visibilityProperty: true }) ?? true));

                // Tab stops at one radio of a group: the checked one, else the first
                return candidates.filter((element) => {
                    if (element.type !== 'radio' || ! element.name) {
                        return true;
                    }
                    const group = candidates.filter((other) => other.type === 'radio' && other.name === element.name && other.form === element.form);

                    return element === (group.find((radio) => radio.checked) ?? group[0]);
                });
            },
            // Where Tab has to send the focus to keep it inside the modal, or null to let the browser move it
            wrapTarget(modal, backwards) {
                const dialog = modal.querySelector('.modal');
                const focusables = this.focusables(modal);
                const first = focusables[0];
                const last = focusables[focusables.length - 1];
                const active = document.activeElement;
                if (! first) {
                    return dialog;
                }
                if (! modal.contains(active)) {
                    return backwards ? last : first;
                }
                if (backwards && (active === first || active === dialog)) {
                    return last;
                }
                if (! backwards && active === last) {
                    return first;
                }

                return null;
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
                    // Read before the hash is set: a fragment navigation clears the focus
                    const focused = document.activeElement;
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

                    if (open) {
                        this.returnTargets.set(modal, focused);
                        this.focusInside(modal);
                    } else {
                        this.restoreFocus(modal);
                    }

                    modal.dispatchEvent(new CustomEvent(open ? 'ig-modal-opened' : 'ig-modal-closed', { bubbles: true }));
                };

                new MutationObserver(sync).observe(modal, { attributes: true, attributeFilter: ['class'] });

                if (options.hash && window.location.hash === '#' + options.hash) {
                    this.open(id);
                    // The browser's own scroll to the fragment during loading clears the focus again
                    if (document.readyState !== 'complete') {
                        window.addEventListener('load', () => this.isOpen(id) && this.focusInside(modal), { once: true });
                    }
                }

                // A modal rendered open never passes through the observer above
                if (open) {
                    this.syncViewport();
                    this.focusInside(modal);
                }
            },
        };

        (window.igModalQueue ?? []).forEach(([id, options]) => window.igModal.register(id, options));
        delete window.igModalQueue;

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                window.igModal.closeAll();
            }
        });

        // Tab and Shift+Tab cycle through the topmost open modal instead of leaving it for the page behind
        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab' || event.defaultPrevented) {
                return;
            }
            const openModals = document.querySelectorAll('.ig-modal:not(.d-none)');
            const modal = openModals[openModals.length - 1];
            if (! modal) {
                return;
            }
            const target = window.igModal.wrapTarget(modal, event.shiftKey);
            if (target) {
                event.preventDefault();
                target.focus();
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
