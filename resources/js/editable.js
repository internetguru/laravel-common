export default function initEditable() {
    return {
        isEdited: false,
        isSubmitting: false,

        init() {
            // Track form changes on all forms without editable-skip class
            document.querySelectorAll('form:not(.editable-skip)').forEach(form => {
                const inputs = form.querySelectorAll('input, select, textarea');

                // Track initial values
                inputs.forEach(input => {
                    input._initialValue = this.getInputValue(input);

                    // Listen for changes
                    input.addEventListener('change', () => this.checkFormEdited(inputs));
                    input.addEventListener('input', () => this.checkFormEdited(inputs));
                });

                // Add submit event listener to the form
                form.addEventListener('submit', () => {
                    this.isSubmitting = true;
                });
            });

            // Set up beforeunload event
            window.addEventListener('beforeunload', (e) => {
                if (this.isEdited && !this.isSubmitting) {
                    e.preventDefault();
                    return;
                }
            });
        },

        getInputValue(input) {
            if (input.type === 'checkbox' || input.type === 'radio') {
                return input.checked;
            }
            return input.value;
        },

        checkFormEdited(inputs) {
            let edited = false;

            inputs.forEach(input => {
                const currentValue = this.getInputValue(input);
                if (currentValue != input._initialValue) {
                    edited = true;
                }
            });

            if (edited !== this.isEdited) {
                this.isEdited = edited;
                this.updateTitle();
            }
        },

        updateTitle() {
            // Update the document title prepending an asterisk
            document.title = this.isEdited
                ? '* ' + document.title.replace(/^\* /, '')
                : document.title.replace(/^\* /, '');
        }
    };
}
