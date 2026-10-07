/**
 * Horizontally scrollable card row with previous/next controls.
 *
 * Scrolling itself is native, so touch swiping and momentum come for free;
 * the arrows page by whole cards and disable at either end.
 */
export default () => ({
    atStart: true,
    atEnd: true,
    overflowing: false,

    /** The gaps between neighbouring cards, where the drag handles sit. */
    grips: [],

    frame: null,
    dragStart: null,
    samples: [],
    glide: null,

    init() {
        // Refs are only bound once Alpine has walked the children.
        this.$nextTick(() => {
            this.update();

            if (typeof ResizeObserver !== 'undefined') {
                this.observer = new ResizeObserver(() => this.update());
                this.observer.observe(this.$refs.track);
            }
        });
    },

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
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
});
