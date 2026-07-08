import initEditable from './editable';
import print from './print';
import clearable from './clearable';
import registerLivewireErrorHandling from './livewire-error-handling';

// Set up for Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('editable', initEditable);
    Alpine.data('print', print);
    Alpine.data('clearable', clearable);
});

registerLivewireErrorHandling();

export { initEditable, print, clearable };
