/**
 * Horizontally scrollable card row with previous/next controls.
 *
 * Scrolling itself is native, so touch swiping and momentum come for free;
 * the arrows page by whole cards and disable at either end. The left and
 * right keys step focus card by card through the row holding focus, or else
 * the one whose buttons are on screen nearest its vertical centre.
 */
const rows = new Set();

/** Controls that use the left and right keys themselves. */
const ownsArrows = 'input, textarea, select, [contenteditable]:not([contenteditable="false"]), '
    + '[role="tablist"], [role="radiogroup"], [role="slider"], [role="menu"], [role="listbox"]';

/** Long enough for a smooth page to finish, so a held key steps rather than queues. */
const repeatInterval = 350;

function keyTarget() {
    const focused = [...rows].find((row) => row.overflowing && row.$root.contains(document.activeElement));

    if (focused) {
        return focused;
    }

    const middle = window.innerHeight / 2;
    let nearest = null;
    let nearestDistance = Infinity;

    rows.forEach((row) => {
        if (!row.overflowing || !row.navVisible) {
            return;
        }

        const box = row.$refs.track.getBoundingClientRect();
        const distance = Math.abs(box.top + box.height / 2 - middle);

        if (distance < nearestDistance) {
            nearest = row;
            nearestDistance = distance;
        }
    });

    return nearest;
}

function onKeydown(event) {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
        return;
    }

    if (event.defaultPrevented || event.isComposing || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
        return;
    }

    if (event.target instanceof Element && event.target.closest(ownsArrows)) {
        return;
    }

    if (document.querySelector('.ig-modal:not(.d-none)')) {
        return;
    }

    const row = keyTarget();

    if (row) {
        event.preventDefault();
        row.keyPage(event.key === 'ArrowLeft' ? -1 : 1, event.repeat);
    }
}

/** A card scrolled off the screen lets go of focus, so the keys move on with the page. */
function onPageScroll() {
    const card = document.activeElement;

    if (!card || !card.classList.contains('card') || ![...rows].some((row) => row.$root.contains(card))) {
        return;
    }

    const box = card.getBoundingClientRect();

    if (box.top < 0 || box.bottom > window.innerHeight) {
        card.blur();
    }
}

export default () => ({
    atStart: true,
    atEnd: true,
    overflowing: false,
    navVisible: false,

    /** The gaps between neighbouring cards, where the drag handles sit. */
    grips: [],

    frame: null,
    dragStart: null,
    samples: [],
    glide: null,
    lastKeyPage: 0,

    init() {
        if (rows.size === 0) {
            document.addEventListener('keydown', onKeydown);
            window.addEventListener('scroll', onPageScroll, { passive: true });
        }

        rows.add(this);

        // Refs are only bound once Alpine has walked the children.
        this.$nextTick(() => {
            this.update();

            if (typeof ResizeObserver !== 'undefined') {
                this.observer = new ResizeObserver(() => this.update());
                this.observer.observe(this.$refs.track);
            }

            if (typeof IntersectionObserver !== 'undefined') {
                this.navObserver = new IntersectionObserver(([entry]) => {
                    this.navVisible = entry.isIntersecting && entry.intersectionRatio > .99;
                }, { threshold: 1 });
                this.navObserver.observe(this.$refs.nav);
            }
        });
    },

    destroy() {
        rows.delete(this);

        if (rows.size === 0) {
            document.removeEventListener('keydown', onKeydown);
            window.removeEventListener('scroll', onPageScroll);
        }

        if (this.observer) {
            this.observer.disconnect();
        }

        if (this.navObserver) {
            this.navObserver.disconnect();
        }

        if (this.frame !== null) {
            cancelAnimationFrame(this.frame);
        }

        this.stopGlide();
    },

    /**
     * Coalesces the burst of scroll events into one update per frame. Unlike
     * a leading-edge throttle it still runs on the final event, so the ends
     * are detected once the scroll (and any snap) settles rather than left
     * stale — while staying live enough for the shadows to track the scroll.
     */
    onScroll() {
        if (this.frame !== null) {
            return;
        }

        this.frame = requestAnimationFrame(() => {
            this.frame = null;
            this.update();
        });
    },

    update() {
        const track = this.$refs.track;

        if (!track) {
            return;
        }

        const max = track.scrollWidth - track.clientWidth;

        // Snapping and sub-pixel layout keep the ends a few pixels off.
        const tolerance = 4;

        this.overflowing = max > tolerance;
        this.atStart = track.scrollLeft <= tolerance;
        this.atEnd = track.scrollLeft >= max - tolerance;
        this.grips = this.overflowing ? this.measureGrips(track) : [];

        // The edge shadows are cast only across the cards, so they have to
        // match the card height rather than the whole component. Cards
        // stretch to a shared height, so any one of them measures it.
        const card = track.firstElementChild;
        this.$root.style.setProperty(
            '--card-row-shadow-height',
            card ? `${card.getBoundingClientRect().height}px` : '0px',
        );
    },

    /**
     * Measures the gaps between neighbouring cards. The handles are laid over
     * the track rather than drawn by the cards, which may clip what overflows.
     */
    measureGrips(track) {
        const cards = [...track.children].filter((child) => child.classList.contains('card'));
        const grips = [];

        for (let index = 1; index < cards.length; index++) {
            const end = cards[index - 1].offsetLeft + cards[index - 1].offsetWidth;
            grips.push({ left: end, width: cards[index].offsetLeft - end });
        }

        return grips;
    },

    /**
     * Lets a mouse drag the row sideways by a handle between cards. The row
     * follows the pointer while held, then glides on in the direction of the
     * throw and settles on a card. Snapping is held off until it has settled,
     * or it would jump the row onto a card the moment the button is let go.
     */
    startDrag(event) {
        if (event.pointerType !== 'mouse' || event.button !== 0) {
            return;
        }

        event.preventDefault();
        event.currentTarget.setPointerCapture(event.pointerId);
        this.stopGlide();

        const track = this.$refs.track;
        this.dragStart = { x: event.clientX, scrollLeft: track.scrollLeft };
        this.samples = [{ x: event.clientX, time: event.timeStamp }];
        this.$root.classList.add('card-row-dragging', 'card-row-free');
    },

    drag(event) {
        if (this.dragStart === null) {
            return;
        }

        this.$refs.track.scrollLeft = this.dragStart.scrollLeft - (event.clientX - this.dragStart.x);

        // Only the last moments of the drag say how hard the row was thrown.
        this.samples.push({ x: event.clientX, time: event.timeStamp });
        this.samples = this.samples.filter((sample) => event.timeStamp - sample.time < 100);
    },

    endDrag(event) {
        if (this.dragStart === null) {
            return;
        }

        event.currentTarget.releasePointerCapture(event.pointerId);
        this.dragStart = null;
        this.$root.classList.remove('card-row-dragging');
        this.settle(this.throwVelocity());
    },

    /** Pointer speed at release in pixels per millisecond, positive to the right. */
    throwVelocity() {
        const first = this.samples[0];
        const last = this.samples[this.samples.length - 1];
        const elapsed = last.time - first.time;

        return elapsed > 0 ? (last.x - first.x) / elapsed : 0;
    },

    /**
     * Glides to the card nearest where the throw would carry the row, then
     * hands the row back to snapping. The glide is animated here rather than
     * by a smooth scrollTo, whose end the browser reports unreliably, and
     * snapping switched back on mid-glide jumps the row onto a card.
     */
    settle(velocity) {
        const track = this.$refs.track;
        const from = track.scrollLeft;
        const to = this.nearestSnap(track, from - velocity * 300);
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const duration = reducedMotion ? 0 : Math.min(600, 250 + Math.abs(to - from) * .5);
        const startTime = performance.now();

        const step = (now) => {
            const progress = duration > 0 ? Math.min(1, (now - startTime) / duration) : 1;
            const eased = 1 - Math.pow(1 - progress, 3);

            track.scrollLeft = from + (to - from) * eased;

            if (progress < 1) {
                this.glide = requestAnimationFrame(step);

                return;
            }

            this.glide = null;
            this.$root.classList.remove('card-row-free');
        };

        this.glide = requestAnimationFrame(step);
    },

    stopGlide() {
        if (this.glide !== null) {
            cancelAnimationFrame(this.glide);
            this.glide = null;
        }
    },

    nearestSnap(track, left) {
        const max = track.scrollWidth - track.clientWidth;
        const padding = parseFloat(getComputedStyle(track).scrollPaddingLeft) || 0;
        // The end of the row is a resting point too, since the last cards
        // cannot be scrolled to the start.
        const positions = [...track.children]
            .filter((child) => child.classList.contains('card'))
            .map((card) => Math.min(max, Math.max(0, card.offsetLeft - padding)))
            .concat(max);

        return positions.reduce((nearest, position) => (
            Math.abs(position - left) < Math.abs(nearest - left) ? position : nearest
        ));
    },

    /**
     * Scrolls one card further in the given direction (-1 back, 1 forward).
     */
    page(direction) {
        const track = this.$refs.track;
        const item = track.firstElementChild;
        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        const step = item ? item.getBoundingClientRect().width + gap : track.clientWidth;

        track.scrollBy({ left: direction * step, behavior: 'smooth' });
    },

    cards() {
        return [...this.$refs.track.children].filter((child) => child.classList.contains('card'));
    },

    isCardInView(card, partly = false) {
        const track = this.$refs.track.getBoundingClientRect();
        const box = card.getBoundingClientRect();

        if (partly) {
            return box.right > track.left && box.left < track.right;
        }

        return box.left >= track.left - 1 && box.right <= track.right + 1;
    },

    /**
     * Moves focus to the neighbouring card, paging the row only when that card
     * is not in view yet. The first press lands on the first card in view, or
     * on the last one going back, without moving the row.
     */
    keyPage(direction, repeat) {
        const now = performance.now();

        if (repeat && now - this.lastKeyPage < repeatInterval) {
            return;
        }

        this.lastKeyPage = now;

        const cards = this.cards();
        const current = cards.indexOf(document.activeElement);
        let target;

        // Partly in view still counts, as while the row is gliding to it.
        if (current !== -1 && this.isCardInView(cards[current], true)) {
            target = cards[current + direction];
        } else {
            const inView = cards.filter((card) => this.isCardInView(card));
            target = direction > 0 ? inView[0] : inView[inView.length - 1];
        }

        if (!target) {
            return;
        }

        target.tabIndex = -1;
        target.focus({ preventScroll: true });

        if (!this.isCardInView(target)) {
            this.page(direction);
        }
    },

    /** Lets Enter on a focused card follow its link, as a click on it would. */
    openCard(event) {
        const card = event.target;

        if (card.parentElement !== this.$refs.track || !card.classList.contains('card')) {
            return;
        }

        const link = card.querySelector('.card-action') ?? card.querySelector('a[href]');

        if (link) {
            event.preventDefault();
            link.click();
        }
    },

    /** A touch swipe or a drag takes over from the keys, so the lifted card drops. */
    releaseCard() {
        if (this.cards().includes(document.activeElement)) {
            document.activeElement.blur();
        }
    },
});
