import initEditable from './editable';
import print from './print';
import clearable from './clearable';
import cardRow from './card-row';
import registerLivewireErrorHandling from './livewire-error-handling';

// Set up for Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('editable', initEditable);
    Alpine.data('print', print);
    Alpine.data('clearable', clearable);
    Alpine.data('cardRow', cardRow);
});

registerLivewireErrorHandling();

export { initEditable, print, clearable, cardRow };
