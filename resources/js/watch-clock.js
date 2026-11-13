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
let toast = null;
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

// The watcher's message is a toast like any other, built in the browser from the template of
// <livewire:ig-messages />, since it reports the very moments a request fails; one at a time
function watchToast() {
    const container = document.querySelector('[data-ig-client-toasts]');
    const template = document.querySelector('template[data-ig-toast]');
    if (!container || !template) {
        return null;
    }
    if (!toast?.isConnected) {
        toast = container.querySelector('[data-testid=watch-notice]');
    }
    if (!toast) {
        toast = template.content.firstElementChild.cloneNode(true);
        toast.dataset.testid = 'watch-notice';
        // Not one of the server's toasts, which its own script shows and removes
        toast.dataset.igInit = '1';
        toast.classList.add('show');
        const close = toast.querySelector('[data-bs-dismiss]');
        close?.removeAttribute('data-bs-dismiss');
        close?.addEventListener('click', closeToast);
        container.append(toast);
    }
    return toast;
}

function showToast(text, onReload = null) {
    const body = watchToast()?.querySelector('.toast-body');
    if (!body) {
        return;
    }
    const label = document.createElement('span');
    label.textContent = text;
    body.replaceChildren(label);
    if (onReload) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-link ms-2 align-baseline ig-toast-action';
        button.textContent = messages.reload;
        button.addEventListener('click', onReload);
        body.append(button);
    }
}

function hideToast() {
    toast?.remove();
    toast = null;
}

// A closed notice stays closed; going offline may show the toast again
function closeToast() {
    hideToast();
    permanentNotice = false;
}

// A notice that stays until the page reloads or it is closed; going offline never replaces it
export function showNotice(text, onReload = () => window.location.reload()) {
    permanentNotice = true;
    showToast(text, onReload);
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
        showToast(text);
    }
}

function onOnline() {
    if (!permanentNotice) {
        hideToast();
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
        hideToast();
    });
    ['mousemove', 'mousedown', 'keydown', 'wheel', 'touchstart'].forEach((type) => {
        window.addEventListener(type, onActivity, { passive: true, capture: true });
    });
}

// No watcher left (e.g. wire:navigate to another page); a notice that must stay is kept
function reset() {
    stopped = false;
    if (!permanentNotice) {
        hideToast();
        if (!navigator.onLine) {
            onOffline();
        }
    }
}

// Being offline breaks every link and form, so the toast shows on every page, not only where a watcher
// runs; a copy loaded again (e.g. by a hot update) takes the listeners over
window.igWatchConnectivity?.abort();
window.igWatchConnectivity = new AbortController();
window.addEventListener('online', onOnline, { signal: window.igWatchConnectivity.signal });
window.addEventListener('offline', onOffline, { signal: window.igWatchConnectivity.signal });
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
