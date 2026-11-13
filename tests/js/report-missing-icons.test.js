import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

let error;
let stop;

// What Font Awesome renders for an icon, with the placeholder group when it is not registered
function icon(name, { prefix = 'fas', missing = true } = {}) {
    return `<svg class="svg-inline--fa fa-${name}" data-prefix="${prefix}" data-icon="${name}">${missing ? '<g class="missing"></g>' : '<path></path>'}</svg>`;
}

async function start() {
    vi.resetModules();
    const { default: reportMissingIcons } = await import('../../resources/js/report-missing-icons.js');
    stop = reportMissingIcons();
}

const mutationsDelivered = () => new Promise((resolve) => setTimeout(resolve));

describe('reportMissingIcons', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        error = vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        stop();
        error.mockRestore();
    });

    it('names an icon missing on load with what to register', async () => {
        document.body.innerHTML = icon('ellipsis-vertical');
        await start();

        expect(error).toHaveBeenCalledOnce();
        expect(error.mock.calls[0][0]).toBe('Font Awesome icon "ellipsis-vertical" (fas) is not registered: add faEllipsisVertical from @fortawesome/free-solid-svg-icons to library.add().');
    });

    it('reports an icon added later, nested in its <i>', async () => {
        await start();
        document.body.innerHTML = `<p><i>${icon('circle-question', { prefix: 'far' })}</i></p>`;
        await mutationsDelivered();

        expect(error).toHaveBeenCalledOnce();
        expect(error.mock.calls[0][0]).toContain('add faCircleQuestion from @fortawesome/free-regular-svg-icons');
    });

    it('reports each icon once and leaves registered icons alone', async () => {
        document.body.innerHTML = icon('xmark', { missing: false }) + icon('angle-down') + icon('angle-down');
        await start();
        document.body.insertAdjacentHTML('beforeend', icon('angle-down'));
        await mutationsDelivered();

        expect(error).toHaveBeenCalledOnce();
        expect(error.mock.calls[0][0]).toContain('faAngleDown');
    });
});
