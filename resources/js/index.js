import initEditable from './editable';
import print from './print';

// Set up for Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('editable', initEditable);
    Alpine.data('print', print);
});

export { initEditable, print };
