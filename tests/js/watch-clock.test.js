import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

// Aligned to every retry delay, so the expected check times are whole multiples
const START = 1_800_000;
const MESSAGES = { offline: 'Offline', expired: 'Expired', signedOut: 'Signed out', updated: 'Updated', reload: 'Reload' };

let clock;
let pageWatch;
const handles = [];

const flush = () => vi.advanceTimersByTimeAsync(0);
const seconds = (ms) => (ms - START) / 1000;
const notice = () => document.querySelector('[data-testid=watch-notice]');

function watch({ interval = 15, check = vi.fn(async () => {}) } = {}) {
    handles.push(clock.registerWatcher({ interval, check, messages: MESSAGES }));
    return check;
}

function checkTimes() {
    const times = [];
    const check = vi.fn(async () => {
        times.push(seconds(Date.now()));
    });
    return { check, times };
}

function setProperty(target, name, value) {
    Object.defineProperty(target, name, { value, configurable: true });
}

beforeEach(async () => {
    vi.useFakeTimers({ now: START });
    vi.spyOn(Math, 'random').mockReturnValue(0);
    vi.resetModules();
    clock = await import('../../resources/js/watch-clock.js');
    pageWatch = (await import('../../resources/js/page-watch.js')).default;
});

afterEach(() => {
    handles.splice(0).forEach((handle) => handle.unregister());
    document.body.innerHTML = '';
    sessionStorage.clear();
    setProperty(window, 'scrollY', 0);
    delete document.hidden;
    delete navigator.onLine;
    vi.useRealTimers();
    vi.restoreAllMocks();
});

describe('schedule', () => {
    it('checks on the interval, and watchers due together in the same tick', async () => {
        const fast = checkTimes();
        const other = checkTimes();
        const slow = checkTimes();
        watch({ check: fast.check });
        watch({ check: other.check });
        watch({ interval: 30, check: slow.check });

        await vi.advanceTimersByTimeAsync(30_000);

        expect(fast.times).toEqual([15, 30]);
        expect(other.times).toEqual([15, 30]);
        expect(slow.times).toEqual([30]);
    });

    it('waits longer after each failed check, and keeps the interval again after a success', async () => {
        const times = [];
        const check = vi.fn(async () => {
            times.push(seconds(Date.now()));
            if (times.length <= 3) {
                throw new Error('Server error');
            }
        });
        watch({ check });

        await vi.advanceTimersByTimeAsync(135_000);

        expect(times).toEqual([15, 30, 60, 120, 135]);
        expect(notice()).toBeNull();
    });

    it('counts a check that hangs for 30 s as failed', async () => {
        const check = watch({ check: vi.fn(() => new Promise(() => {})) });

        await vi.advanceTimersByTimeAsync(59_000);
        expect(check).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(1_000);
        expect(check).toHaveBeenCalledTimes(2);
    });

    it('checks a hidden page ten times less often', async () => {
        setProperty(document, 'hidden', true);
        const check = watch();

        await vi.advanceTimersByTimeAsync(149_000);
        expect(check).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(1_000);
        expect(check).toHaveBeenCalledTimes(1);
    });

    it('checks nothing while offline', async () => {
        setProperty(navigator, 'onLine', false);
        const check = watch();

        await vi.advanceTimersByTimeAsync(60_000);

        expect(check).not.toHaveBeenCalled();
    });
});

describe('checking now', () => {
    it('makes one check of a focus and a visibility change arriving together', async () => {
        const check = watch();

        window.dispatchEvent(new Event('focus'));
        document.dispatchEvent(new Event('visibilitychange'));
        await vi.advanceTimersByTimeAsync(2_000);

        expect(check).toHaveBeenCalledTimes(1);
    });

    it('still makes an explicit check asked for right after another, a moment later', async () => {
        const check = watch();

        clock.checkAll();
        clock.checkAll();
        await flush();
        expect(check).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(1_000);
        expect(check).toHaveBeenCalledTimes(2);
    });

    it('makes a check asked for while one runs once that one ends', async () => {
        let finish;
        const check = watch({ check: vi.fn(() => new Promise((resolve) => (finish = resolve))) });
        clock.checkAll();
        await flush();

        await vi.advanceTimersByTimeAsync(1_000);
        window.dispatchEvent(new Event('ig-watch:check'));
        await flush();
        expect(check).toHaveBeenCalledTimes(1);

        finish();
        await flush();
        expect(check).toHaveBeenCalledTimes(2);
    });
});

describe('stopping', () => {
    it.each([
        [401, 'Signed out'],
        [403, 'Signed out'],
        [404, 'Updated'],
        [410, 'Updated'],
        [419, 'Expired'],
    ])('stops on %i with a notice that keeps the page', async (status, message) => {
        const stopped = vi.fn();
        window.addEventListener('ig-watch:stopped', stopped);
        const check = watch({ check: vi.fn(async () => Promise.reject({ status })) });

        clock.checkAll();
        await flush();

        expect(notice().textContent).toContain(message);
        expect(stopped).toHaveBeenCalledTimes(1);
        await vi.advanceTimersByTimeAsync(600_000);
        window.dispatchEvent(new Event('ig-watch:check'));
        await flush();
        expect(check).toHaveBeenCalledTimes(1);
        window.removeEventListener('ig-watch:stopped', stopped);
    });
});

describe('offline bar', () => {
    it('shows while offline, then hides and checks at once when back online', async () => {
        const check = watch();

        window.dispatchEvent(new Event('offline'));
        expect(notice().textContent).toContain('Offline');

        window.dispatchEvent(new Event('online'));
        await flush();
        expect(notice()).toBeNull();
        expect(check).toHaveBeenCalledTimes(1);
    });

    it('shows on a page without a watcher, with the page\'s own message', () => {
        document.body.innerHTML = '<div data-ig-offline="Offline page"></div>';

        window.dispatchEvent(new Event('offline'));
        expect(notice().textContent).toContain('Offline page');

        window.dispatchEvent(new Event('online'));
        expect(notice()).toBeNull();
    });

    it('shows nothing on a page that offers no message', () => {
        window.dispatchEvent(new Event('offline'));

        expect(notice()).toBeNull();
    });

    it('says the page is not being updated while a watcher runs, and the page\'s message once none does', () => {
        document.body.innerHTML = '<div data-ig-offline="Offline page"></div>';
        setProperty(navigator, 'onLine', false);
        const handle = clock.registerWatcher({ interval: 15, check: vi.fn(), messages: MESSAGES });
        expect(notice().textContent).toContain('Offline');
        expect(notice().textContent).not.toContain('Offline page');

        handle.unregister();

        expect(notice().textContent).toContain('Offline page');
        expect(document.querySelectorAll('[data-testid=watch-notice]')).toHaveLength(1);
    });

    it('never replaces a notice that must stay', () => {
        watch();
        clock.showNotice('Changed');

        window.dispatchEvent(new Event('offline'));
        window.dispatchEvent(new Event('online'));

        expect(notice().textContent).toContain('Changed');
    });
});

describe('reloading unnoticed', () => {
    it('waits until nobody has moved for 3 s', async () => {
        watch();
        expect(clock.canReloadUnnoticed()).toBe(true);

        window.dispatchEvent(new Event('mousemove'));
        expect(clock.canReloadUnnoticed()).toBe(false);

        await vi.advanceTimersByTimeAsync(3_000);
        expect(clock.canReloadUnnoticed()).toBe(true);
    });

    it('waits for the view at the top of the page', () => {
        watch();
        setProperty(window, 'scrollY', 200);

        expect(clock.canReloadUnnoticed()).toBe(false);
    });

    it.each([
        ['a typed text', () => Object.assign(document.createElement('input'), { defaultValue: 'a', value: 'b' })],
        ['a ticked checkbox', () => Object.assign(document.createElement('input'), { type: 'checkbox', checked: true })],
        ['a chosen option', () => {
            const select = document.createElement('select');
            select.append(new Option('First', '1'), new Option('Second', '2'));
            select.value = '2';
            return select;
        }],
    ])('never reloads over %s', (_, field) => {
        watch();
        document.body.append(field());

        expect(clock.isEditing()).toBe(true);
        expect(clock.canReloadUnnoticed()).toBe(false);
    });

    it('may reload at once for a while after the user asked for fresh data, but never over an edit', async () => {
        watch();
        setProperty(window, 'scrollY', 200);
        window.dispatchEvent(new Event('mousemove'));

        clock.allowReload(20);
        expect(clock.canReloadUnnoticed()).toBe(true);

        document.body.append(Object.assign(document.createElement('input'), { defaultValue: 'a', value: 'b' }));
        expect(clock.canReloadUnnoticed()).toBe(false);
        document.body.innerHTML = '';

        await vi.advanceTimersByTimeAsync(20_000);
        expect(clock.canReloadUnnoticed()).toBe(false);
    });
});

describe('watch requests', () => {
    const payload = (...methods) => JSON.stringify({ components: [{ calls: methods.map((method) => ({ method })) }] });

    it('are requests made of background calls only', () => {
        expect(clock.isWatchRequest(payload('watchRefresh'))).toBe(true);
        expect(clock.isWatchRequest(payload('$commit', 'watchCheck'))).toBe(true);
        expect(clock.isWatchRequest(payload('watchCheck', 'save'))).toBe(false);
        expect(clock.isWatchRequest(payload())).toBe(false);
        expect(clock.isWatchRequest('not json')).toBe(false);
    });

    it('include an application\'s own background calls once added', () => {
        clock.addWatchMethods('syncStatus');

        expect(clock.isWatchRequest(payload('syncStatus', 'watchCheck'))).toBe(true);
    });
});

describe('page watch', () => {
    function watchPage(changed = true) {
        const component = pageWatch({ interval: 15, messages: MESSAGES });
        component.$wire = {
            watchCheck: vi.fn(async () => ({ changed, message: 'Changed' })),
            watchReload: vi.fn(),
        };
        component.init();
        handles.push(component.watcher);
        return component.$wire;
    }

    async function checkNow() {
        clock.checkAll();
        await flush();
    }

    it('leaves an unchanged page alone and keeps watching', async () => {
        const wire = watchPage(false);

        await checkNow();
        await vi.advanceTimersByTimeAsync(15_000);

        expect(wire.watchCheck).toHaveBeenCalledTimes(2);
        expect(wire.watchReload).not.toHaveBeenCalled();
        expect(notice()).toBeNull();
    });

    it('reloads a changed page nobody would notice, and stops watching', async () => {
        const stopped = vi.fn();
        window.addEventListener('ig-watch:stopped', stopped);
        const wire = watchPage();

        await checkNow();
        await vi.advanceTimersByTimeAsync(60_000);

        expect(wire.watchReload).toHaveBeenCalledTimes(1);
        expect(wire.watchCheck).toHaveBeenCalledTimes(1);
        expect(stopped).toHaveBeenCalledTimes(1);
        expect(notice()).toBeNull();
        window.removeEventListener('ig-watch:stopped', stopped);
    });

    it('shows a notice with Reload instead while the user moves', async () => {
        const wire = watchPage();
        window.dispatchEvent(new Event('mousemove'));

        await checkNow();

        expect(wire.watchReload).not.toHaveBeenCalled();
        expect(notice().textContent).toContain('Changed');
        notice().querySelector('button').click();
        expect(wire.watchReload).toHaveBeenCalledTimes(1);
    });

    it('reloads while the user moves right after they asked for fresh data', async () => {
        const wire = watchPage();
        window.dispatchEvent(new Event('mousemove'));
        clock.allowReload(20);

        await checkNow();

        expect(wire.watchReload).toHaveBeenCalledTimes(1);
    });

    it('shows a notice rather than reload again right after its own reload', async () => {
        sessionStorage.setItem('ig-page-watch-reloaded', window.location.href);
        const wire = watchPage();

        await checkNow();

        expect(wire.watchReload).not.toHaveBeenCalled();
        expect(notice().textContent).toContain('Changed');
    });
});
