import { registerWatcher } from './watch-clock';

// Refreshes the surrounding Livewire component (WatchesForChanges) whenever its data changed
export default ({ interval = 15, messages = {} } = {}) => ({
    init() {
        this.watcher = registerWatcher({
            interval,
            messages,
            check: () => this.$wire.watchRefresh(),
        });
    },

    destroy() {
        this.watcher?.unregister();
    },
});
