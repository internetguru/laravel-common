import initEditable from './editable';
import print from './print';
import clearable from './clearable';
import cardRow from './card-row';
import tagCloud from './tag-cloud';
import registerLivewireErrorHandling from './livewire-error-handling';
import registerDatePlaceholders from './date-placeholder';

// Set up for Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('editable', initEditable);
    Alpine.data('print', print);
    Alpine.data('clearable', clearable);
    Alpine.data('cardRow', cardRow);
    Alpine.data('tagCloud', tagCloud);
});

registerLivewireErrorHandling();
registerDatePlaceholders();

export { initEditable, print, clearable, cardRow, tagCloud };
