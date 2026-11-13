import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import cardRow from '../../resources/js/card-row.js';

const CARD_WIDTH = 100;
const TRACK_WIDTH = 250;

function box(left, width) {
    return { left, right: left + width, top: 0, bottom: 100, width, height: 100 };
}

// A row of four cards, the first two fully in view; wrapped puts each card in an item of the application's own
function renderRow({ wrapped }) {
    const cards = [0, 1, 2, 3].map((index) => {
        const card = `<div class="card" data-index="${index}"><a class="card-action" href="#card-${index}">Open</a></div>`;

        return wrapped ? `<div class="talk-item"><span class="talk-year">2026</span>${card}</div>` : card;
    }).join('');
    document.body.innerHTML = `<div class="card-row"><div class="card-row-track">${cards}<template x-for="grip in grips"></template></div></div>`;

    const track = document.querySelector('.card-row-track');
    track.getBoundingClientRect = () => box(0, TRACK_WIDTH);
    track.scrollBy = vi.fn();
    document.querySelectorAll('.card').forEach((card) => {
        const left = Number(card.dataset.index) * (CARD_WIDTH + 10);
        card.getBoundingClientRect = () => box(left, CARD_WIDTH);
        (wrapped ? card.parentElement : card).getBoundingClientRect = () => box(left, CARD_WIDTH);
    });

    const row = cardRow();
    row.$refs = { track };
    row.$root = document.querySelector('.card-row');

    return row;
}

const focusedIndex = () => document.activeElement.dataset.index;

describe.each([false, true])('cardRow with wrapped cards: %s', (wrapped) => {
    let row;

    beforeEach(() => {
        row = renderRow({ wrapped });
    });

    it('steps focus card by card with the keys', () => {
        row.keyPage(1, false);
        expect(focusedIndex()).toBe('0');

        row.keyPage(1, false);
        expect(focusedIndex()).toBe('1');

        row.keyPage(-1, false);
        expect(focusedIndex()).toBe('0');
        expect(row.$refs.track.scrollBy).not.toHaveBeenCalled();
    });

    it('pages the row when the next card is out of view', () => {
        row.keyPage(1, false);
        row.keyPage(1, false);
        row.keyPage(1, false);

        expect(focusedIndex()).toBe('2');
        expect(row.$refs.track.scrollBy).toHaveBeenCalledOnce();
    });

    it('follows the link of the focused card on Enter', () => {
        row.keyPage(1, false);
        const link = document.activeElement.querySelector('.card-action');
        const click = vi.spyOn(link, 'click').mockImplementation(() => {});
        const event = { target: document.activeElement, preventDefault: vi.fn() };

        row.openCard(event);

        expect(click).toHaveBeenCalledOnce();
        expect(event.preventDefault).toHaveBeenCalledOnce();
    });
});

describe('cardRow choosing the row for the keys', () => {
    const rows = [];

    // Rows stacked down the page, each at the given top on the screen and 100 high
    function renderRows(tops) {
        document.body.innerHTML = tops.map(() => '<div class="card-row"><div class="card-row-track"><div class="card"></div></div></div>').join('');

        return [...document.querySelectorAll('.card-row')].map((root, index) => {
            const track = root.querySelector('.card-row-track');
            track.getBoundingClientRect = () => ({ ...box(0, TRACK_WIDTH), top: tops[index], bottom: tops[index] + 100, height: 100 });

            const row = cardRow();
            row.$refs = { track };
            row.$root = root;
            row.$nextTick = () => {};
            row.keyPage = vi.fn();
            row.init();
            row.overflowing = true;
            rows.push(row);

            return row;
        });
    }

    const pressRight = () => document.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight' }));

    afterEach(() => {
        rows.splice(0).forEach((row) => row.destroy());
    });

    it('pages the row whose middle is nearest the middle of the screen', () => {
        const middle = window.innerHeight / 2;
        const [far, near] = renderRows([0, middle - 40]);

        pressRight();

        expect(near.keyPage).toHaveBeenCalledWith(1, false);
        expect(far.keyPage).not.toHaveBeenCalled();
    });

    it('leaves a row off the screen alone however near it is', () => {
        const [above, below] = renderRows([-100, window.innerHeight]);

        pressRight();

        expect(above.keyPage).not.toHaveBeenCalled();
        expect(below.keyPage).not.toHaveBeenCalled();
    });
});
