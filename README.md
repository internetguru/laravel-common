# Laravel Common

> This package provides handy utilities for Laravel applications.

| Branch  | Status | Code Coverage |
| :------------- | :------------- | :------------- |
| Main | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=main) | ![coverage](https://raw.githubusercontent.com/internetguru/laravel-common/refs/heads/badges/main-coverage.svg) |
| Staging | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=staging) | ![coverage](https://raw.githubusercontent.com/internetguru/laravel-common/refs/heads/badges/staging-coverage.svg) |
| Dev | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=dev) | ![coverage](https://raw.githubusercontent.com/internetguru/laravel-common/refs/heads/badges/dev-coverage.svg) |

## Installation

You can install the package via Composer:

```bash
composer require internetguru/laravel-common
```

## Run Tests Locally

In Visual Studio Code you can simpy use `Ctrl+Shift+B` to run the tests.

To run the tests manually, you can use the following commands:

```sh
# Build the Docker image
docker build -t laravel-common .
# Run the tests
docker run --rm laravel-common
# Both steps combined
docker build -t laravel-common . && docker run --rm laravel-common
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

## Helper Macros

> Package registers a set of useful macros for Carbon and Numbers. See the [Macros](src/Support/Macros.php) class for all available macros.

Example usage:

```php
use Carbon\Carbon;
use Illuminate\Support\Facades\Number;

Number::useCurrency('USD'); // Set the default currency
echo Number::currencyForHumans(1234);
// Output (en_US locale): $1,234
echo Number::currencyForHumans();
// Output (en_US locale): $
echo Number::currencyForHumans(1234.567, in: 'EUR', precision: 2);
// Output (en_US locale): €1,234.57
app()->setLocale('cs_CZ'); // Set the locale to Czech
echo Number::currencyForHumans(1234.567, in: 'EUR', precision: 2);
// Output (cs_CZ locale): 1 234,57 €

$date = Carbon::parse('2023-12-31');
echo $date->dateForHumans();
// Output (en_US locale): 12/31/2023
$dateTime = Carbon::parse('2023-12-31 18:30:00');
echo $dateTime->dateTimeForHumans();
// Output (en_US locale): 12/31/2023 6:30 PM
```

## Translation Service Provider

> Logs missing translations and translation variables in the current language. Throws an exception when not in production environment. In debug mode, checks all available languages.

- **Logs warning** when a translation key is missing or a variable required in a translation string is not provided.
- **Checks all languages** in debug mode from all available locales.
- **Throws exception** `InternetGuru\LaravelCommon\Exceptions\TranslatorException` instead of logging when the app is not in production mode.

To use the provider, add the following lines to the `config/app.php` file:

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

## Breadcrumb Blade Component

> Renders breadcrumb navigation based on routes matching the current URL segments. Supports translations with short and long labels, custom divider, and segment skipping.

Key Features:

- **Customizable Divider** – Allows a custom divider symbol between breadcrumb items.
- **Short and Long Labels** – Using `trans_choice` if available shows n-th right translation based on the item positon.
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

## System Messages Blade Component

> Renders system temporary success messages and persistent error messages in different colors, with a close button.

Include the component in your Blade template where you want the system messages to appear:

```html
<x-ig::system-messages />
```

## Form Blade Components

> The package provides a set of Blade components for form and various inputs.

Notes:

- The [Google Recaptcha V3](https://developers.google.com/recaptcha/docs/v3) service is enabled by default. To disable it, set the `recaptcha` attribute to `false`.
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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
