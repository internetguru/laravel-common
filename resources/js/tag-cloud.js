/**
 * Typographic tag cloud.
 *
 * The tags are packed into lines and each line is scaled to fill the width of
 * the cloud exactly, which is what closes the gaps a plain wrapping flex row
 * leaves behind. Sizes therefore come from the layout, not from the markup.
 */
export default () => ({
    /**
     * Font sizes a tag may be scaled to, in pixels. Only these two bounds
     * set the scale of the cloud: every line is fitted to the width it has,
     * so raising them means fewer, larger words per line.
     */
    min: 25.6,
    max: 115,

    /**
     * How the lines take turns: each number is a line's smallest tag as a
     * multiple of `min`, so a high one gives a line of few large words and
     * a low one a line of many small ones.
     */
    rhythm: [2.6, 1, 1.7, 1.15, 2.1],

    /** How wide the top and bottom lines are, against the middle ones. */
    waist: 0.1,

    /** How far tags stray off their baseline, in ems of their own size. */
    scatter: 0.3,

    /** How far the largest tag outgrows the smallest one. */
    spread: { min: 1, max: 2.8 },

    /** @type {HTMLElement[]} */
    tags: [],

    init() {
        this.tags = Array.from(this.$el.children);

        this.$nextTick(() => this.layout(true));

        // A fallback font measures differently, so lay out again once the
        // real one has arrived.
        if (document.fonts) {
            document.fonts.ready.then(() => this.layout(true));
        }

        if (typeof ResizeObserver !== 'undefined') {
            this.observer = new ResizeObserver(() => this.layout());
            this.observer.observe(this.$el);
        }
    },

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    },

    /**
     * The width of a tag's text at a font size of one pixel, so a line's
     * font size follows from the width it has to fill.
     */
    ratio(tag) {
        const styles = getComputedStyle(tag);
        const canvas = this.canvas || (this.canvas = document.createElement('canvas'));
        const context = canvas.getContext('2d');
        const reference = 100;

        context.font = `${styles.fontStyle} ${styles.fontWeight} ${reference}px ${styles.fontFamily}`;

        return context.measureText(tag.textContent.trim()).width / reference;
    },

    /**
     * Lays the tags out for the current width. Resizing the cloud changes
     * its height too, which the observer reports back, so a layout that
     * would not change anything is skipped unless it is forced.
     */
    layout(force = false) {
        const width = this.$el.clientWidth;

        if (! width || this.tags.length === 0 || (! force && width === this.width)) {
            return;
        }

        this.width = width;

        const gap = parseFloat(getComputedStyle(this.$el).columnGap) || 0;
        const ratios = this.tags.map((tag) => this.ratio(tag));
        const weights = this.tags.map((tag, index) => this.weight(index));

        this.$el.querySelectorAll('.tag-break').forEach((brk) => brk.remove());

        /**
         * The scale at which the given tags fill the given width. A tag's
         * own size is that scale times its weight, so the tags on a line
         * differ in size while the line still comes out flush.
         */
        const fit = (line, lineWidth) => {
            const total = line.reduce((sum, index) => sum + ratios[index] * weights[index], 0);

            return (lineWidth - gap * (line.length - 1)) / total;
        };

        /**
         * Fills one line at a time, each only as wide as `widthOf` allows.
         *
         * @param {(position: number) => number} widthOf
         * @returns {number[][]}
         */
        const pack = (widthOf) => {
            const lines = [];
            let line = [];

            this.tags.forEach((tag, index) => {
                const trial = [...line, index];

                // Another tag on this line means a smaller size for all of
                // them; once the smallest would fall below the line's own
                // floor, the line is full and the tag opens the next one.
                const smallest = fit(trial, widthOf(lines.length))
                    * Math.min(...trial.map((i) => weights[i]));

                if (line.length > 0 && smallest < this.floor(lines.length)) {
                    lines.push(line);
                    line = [];
                }

                line.push(index);
            });

            lines.push(line);

            return lines;
        };

        // The silhouette needs to know how many lines there will be, and
        // the line count depends on the silhouette, so pack once at full
        // width to count and then again to shape.
        let lines = pack(() => width);

        for (let pass = 0; pass < 2; pass += 1) {
            const count = lines.length;

            lines = pack((position) => width * this.profile(position, count));
        }

        const count = lines.length;

        lines.forEach((indexes, position) => {
            const largest = Math.max(...indexes.map((index) => weights[index]));

            // The last line is usually short, so it keeps a size in
            // proportion rather than being blown up to fill its width.
            const scale = Math.min(
                this.max / largest,
                fit(indexes, width * this.profile(position, count)),
            );

            indexes.forEach((index) => {
                // A hair under the fitted size, so rounding never wraps a
                // line that was measured to fit.
                this.tags[index].style.fontSize = `${scale * weights[index] * 0.995}px`;
                this.tags[index].style.setProperty('--tag-shift', this.shift(index));
            });

            if (position < lines.length - 1) {
                this.tags[indexes[indexes.length - 1]].after(this.break());
            }
        });

        // Revealed only once measured, so the fallback sizes never show.
        this.$el.dataset.laidOut = '';
    },

    /**
     * The smallest a tag on this line may be. Every line fills its width,
     * so without this they all end up packed to the same smallest size and
     * the cloud reads as even rows; a line that stops early instead holds
     * a few large words, and one with a low floor crams in small ones.
     */
    floor(position) {
        return this.min * this.rhythm[position % this.rhythm.length];
    },

    /**
     * The share of the full width a line may take, by its position in the
     * cloud: the outer lines stay short and the middle ones run the whole
     * way, which is what rounds the block off into a cloud. Two or three
     * lines are too few to shape, so they fill the width.
     */
    profile(position, count) {
        if (count < 4) {
            return 1;
        }

        const arc = Math.sin((Math.PI * (position + 0.5)) / count);

        return this.waist + (1 - this.waist) * arc;
    },

    /**
     * How large a tag is relative to its neighbours. A real cloud sizes by
     * frequency, which a list of headings does not have, so the weights
     * step through the range by the golden ratio: no two neighbours match,
     * and the pattern repeats only after a long run.
     */
    weight(index) {
        const golden = 0.618033988749895;

        return this.spread.min + ((index * golden) % 1) * (this.spread.max - this.spread.min);
    },

    /**
     * How far a tag sits off its baseline, as a share of its own size, so
     * the cloud reads as a scatter of words rather than as a few lines.
     * The offset is a transform, so it moves nothing else around it.
     */
    shift(index) {
        const plastic = 0.7548776662466927;

        return `${(((index * plastic) % 1) - 0.5) * this.scatter}em`;
    },

    /** A zero-height item that fills the row, forcing the flex wrap. */
    break() {
        const brk = document.createElement('li');

        brk.className = 'tag-break';
        brk.setAttribute('aria-hidden', 'true');

        return brk;
    },
});
