<?php

return [
    'umami_src' => env('UMAMI_SRC', 'https://umami.internetguru.io/script.js'),
    'umami_website_id' => env('UMAMI_WEBSITE_ID', ''),

    'lang_domains' => collect(explode(',', env('LANG_DOMAINS', '')))
        ->filter()
        ->mapWithKeys(function (string $item) {
            [$lang, $domain] = explode(':', $item, 2);

            return [$lang => $domain];
        })
        ->toArray(),

    // Route URI prefixes that should be treated as error pages in breadcrumbs
    // (no navigation generated, prevents missing translation warnings)
    'breadcrumb_skip_prefixes' => [
        '_debugbar',
        '_ignition',
        'livewire',
        'storage',
        'telescope',
        'horizon',
    ],
];
