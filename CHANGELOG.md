# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [0.7.6] - 2025-04-04

### Fixed

- Fix 419, 429 and ConnectException go back with input and error message.
- Fix missing json error handling.

## [0.7.5] - 2025-04-03

### Fixed

- Fix parseUrlPath not working with livewire properly.

## [0.7.4] - 2025-04-03

### Fixed

- Add SetPrevPage middleware to fix laravel _prev session.

## [0.7.3] - 2025-04-03

### Fixed

- Remove floating label custom styles to fix background.

## [0.7.2] - 2025-04-03

### Fixed

- Make label color transparent.

## [0.7.1] - 2025-04-01

### Fixed

- Fix error pages and reduce views.)

## [0.7.0] - 2025-04-01

_Stable release based on [0.7.0-rc.1]._

## [0.7.0-rc.1] - 2025-04-01

### Added

- Add error handler and error pages.
- Add errors index page.
- Add i18n index page.

## [0.6.3] - 2025-03-14

### Fixed

- Update error pages.

## [0.6.2] - 2025-03-14

### Fixed

- Show if component signature is not available.

## [0.6.1] - 2025-03-14

### Fixed

- Fix missing email subcopy usp plain.

## [0.6.0] - 2025-03-14

_Stable release based on [0.6.0-rc.1]._

## [0.6.0-rc.1] - 2025-03-14

### Added

- Add base view.
- Add Translator test pages.

### Fixed

- Fix Translator missing translation detection and fallback.

## [0.5.4] - 2025-03-13

### Fixed

- Fix Translator double logging in debug mode.

## [0.5.3] - 2025-03-13

### Fixed

- Always log missing translation.

## [0.5.2] - 2025-03-13

### Fixed

- Fix Translator fallback.

### Changed

- Make multiple i18n testing pages.

## [0.5.1] - 2025-03-13

### Fixed

- Add missing email button usp.

## [0.5.0] - 2025-03-13

_Stable release based on [0.5.0-rc.2]._

## [0.5.0-rc.2] - 2025-03-13

### Added

- Add email layouts, components and translations.

## [0.5.0-rc.1] - 2025-02-19

## [0.4.7] - 2025-03-04

### Fixed

- Check vendor for missing translations.

## [0.4.6] - 2025-03-04

### Fixed

- Demonstrate translations on /test-trans page.

## [0.4.5] - 2025-03-04

### Fixed

- Fix check for missing translations only when debug true.

## [0.4.4] - 2025-03-03

### Fixed

- Keep placeholder bg to white.

## [0.4.3] - 2025-02-27

### Fixed

- Hide branch and commit in component signatures because vendor normaly not have `.git` folder.

## [0.4.2] - 2025-02-19

### Fixed

- Add missing mark styles.

## [0.4.1] - 2025-02-19

### Added

- Add component signatures component.

## [0.4.0] - 2025-02-19

_Stable release based on [0.4.0-rc.1]._

## [0.4.0-rc.1] - 2025-02-19

### Added

- Add `CheckPostItemNames` middleware.
- Add Number currency and Carbon macros.
- Generate code-coverage badges.
- Add Dockerfile for local testing.
- Add `getEmailClientLink` function to helpers.
- Add `<x-ig::breadcrumb>` blade component.
- Merge `internetguru/blade-components` into this package.
- Add custom Translator class to handle missing translation keys and variables.

## [0.3.1] - 2024-09-12

### Fixed

- Fix CarbonIntervalCast usage.

## [0.3.0] - 2024-09-12

_Stable release based on [0.3.0-rc.1]._

## [0.3.0-rc.1] - 2024-09-12

### Added

- Add `carbon-interval` cast.

## [0.2.0] - 2024-09-12

_Stable release based on [0.2.0-rc.1]._

## [0.2.0-rc.1] - 2024-09-12

### Changed

- Update README.md file installation instructions and usage examples.

## [0.1.0] - 2024-09-12

_Stable release based on [0.1.0-rc.1]._

## [0.1.0-rc.1] - 2024-09-12

### Added

- Create tests for `Helpers` class.
- Add `Helpers` class with `getAppInfoArray` and `getAppInfo` methods.
- Initial project structure.
- New changelog file.

## [0.0.0] - 2024-09-12

[0.7.6]: https://https://github.com/internetguru/laravel-common/compare/v0.7.5...v0.7.6
[0.7.5]: https://https://github.com/internetguru/laravel-common/compare/v0.7.4...v0.7.5
[0.7.4]: https://https://github.com/internetguru/laravel-common/compare/v0.7.3...v0.7.4
[0.7.3]: https://https://github.com/internetguru/laravel-common/compare/v0.7.2...v0.7.3
[0.7.2]: https://https://github.com/internetguru/laravel-common/compare/v0.7.1...v0.7.2
[0.7.1]: https://https://github.com/internetguru/laravel-common/compare/v0.7.0...v0.7.1
[0.7.0]: https://https://github.com/internetguru/laravel-common/compare/v0.6.3...v0.7.0
[0.7.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.6.3
[0.6.3]: https://https://github.com/internetguru/laravel-common/compare/v0.6.2...v0.6.3
[0.6.2]: https://https://github.com/internetguru/laravel-common/compare/v0.6.1...v0.6.2
[0.6.1]: https://https://github.com/internetguru/laravel-common/compare/v0.6.0...v0.6.1
[0.6.0]: https://https://github.com/internetguru/laravel-common/compare/v0.5.4...v0.6.0
[0.6.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.5.4
[0.5.4]: https://https://github.com/internetguru/laravel-common/compare/v0.5.3...v0.5.4
[0.5.3]: https://https://github.com/internetguru/laravel-common/compare/v0.5.2...v0.5.3
[0.5.2]: https://https://github.com/internetguru/laravel-common/compare/v0.5.1...v0.5.2
[0.5.1]: https://https://github.com/internetguru/laravel-common/compare/v0.5.0...v0.5.1
[0.5.0]: https://https://github.com/internetguru/laravel-common/compare/v0.4.7...v0.5.0
[0.5.0-rc.2]: https://github.com/internetguru/laravel-common/releases/tag/v0.4.7
[0.5.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.4.2
[0.4.7]: https://https://github.com/internetguru/laravel-common/compare/v0.4.6...v0.4.7
[0.4.6]: https://https://github.com/internetguru/laravel-common/compare/v0.4.5...v0.4.6
[0.4.5]: https://https://github.com/internetguru/laravel-common/compare/v0.4.4...v0.4.5
[0.4.4]: https://https://github.com/internetguru/laravel-common/compare/v0.4.3...v0.4.4
[0.4.3]: https://https://github.com/internetguru/laravel-common/compare/v0.4.2...v0.4.3
[0.4.2]: https://https://github.com/internetguru/laravel-common/compare/v0.4.1...v0.4.2
[0.4.1]: https://https://github.com/internetguru/laravel-common/compare/v0.4.0...v0.4.1
[0.4.0]: https://https://github.com/internetguru/laravel-common/compare/v0.3.1...v0.4.0
[0.4.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.3.1
[0.3.1]: https://https://github.com/internetguru/laravel-common/compare/v0.3.0...v0.3.1
[0.3.0]: https://https://github.com/internetguru/laravel-common/compare/v0.2.0...v0.3.0
[0.3.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.2.0
[0.2.0]: https://https://github.com/internetguru/laravel-common/compare/v0.1.0...v0.2.0
[0.2.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.1.0
[0.1.0]: https://https://github.com/internetguru/laravel-common/compare/v0.0.0...v0.1.0
[0.1.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.0.0
[0.0.0]: git log v0.0.0
