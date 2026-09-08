<?php

use InternetGuru\LaravelCommon\Rules\Ulid32;

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

    // Normalize input before it is validated, driven by what a value is rather
    // than by a list of call sites. Applies to controllers and Livewire
    // components alike - see the Sanitization section of the README.
    'sanitize' => [
        'enabled' => env('IG_SANITIZE', true),

        // Never sanitized, never reported as unmapped - matched on the whole
        // path or on any segment of it, so 'password' covers 'user.password'.
        //
        // A password must not even be trimmed. The rest are machine-generated
        // values that carry a signature or address the framework itself:
        // normalizing them can only break them, and they are not input anyone
        // types.
        'except' => [
            'password',
            'password_confirmation',
            'current_password',
            '_token',
            '_method',
            '_previous',
            'g-recaptcha-response',
        ],

        // Named pipelines. A bare name used inside a pipeline expands to that
        // pipeline's operations, so 'base' composes into the others. An entry
        // may also be an invokable class or any callable, which is how an app
        // adds behaviour without registering anything.
        'pipelines' => [
            // The baseline. Removes what no honest form control submits:
            // control and zero-width characters, bidi overrides, stray CR,
            // doubled spaces and tabs, surrounding whitespace. Horizontal
            // whitespace only - line breaks survive, so a textarea keeps its
            // paragraphs.
            'base' => ['strip_invisible', 'normalize_newlines', 'collapse_spaces', 'trim'],

            // Applied to input that matched no type. Deliberately conservative:
            // this is the treatment for a field nobody declared.
            'strict' => ['base', 'squish'],

            'name' => ['base', 'squish'],
            'text' => ['base'],
            'email' => ['base', 'strip:\\s', 'ascii', 'lower'],
            'url' => ['base', 'strip:\\s'],
            'host' => ['base', 'strip:\\s', 'ascii', 'lower'],
            'number' => ['base', 'strip:\\s'],
            'digits' => ['base', 'digits'],
            'code' => ['base', 'strip:\\s'],
            'ulid' => ['base', 'strip:\\s', 'upper'],
            'ulid32' => ['base', 'ascii', 'lower', 'keep:a-z0-9'],
            'flag' => ['base', 'strip:\\s', 'lower'],
            'locale' => ['base', 'strip:\\s', 'lower'],
            'datetime' => ['base', 'squish'],
            'search' => ['base', 'squish', 'max:255'],

            // Drops the separators people type between groups of digits -
            // "+420 777 123 456", "(02) 1234-5678" - without touching anything
            // else, so a value that was never a phone number still fails its
            // rule.
            'phone' => ['base', 'strip:\\s', 'strip:()\\-./'],

            // Comma-separated list: one space after each comma, no empty
            // entries.
            'list' => ['base', 'squish', 'normalize_list'],

            // A DNS label. Length and hyphen placement stay in the validation
            // rule - this only reduces what was typed to the allowed alphabet.
            'subdomain' => ['base', 'ascii', 'lower', 'keep:a-z0-9-'],
        ],

        // What a value is => which pipeline normalizes it. A key is either a
        // field name (exact or glob) or a validation rule name / rule class FQN.
        //
        // A key is tried as a field name first, against the last named segment
        // of the data key - numeric segments are skipped, so 'recipients.0'
        // matches on 'recipients' - normalized with Str::snake() and
        // lowercased, so one entry covers 'toEmail', 'to_email' and
        // 'items.0.to_email' alike. Exact matches are tried before globs, and
        // globs in the order written. Only if nothing matched the name are the
        // field's validation rules consulted.
        'types' => [
            // People, companies, places
            'name' => 'name',
            '*_name' => 'name',
            'fullname' => 'name',
            'nickname' => 'name',
            'company' => 'name',
            'address' => 'name',
            'location' => 'name',
            'room' => 'name',
            'label' => 'name',
            'title' => 'name',
            'subject' => 'name',
            'author' => 'name',
            'role' => 'code',

            // Free text - line breaks preserved
            'note' => 'text',
            'message' => 'text',
            '*message' => 'text',
            'description' => 'text',
            '*description' => 'text',
            'content' => 'text',
            'conditions' => 'text',
            '*_suggestion' => 'text',

            // Comma-separated lists, not free text
            'allergens' => 'list',
            'groups' => 'list',

            // Contact
            'email' => 'email',
            '*_email' => 'email',
            // Keyed by e-mail address, holding the recipient's name.
            'recipients' => 'name',
            'phone' => 'phone',
            '*_phone' => 'phone',
            'tel' => 'phone',
            'url' => 'url',
            'website' => 'url',
            '*_url' => 'url',
            'endpoint' => 'url',
            'subdomain' => 'subdomain',
            'base_domain' => 'host',
            'domain' => 'host',
            '*_domain' => 'host',

            // Identifiers and codes
            'id' => 'code',
            '*_id' => 'code',
            'code' => 'code',
            '*_code' => 'code',
            'pin' => 'digits',
            'ref' => 'code',
            'ic' => 'digits',
            'dic' => 'code',
            'inventory_number' => 'code',
            'locale' => 'locale',
            'language' => 'locale',

            // Numbers
            'value' => 'number',
            'amount' => 'number',
            '*_price' => 'number',
            'price*' => 'number',
            'seats' => 'digits',
            'persons' => 'digits',
            'children' => 'digits',
            'duration' => 'number',
            'repetitions' => 'digits',
            'max_usage' => 'digits',
            '*_interval' => 'number',

            // Dates and times
            'datetime' => 'datetime',
            '*_at' => 'datetime',
            '*_from' => 'datetime',
            '*_to' => 'datetime',
            '*_date' => 'datetime',
            '*_date_string' => 'datetime',
            '*_time' => 'datetime',

            // Flags submitted as strings by checkboxes
            'anonymous' => 'flag',
            'standalone' => 'flag',
            'remember' => 'flag',
            'newsletter' => 'flag',
            'subscribe' => 'flag',
            'subscribed' => 'flag',
            'acknowledgement' => 'flag',
            'overnight' => 'flag',
            'visibility' => 'flag',
            'submit' => 'flag',

            // Search, matching what laravel-model-browser already does by hand
            'search_query' => 'search',
            'q' => 'search',

            // Validation rules. These reach fields the names above did not
            // claim - a field called 'value' validated as 'numeric', an
            // 'identifier' validated with Ulid32::class. Entries that repeat a
            // field name above ('email', 'url', 'digits') are harmless: both
            // spellings of the same concept want the same pipeline.
            'active_url' => 'url',
            'ip' => 'host',
            'ipv4' => 'host',
            'ipv6' => 'host',
            'mac_address' => 'host',
            'uuid' => 'host',
            'ulid' => 'ulid',
            'numeric' => 'number',
            'integer' => 'number',
            'decimal' => 'number',
            'digits_between' => 'digits',
            'alpha' => 'name',
            'alpha_num' => 'name',
            'alpha_dash' => 'name',
            'date' => 'datetime',
            'date_format' => 'datetime',
            'timezone' => 'datetime',
            'ulid32' => 'ulid32',
            Ulid32::class => 'ulid32',
        ],

        // What to do with a string input that matched nothing above. On debug,
        // throw so the field gets declared; otherwise apply the fallback
        // pipeline and log the field, once per field per request.
        'unmapped' => [
            'throw_when_debug' => env('IG_SANITIZE_STRICT', true),
            'pipeline' => 'strict',
            'log_level' => 'warning',
        ],
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
