# Laravel Common

> This package provides handy utilities for Laravel applications.

| branch  | status |
| :------------- | :------------- |
| main | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/laravel-tests.yml/badge.svg?branch=main) |
| staging | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/laravel-tests.yml/badge.svg?branch=staging) |
| dev | ![tests](https://github.com/internetguru/laravel-common/actions/workflows/laravel-tests.yml/badge.svg?branch=dev) |


## Installation

You can install the package via Composer:

```bash
composer require internetguru/laravel-common
```

## Usage

### Helpers

You can use the `Helpers` class methods, such as `getAppInfoArray` and `getAppInfo`, to get information about the application.

```php
use InternetGuru\LaravelCommon\Support\Helpers;

// Get an array of app info
$info = Helpers::getAppInfoArray();

// Get a string of app info
$infoString = Helpers::getAppInfo();
```

For more available methods, please refer to the `Helpers` class.

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
