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
- [Service Providers](#service-providers)
  - [CommonServiceProvider](#commonserviceprovider)
  - [ReadOnlyServiceProvider](#readonlyserviceprovider)
  - [TranslationServiceProvider](#translationserviceprovider)
- [Middleware](#middleware)
  - [CheckPostItemNames](#checkpostitemnames-middleware)
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
  - [Demo Info](#demo-info-blade-component)
  - [Read-Only Mode Info](#read-only-mode-info-blade-component)
  - [Email Feedback](#email-feedback-blade-component)
  - [Editable](#editable-blade-component)
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

Read-only operations (`SELECT`, `SHOW`, `DESCRIBE`, `EXPLAIN`, `PRAGMA`) are always allowed. Queries targeting `sessions`, `token_auths`, `mail_logs`, `users`, and `socialites` tables are whitelisted.

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
```

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
