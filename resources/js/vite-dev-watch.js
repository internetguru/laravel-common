import path from 'node:path';

/**
 * Keeps the Vite dev server from watching what it never bundles: vendor/ apart from the
 * Internet Guru packages (and their own vendor/ and node_modules/), and Laravel's runtime
 * storage. Watching them uses up the Linux limit on watched files that every app shares.
 * The dev server only; a build never loads it.
 *
 * Register it in the application's `vite.config.js`:
 *
 *     import igDevWatch from './vendor/internetguru/laravel-common/resources/js/vite-dev-watch.js';
 *
 *     plugins: [laravel({ ... }), igDevWatch()],
 *
 * @return {import('vite').Plugin}
 */
export default function igDevWatch() {
    let root = process.cwd();

    return {
        name: 'ig-common-dev-watch',
        apply: 'serve',
        config: () => ({
            server: {
                watch: {
                    ignored: [(file) => isUnwatched(file, root)],
                },
            },
        }),
        configResolved(config) {
            root = config.root;
        },
    };
}

/**
 * @param {string} file an absolute path
 * @param {string} root the application's root
 * @return {boolean}
 */
export function isUnwatched(file, root) {
    const inRoot = (...parts) => file.startsWith(path.join(root, ...parts) + path.sep);
    // storage/app stays watched: public/storage links to it
    if (inRoot('storage', 'framework') || inRoot('storage', 'logs')) {
        return true;
    }
    if (!inRoot('vendor')) {
        return false;
    }
    const igVendorDir = path.join(root, 'vendor', 'internetguru') + path.sep;
    if (!file.startsWith(igVendorDir)) {
        return true;
    }
    const packageDir = file.slice(igVendorDir.length).split(path.sep)[1];
    return packageDir === 'vendor' || packageDir === 'node_modules';
}
