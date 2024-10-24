# Laravel Common

> This package provides handy utilities for Laravel applications.

| Branch  | Status |
| :------------- | :------------- |
| Main | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=main) |
| Staging | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=staging) |
| Dev | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=dev) |

## Installation

You can install the package via Composer:

```bash
composer require internetguru/laravel-common
```

## Helper Methods ~ Globals

> The `Helpers` class provides useful methods for Laravel applications.

Configuration and example usage:

1. Add the following lines to the `config/app.php` file:

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

For all available methods, see the [Helpers](src/Support/Helpers.php) class.

## Translation Service Provider

> Enhanced logging for missing translations and variables.

- **Missing Translation Logging**: Logs warnings when a translation key is missing.
- **Missing Variables Logging**: Logs warnings when variables required in a translation string are not provided.
- **All Locale Check**: In debug mode, it checks all available locales for missing translations and variables.
- **TranslatorException**: Throws an `InternetGuru\LaravelCommon\Exceptions\TranslatorException` exception instead of logging when the app is not in production mode.

Add the following lines to the `config/app.php` file to use the `TranslationServiceProvider`:

```php
use Illuminate\Support\ServiceProvider;

'providers' => ServiceProvider::defaultProviders()->replace([
    Illuminate\Translation\TranslationServiceProvider::class => InternetGuru\LaravelCommon\TranslationServiceProvider::class,
])->toArray(),
```

## Carbon Interval Cast

> Casts a string to a `CarbonInterval` and back.

Example usage:

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

## Blade Components

> The package provides a set of Blade components for Laravel applications.

### Breadcrumb

> The Breadcrumb Blade component renders breadcrumb navigation in your application, helping users understand their location within the app's hierarchy.

Key Features:

- **Automatic Path Parsing**: Automatically parses the current URL and generates breadcrumb items based on your routes and translations.
- **Customizable Divider**: Allows customization of the divider symbol between breadcrumb items.
- **Localization Support**: Supports translation of breadcrumb items using Laravel's localization system.
- **Short and Long Labels**: Supports both short and long labels for breadcrumb items.
- **Segment Skipping**: Optionally skip a specified number of URL segments, useful for nested routes or prefixes.

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
- And your translation files (resources/lang/en/navig.php) include:
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

### System Messages

> The `messages` Blade component renders system success messages and error messages.

Include the component in your Blade template where you want the system messages to appear:

```html
<x-ig::system-messages />
```

### Form Inputs

> The package provides a set of Blade components for form inputs.

Notes:

- Google Recaptcha V3 is enabled by default. To disable it, set the `recaptcha` attribute to `false`.
- You need to install the [internetguru/laravel-recaptchav3](https://github.com/internetguru/laravel-recaptchav3) package for the Recaptcha to work.

Complete example:

```html
<x-ig::form action="route('test')" :recaptcha="false"/>
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

## Testing

To run the tests, use the following command:

```bash
./vendor/bin/phpunit
```

This package uses [Orchestra Testbench](https://github.com/orchestral/testbench) to bootstrap a minimal Laravel environment for testing.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
