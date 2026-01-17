export default () => ({
    input: null,
    clearButton: null,
    observer: null,

    init() {
        this.setupClearable();
        this.setupLivewireListeners();
    },

    setupClearable() {
        if (!this.$el) {
            console.warn('Clearable: No element found');
            return;
        }
        this.input = this.$el.querySelector('input[type="text"], input[type="search"], input[type="email"], input[type="url"], input[type="tel"], textarea, input[type="password"]');
        if (!this.input) {
            console.warn('Clearable: No suitable input found');
            return;
        }
        if (this.input.disabled || this.input.classList.contains('is-invalid')) {
            return;
        }

        if (window.getComputedStyle(this.$el).position === 'static') {
            this.$el.style.position = 'relative';
        }

        this.clearButton = document.createElement('button');
        this.clearButton.type = 'button';
        this.clearButton.setAttribute('tabindex', '-1');
        this.clearButton.innerHTML = '✕';
        this.clearButton.className = 'clear-button';
        this.clearButton.style.cssText = `
            position: absolute;
            right: 0.5em;
            top: 1.5em;
            transform: translateY(-50%);
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1.125em;
            line-height: 1;
            padding: 0.25em 0.375em;
            color: #999;
            display: none;
            z-index: 10;
        `;

        this.clearButton.addEventListener('mouseenter', () => {
            this.clearButton.style.color = '#333';
        });
        this.clearButton.addEventListener('mouseleave', () => {
            this.clearButton.style.color = '#999';
        });

        this.clearButton.addEventListener('click', () => this.clear());
        this.$el.appendChild(this.clearButton);
        this.input.addEventListener('input', () => this.updateVisibility());
        this.updateVisibility();
    },

    setupLivewireListeners() {
        document.addEventListener('livewire:navigated', () => {
            this.reinitialize();
        });

        document.addEventListener('livewire:updated', (event) => {
            if (this.$el.contains(event.detail.component.el) || event.detail.component.el.contains(this.$el)) {
                this.reinitialize();
            }
        });

        this.observer = new MutationObserver(() => {
            if (!this.$el.contains(this.clearButton) || !this.$el.contains(this.input)) {
                this.reinitialize();
            }
        });

        this.observer.observe(this.$el, {
            childList: true,
            subtree: true
        });
    },

    reinitialize() {
        // Clean up existing button
        const btn = this.$el.querySelector('.clear-button');
        if (btn) {
            btn.remove();
        }
        // Re-setup clearable
        setTimeout(() => {
            this.setupClearable();
        }, 10);
    },

    clear() {
        if (this.input) {
            this.input.value = '';
            this.input.dispatchEvent(new Event('input', { bubbles: true }));
            this.input.focus();
            this.updateVisibility();
        }
    },

    updateVisibility() {
        if (this.clearButton) {
            const shouldShow = this.input
                && this.input.value.length > 0
                && !this.input.disabled
                && !this.input.classList.contains('is-invalid');
            this.clearButton.style.display = shouldShow ? 'block' : 'none';
        }
    },

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
        if (this.clearButton && this.$el.contains(this.clearButton)) {
            this.clearButton.remove();
        }
    }
});
