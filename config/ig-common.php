<?php

return [
    'umami_src' => env('UMAMI_SRC', 'https://umami.internetguru.io/script.js'),
    'umami_website_id' => env('UMAMI_WEBSITE_ID', ''),

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
