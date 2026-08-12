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

    frame: null,

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

        // The edge shadows are cast only across the cards, so they have to
        // match the card height rather than the whole component. Cards
        // stretch to a shared height, so any one of them measures it.
        const card = track.firstElementChild;
        this.$el.style.setProperty(
            '--card-row-shadow-height',
            card ? `${card.getBoundingClientRect().height}px` : '0px',
        );
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
