// @vitest-environment node
import { describe, expect, it } from 'vitest';
import igDevWatch, { isUnwatched } from '../../resources/js/vite-dev-watch.js';

const root = '/app';

describe('isUnwatched', () => {
    it.each([
        '/app/resources/js/app.js',
        '/app/vendor',
        '/app/vendor/internetguru/laravel-common/resources/sass/_chip.scss',
        '/app/storage/app/public/images/logo.png',
        '/app/storage',
    ])('watches %s', (file) => {
        expect(isUnwatched(file, root)).toBe(false);
    });

    it.each([
        '/app/vendor/laravel/framework/src/Foundation/Application.php',
        '/app/vendor/internetguru-foo/package/index.js',
        '/app/vendor/internetguru/laravel-common/vendor/autoload.php',
        '/app/vendor/internetguru/laravel-common/node_modules/vitest/index.js',
        '/app/storage/framework/views/abc.php',
        '/app/storage/framework/cache/data/ab/cd/ef',
        '/app/storage/logs/laravel.log',
    ])('ignores %s', (file) => {
        expect(isUnwatched(file, root)).toBe(true);
    });

    it('ignores relative to the root it is given', () => {
        expect(isUnwatched('/srv/site/vendor/laravel/framework/x.php', '/srv/site')).toBe(true);
        expect(isUnwatched('/app/vendor/laravel/framework/x.php', '/srv/site')).toBe(false);
    });
});

describe('igDevWatch', () => {
    it('runs only on the dev server', () => {
        expect(igDevWatch().apply).toBe('serve');
    });

    it('adds its rule to the watcher, relative to the resolved root', () => {
        const plugin = igDevWatch();
        const [matcher] = plugin.config().server.watch.ignored;
        plugin.configResolved({ root: '/srv/site' });

        expect(matcher('/srv/site/vendor/laravel/framework/x.php')).toBe(true);
        expect(matcher('/srv/site/resources/js/app.js')).toBe(false);
    });
});
