import { canReloadUnnoticed, registerWatcher, showNotice } from './watch-clock';

const RELOADED_KEY = 'ig-page-watch-reloaded';

const storage = {
    take(key) {
        try {
            const value = sessionStorage.getItem(key);
            sessionStorage.removeItem(key);
            return value;
        } catch {
            return null;
        }
    },
    put(key, value) {
        try {
            sessionStorage.setItem(key, value);
        } catch {
            // without storage the loop guard is off, nothing else changes
        }
    },
};

// Watches a whole page (x-ig::page-watch): on a change it reloads when nobody would notice,
// otherwise it shows a notice that stays; either way it stops watching
export default ({ interval = 15, messages = {} } = {}) => ({
    init() {
        // A change found right after the watcher reloaded means a source never matches; don't loop
        this.justReloaded = storage.take(RELOADED_KEY) === window.location.href;
        this.watcher = registerWatcher({
            interval,
            messages,
            check: () => this.check(),
        });
    },

    destroy() {
        this.watcher?.unregister();
    },

    async check() {
        const { changed, message } = await this.$wire.watchCheck();
        const justReloaded = this.justReloaded;
        this.justReloaded = false;
        if (!changed) {
            return;
        }
        this.watcher.stop();
        window.dispatchEvent(new Event('ig-watch:stopped'));
        if (!justReloaded && canReloadUnnoticed()) {
            storage.put(RELOADED_KEY, window.location.href);
            this.$wire.watchReload();
            return;
        }
        showNotice(message, () => this.$wire.watchReload());
    },
});
