# Laravel Common

> This package provides handy utilities for Laravel applications.

| branch  | status |
| :------------- | :------------- |
| main | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=main) |
| staging | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=staging) |
| dev | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/phpunit.yml/badge.svg?branch=dev) |


## Installation

You can install the package via Composer:

```bash
composer require internetguru/laravel-common
```

Create aliases for the package classes in the `config/app.php` file:

```php
    use Illuminate\Support\Facades\Facade;

    'aliases' => Facade::defaultAliases()->merge([
        'Helpers' => InternetGuru\LaravelCommon\Support\Helpers::class,
    ])->toArray(),
```

## Usage

### Helpers

> The `Helpers` class provides useful methods for Laravel applications.

You can use the `Helpers` class methods, such as `getAppInfoArray` and `getAppInfo`, to get information about the application.

```html
<meta name="generator" content="{{ Helpers::getAppInfo() }}"/>
```

For more available methods, please refer to the `Helpers` class.

### Casts

#### Carbon Interval

> Casts a string to a `CarbonInterval` and back.

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

### Blade Components

#### Breadcrumb

> Blade component that renders breadcrumb navigation.

Key features:
- Breadcrumb items are generated from the current URL path.
- Divider character can be customized, default `›`.
- Skip first segment option, default `true`. E.g. to skip the language.
- Translation support for each breadcrumb item.
- Short translation support for each breadcrumb item.

Translation keys are in format `navig.{segment}`. Short translation keys are in format `navig.{segment}.short`.

```html
<x-ig::breadcrumb divider="|">
```

#### Form components

> Blade components that render form elements.

Recaptcha is enabled by default. To disable it, set the `recaptcha` attribute to `false`.
You need to install the `internetguru/laravel-recaptchav3` package for the Recaptcha to work. See the [documentation](https://github.com/internetguru/laravel-recaptchav3) for more information.

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
