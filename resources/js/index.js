import initEditable from './editable';
import print from './print';
import clearable from './clearable';
import cardRow from './card-row';
import tagCloud from './tag-cloud';
import registerLivewireErrorHandling from './livewire-error-handling';
import registerDatePlaceholders from './date-placeholder';
import watch from './watch';
import pageWatch from './page-watch';
import keepUnchangedIcons from './keep-unchanged-icons';
import reportMissingIcons from './report-missing-icons';
import { addWatchMethods, allowReload, checkAll as checkWatchers, isWatchRequest } from './watch-clock';

// Set up for Alpine.js
document.addEventListener('alpine:init', () => {
    Alpine.data('editable', initEditable);
    Alpine.data('print', print);
    Alpine.data('clearable', clearable);
    Alpine.data('cardRow', cardRow);
    Alpine.data('tagCloud', tagCloud);
    Alpine.data('igWatch', watch);
    Alpine.data('igPageWatch', pageWatch);
});

// The watch clock for apps: skip background checks in their error hooks, add background calls, allow a reload, check now
window.igWatch = { isWatchRequest, addWatchMethods, allowReload, checkAll: checkWatchers };

registerLivewireErrorHandling();
registerDatePlaceholders();
reportMissingIcons();

export { initEditable, print, clearable, cardRow, tagCloud, watch, pageWatch, keepUnchangedIcons };
