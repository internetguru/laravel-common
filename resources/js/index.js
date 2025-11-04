import initEditable from './editable';
import print from './print';
import clearable from './clearable';

// Set up for Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('editable', initEditable);
    Alpine.data('print', print);
    Alpine.data('clearable', clearable);
});

export { initEditable, print, clearable };
