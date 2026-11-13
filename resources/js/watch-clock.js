// One clock for every watcher on the page; checks due in the same tick share one Livewire request
const TICK_MS = 1000;
const HIDDEN_FACTOR = 10;
const RETRY_SECONDS = [15, 30, 60, 120, 300];
const CHECK_ALL_DEBOUNCE_MS = 1000;
const CHECK_TIMEOUT_MS = 30000;
const IDLE_MS = 3000;
const STOP_REASONS = { 401: 'signedOut', 403: 'signedOut', 404: 'updated', 410: 'updated', 419: 'expired' };

const watchMethods = new Set(['watchRefresh', 'watchCheck']);

const watchers = new Set();
let messages = {};
let timer = null;
let stopped = false;
let permanentNotice = false;
let lastCheckAll = 0;
let lastActivity = 0;
let reloadAllowedUntil = 0;
let region = null;
let bar = null;
let trailingCheck = null;

// Same intervals meet on the same seconds; the offset per page spreads browsers apart
const offset = Math.floor(Math.random() * 15000);
const alignedTime = (ms) => Math.ceil((Date.now() + 1 - offset) / ms) * ms + offset;

function delayFor(watcher) {
    if (watcher.failures > 0) {
        return RETRY_SECONDS[Math.min(watcher.failures, RETRY_SECONDS.length) - 1] * 1000;
    }
    return document.hidden ? watcher.interval * HIDDEN_FACTOR : watcher.interval;
}

function schedule(watcher) {
    watcher.nextAt = alignedTime(delayFor(watcher));
}

async function run(watcher) {
    if (stopped || !navigator.onLine || !watchers.has(watcher)) {
        return;
    }
    if (watcher.busy) {
        watcher.queued = true;
        return;
    }
    watcher.busy = true;
    const token = Symbol('check');
    watcher.token = token;
    const timeout = setTimeout(() => {
        if (watcher.token === token) {
            watcher.token = null;
            watcher.busy = false;
            watcher.failures++;
            schedule(watcher);
        }
    }, CHECK_TIMEOUT_MS);
    try {
        await watcher.check();
        watcher.failures = 0;
    } catch (error) {
        const reason = STOP_REASONS[error?.status];
        if (reason) {
            stop(reason);
        } else if (navigator.onLine) {
            watcher.failures++;
        }
    } finally {
        clearTimeout(timeout);
        if (watcher.token !== token) {
            return;
        }
        watcher.busy = false;
        schedule(watcher);
        if (watcher.queued && !stopped) {
            watcher.queued = false;
            run(watcher);
        }
    }
}

function tick() {
    const now = Date.now();
    watchers.forEach((watcher) => {
        if (!watcher.busy && now >= watcher.nextAt) {
            run(watcher);
        }
    });
}

// Triggers arriving together (focus with visibility) make one check; an explicit request
// (a push) made right after one still gets its own, a moment later
export function checkAll({ trailing = true } = {}) {
    const wait = lastCheckAll + CHECK_ALL_DEBOUNCE_MS - Date.now();
    if (wait > 0) {
        if (trailing) {
            trailingCheck ??= setTimeout(() => {
                trailingCheck = null;
                checkAll();
            }, wait);
        }
        return;
    }
    lastCheckAll = Date.now();
    watchers.forEach(run);
}

const checkOnReturn = () => checkAll({ trailing: false });

// Assistive technology announces content added to a live region that already exists
function liveRegion() {
    if (!region?.isConnected) {
        region = document.querySelector('.ig-watch-region');
    }
    if (!region) {
        region = document.createElement('div');
        region.className = 'ig-watch-region';
        region.setAttribute('role', 'status');
        region.setAttribute('aria-live', 'polite');
        document.body.append(region);
    }
    return region;
}

function showBar(text, onReload = null) {
    bar?.remove();
    bar = document.createElement('div');
    bar.className = 'ig-watch-notice alert alert-warning shadow d-flex flex-wrap align-items-center gap-2 mb-0';
    bar.dataset.testid = 'watch-notice';
    const label = document.createElement('span');
    label.textContent = text;
    bar.append(label);
    if (onReload) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-link alert-link p-0 align-baseline';
        button.textContent = messages.reload;
        button.addEventListener('click', onReload);
        bar.append(button);
    }
    liveRegion().replaceChildren(bar);
}

function hideBar() {
    bar?.remove();
    bar = null;
}

// A notice that stays until the page reloads; the offline bar never replaces it
export function showNotice(text, onReload = () => window.location.reload()) {
    permanentNotice = true;
    showBar(text, onReload);
}

function stop(reason) {
    stopped = true;
    showNotice(messages[reason]);
    window.dispatchEvent(new Event('ig-watch:stopped'));
}

// A watched page says it is not being updated; any other page says what its messages component offers
function offlineMessage() {
    return watchers.size > 0 ? messages.offline : document.querySelector('[data-ig-offline]')?.dataset.igOffline;
}

function onOffline() {
    const text = offlineMessage();
    if (text && !permanentNotice) {
        showBar(text);
    }
}

function onOnline() {
    if (!permanentNotice) {
        hideBar();
    }
    checkAll();
}

function onVisibilityChange() {
    if (!document.hidden) {
        checkOnReturn();
    }
}

function onActivity() {
    lastActivity = Date.now();
}

// An HTTP redirect answering a background check (e.g. to the login page) must not take the page
// away; a component's own redirect is followed
function stopOnRedirect(Livewire) {
    Livewire.interceptRequest(({ request, onResponse, onRedirect }) => {
        let redirected = false;
        onResponse(({ response }) => {
            redirected = response.redirected;
        });
        onRedirect(({ preventDefault }) => {
            if (redirected && isWatchRequest(request.options?.body)) {
                preventDefault();
                stop('signedOut');
            }
        });
    });
}

let interceptorAdded = false;

function start() {
    if (!interceptorAdded && window.Livewire?.interceptRequest) {
        interceptorAdded = true;
        stopOnRedirect(window.Livewire);
    }
    if (timer) {
        return;
    }
    timer = setInterval(tick, TICK_MS);
    document.addEventListener('visibilitychange', onVisibilityChange);
    window.addEventListener('focus', checkOnReturn);
    window.addEventListener('pageshow', (event) => event.persisted && checkOnReturn());
    window.addEventListener('ig-watch:check', () => checkAll());
    document.addEventListener('livewire:navigated', () => {
        permanentNotice = false;
        hideBar();
    });
    ['mousemove', 'mousedown', 'keydown', 'wheel', 'touchstart'].forEach((type) => {
        window.addEventListener(type, onActivity, { passive: true, capture: true });
    });
}

// No watcher left (e.g. wire:navigate to another page); a notice that must stay is kept
function reset() {
    stopped = false;
    if (!permanentNotice) {
        hideBar();
        if (!navigator.onLine) {
            onOffline();
        }
    }
}

// Being offline breaks every link and form, so the bar shows on every page, not only where a watcher runs
window.addEventListener('online', onOnline);
window.addEventListener('offline', onOffline);
if (!navigator.onLine) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onOffline, { once: true });
    } else {
        onOffline();
    }
}

export function registerWatcher({ interval, check, messages: watcherMessages = {} }) {
    messages = { ...messages, ...watcherMessages };
    const watcher = { interval: interval * 1000, check, busy: false, queued: false, failures: 0, nextAt: 0 };
    watchers.add(watcher);
    schedule(watcher);
    start();
    if (!navigator.onLine) {
        onOffline();
    }
    return {
        stop: () => watchers.delete(watcher),
        unregister: () => {
            watchers.delete(watcher);
            if (watchers.size === 0) {
                reset();
            }
        },
    };
}

// Background calls of an application's own (e.g. a sync status) whose failures stay quiet as well
export function addWatchMethods(...methods) {
    methods.forEach((method) => watchMethods.add(method));
}

// Only background calls (typed values may ride along); the global error handlers leave its failure alone
export function isWatchRequest(payload) {
    try {
        const methods = (JSON.parse(payload)?.components ?? [])
            .flatMap((component) => (component.calls ?? []).map((call) => call.method))
            .filter((method) => method !== '$commit');
        return methods.length > 0 && methods.every((method) => watchMethods.has(method));
    } catch {
        return false;
    }
}

const isFieldDirty = (field) => {
    if (field.type === 'checkbox' || field.type === 'radio') {
        return field.checked !== field.defaultChecked;
    }
    if (field.type === 'file') {
        return field.files.length > 0;
    }
    if (field instanceof HTMLSelectElement) {
        return [...field.options].some((option) => option.selected !== option.defaultSelected);
    }
    return field.value !== field.defaultValue;
};

// Livewire's own check, without $wire.$dirty(), which adds an interceptor on every call
const hasPendingLivewireChanges = () => (window.Livewire?.all?.() ?? [])
    .some((component) => JSON.stringify(component.canonical) !== JSON.stringify(component.reactive));

export function isEditing() {
    if (document.activeElement?.isContentEditable) {
        return true;
    }
    // A closed x-ig::modal keeps .show and is hidden by its wrapper, so count only what is rendered
    if ([...document.querySelectorAll('.modal.show, .dropdown-menu.show')].some((el) => el.getClientRects().length > 0)) {
        return true;
    }
    if ((window.getSelection?.()?.toString() ?? '') !== '') {
        return true;
    }
    if (hasPendingLivewireChanges()) {
        return true;
    }
    return [...document.querySelectorAll('input, textarea, select')]
        .filter((field) => !['hidden', 'submit', 'button', 'reset'].includes(field.type))
        .some(isFieldDirty);
}

// Safe to reload on its own: nobody editing, the view at the top, nobody moving for a while
export function canReloadUnnoticed() {
    if (isEditing()) {
        return false;
    }
    return Date.now() < reloadAllowedUntil || (window.scrollY === 0 && Date.now() - lastActivity >= IDLE_MS);
}

// The user asked for fresh data (e.g. a refresh button): a change found soon after may reload even while they move
export function allowReload(seconds) {
    reloadAllowedUntil = Date.now() + seconds * 1000;
}
