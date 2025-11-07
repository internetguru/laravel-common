# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [2.16.7] - 2025-11-07

### Added

- IP label before its value in email footer.

## [2.16.6] - 2025-11-07

### Fixed

- Email footer newlines.

## [2.16.5] - 2025-11-07

### Fixed

- Check input errors exists.

## [2.16.4] - 2025-11-07

### Fixed

- Fix clerable.

## [2.16.3] - 2025-11-04

### Fixed

- Fix double init clearable button.

## [2.16.2] - 2025-11-04

### Fixed

- Remove clearable input padding.

## [2.16.1] - 2025-11-04

### Fixed

- Make clearable work with livewire.

## [2.16.0] - 2025-11-04

_Stable release based on [2.16.0-rc.1]._

## [2.16.0-rc.1] - 2025-11-04

### Added

- Add alpine js `clearable` component to input forms, use it by default in ig input component.

### Fixed

- Allow to define ig input class.

## [2.15.3] - 2025-10-14

### Fixed

- Fix check missing variables for replace string containing `:`.

## [2.15.2] - 2025-10-13

### Fixed

- Fix timezone mw.

## [2.15.1] - 2025-10-13

### Fixed

- Fix timezone mw return.

## [2.15.0] - 2025-10-13

_Stable release based on [2.15.0-rc.1]._

## [2.15.0-rc.1] - 2025-10-13

### Added

- Add ip and timezone into email footer.
- Add `TimezoneMiddleware` to set `display_timezone` session by ip.

## [2.14.2] - 2025-10-10

### Fixed

- Fix comment.

## [2.14.1] - 2025-10-10

### Fixed

- Add missing printing dialog class.

## [2.14.0] - 2025-10-10

_Stable release based on [2.14.0-rc.1]._

## [2.14.0-rc.1] - 2025-10-10

### Added

- Add `print-button` component.

## [2.13.0] - 2025-10-09

_Stable release based on [2.13.0-rc.1]._

## [2.13.0-rc.1] - 2025-10-09

### Changed

- Add description to base layout.

## [2.12.6] - 2025-10-08

### Fixed

- Fix generating breadcrumb for protected routes.

## [2.12.5] - 2025-10-02

### Fixed

- Show 1 year for date between 11 months and 15 days and 12 months and 15 days.

## [2.12.4] - 2025-10-02

### Fixed

- Do not shrink message close button.

## [2.12.3] - 2025-10-02

### Fixed

- Skip bc routes that are not found.

## [2.12.2] - 2025-09-19

### Fixed

- Fix plain text email template

## [2.12.1] - 2025-09-19

### Fixed

- Fix email template

## [2.12.0] - 2025-09-19

_Stable release based on [2.12.0-rc.1]._

## [2.12.0-rc.1] - 2025-09-19

## [2.11.0] - 2025-09-18

_Stable release based on [2.11.0-rc.1]._

## [2.11.0-rc.1] - 2025-09-18

### Changed

- Remove external email translations.
- Simplify email template.

## [2.10.2] - 2025-09-09

### Fixed

- Fix event response can be null error in LogSentNotification.

## [2.10.1] - 2025-09-02

### Fixed

- Skip unnamed navig sub-routes.

## [2.10.0] - 2025-08-28

_Stable release based on [2.10.0-rc.1]._

## [2.10.0-rc.1] - 2025-08-28

### Changed

- Simplify eamil regards to "Sent from :url".

## [2.9.0] - 2025-08-25

_Stable release based on [2.9.0-rc.1]._

## [2.9.0-rc.1] - 2025-08-25

### Added

- Add danish translation.

## [2.8.8] - 2025-08-18

### Fixed

- Fix lang publish path.

## [2.8.7] - 2025-08-18

### Fixed

- Publish config, translations...

## [2.8.6] - 2025-08-15

### Fixed

- Add missing use.

## [2.8.5] - 2025-08-06

### Changed

- Geolocationservice throws exceptions instead of returning fallback.

## [2.8.4] - 2025-08-06

### Fixed

- Fix GeoLocation fallback.

## [2.8.3] - 2025-08-04

### Fixed

- Remove wire:ignore from form recaptcha.

## [2.8.2] - 2025-07-30

### Fixed

- Fix form wire ignore self to wire ignore.

## [2.8.1] - 2025-07-30

### Fixed

- Fix add wire ignore self to for recaptcha script"

## [2.8.0] - 2025-07-24

_Stable release based on [2.8.0-rc.1]._

## [2.8.0-rc.1] - 2025-07-24

## [2.7.1] - 2025-07-23

### Fixed

- Fix myDiffForHumans to be absolute.

## [2.7.0] - 2025-07-22

_Stable release based on [2.7.0-rc.1]._

## [2.7.0-rc.1] - 2025-07-22

## [2.6.2] - 2025-06-27

### Fixed

- Fix readonly mode translation.

## [2.6.1] - 2025-06-26

### Fixed

- Add users and socialites into allow tables for readonly mode.

## [2.6.0] - 2025-06-26

_Stable release based on [2.6.0-rc.1]._

## [2.6.0-rc.1] - 2025-06-26

### Changed

- Local mail port is always 8025.

## [2.5.1] - 2025-06-26

### Fixed

- Fix token auth not working in read-only mode.

## [2.5.0] - 2025-06-26

_Stable release based on [2.5.0-rc.1]._

## [2.5.0-rc.1] - 2025-06-26

### Changed

- Do not report DbReadOnlyException.
- Skip token_auths table queries in read-only mode.

## [2.4.2] - 2025-06-26

### Fixed

- Fix hide messages.

## [2.4.1] - 2025-06-25

### Fixed

- Update readonly info style.

## [2.4.0] - 2025-06-25

_Stable release based on [2.4.0-rc.1]._

## [2.4.0-rc.1] - 2025-06-25

### Added

- Add read-only mode info component.
- Add ReadOnlyServiceProvider to handle read-only mode.

## [2.3.2] - 2025-06-16

### Fixed

- Fix no action name.

## [2.3.1] - 2025-06-16

### Fixed

- Fix no-action form route testid.

## [2.3.0] - 2025-06-16

_Stable release based on [2.3.0-rc.1]._

## [2.3.0-rc.1] - 2025-06-16

### Added

- Add medium light border to system messages.

## [2.2.3] - 2025-06-16

### Fixed

- Fix getting route name.

## [2.2.2] - 2025-06-16

### Fixed

- Fix getting form action.

## [2.2.1] - 2025-06-16

### Fixed

- Fix missing testid.

## [2.2.0] - 2025-06-16

_Stable release based on [2.2.0-rc.1]._

## [2.2.0-rc.1] - 2025-06-16

### Added

- Add `data-testid` attributes to components for easier testing.

## [2.1.1] - 2025-05-31

### Fixed

- Make debug error page simple dd.

## [2.1.0] - 2025-05-29

_Stable release based on [2.1.0-rc.1]._

## [2.1.0-rc.1] - 2025-05-29

### Changed

- Update demo mode info styles and message.

## [2.0.5] - 2025-05-29

### Fixed

- Show exception status code in debug.

## [2.0.4] - 2025-05-28

### Fixed

- Throw execption iff app queue connection is sync.

## [2.0.3] - 2025-05-27

### Fixed

- Revert previous hotfix.

## [2.0.2] - 2025-05-27

### Fixed

- Fix footer section default content.

## [2.0.1] - 2025-05-15

### Fixed

- Log also replyto.

## [2.0.0] - 2025-05-15

_Stable release based on [2.0.0-rc.1]._

## [2.0.0-rc.1] - 2025-05-15

### Added

- Add `MailMessage` that add ref to subject.
- Log emails into database and to standard log using notification sent listener.

## [1.0.4] - 2025-05-09

### Fixed

- Fix compose recaptcha version to dev-master.

## [1.0.3] - 2025-05-09

### Fixed

- Add recaptcha to form only iff enabled.

## [1.0.2] - 2025-05-09

### Fixed

- Update ReCaptcha isEnabled function.

## [1.0.1] - 2025-05-09

### Fixed

- Fix recaptcha composer version.

## [1.0.0] - 2025-05-09

_Stable release based on [1.0.0-rc.1]._

## [1.0.0-rc.1] - 2025-05-09

### Added

- Bind default `ReCaptchaInterface` service.

## [0.14.3] - 2025-05-08

### Fixed

- Fix handling 403.

## [0.14.2] - 2025-05-07

### Fixed

- Do not log `Translator:has`.

## [0.14.1] - 2025-04-25

### Fixed

- Fix diff.sh to show composer.lock diff

## [0.14.0] - 2025-04-25

_Stable release based on [0.14.0-rc.1]._

## [0.14.0-rc.1] - 2025-04-25

### Added

- Add diff.sh script to show components version diff to given revision.

## [0.13.2] - 2025-04-25

### Fixed

- Fix email feedback variable name.

## [0.13.1] - 2025-04-25

### Fixed

- Fix tech support link encoding

## [0.13.0] - 2025-04-17

_Stable release based on [0.13.0-rc.1]._

## [0.13.0-rc.1] - 2025-04-17

### Added

- Add `<x-ig::footer-copy>` blade component.
- Add `randomWorkTime` macro for `Carbon`.
- Add `timeForHumans` macro for `Carbon`.

### Changed

- Always show `Ignition` error page in debug mode.

### Fixed

- Translate i18n index page.
- Fix navig translation for 404 pages.

## [0.12.1] - 2025-04-16

### Fixed

- Fix empty translation key in log.

## [0.12.0] - 2025-04-16

_Stable release based on [0.12.0-rc.1]._

## [0.12.0-rc.1] - 2025-04-16

### Added

- Support breadcrumb dynamic parameter translations.

### Fixed

- Fix `Translator::has` function check for missing translations with parameters.

## [0.11.2] - 2025-04-16

### Fixed

- Separate email salutation-plain.
- Separate email service into blade to be customizable.
- Fix error messages foreach error bag.

## [0.11.1] - 2025-04-16

### Fixed

- Fix success messages duplication.

## [0.11.0] - 2025-04-16

_Stable release based on [0.11.0-rc.1]._

## [0.11.0-rc.1] - 2025-04-16

### Changed

- Refactor messages component into livewire with `ig-message` event listener for dynamic messages.

## [0.10.2] - 2025-04-15

### Fixed

- Do not process any exception in testing mode.

## [0.10.1] - 2025-04-15

### Fixed

- Fix editable warning when submitting form.

## [0.10.0] - 2025-04-11

_Stable release based on [0.10.0-rc.1]._

## [0.10.0-rc.1] - 2025-04-11

### Added

- Add `email-feedback` blade component.

## [0.9.0] - 2025-04-09

_Stable release based on [0.9.0-rc.1]._

## [0.9.0-rc.1] - 2025-04-09

### Added

- Add editable Alpine JS component and simple blade helper `<x-ig::editable />`.

## [0.8.2] - 2025-04-08

### Fixed

- Fix route dynamic parameters.

## [0.8.1] - 2025-04-08

### Changed

- Separate email salutation into blade component to be customizable.

## [0.8.0] - 2025-04-07

_Stable release based on [0.8.0-rc.1]._

## [0.8.0-rc.1] - 2025-04-07

### Added

- Support route parameters in `parseUrlPath` ~ in breadcrumb and title.

## [0.7.11] - 2025-04-07

### Fixed

- Do not accept img requests im prevPage.

## [0.7.10] - 2025-04-07

### Fixed

- Fix routes are not in web middleware group.

## [0.7.9] - 2025-04-07

### Fixed

- Fix error page index description and links translations.

## [0.7.8] - 2025-04-04

### Fixed

- Keep Laravel handle the 500 error in debug mode.

## [0.7.7] - 2025-04-04

### Fixed

- Rethrow 500 if debug mode is enabled.

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

- Fix error pages and reduce views.

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

[2.16.7]: https://https://github.com/internetguru/laravel-common/compare/v2.16.6...v2.16.7
[2.16.6]: https://https://github.com/internetguru/laravel-common/compare/v2.16.5...v2.16.6
[2.16.5]: https://https://github.com/internetguru/laravel-common/compare/v2.16.4...v2.16.5
[2.16.4]: https://https://github.com/internetguru/laravel-common/compare/v2.16.3...v2.16.4
[2.16.3]: https://https://github.com/internetguru/laravel-common/compare/v2.16.2...v2.16.3
[2.16.2]: https://https://github.com/internetguru/laravel-common/compare/v2.16.1...v2.16.2
[2.16.1]: https://https://github.com/internetguru/laravel-common/compare/v2.16.0...v2.16.1
[2.16.0]: https://https://github.com/internetguru/laravel-common/compare/v2.15.3...v2.16.0
[2.16.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.15.3
[2.15.3]: https://https://github.com/internetguru/laravel-common/compare/v2.15.2...v2.15.3
[2.15.2]: https://https://github.com/internetguru/laravel-common/compare/v2.15.1...v2.15.2
[2.15.1]: https://https://github.com/internetguru/laravel-common/compare/v2.15.0...v2.15.1
[2.15.0]: https://https://github.com/internetguru/laravel-common/compare/v2.14.2...v2.15.0
[2.15.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.14.2
[2.14.2]: https://https://github.com/internetguru/laravel-common/compare/v2.14.1...v2.14.2
[2.14.1]: https://https://github.com/internetguru/laravel-common/compare/v2.14.0...v2.14.1
[2.14.0]: https://https://github.com/internetguru/laravel-common/compare/v2.13.0...v2.14.0
[2.14.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.13.0
[2.13.0]: https://https://github.com/internetguru/laravel-common/compare/v2.12.6...v2.13.0
[2.13.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.12.6
[2.12.6]: https://https://github.com/internetguru/laravel-common/compare/v2.12.5...v2.12.6
[2.12.5]: https://https://github.com/internetguru/laravel-common/compare/v2.12.4...v2.12.5
[2.12.4]: https://https://github.com/internetguru/laravel-common/compare/v2.12.3...v2.12.4
[2.12.3]: https://https://github.com/internetguru/laravel-common/compare/v2.12.2...v2.12.3
[2.12.2]: https://https://github.com/internetguru/laravel-common/compare/v2.12.1...v2.12.2
[2.12.1]: https://https://github.com/internetguru/laravel-common/compare/v2.12.0...v2.12.1
[2.12.0]: https://https://github.com/internetguru/laravel-common/compare/v2.11.0...v2.12.0
[2.12.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.11.0
[2.11.0]: https://https://github.com/internetguru/laravel-common/compare/v2.10.2...v2.11.0
[2.11.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.10.2
[2.10.2]: https://https://github.com/internetguru/laravel-common/compare/v2.10.1...v2.10.2
[2.10.1]: https://https://github.com/internetguru/laravel-common/compare/v2.10.0...v2.10.1
[2.10.0]: https://https://github.com/internetguru/laravel-common/compare/v2.9.0...v2.10.0
[2.10.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.9.0
[2.9.0]: https://https://github.com/internetguru/laravel-common/compare/v2.8.8...v2.9.0
[2.9.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.8.8
[2.8.8]: https://https://github.com/internetguru/laravel-common/compare/v2.8.7...v2.8.8
[2.8.7]: https://https://github.com/internetguru/laravel-common/compare/v2.8.6...v2.8.7
[2.8.6]: https://https://github.com/internetguru/laravel-common/compare/v2.8.5...v2.8.6
[2.8.5]: https://https://github.com/internetguru/laravel-common/compare/v2.8.4...v2.8.5
[2.8.4]: https://https://github.com/internetguru/laravel-common/compare/v2.8.3...v2.8.4
[2.8.3]: https://https://github.com/internetguru/laravel-common/compare/v2.8.2...v2.8.3
[2.8.2]: https://https://github.com/internetguru/laravel-common/compare/v2.8.1...v2.8.2
[2.8.1]: https://https://github.com/internetguru/laravel-common/compare/v2.8.0...v2.8.1
[2.8.0]: https://https://github.com/internetguru/laravel-common/compare/v2.7.1...v2.8.0
[2.8.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.7.1
[2.7.1]: https://https://github.com/internetguru/laravel-common/compare/v2.7.0...v2.7.1
[2.7.0]: https://https://github.com/internetguru/laravel-common/compare/v2.6.2...v2.7.0
[2.7.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.6.2
[2.6.2]: https://https://github.com/internetguru/laravel-common/compare/v2.6.1...v2.6.2
[2.6.1]: https://https://github.com/internetguru/laravel-common/compare/v2.6.0...v2.6.1
[2.6.0]: https://https://github.com/internetguru/laravel-common/compare/v2.5.1...v2.6.0
[2.6.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.5.1
[2.5.1]: https://https://github.com/internetguru/laravel-common/compare/v2.5.0...v2.5.1
[2.5.0]: https://https://github.com/internetguru/laravel-common/compare/v2.4.2...v2.5.0
[2.5.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.4.2
[2.4.2]: https://https://github.com/internetguru/laravel-common/compare/v2.4.1...v2.4.2
[2.4.1]: https://https://github.com/internetguru/laravel-common/compare/v2.4.0...v2.4.1
[2.4.0]: https://https://github.com/internetguru/laravel-common/compare/v2.3.2...v2.4.0
[2.4.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.3.2
[2.3.2]: https://https://github.com/internetguru/laravel-common/compare/v2.3.1...v2.3.2
[2.3.1]: https://https://github.com/internetguru/laravel-common/compare/v2.3.0...v2.3.1
[2.3.0]: https://https://github.com/internetguru/laravel-common/compare/v2.2.3...v2.3.0
[2.3.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.2.3
[2.2.3]: https://https://github.com/internetguru/laravel-common/compare/v2.2.2...v2.2.3
[2.2.2]: https://https://github.com/internetguru/laravel-common/compare/v2.2.1...v2.2.2
[2.2.1]: https://https://github.com/internetguru/laravel-common/compare/v2.2.0...v2.2.1
[2.2.0]: https://https://github.com/internetguru/laravel-common/compare/v2.1.1...v2.2.0
[2.2.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.1.1
[2.1.1]: https://https://github.com/internetguru/laravel-common/compare/v2.1.0...v2.1.1
[2.1.0]: https://https://github.com/internetguru/laravel-common/compare/v2.0.5...v2.1.0
[2.1.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v2.0.5
[2.0.5]: https://https://github.com/internetguru/laravel-common/compare/v2.0.4...v2.0.5
[2.0.4]: https://https://github.com/internetguru/laravel-common/compare/v2.0.3...v2.0.4
[2.0.3]: https://https://github.com/internetguru/laravel-common/compare/v2.0.2...v2.0.3
[2.0.2]: https://https://github.com/internetguru/laravel-common/compare/v2.0.1...v2.0.2
[2.0.1]: https://https://github.com/internetguru/laravel-common/compare/v2.0.0...v2.0.1
[2.0.0]: https://https://github.com/internetguru/laravel-common/compare/v1.0.4...v2.0.0
[2.0.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v1.0.4
[1.0.4]: https://https://github.com/internetguru/laravel-common/compare/v1.0.3...v1.0.4
[1.0.3]: https://https://github.com/internetguru/laravel-common/compare/v1.0.2...v1.0.3
[1.0.2]: https://https://github.com/internetguru/laravel-common/compare/v1.0.1...v1.0.2
[1.0.1]: https://https://github.com/internetguru/laravel-common/compare/v1.0.0...v1.0.1
[1.0.0]: https://https://github.com/internetguru/laravel-common/compare/v0.14.3...v1.0.0
[1.0.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.14.3
[0.14.3]: https://https://github.com/internetguru/laravel-common/compare/v0.14.2...v0.14.3
[0.14.2]: https://https://github.com/internetguru/laravel-common/compare/v0.14.1...v0.14.2
[0.14.1]: https://https://github.com/internetguru/laravel-common/compare/v0.14.0...v0.14.1
[0.14.0]: https://https://github.com/internetguru/laravel-common/compare/v0.13.2...v0.14.0
[0.14.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.13.2
[0.13.2]: https://https://github.com/internetguru/laravel-common/compare/v0.13.1...v0.13.2
[0.13.1]: https://https://github.com/internetguru/laravel-common/compare/v0.13.0...v0.13.1
[0.13.0]: https://https://github.com/internetguru/laravel-common/compare/v0.12.1...v0.13.0
[0.13.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.12.1
[0.12.1]: https://https://github.com/internetguru/laravel-common/compare/v0.12.0...v0.12.1
[0.12.0]: https://https://github.com/internetguru/laravel-common/compare/v0.11.2...v0.12.0
[0.12.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.11.2
[0.11.2]: https://https://github.com/internetguru/laravel-common/compare/v0.11.1...v0.11.2
[0.11.1]: https://https://github.com/internetguru/laravel-common/compare/v0.11.0...v0.11.1
[0.11.0]: https://https://github.com/internetguru/laravel-common/compare/v0.10.2...v0.11.0
[0.11.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.10.2
[0.10.2]: https://https://github.com/internetguru/laravel-common/compare/v0.10.1...v0.10.2
[0.10.1]: https://https://github.com/internetguru/laravel-common/compare/v0.10.0...v0.10.1
[0.10.0]: https://https://github.com/internetguru/laravel-common/compare/v0.9.0...v0.10.0
[0.10.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.9.0
[0.9.0]: https://https://github.com/internetguru/laravel-common/compare/v0.8.2...v0.9.0
[0.9.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.8.2
[0.8.2]: https://https://github.com/internetguru/laravel-common/compare/v0.8.1...v0.8.2
[0.8.1]: https://https://github.com/internetguru/laravel-common/compare/v0.8.0...v0.8.1
[0.8.0]: https://https://github.com/internetguru/laravel-common/compare/v0.7.11...v0.8.0
[0.8.0-rc.1]: https://github.com/internetguru/laravel-common/releases/tag/v0.7.11
[0.7.11]: https://https://github.com/internetguru/laravel-common/compare/v0.7.10...v0.7.11
[0.7.10]: https://https://github.com/internetguru/laravel-common/compare/v0.7.9...v0.7.10
[0.7.9]: https://https://github.com/internetguru/laravel-common/compare/v0.7.8...v0.7.9
[0.7.8]: https://https://github.com/internetguru/laravel-common/compare/v0.7.7...v0.7.8
[0.7.7]: https://https://github.com/internetguru/laravel-common/compare/v0.7.6...v0.7.7
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
