import initEditable from './editable';

// Set up for Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('editable', initEditable);
});

export { initEditable };
