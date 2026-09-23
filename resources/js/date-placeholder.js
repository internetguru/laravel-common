// Keeps data-empty on date-like floating inputs, so _form.scss can rest their label like on an empty text input
const selector = '.form-floating > :is(input[type="date"], input[type="datetime-local"], input[type="month"], input[type="week"], input[type="time"])';

// An incomplete date reads as '' but still shows what the user typed, so it is not empty
const sync = (input) => input.toggleAttribute('data-empty', input.value === '' && !input.validity.badInput);

const syncWithin = (root) => root.querySelectorAll(selector).forEach(sync);

// wire:model sets the value without an input event, after the morph, so wait a frame
const syncWithinLater = (root) => requestAnimationFrame(() => syncWithin(root));

export default function registerDatePlaceholders() {
    const syncTarget = (event) => {
        if (event.target instanceof HTMLInputElement && event.target.matches(selector)) {
            sync(event.target);
        }
    };
    document.addEventListener('input', syncTarget);
    document.addEventListener('change', syncTarget);
    document.addEventListener('focusout', syncTarget);

    document.addEventListener('livewire:initialized', () => syncWithinLater(document));
    document.addEventListener('livewire:navigated', () => syncWithinLater(document));
    window.Livewire?.hook('morphed', ({ el }) => syncWithinLater(el));
}
