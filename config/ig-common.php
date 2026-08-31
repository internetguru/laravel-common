<?php

return [
    'meta_robots' => env('META_ROBOTS', null),

    'umami_src' => env('UMAMI_SRC', 'https://umami.internetguru.io/script.js'),
    'umami_website_id' => env('UMAMI_WEBSITE_ID', ''),
    'umami_identify' => env('UMAMI_IDENTIFY', true),
    'umami_identify_hash' => env('UMAMI_IDENTIFY_HASH', false),

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

    // Collapse repeats of the same log record into one entry per window, so a
    // single fault - a scanner working through a component, a failing
    // dependency - cannot bury the rest of the log. Applied to every configured
    // channel.
    'log_deduplication' => [
        'enabled' => env('IG_LOG_DEDUPLICATION', true),
        // Levels to collapse, matched exactly and comma separated. A level that
        // is not listed is never collapsed, so 'error' does not cover
        // 'critical' and above.
        'levels' => explode(',', env('IG_LOG_DEDUPLICATION_LEVELS', 'error,debug')),
        'seconds' => env('IG_LOG_DEDUPLICATION_SECONDS', 60),
    ],

    'association_history' => [
        // Map model FQN to a translation key prefix used to label its history columns.
        // Column names are resolved as "{prefix}.{column_name}" via the translator;
        // missing keys fall back to the raw column name.
        // Example: App\Models\Reservation::class => 'reservation.history.column',
        'columns' => [],

        // Optional relation overrides for foreign key columns whose belongs-to
        // relation is not named after the column. Values are resolved to the
        // related model's label (display_name, name, title, label or code).
        // Example: App\Models\Reservation::class => ['owner_id' => 'user'],
        'relations' => [],
    ],
];
