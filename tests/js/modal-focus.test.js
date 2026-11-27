import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { beforeAll, beforeEach, describe, expect, it } from 'vitest';

const blade = readFileSync(resolve(__dirname, '../../resources/views/components/partials/modal-script.blade.php'), 'utf8');
const script = blade.match(/<script data-testid="ig-modal-script">([\s\S]*?)<\/script>/)[1];

// The mutation observers report on a later tick
const settle = () => new Promise((resolve) => setTimeout(resolve));
const byId = (testid) => document.querySelector(`[data-testid="${testid}"]`);

// What <x-ig::modal> renders, reduced to what the focus handling reads
function modal(id, body = '', { open = false } = {}) {
    return `
        <div id="${id}" class="ig-modal${open ? '' : ' d-none'}">
            <div class="modal" tabindex="-1" role="dialog" aria-modal="true" data-testid="${id}-dialog">
                <div class="modal-dialog"><div class="modal-content"><div class="modal-body">
                    <h5 class="modal-title">Title</h5>
                    <button type="button" class="btn-close" data-testid="${id}-close"></button>
                    ${body}
                </div></div></div>
            </div>
            <div class="modal-backdrop"></div>
        </div>`;
}

function render(html, ids) {
    document.body.innerHTML = `<button type="button" data-testid="trigger">Open</button>${html}`;
    ids.forEach((id) => window.igModal.register(id));
}

function pressTab({ shiftKey = false } = {}) {
    const event = new KeyboardEvent('keydown', { key: 'Tab', shiftKey, bubbles: true, cancelable: true });
    (document.activeElement ?? document.body).dispatchEvent(event);

    return event;
}

async function openFromTrigger(id) {
    byId('trigger').focus();
    window.igModal.open(id);
    await settle();
}

beforeAll(() => {
    new Function(script)();
});

beforeEach(() => {
    document.body.innerHTML = '';
});

describe('modal focus', () => {
    it('moves the focus onto the dialog when it opens and back to the trigger when it closes', async () => {
        render(modal('m', '<input data-testid="field">'), ['m']);

        await openFromTrigger('m');
        expect(document.activeElement).toBe(byId('m-dialog'));

        window.igModal.close('m');
        await settle();
        expect(document.activeElement).toBe(byId('trigger'));
    });

    it('focuses the autofocus element instead of the dialog', async () => {
        render(modal('m', '<input data-testid="field"><input data-testid="search" autofocus>'), ['m']);

        await openFromTrigger('m');

        expect(document.activeElement).toBe(byId('search'));
    });

    it('focuses a modal rendered open once it registers', () => {
        render(modal('m', '', { open: true }), ['m']);

        expect(document.activeElement).toBe(byId('m-dialog'));
    });

    it('gives the focus back on Escape', async () => {
        render(modal('m'), ['m']);
        await openFromTrigger('m');

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await settle();

        expect(document.activeElement).toBe(byId('trigger'));
    });

    it('does not send the focus to a trigger that a re-render replaced', async () => {
        render(modal('m'), ['m']);
        await openFromTrigger('m');

        byId('trigger').replaceWith(byId('trigger').cloneNode(true));
        window.igModal.close('m');
        await settle();

        expect(document.activeElement).not.toBe(byId('trigger'));
    });

    it('focuses the dialog when the autofocus element is hidden or disabled', async () => {
        render(modal('m', '<div class="d-none"><input autofocus></div><input disabled autofocus><input data-testid="field">'), ['m']);

        await openFromTrigger('m');

        expect(document.activeElement).toBe(byId('m-dialog'));
    });

    it('returns the focus to the control in the first modal when the second one closes', async () => {
        render(modal('a', '<button type="button" data-testid="a-next">Next</button>') + modal('b'), ['a', 'b']);
        await openFromTrigger('a');

        byId('a-next').focus();
        window.igModal.open('b');
        await settle();
        window.igModal.close('b');
        await settle();

        expect(document.activeElement).toBe(byId('a-next'));
    });

    it.each([
        ['registered in page order', ['a', 'b']],
        ['registered in reverse', ['b', 'a']],
    ])('returns the focus to the page trigger when Escape closes stacked modals %s', async (_, order) => {
        render(modal('a', '<button type="button" data-testid="a-next">Next</button>') + modal('b'), order);
        await openFromTrigger('a');
        byId('a-next').focus();
        window.igModal.open('b');
        await settle();

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await settle();

        expect(document.activeElement).toBe(byId('trigger'));
    });

    it('follows a modal that replaced the one it was opened from back to the page trigger', async () => {
        render(modal('a', '<button type="button" data-testid="a-next">Next</button>') + modal('b'), ['b', 'a']);
        await openFromTrigger('a');
        byId('a-next').focus();

        window.igModal.close('a');
        window.igModal.open('b');
        await settle();
        window.igModal.close('b');
        await settle();

        expect(document.activeElement).toBe(byId('trigger'));
    });

    it('leaves the focus in another modal that opened meanwhile', async () => {
        render(modal('a') + modal('b', '<input data-testid="b-field" autofocus>'), ['a', 'b']);
        await openFromTrigger('a');

        window.igModal.open('b');
        await settle();
        window.igModal.close('a');
        await settle();

        expect(document.activeElement).toBe(byId('b-field'));
    });
});

describe('modal Tab cycle', () => {
    const fields = '<input data-testid="first"><input type="hidden"><input disabled><input data-testid="last">';

    beforeEach(async () => {
        render(modal('m', fields), ['m']);
        await openFromTrigger('m');
    });

    it('wraps Tab from the last control to the first', () => {
        byId('last').focus();

        expect(pressTab().defaultPrevented).toBe(true);
        expect(document.activeElement).toBe(byId('m-close'));
    });

    it('wraps Shift+Tab from the first control to the last', () => {
        byId('m-close').focus();

        pressTab({ shiftKey: true });

        expect(document.activeElement).toBe(byId('last'));
    });

    it('sends Shift+Tab from the dialog itself to the last control', () => {
        pressTab({ shiftKey: true });

        expect(document.activeElement).toBe(byId('last'));
    });

    it('lets Tab move between the controls inside', () => {
        byId('first').focus();

        expect(pressTab().defaultPrevented).toBe(false);
    });

    it('brings the focus back in from the page behind', () => {
        byId('trigger').focus();

        pressTab();

        expect(document.activeElement).toBe(byId('m-close'));
    });

    it('lets Shift+Tab leave a summary in the middle for the browser to handle', async () => {
        render(modal('s', '<details><summary data-testid="summary">More</summary></details><input data-testid="s-last">'), ['s']);
        await openFromTrigger('s');
        byId('summary').focus();

        expect(pressTab({ shiftKey: true }).defaultPrevented).toBe(false);
    });

    it('wraps Tab from a summary in the last place', async () => {
        render(modal('s', '<details><summary data-testid="summary">More</summary></details>'), ['s']);
        await openFromTrigger('s');
        byId('summary').focus();

        pressTab();

        expect(document.activeElement).toBe(byId('s-close'));
    });

    it('wraps Tab from the checked radio of a group in the last place', async () => {
        render(modal('r', '<input type="radio" name="size" value="s"><input type="radio" name="size" value="m" checked data-testid="checked"><input type="radio" name="size" value="l">'), ['r']);
        await openFromTrigger('r');
        byId('checked').focus();

        pressTab();

        expect(document.activeElement).toBe(byId('r-close'));
    });

    it('skips the fields of a disabled fieldset', async () => {
        render(modal('f', '<input data-testid="f-last"><fieldset disabled><input></fieldset>'), ['f']);
        await openFromTrigger('f');
        byId('f-close').focus();

        pressTab({ shiftKey: true });

        expect(document.activeElement).toBe(byId('f-last'));
    });

    it('leaves Tab alone once every modal is closed', async () => {
        window.igModal.close('m');
        await settle();

        expect(pressTab().defaultPrevented).toBe(false);
    });
});
