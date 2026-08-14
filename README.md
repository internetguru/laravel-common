# Laravel Common

> This package provides handy utilities for Laravel applications.

| Branch  | Status | Code Coverage |
| :------------- | :------------- | :------------- |
| Main | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=main) | ![coverage](https://raw.githubusercontent.com/internetguru/laravel-common/refs/heads/badges/main-coverage.svg) |
| Staging | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=staging) | ![coverage](https://raw.githubusercontent.com/internetguru/laravel-common/refs/heads/badges/staging-coverage.svg) |
| Dev | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=dev) | ![coverage](https://raw.githubusercontent.com/internetguru/laravel-common/refs/heads/badges/dev-coverage.svg) |

## Table of Contents

- [Installation](#installation)
- [Run Tests Locally](#run-tests-locally)
- [E2E Tests](#e2e-tests)
- [Service Providers](#service-providers)
  - [CommonServiceProvider](#commonserviceprovider)
  - [ReadOnlyServiceProvider](#readonlyserviceprovider)
  - [TranslationServiceProvider](#translationserviceprovider)
- [Middleware](#middleware)
  - [CheckPostItemNames](#checkpostitemnames-middleware)
  - [InjectMetaRobots](#injectmetarobots-middleware)
  - [InjectUmamiScript](#injectumamiscript-middleware)
  - [PreventDuplicateSubmissions](#preventduplicatesubmissions-middleware)
  - [SetPrevPage](#setprevpage-middleware)
  - [TimezoneMiddleware](#timezonemiddleware)
  - [VerifyCsrfToken](#verifycsrftoken)
- [Helper Methods](#helper-methods)
- [Helper Macros](#helper-macros)
- [Blade Components](#blade-components)
  - [Breadcrumb](#breadcrumb-blade-component)
  - [System Messages (Livewire)](#system-messages-livewire-component)
  - [Form & Inputs](#form-blade-components)
  - [Language Switch](#language-switch-blade-component)
  - [Print Button](#print-button-blade-component)
  - [Footer Copy](#footer-copy-blade-component)
  - [Footer](#footer-blade-component)
  - [Modal](#modal-blade-component)
  - [Share Page](#share-page-blade-component)
  - [Copy URL](#copy-url-blade-component)
  - [Card](#card-blade-component)
  - [Card Row](#card-row-blade-component)
  - [Tag Cloud](#tag-cloud-blade-component)
  - [Demo Info](#demo-info-blade-component)
  - [Read-Only Mode Info](#read-only-mode-info-blade-component)
  - [Email Feedback](#email-feedback-blade-component)
  - [Editable](#editable-blade-component)
  - [Admin Button Text](#admin-button-text-blade-component)
  - [Association History](#association-history-blade-component)
  - [Component Signatures](#component-signatures-blade-component)
- [Casts](#casts)
  - [CarbonIntervalCast](#carbonintervalcast)
- [Traits](#traits)
  - [Ulid32 Trait](#ulid32-trait)
- [Rules](#rules)
  - [Ulid32 Validation Rule](#ulid32-validation-rule)
- [Services](#services)
  - [GeolocationService](#geolocationservice)
- [Notifications](#notifications)
  - [BaseNotification](#basenotification)
  - [MailMessage](#mailmessage)
  - [Mail Logging](#mail-logging)
- [Exception Handling](#exception-handling)
- [Logging](#logging)
  - [JsonDailyLogger](#jsondailylogger)
- [Localization](#localization)
- [Publishing Assets](#publishing-assets)

## Installation

You can install the package via Composer:

```bash
composer require internetguru/laravel-common
```

The `CommonServiceProvider` is auto-discovered via `composer.json` `extra.laravel.providers`. No manual registration is needed for the core provider.

## Run Tests Locally

To run the tests manually, you can use the following command:

```sh
./test.sh
```

## E2E Tests

The package ships reusable [Playwright](https://playwright.dev/) test helpers in `tests/e2e/common-tests.js`. Consuming projects can register them into their own test suite with a single call.

### Setup

1. Install Playwright in your project if not already present:

    ```bash
    npm init playwright@latest
    ```

2. In your test file, import and register the helpers:

    ```js
    import { test, expect } from '@playwright/test';
    import { registerCommonTests } from '../../vendor/internetguru/laravel-common/tests/e2e/common-tests.js';

    registerCommonTests(test, expect, {
      languages: { en: 'English', cs: 'Česky' },
      demo: process.env.APP_DEMO === 'true',
    });
    ```

### Options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `languages` | `object` | `{ en: 'English', cs: 'Česky' }` | Language code → label map. Language switch tests are skipped when only one language is configured. |
| `demo` | `boolean` | `false` | When `true`, includes a test verifying the demo mode banner is visible. |

### Covered test groups

| Group | What is tested |
| --- | --- |
| `layout` | `header`, `main`, `footer` presence; `charset`, `viewport`, and `title` meta tags. |
| `breadcrumb` | Visibility, item count, active state, growth on subpages. |
| `language switch` | Visibility, correct item count, active highlight, language change, persistence across pages. _(skipped when `languages` has one entry)_ |
| `error pages` | 401, 403, 404, 500, 503 status codes and `h1` content; error index links; unknown code falls back to 404. |
| `messages` | `.messages-wrapper` is present on every page. |
| `demo mode` | Demo banner visible. _(skipped unless `demo: true`)_ |
| `csrf` | CSRF token meta tag is present and non-empty. |
| `i18n pages` | `/i18n`, `/i18n/complete`, `/i18n/missing-all`, `/i18n/missing-cs`, `/i18n/missing-en` all load. |
| `html structure` | `<html lang>` attribute is set; exactly one `<h1>` per page. |

## Service Providers

### CommonServiceProvider

Auto-registered via package discovery. It provides:

- Custom [exception handler](#exception-handling) registration.
- Loading of package routes, views (`ig-common` namespace), translations, and Blade components (`ig` namespace).
- Registration of the [Livewire Messages](#system-messages-livewire-component) component.
- Listener for [mail logging](#mail-logging) (`NotificationSent` event).
- Registration of the [`ulid32` validation rule](#ulid32-validation-rule).
- Registration of all [helper macros](#helper-macros) (String, Number, Carbon).
- Automatic registration of all [middleware](#middleware) into the `web` middleware group.
- Queue connection safety check — throws an exception at boot if the queue connection is set to `sync` (except during unit tests).

### ReadOnlyServiceProvider

> Intercepts all write database queries and throws `DbReadOnlyException` when `config('app.readonly')` is `true`.

Read-only operations (`SELECT`, `SHOW`, `DESCRIBE`, `EXPLAIN`, `PRAGMA`) are always allowed. Queries targeting `sessions`, `pin_logins`, `mail_logs`, `users`, and `socialites` tables are whitelisted.

To use, register the provider in your `config/app.php`:

```php
'providers' => ServiceProvider::defaultProviders()->merge([
    InternetGuru\LaravelCommon\ReadOnlyServiceProvider::class,
])->toArray(),
```

Then set `readonly` to `true` in `config/app.php` (or via environment variable) to activate read-only mode.

### TranslationServiceProvider

> Logs missing translations and translation variables in the current language. Throws an exception when not in production environment. In debug mode, checks all available languages.

- **Logs warning** when a translation key is missing or a variable required in a translation string is not provided.
- **Checks all languages** in debug mode from all available locales.
- **Throws exception** `InternetGuru\LaravelCommon\Exceptions\TranslatorException` instead of logging when the app is not in production mode.

To use the provider, replace the default `TranslationServiceProvider` in `config/app.php`:

```php
use Illuminate\Support\ServiceProvider;

'providers' => ServiceProvider::defaultProviders()->replace([
    Illuminate\Translation\TranslationServiceProvider::class => InternetGuru\LaravelCommon\TranslationServiceProvider::class,
])->toArray(),
```

## Middleware

All middleware listed below is automatically registered in the `web` middleware group by the `CommonServiceProvider`. No manual registration is needed.

To bypass a specific middleware on a route, use the `withoutMiddleware` method:

```php
Route::get('/example', ExampleController::class)
    ->withoutMiddleware(\InternetGuru\LaravelCommon\Http\Middleware\PreventDuplicateSubmissions::class);
```

### `CheckPostItemNames` Middleware

> Checks for invalid POST parameter names containing dots `"."`. Helps prevent issues with Laravel's input handling. Throws an exception in non-production environments and logs a warning in production.

Example:

- When a POST request contains parameter names with dots:

    ```http
    POST /submit-form
    Content-Type: application/x-www-form-urlencoded

    username=johndoe&user.email=johndoe@example.com
    ```

- **In Non-Production Environments**: The middleware will throw an HTTP 400 exception:

  ```
  Invalid POST parameter names containing dots: user.email
  ```

- **In Production Environment**: The middleware will log a warning:

  ```
  [WARNING] Invalid POST parameter names containing dots: user.email
  ```

### `InjectMetaRobots` Middleware

> Automatically injects a `<meta name="robots">` tag into HTML responses before `</head>` when `META_ROBOTS` is set.

Set the following environment variable to enable:

| Variable | Description | Default |
| --- | --- | --- |
| `META_ROBOTS` | Robots directive (e.g. `noindex`, `noindex,nofollow`). | `null` (disabled) |

Example `.env`:

```dotenv
META_ROBOTS=noindex,nofollow
```

The injected tag:

```html
<meta name="robots" content="noindex,nofollow"/>
```

### `InjectUmamiScript` Middleware

> Automatically injects the [Umami](https://umami.is/) analytics tracking script into HTML responses when `UMAMI_WEBSITE_ID` is set.

The script is injected before the closing `</head>` tag. Set the following environment variables to enable:

| Variable | Description | Default |
| --- | --- | --- |
| `UMAMI_WEBSITE_ID` | Your Umami website ID (required to enable tracking). | `''` (disabled) |
| `UMAMI_SRC` | URL to the Umami tracking script. | `https://umami.internetguru.io/script.js` |
| `UMAMI_IDENTIFY` | Send user identity data (`id`, `user_type`, optional `user_role`) on page load. | `true` |
| `UMAMI_IDENTIFY_HASH` | Hash the user ID with SHA-256 before sending. | `false` |

Example `.env`:

```dotenv
UMAMI_WEBSITE_ID=0d38f931-afdc-4a99-a913-5c601fc95629
```

The injected script:

```html
<script defer src="https://umami.internetguru.io/script.js" data-website-id="0d38f931-afdc-4a99-a913-5c601fc95629"></script>
```

### `PreventDuplicateSubmissions` Middleware

> Prevents duplicate POST form submissions by caching a hashed request fingerprint (IP + path + input minus reCAPTCHA) for 1 minute. Livewire update requests are excluded.

When a duplicate submission is detected, the user is redirected back with input and an error message.

### `SetPrevPage` Middleware

> Tracks the current and previous page URLs in the session for GET requests. Used internally by the exception handler to redirect users back to meaningful pages on errors.

Ignores AJAX requests and image (`img/*`) requests. Prevents tracking the same URL consecutively.

### `TimezoneMiddleware`

> Detects the user's timezone via IP geolocation and stores it in the session as `display_timezone`.

Uses the [GeolocationService](#geolocationservice) to resolve the IP address. Falls back to `config('geoip.default_location.timezone')` on failure. Resolves only once per session.

### `VerifyCsrfToken`

> Extends Laravel's CSRF verification with HMAC-based request signature verification. Requests containing a valid `X-Signature` and `X-Timestamp` header pair bypass CSRF checks. Livewire routes are also excluded by default.

The signature is validated using the app key with a 60-second freshness window.

## Helper Methods

> The `Helpers` class provides useful static methods for Laravel applications.

Configuration and example usage:

1. Add the following lines to `config/app.php`:

    ```php
    use Illuminate\Support\Facades\Facade;

    'aliases' => Facade::defaultAliases()->merge([
        'Helpers' => InternetGuru\LaravelCommon\Support\Helpers::class,
    ])->toArray(),
    ```

2. Use `Helpers` class methods in your application:

    ```html
    <meta name="generator" content="{{ Helpers::getAppInfo() }}"/>
    ```

Available methods:

| Method | Description |
| --- | --- |
| `getAppInfoArray()` | Returns app name, environment, version, git branch, and commit as an array. |
| `getAppInfo()` | Returns app info as a single string. |
| `parseUrlPath($homeRoute, $skipFirst)` | Parses the current URL path into breadcrumb segments with translations. |
| `createTitle($separator, $homeRoute)` | Generates a page title from breadcrumb segments (reversed, separated). |
| `getEmailClientLink()` | Returns a link to the Mailpit inbox when using Mailpit mailer. |
| `verifyRequestSignature(Request $request)` | Verifies HMAC-SHA256 request signature (`X-Signature` + `X-Timestamp` headers). |

For full implementation details, see the [Helpers](src/Support/Helpers.php) class.

## Helper Macros

> The package registers a set of useful macros for `Str`, `Carbon`, and `Number`. See [macros.php](src/Support/macros.php) for the complete list.

### String Macros

| Macro | Description |
| --- | --- |
| `Str::ref($length)` | Generates a random alphanumeric reference code (excludes ambiguous characters `i`, `l`, `o`, `0`, `1`, `u`). Starts with a letter and contains at least one digit. |

### Number Macros

| Macro | Description |
| --- | --- |
| `Number::currencyForHumans($number, $in, $precision)` | Formats a number as a locale-aware currency string. Returns the currency symbol if no number is provided. |
| `Number::formatCurrencyToInput($number, $in, $precision, $inputTemplate)` | Formats a number for use inside an input field with a currency symbol. |

### Carbon Macros

| Macro | Description |
| --- | --- |
| `$date->dateForHumans()` | Locale-aware date (`L` format). |
| `$date->dateTimeForHumans()` | Locale-aware date and time (`L LT` format). |
| `$date->myDiffForHumans()` | Human-readable time difference with "just now" for <60 seconds and "1 year" normalization. |
| `$date->timeForHumans()` | Clean time format (removes leading zeros and `:00`). |
| `$date->toDisplayTimezone()` | Converts the date to the user's display timezone stored in the session (`display_timezone`), falling back to `config('app.timezone')`. |
| `$date->randomWorkTime($from, $to)` | Sets a random time during work hours (default 9–17). |

Example usage:

```php
use Carbon\Carbon;
use Illuminate\Support\Facades\Number;
use Illuminate\Support\Str;

echo Str::ref(6);
// Output: "k3mhpq"

Number::useCurrency('USD');
echo Number::currencyForHumans(1234);
// Output (en_US locale): $1,234
echo Number::currencyForHumans();
// Output (en_US locale): $
echo Number::currencyForHumans(1234.567, in: 'EUR', precision: 2);
// Output (en_US locale): €1,234.57

$date = Carbon::parse('2023-12-31');
echo $date->dateForHumans();
// Output (en_US locale): 12/31/2023
$dateTime = Carbon::parse('2023-12-31 18:30:00');
echo $dateTime->dateTimeForHumans();
// Output (en_US locale): 12/31/2023 6:30 PM
```

## Blade Components

All Blade components are registered under the `ig` namespace and can be used with `<x-ig::component-name />`.

### Breadcrumb Blade Component

> Renders breadcrumb navigation based on routes matching the current URL segments. Supports translations with short and long labels, custom divider, and segment skipping.

Key Features:

- **Customizable Divider** – Allows a custom divider symbol between breadcrumb items.
- **Short and Long Labels** – Using `trans_choice` if available shows n-th right translation based on the item position.
- **Segment Skipping** – Skips a specified number of URL segments. Useful for nested routes or routes with prefixes (e.g. language).
- **Skip Prefixes** – Routes whose URI starts with a configured prefix are treated as error pages (no breadcrumb generated), preventing missing translation warnings. Configured via `breadcrumb_skip_prefixes` in `config/ig-common.php`:

    ```php
    'breadcrumb_skip_prefixes' => [
        '_debugbar',
        '_ignition',
        'livewire',
        'storage',
        'telescope',
        'horizon',
    ],
    ```

Usage:

```html
<!-- By default, this will generate breadcrumb items based on the current URL path. -->
<x-ig::breadcrumb/>
<!-- You can change the divider symbol by setting the divider attribute -->
<x-ig::breadcrumb divider="|" />
<!-- If you need to skip certain segments of the URL (e.g., a language prefix), use the skipFirst attribute -->
<x-ig::breadcrumb :skipFirst="1" />
```

Example:

- Assuming you have the following routes defined:
    ```php
    <?php
    Route::get('/', function () {
        // ...
    })->name('home');

    Route::get('/products', function () {
        // ...
    })->name('products.index');

    Route::get('/products/{product}', function ($product) {
        // ...
    })->name('products.show');
    ```
- And your translation files (`resources/lang/en/navig.php`) include:
    ```php
    <?php
    return [
        'home' => 'Long Application Name|LAN',
        'products.index' => 'All Products|Products',
        'products.show' => 'Product Details',
    ];
    ```
- When you visit the `/products/123` URL, the short translation will be used for the `home` and `products.index` routes.
    ```
    LAN > Products > Product Details
    ```
- When you visit the `/products` URL, the short label will be used for the `home` route.
    ```
    LAN > All Products
    ```
- When you visit the `/` URL, the long label will be used for the `home` route.
    ```
    Long Application Name
    ```

### System Messages (Livewire Component)

> Renders system temporary success messages and persistent error messages in different colors, with a close button. Powered by Livewire.

The component automatically picks up session `success` and `errors` data. You can also send messages dynamically via Livewire events.

Include the component in your Blade template:

```html
<livewire:ig-messages />
```

Dispatching messages from other Livewire components:

```php
$this->dispatch('ig-message', type: 'success', message: 'Item saved!');
$this->dispatch('ig-message', type: 'danger', message: 'Something went wrong.');
```

### Form Blade Components

> The package provides a set of Blade components for forms and various input types.

Notes:

- The [Google reCAPTCHA V3](https://developers.google.com/recaptcha/docs/v3) service is enabled by default. To disable it, set the `recaptcha` attribute to `false`.

Complete example:

```html
<x-ig::form action="route('test')" :recaptcha="false">
    <x-ig::input type="text" name="name" required>Name</x-ig::input>
    <x-ig::input type="option" name="simple-options" :value="['a', 'b', 'c']">Simple Options</x-ig::input>
    <x-ig::input type="option" name="advanced-options" :value="[
        ['id' => '1', 'value' => 'User 1' ],
        ['id' => '2', 'value' => 'User 2' ],
        ['id' => '3', 'value' => 'User 3' ],
    ]">Advanced Options</x-ig::input>
    <x-ig::input type="checkbox" name="checkbox" value="1">Checkbox</x-ig::input>
    <x-ig::input type="radio" name="radio" value="1">Radio</x-ig::input>
    <x-ig::input type="textarea" name="description">Description</x-ig::input>
    <x-ig::submit>Submit Form</x-ig::submit>
</x-ig::form>
```

### Language Switch Blade Component

> Renders a language switcher as a list of links with the current language highlighted.

```html
<x-ig::lang-switch />
```

### Print Button Blade Component

> Renders a print button that triggers the browser's print dialog.

```html
<x-ig::print-button />
```

### Footer Copy Blade Component

> Renders a copyright footer with provider information and year range.

```html
<x-ig::footer-copy />
<x-ig::footer-copy icon="" />
<x-ig::footer-copy icon="fa-solid fa-fw fa-leaf" />
```

| Prop | Default | Description |
| --- | --- | --- |
| `icon` | `null` | `null` renders the bundled duotone seedling SVG, an empty string omits the icon, any other value is used as an icon class. The icon is coloured by the `provider-ico` class (`$provider-ico-color`). |

### Footer Blade Component

> Renders the default application footer: page QR code, copy link, complaints form link, technical feedback form link, language switch and copyright.

```html
<x-ig::footer />
```

Both feedback forms are [laravel-feedback](https://github.com/internetguru/laravel-feedback) Livewire components declared by the footer itself, with the ids `feedback-form` and `complaints-form`. The technical feedback form is rendered whenever that package is installed and goes to the provider address; the complaints form additionally requires `complaints-email` to be set. Without the package the footer silently drops both forms and their links, everything else is rendered as usual.

Anything passed to the slot is rendered at the top of the footer, above the links. The component's own content is wrapped in a single `div`, so `footer > div` can be styled uniformly when the slot passes block elements too.

```html
<x-ig::footer
    complaints-email="restaurant@example.com"
    :complaints-locations="['Restaurant Downtown', 'Restaurant Airport']"
>
    <ul class="list-inline">
        <li class="list-inline-item"><a href="https://www.facebook.com/example">Facebook</a></li>
    </ul>
</x-ig::footer>
```

| Prop | Default | Description |
| --- | --- | --- |
| `feedback-email` | `ig-common::layouts.provider.email` | Technical feedback recipient. |
| `feedback-name` | `ig-common::layouts.provider.name` | Technical feedback recipient name. |
| `feedback-title` | `ig-common::layouts.support.link` | Link text and modal title. |
| `feedback-subject` | `ig-common::layouts.support.subject` | Email subject. |
| `feedback-description` | `null` | Modal description, falls back to the feedback package default. |
| `feedback-fields` | `message`, `attachments`, `email` | Technical feedback field definitions, overrides the defaults entirely. |
| `complaints-email` | `null` | Complaints recipient; the form is omitted when not set. |
| `complaints-name` | `config('app.name')` | Complaints recipient name. |
| `complaints-title` | `ig-common::layouts.complaints.link` | Link text and modal title. |
| `complaints-subject` | app name + title | Email subject. |
| `complaints-description` | `null` | Modal description. |
| `complaints-fields` | `location`, `occurred_at`, `message`, `email` | Feedback field definitions, overrides the defaults entirely. |
| `complaints-locations` | `[]` | Location names rendered as a required select; the field is omitted when empty. |
| `feedback-icon` | none | Optional technical feedback link icon class. |
| `complaints-icon` | none | Optional complaints link icon class. |
| `share` | `true` | Render the share page link. |
| `lang-switch` | `true` | Render the language switch. |
| `generated` | `false` | Render the page generation time in the display timezone. |

The `location` and `occurred_at` field definitions are registered into `ig-feedback.names` when laravel-feedback is installed, unless the application already defines them.

The technical feedback form declares an optional `attachments` field, so users can attach screenshots of what went wrong (up to 3 images or PDFs, 5 MB each). Limits and accepted types are configurable under `ig-feedback.names.attachments`, and `feedback-fields` replaces the field set altogether.

### Modal Blade Component

> Renders a Bootstrap modal whose markup is on the page from the start and only hidden, so it can be shown without a server round trip.

```blade
<x-ig::modal id="my-modal" title="My title">Body</x-ig::modal>
<a href="javascript:void(0)" onclick="window.igModal.open('my-modal')">Open</a>
```

| Prop | Default | Description |
| --- | --- | --- |
| `id` | slug of the title | Wrapper id, passed to `window.igModal` to open and close it. |
| `title` | `null` | Modal title, next to the close button. |
| `open` | `false` | Render it visible right away. |
| `centered` | `false` | Vertically centre the dialog. |
| `hash` | `null` | URL fragment opening the modal on page load; the fragment then follows it. |
| `wire-open` | `null` | Livewire property mirroring the open state, kept in sync so a re-render does not close the modal. |

Additional attributes are merged onto the wrapper. The wrapper carries the `ig-modal` class and is hidden with `d-none`; `window.igModal.open(id)`, `close(id)` and `closeAll()` toggle it, the escape key and a click on the backdrop close it. Every modal registers itself and is watched for class changes, so a Livewire re-render that opens or closes it is handled the same way as a click; opening and closing emit the bubbling `ig-modal-opened` and `ig-modal-closed` events. The helper is a small inline script, free of Alpine.js and Livewire, so the modal responds to the first click without waiting for a bundle to load.

### Share Page Blade Component

> Renders a link that opens a modal with a QR code of the current page URL, a copy button and the full URL below it, so the page can be opened on a phone or passed on.

```html
<x-ig::share-page />
<x-ig::share-page url="https://example.com/menu" title="Share the menu" :size="320">Menu QR</x-ig::share-page>
```

| Prop | Default | Description |
| --- | --- | --- |
| `url` | current URL | Encoded and shared content. |
| `title` | `ig-common::layouts.share.title` | Modal title. |
| `icon` | `fa-regular fa-fw fa-share-from-square` | Link icon class, pass an empty string to omit. |
| `size` | `240` | SVG size in pixels. |
| `id` | slug of the title | Modal id, also the URL fragment opening it. |

Links with an icon get the `link-ico` class, which positions the icon outside the link box so the link underline runs under the text only. The QR code is rendered server-side as an inline SVG, and the modal is the [modal component](#modal-blade-component), so it opens on the first click without waiting for a bundle to load, and linking to `#share-page` opens it on page load. The [copy URL](#copy-url-blade-component) button copies the URL to the clipboard, and the URL below it wraps over as many lines as it needs.

### Copy URL Blade Component

> Renders a link that copies the current page URL to the clipboard, confirming with a check icon for two seconds.

```html
<x-ig::copy-url />
<x-ig::copy-url url="https://example.com/menu">Copy the menu link</x-ig::copy-url>
```

| Prop | Default | Description |
| --- | --- | --- |
| `url` | current URL | Copied value and `href` fallback. |
| `icon` | `fa-regular fa-fw fa-copy` | Idle icon class, pass an empty string to omit both icons. |
| `copied-icon` | `fa-solid fa-fw fa-check` | Icon shown for two seconds after copying. |

Clipboard access requires a secure context (HTTPS or localhost); when it is unavailable the failure is reported as an error message.

### Card Blade Component

> Renders a card: an optional row of chips, a heading, a subtitle and free content, optionally turning its whole surface into a link.

```html
<x-ig::card title="Reduced balls and dual graphs" subtitle="Prague, 2026">
    <p>Content of the card.</p>
</x-ig::card>

<x-ig::card
    title="Higher-dimensional chordality"
    badge="Preprint"
    badge-type="preprint"
    link="https://example.com/paper"
    link-label="Read the paper"
/>

<x-ig::card title="What we do" gray />
```

| Prop | Default | Description |
| --- | --- | --- |
| `title` | – | Card heading. |
| `level` | `4` | Heading level of the title, so a card keeps the outline of the page it sits on. |
| `subtitle` | – | A line of context below the heading, rendered as `p.lead`. |
| `badge` | – | Text of the chip above the heading, or an array of several. |
| `badge-type` | – | Kind of chip, added as the `badge-{type}` class, which colours its dot. |
| `link` | – | Makes the whole card follow this link. |
| `link-label` | `ig-common::layouts.card.open` | Accessible name of the link, since the button itself is only an icon. |
| `icon` | `fa-solid fa-arrow-up-right-from-square` | Icon of the corner link button. |
| `gray` | `false` | Puts the card on a grey surface, with its heading and content centred. |

Styles come from `ig::common/card`. The dot colour per badge kind is taken from the `$card-badge-dots` map, keyed by `badge-type`, which is empty by default:

```scss
$card-badge-dots: (
    preprint: $orange,
    article: $green,
    thesis: $indigo,
);

@import 'ig::common/card';
```

Two content classes are styled for use inside the slot: `card-image` for a full-bleed picture reaching over the card's padding, and `card-list` for a list of what the card holds. An `actions` element is pushed to the foot of the card, so buttons line up across cards of unequal height.

### Card Row Blade Component

> Groups cards into a sideways scrolling carousel with paging arrows, or into a grid wrapping onto as many lines as needed.

```html
<x-ig::card-row label="Publications">
    <x-ig::card title="First" />
    <x-ig::card title="Second" />
</x-ig::card-row>

<x-ig::card-row label="Research directions" layout="grid" size="narrow" tinted centered>
    <x-ig::card title="First" />
</x-ig::card-row>
```

| Prop | Default | Description |
| --- | --- | --- |
| `label` | `ig-common::layouts.card_row.label` | Accessible name of the group of cards. |
| `layout` | `carousel` | `carousel` scrolls sideways with arrows, `grid` wraps onto as many lines as needed. |
| `size` | – | In the grid layout, `narrow` fits four cards to a line and `wide` two; omit for three. |
| `tinted` | `false` | Tints each card with a colour derived from the label. |
| `centered` | `false` | Centres the cards instead of pinning them to the left edge. |

Styles come from `ig::common/card-row`; the carousel needs the `cardRow` Alpine.js component, which `ig::common-js` registers. The row's gap, the widths its cards settle at and the look of the paging buttons are set by the `$card-row-*` variables, which have to be given before the stylesheet is imported. A carousel scrolls sideways, so its track clips downwards too — `$card-row-bleed` is the room the cards' shadows get inside it, and a card with a deeper shadow than the default needs it raised.

The tints are derived from the label, so a row keeps the same palette across requests while different rows get different colours. The palette repeats every eight cards, and neighbouring cards - including the wrap from the eighth back to the first - always sit about 135 degrees apart on the colour wheel.

### Tag Cloud Blade Component

> Shows a list of terms as coloured chips, or as a typographic word cloud whose lines are fitted to the width they have.

```html
<x-ig::tag-cloud :tags="['Research', 'Teaching', 'Awards']" />

<x-ig::tag-cloud typography tags="Research, Teaching, Awards" />
```

| Prop | Default | Description |
| --- | --- | --- |
| `tags` | `[]` | The terms to show, as a list of strings or a comma separated string. |
| `typography` | `false` | Draws the terms as plain coloured words of varying size, not as chips. |

Hues are spread by the golden angle, so neighbouring tags never land on a similar colour however many there are.

Styles come from `ig::common/tag-cloud`; the look of the chips and of the word cloud is set by the `$tag-*` and `$tag-cloud-*` variables, which have to be given before the stylesheet is imported. A cloud sitting on a card is usually given a negative inline margin through `$tag-cloud-margin`, so it reaches past the card's padding and reads as its own shape rather than as a block of text.

The typographic cloud needs the `tagCloud` Alpine.js component, which `ig::common-js` registers: its sizes are measured in the browser, since every line is packed and then scaled to fill the width of the cloud exactly. The cloud stays hidden until it has been measured, so the fallback sizes rendered server-side never show; without JavaScript those sizes are what is shown.

### Demo Info Blade Component

> Renders a demo mode warning banner informing users that displayed information is illustrative and may reset.

```html
<x-ig::demo-info />
```

### Read-Only Mode Info Blade Component

> Renders an informational banner indicating the application is in read-only mode and editing is disabled.

```html
<x-ig::read-only-mode-info />
```

### Email Feedback Blade Component

> Renders a technical support email link with pre-filled subject and diagnostic data.

```html
<x-ig::email-feedback />
```

### Editable Blade Component

> Provides an Alpine.js `editable` data component for inline editing functionality.

```html
<x-ig::editable />
```

### Admin Button Text Blade Component

> Renders a slot for button text that is replaced with a configurable admin label when the authenticated user is an admin.

```html
<x-ig::admin-button-text>Save</x-ig::admin-button-text>
```

When `auth()->user()->isAdmin()` is `true`, the slot content is replaced with the `ig-common::layouts.submit-admin` translation string.

### Association History Blade Component

> Renders a chronological edit history for a model, grouped by author and 10-minute time windows. Requires the `AssociationHistory` trait on the model.

**Setup:**

1. Publish and run the migration:

    ```bash
    php artisan vendor:publish --tag=ig-common:migrations
    php artisan migrate
    ```

2. Add the trait and declare which fields to track:

    ```php
    use InternetGuru\LaravelCommon\Traits\AssociationHistory;

    class Reservation extends Model
    {
        use AssociationHistory;

        protected array $associationHistoryTracked = ['status', 'note'];
    }
    ```

3. Optionally configure column label and label value translations in `config/ig-common.php`:

    ```php
    'association_history' => [
        'columns' => [
            \App\Models\Reservation::class => 'reservation.history.column',
        ],
    ],
    ```

    With corresponding translation keys like `reservation.history.column.status`, `reservation.history.column.status.pending`, `reservation.history.column.status.confirmed` and `reservation.history.column.note`.

4. Render the component:

    ```html
    <x-ig::association-history :model="$reservation" />
    ```

    Optional `:limit` attribute (default `10`) controls how many entries are loaded.

The component derives the "new value" for each entry from the current model state and automatically reads the `created_by` field (override via `$associationHistoryCreatedBy`) to prepend a creation entry.

### Component Signatures Blade Component

> Renders an HTML comment listing all installed `internetguru/*` package names and versions. Only active in debug mode.

```html
<x-ig::component-signatures />
```

## Casts

### CarbonIntervalCast

> Casts a string to a `CarbonInterval` and back. Uses `CarbonInterval::fromString()` to parse and `forHumans()` (in English locale) to serialize.

```php
use Illuminate\Database\Eloquent\Model;
use InternetGuru\LaravelCommon\Casts\CarbonIntervalCast;

class Task extends Model
{
    protected $casts = [
        'duration' => CarbonIntervalCast::class,
    ];
}
```

## Traits

### Ulid32 Trait

> Provides ULID (Crockford Base32) utility methods for Eloquent models. Generates 26-character Base32-encoded UUIDs and adds human-readable formatting, URL generation, and link rendering.

```php
use InternetGuru\LaravelCommon\Traits\Ulid32;

class Order extends Model
{
    use Ulid32;
}
```

Available methods:

| Method | Description |
| --- | --- |
| `$model->ulidForHumans()` | Formats the ULID with dashes (e.g. `01JM-ABCDEF-GHIJKL-MNOPQR-STUV`). |
| `$model->shortUlidForHumans()` | Returns the last 7 characters of the formatted ULID. |
| `$model->ulidUrl($usp)` | Generates a URL to the model's `show` route. |
| `$model->ulidLink($content)` | Renders an HTML link to the model. |
| `Model::generateBase32Uuid()` | Generates a new Crockford Base32-encoded UUID (26 characters). |

## Rules

### Ulid32 Validation Rule

> Validates that a value is a valid 26-character Crockford Base32 ULID (no `I`, `L`, `O`, `U` characters).

Can be used as a class-based rule or via the globally registered `ulid32` rule:

```php
// Class-based
use InternetGuru\LaravelCommon\Rules\Ulid32;

$request->validate([
    'code' => ['required', new Ulid32],
]);

// String-based (registered globally by CommonServiceProvider)
$request->validate([
    'code' => 'required|ulid32',
]);
```

## Services

### GeolocationService

> Resolves an IP address to a geographic location using the `torann/geoip` package. Results are cached and rate-limited (5 lookups per 60 seconds).

```php
use InternetGuru\LaravelCommon\Services\GeolocationService;

$geoService = app(GeolocationService::class);
$location = $geoService->getLocation('8.8.8.8');

echo $location->timezone; // "America/Chicago"
echo $location->country;  // "US"
```

Throws `GeolocationServiceException` on failure or rate limit.

## Notifications

### BaseNotification

> Abstract queued notification class that captures request context (IP, timezone, user ID, URL) at creation time and sends via the `mail` channel.

Features:

- **Queued** with 10 retries and 2-minute backoff.
- **Retry middleware** via `LogNotificationFailure` — logs warnings on transient failures.
- Captures the sender's IP, timezone (via geolocation), authenticated user ID, and current page URL.
- Logs permanently failed notifications.

Extend this class to create your own notifications:

```php
use InternetGuru\LaravelCommon\Notifications\BaseNotification;

class OrderConfirmation extends BaseNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new \InternetGuru\LaravelCommon\Mail\MailMessage)
            ->setExtraMailData($this->getExtraMailData())
            ->subject('Order Confirmed')
            ->view(['html' => 'emails.order-confirmed', 'text' => 'emails.order-confirmed-text']);
    }
}
```

### MailMessage

> Extends Laravel's `MailMessage` with automatic reference number generation, no-reply detection, and extra mail data injection.

Features:

- Appends a random reference code (`Ref XXXXX`) to every subject line for tracking.
- Automatically detects no-reply addresses and adds a "replies not delivered" note.
- Supports arbitrary extra data (`setExtraMailData`) passed to email views (IP, timezone, user ID, etc.).

Available methods:

| Method | Description |
| --- | --- |
| `->to($address, $name)` | Set one or multiple recipients. Accepts a single address or an array of `[email => name]` pairs. |
| `->withoutRefNumber()` | Suppress the `Ref XXXXX` suffix from the subject and footer. |
| `->setRefNumber($ref)` | Override the auto-generated reference code. |
| `->setExtraMailData($data)` | Merge additional data passed to the email view. |

### Mail Logging

> All sent mail notifications are automatically logged to the `mail_logs` database table via the `LogSentNotification` listener.

Publish the migration to create the `mail_logs` table:

```bash
php artisan vendor:publish --tag=ig-common:migrations
php artisan migrate
```

Logged fields: `to`, `replyto`, `subject`, `body`, `created_at`, `updated_at`.

## Exception Handling

> The package registers a custom exception handler that provides user-friendly error pages and JSON responses for common HTTP errors (401, 402, 403, 404, 419, 429, 500, 503).

Features:

- **Read-only mode**: `DbReadOnlyException` returns a 503 response (JSON or redirect with error).
- **Connection errors**: `ConnectException` returns a friendly error message.
- **Rate limiting (429)** and **session expiration (419)**: Handled with translated messages.
- **Debug mode**: Uses `dd()` for detailed exception inspection.
- **JSON support**: Returns JSON responses when the request expects JSON.
- Redirects to the previously tracked page (via `SetPrevPage` middleware) on error.
- **503 auto-refresh**: The 503 error page automatically reloads after 30 seconds.

Custom error views are included for standard HTTP status codes. The error pages use the `ig-common::layouts.base` layout.

## Logging

### JsonDailyLogger

> A custom Monolog logger that writes JSON-formatted daily rotating log files enriched with request context.

Each log entry includes:

- **User info**: ID, name, email (if authenticated).
- **Request input**: All inputs except `password`, `_token`, and `g-recaptcha-response`. Livewire snapshot data is stripped.
- **Session ID**.
- **App info**: Git branch and commit.
- **Request info**: URL, IP, HTTP method, server, referrer, user agent.

Configuration in `config/logging.php`:

```php
'channels' => [
    'json_daily' => [
        'driver' => 'custom',
        'via' => InternetGuru\LaravelCommon\Logging\JsonDailyLogger::class,
        'path' => storage_path('logs/app.log'),
        'days' => 14,
        'level' => 'debug',
    ],
],
```

## Localization

The package includes translations in **English** (`en`), **Czech** (`cs`), and **Danish** (`da`) for:

- **Error pages** (`errors.php`) – HTTP status messages and descriptions.
- **Layout strings** (`layouts.php`) – Footer, email templates, support links, and UI labels.
- **System messages** (`messages.php`) – Validation messages, email labels, and demo mode warnings.
- **Navigation** (`navig.php`) – Breadcrumb labels for routes.

## Publishing Assets

You can publish package assets to customize them:

```bash
# Publish database migrations
php artisan vendor:publish --tag=ig-common:migrations

# Publish configuration
php artisan vendor:publish --tag=ig-common:config

# Publish views for customization
php artisan vendor:publish --tag=ig-common:views

# Publish language files
php artisan vendor:publish --tag=ig-common:lang
```

## License & Commercial Terms

### License

Copyright © 2026 **Internet Guru**

This software is licensed under the [Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (CC BY-NC-SA 4.0)](http://creativecommons.org/licenses/by-nc-sa/4.0/) license.

> **Disclaimer:** This software is provided "as is", without warranty of any kind, express or implied. In no event shall the authors or copyright holders be liable for any claim, damages or other liability.

### Commercial Use

The standard CC BY-NC-SA license prohibits commercial use. If you wish to use this software in a commercial environment or product, we offer **flexible commercial licenses** tailored to:

* Your company size.
* The nature of your project.
* Your specific integration needs.

**Note:** In many instances (especially for startups or small-scale tools), this may result in no fees being charged at all. Please contact us to obtain written permission or a commercial agreement.

**Contact for Licensing:** [info@internetguru.io](mailto:info@internetguru.io)

### Professional Services

Are you looking to get the most out of this project? We are available for:

* **Custom Development:** Tailoring the software to your specific requirements.
* **Integration & Support:** Helping your team implement and maintain the solution.
* **Training & Workshops:** Seminars and hands-on workshops for your developers.

Reach out to us at [info@internetguru.io](mailto:info@internetguru.io) — we are more than happy to assist you!
