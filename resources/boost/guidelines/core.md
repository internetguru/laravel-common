# Laravel Common (internetguru/laravel-common)

Shared foundation of every Internet Guru application: Blade components, input sanitization, middleware, macros, labels, ULIDs, notifications and error pages. The full reference is `vendor/internetguru/laravel-common/README.md`; read the relevant section before using a feature for the first time.

## Components (`x-ig::`)

- Build forms with `x-ig::form` (it adds a reCAPTCHA v3 field whenever laravel-recaptchav3 is enabled, which it is not locally, in tests or for signed-in users; pass `:recaptcha="false"` to turn it off), `x-ig::input` (the slot is the label; `type` is any native input type, or `textarea`, `checkbox`, or `select` with `:options` as a list of values, `id`/`name` pairs, or a keyed array with `useoptionkeys`) and `x-ig::submit`. They render labels, validation errors and old input.
- Other components: `card`, `card-row`, `modal` (opened with `window.igModal.open(id)`), `label`, `breadcrumb`, `print-button`, `share-page`, `copy-url`, `tag-cloud`, `editable`, `association-history`, `footer`, `lang-switch`, `admin-button-text`. Check a component's class in `src/View/Components` for its props before using it.
- Session flashes (`success`, `errors`) are shown by `<livewire:ig-messages />`. From a Livewire component, show a message with `$this->dispatch('ig-message', type: 'success', message: __('…'))`.
- Package views are namespaced `ig-common::`, translations `ig-common::file.key`. An application overrides a view in `resources/views/vendor/ig-common`.
- Sass and JS come in through Vite aliases: `@import 'ig::common/variables'`, `'ig::common/card'` and so on in `app.scss`, and `import 'ig::common-js'` in `app.js`, which registers the Alpine components `editable`, `print`, `clearable`, `cardRow` and `tagCloud`.

## Labels

- A fixed set of values (status, role, payment type) is a backed string enum implementing `InternetGuru\LaravelCommon\Contracts\HasLabel` with the `RendersLabel` trait. It defines `label()` (a `match` with literal translation keys) and `variant()` (a Bootstrap theme colour), and renders with `toLabelHtml()`.
- A value without a fixed set, such as a branch name from the database, is rendered with `InternetGuru\LaravelCommon\View\Components\Label::html($text, seed: $stableValue)`. The colour is derived from the seed, so seed on something that does not change with the locale.
- In Blade, use `<x-ig::label text="…" variant="success" />`. In PHP that returns markup (model browser formatters), use `toLabelHtml()` or `Label::html()`.

## Input sanitization

- Every validation (`$request->validate()`, Livewire `$this->validate()`, `validator()`) sanitizes input first, driven by `config/ig-common.php` → `sanitize`. That is why applications validate in controllers and Livewire components without Form Requests.
- **A new input field must resolve to a pipeline.** It is matched by field name (exact or glob such as `*_email`) or by one of its validation rules. Otherwise `UnmappedInputException` is thrown while `APP_DEBUG` is on, and the `strict` pipeline plus a log entry apply in production. When adding a field whose name and rules don't identify it, add it to `sanitize.types`.
- Livewire components with dynamic property names use the `SanitizesInput` trait (`sanitizeTypes()`), and read the cleaned values from what `validate()` returns.
- Passwords, `_token` and `#[Locked]` properties are never touched.

## Behaviour to keep in mind

- All middleware is registered in the `web` group automatically. `CheckPostItemNames` rejects POST field names containing a dot outside production. `PreventDuplicateSubmissions` rejects an identical POST within a minute. `TimezoneMiddleware` stores `display_timezone` in the session.
- The queue connection must not be `sync` outside unit tests; the provider throws at boot.
- With the package's `TranslationServiceProvider` in place, a missing translation key or parameter throws `TranslatorException` outside production. A missing key fails the page and its tests.
- `ReadOnlyServiceProvider` (when registered and `app.readonly` is true) blocks write queries with `DbReadOnlyException`.

## Helpers

- `Ulid32` trait on models: `ulidForHumans()`, `shortUlidForHumans()`, `ulidUrl()`, `ulidLink()`. Validate ULIDs with the `ulid32` rule or `new Ulid32`.
- Macros: `Str::ref()`, `Number::currencyForHumans()`, `$date->dateForHumans()`, `dateTimeForHumans()`, `timeForHumans()`, `toDisplayTimezone()`.
- Notifications extend `BaseNotification` (queued, retried) and build mail with `InternetGuru\LaravelCommon\Mail\MailMessage`. Sent mail is logged to `mail_logs`.
- `AssociationHistory` trait plus `$associationHistoryTracked` records field changes, shown by `<x-ig::association-history :model="…" />`.
