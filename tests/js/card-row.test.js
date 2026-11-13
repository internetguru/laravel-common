import { beforeEach, describe, expect, it, vi } from 'vitest';
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
