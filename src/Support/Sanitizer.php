<?php

namespace InternetGuru\LaravelCommon\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\InvokableValidationRule;
use Illuminate\Validation\ValidationRuleParser;
use InternetGuru\LaravelCommon\Exceptions\UnmappedInputException;
use InvalidArgumentException;
use Transliterator;

/**
 * Normalizes input before it is validated, driven by what a value is rather
 * than by where it was submitted from.
 *
 * A value's type comes from its field name or, failing that, from the
 * validation rules already declared at the call site; the type names a pipeline
 * of operations in ig-common.sanitize. Nothing here knows about HTTP or
 * Livewire, so the same rules cover both.
 *
 * Bound scoped, not singleton: the reported-key set below is per-request state,
 * and a scoped binding is flushed between requests under Octane.
 */
class Sanitizer
{
    /**
     * The built-in operation names.
     *
     * These are reserved: inside a pipeline they always mean the operation,
     * never a pipeline that happens to share the name. Without that rule the
     * shipped `digits` pipeline - ['base', 'digits'] - reads its own second
     * entry as a reference to itself.
     *
     * @var array<int, string>
     */
    private const OPERATIONS = [
        'trim', 'strip_invisible', 'normalize_newlines', 'collapse_spaces', 'squish',
        'lower', 'upper', 'ascii', 'digits', 'keep', 'strip', 'normalize_list', 'max', 'nullify',
    ];

    /**
     * Keys already reported as unmapped during this request, so one validate()
     * call over a component's whole property set does not repeat itself.
     *
     * @var array<string, true>
     */
    private array $reported = [];

    /**
     * Types declared for this request by a caller that knows something the
     * config cannot, keyed by field path.
     *
     * @var array<string, string>
     */
    private array $declared = [];

    /**
     * Operations of a pipeline being expanded, used to catch a config cycle.
     *
     * @var array<int, string>
     */
    private array $expanding = [];

    /**
     * Return $data with every string value normalized.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules  validation rules, used to type fields the name did not
     * @param  array<string, string>  $map  call-site overrides, field path => pipeline name
     * @return array<string, mixed>
     */
    public function sanitize(array $data, array $rules = [], array $map = []): array
    {
        foreach ($this->changes($data, $rules, $map) as $change) {
            $this->pathSet($data, $change['path'], $change['value']);
        }

        return $data;
    }

    /**
     * The values sanitization would change, keyed by dotted path.
     *
     * Returning only the differences is what lets callers write back to a
     * request or a Livewire component without touching anything untouched.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $map
     * @param  array<int, string>  $ignore  top-level keys to leave alone entirely
     * @return array<int, array{path: array<int, string>, key: string, value: mixed}>
     */
    public function changes(array $data, array $rules = [], array $map = [], array $ignore = []): array
    {
        if (! config('ig-common.sanitize.enabled', true)) {
            return [];
        }

        $changes = [];

        $this->walk(
            $data,
            [],
            [...(array) config('ig-common.sanitize.except', []), ...$ignore],
            $this->explodeRules($data, $rules),
            $map,
            $changes
        );

        return $changes;
    }

    /**
     * Visit every string leaf, carrying the path to it as a list of segments.
     *
     * Paths are kept segmented rather than dotted because an array key may
     * itself contain dots - laravel-feedback keys its recipients by e-mail
     * address - and re-splitting such a path would read the wrong name and,
     * on write-back, bury the value under a tree of invented keys.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $path
     * @param  array<int, string>  $except
     * @param  array<string, array<int, mixed>>  $rules
     * @param  array<string, string>  $map
     * @param  array<int, array{path: array<int, string>, key: string, value: mixed}>  $changes
     */
    private function walk(array $data, array $path, array $except, array $rules, array $map, array &$changes): void
    {
        foreach ($data as $segment => $value) {
            $segment = (string) $segment;
            $current = [...$path, $segment];

            if ($this->isExcepted($current, $except)) {
                continue;
            }

            if (is_array($value)) {
                $this->walk($value, $current, $except, $rules, $map, $changes);

                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            // Rules are keyed the way the validator keys them, which is the
            // segments joined with dots.
            $key = implode('.', $current);
            $sanitized = $this->pipe($value, $this->pipelineFor($current, $rules[$key] ?? [], $map));

            if ($sanitized !== $value) {
                $changes[] = ['path' => $current, 'key' => $key, 'value' => $sanitized];
            }
        }
    }

    /**
     * Read the value at a segmented path out of an array or object.
     *
     * @param  array<int, string>  $path
     */
    public function pathGet(mixed $target, array $path, mixed $default = null): mixed
    {
        foreach ($path as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];

                continue;
            }

            if (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};

                continue;
            }

            return $default;
        }

        return $target;
    }

    /**
     * Write a value at a segmented path into an array or object.
     *
     * An array held on an object property is returned by value, so each level
     * is written back explicitly rather than assigned through.
     *
     * @param  array<int, string>  $path
     */
    public function pathSet(mixed &$target, array $path, mixed $value): void
    {
        $segment = array_shift($path);

        if ($segment === null) {
            return;
        }

        if ($path === []) {
            if (is_array($target)) {
                $target[$segment] = $value;
            } elseif (is_object($target)) {
                $target->{$segment} = $value;
            }

            return;
        }

        if (is_array($target)) {
            $this->pathSet($target[$segment], $path, $value);
        } elseif (is_object($target)) {
            $child = $target->{$segment};
            $this->pathSet($child, $path, $value);
            $target->{$segment} = $child;
        }
    }

    /**
     * Record types for the rest of this request.
     *
     * A caller that types a field explicitly should not then see it reported as
     * unmapped when the validator resolver reaches the same data - the
     * declaration has to outlive the call that made it, which is why the
     * sanitizer is bound scoped.
     *
     * @param  array<string, string>  $map  field path => pipeline name
     */
    public function declareTypes(array $map): void
    {
        $this->declared = [...$this->declared, ...$map];
    }

    /**
     * The changes for just the paths named in $map, leaving every other value
     * alone and reporting nothing.
     *
     * This is the explicit path, for callers that know the type of a property
     * the config cannot describe.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $map  field path => pipeline name
     * @return array<int, array{path: array<int, string>, key: string, value: mixed}>
     */
    public function changesFor(array $data, array $map): array
    {
        if (! config('ig-common.sanitize.enabled', true)) {
            return [];
        }

        $changes = [];

        foreach ($map as $path => $pipeline) {
            $segments = explode('.', (string) $path);
            $value = $this->pathGet($data, $segments);

            if (! is_string($value)) {
                continue;
            }

            $sanitized = $this->pipe($value, $this->expand($pipeline));

            if ($sanitized !== $value) {
                $changes[] = ['path' => $segments, 'key' => (string) $path, 'value' => $sanitized];
            }
        }

        return $changes;
    }

    /**
     * The operations that normalize the value at $key.
     *
     * Resolution order, first match wins: the call-site map, types declared
     * earlier this request, the field name, the field's validation rules,
     * then the unmapped pipeline.
     *
     * @param  array<int, string>  $path
     * @param  array<int, mixed>  $fieldRules
     * @param  array<string, string>  $map
     * @return array<int, mixed>
     */
    public function pipelineFor(array $path, array $fieldRules = [], array $map = []): array
    {
        $types = (array) config('ig-common.sanitize.types', []);

        $name = $this->matchName($map, $path)
            ?? $this->matchName($this->declared, $path)
            ?? $this->matchName($types, $path)
            ?? $this->matchRules($types, $fieldRules);

        if ($name === null) {
            $this->reportUnmapped(implode('.', $path));

            $name = (string) config('ig-common.sanitize.unmapped.pipeline', 'strict');
        }

        return $this->expand($name);
    }

    /**
     * Run a value through a resolved pipeline.
     *
     * @param  array<int, mixed>  $pipeline
     */
    public function pipe(mixed $value, array $pipeline): mixed
    {
        foreach ($pipeline as $operation) {
            if (! is_string($value)) {
                return $value;
            }

            $value = $this->apply($value, $operation);
        }

        return $value;
    }

    /**
     * Expand a pipeline name into its operations, splicing in any pipeline it
     * references by name.
     *
     * @return array<int, mixed>
     */
    private function expand(string $name): array
    {
        $pipelines = (array) config('ig-common.sanitize.pipelines', []);

        if (! array_key_exists($name, $pipelines)) {
            throw new InvalidArgumentException(
                "Sanitization pipeline [$name] is not defined in ig-common.sanitize.pipelines."
            );
        }

        if (in_array($name, $this->expanding, true)) {
            $cycle = implode(' -> ', [...$this->expanding, $name]);

            throw new InvalidArgumentException("Sanitization pipeline [$name] references itself: $cycle.");
        }

        $this->expanding[] = $name;

        try {
            $operations = [];

            foreach ((array) $pipelines[$name] as $operation) {
                if (is_string($operation) && $this->isPipelineReference($operation, $pipelines)) {
                    array_push($operations, ...$this->expand($operation));

                    continue;
                }

                $operations[] = $operation;
            }

            return $operations;
        } finally {
            array_pop($this->expanding);
        }
    }

    /**
     * Whether a token inside a pipeline names another pipeline rather than an
     * operation. Operation names win, so a pipeline may safely carry the name
     * of the operation it is built around.
     *
     * @param  array<string, mixed>  $pipelines
     */
    private function isPipelineReference(string $operation, array $pipelines): bool
    {
        $name = explode(':', $operation, 2)[0];

        return ! in_array($name, self::OPERATIONS, true) && array_key_exists($operation, $pipelines);
    }

    /**
     * Apply one operation. An unrecognized name is a callable or an invokable
     * class, which is how an app adds behaviour without registering anything.
     */
    private function apply(string $value, mixed $operation): mixed
    {
        if (! is_string($operation)) {
            return $this->callCustom($value, $operation);
        }

        [$name, $parameter] = array_pad(explode(':', $operation, 2), 2, null);

        return match ($name) {
            'trim' => Str::trim($value),
            'strip_invisible' => $this->stripInvisible($value),
            'normalize_newlines' => $this->normalizeNewlines($value),
            'collapse_spaces' => $this->collapseSpaces($value),
            'squish' => Str::squish($value),
            'lower' => Str::lower($value),
            'upper' => Str::upper($value),
            'ascii' => $this->ascii($value),
            'digits' => $this->replace('/[^0-9]/u', '', $value),
            'keep' => $this->replace('/[^' . $this->characterClass($parameter) . ']/u', '', $value, $operation),
            'strip' => $this->replace('/[' . $this->characterClass($parameter) . ']/u', '', $value, $operation),
            'normalize_list' => $this->normalizeList($value),
            'max' => mb_substr($value, 0, max(0, (int) $parameter)),
            'nullify' => $value === '' ? null : $value,
            default => $this->callCustom($value, $operation),
        };
    }

    /**
     * Prepare the body of a `keep:` / `strip:` character class.
     *
     * The pattern is delimited with a slash, so an unescaped slash in the class
     * - phone punctuation is written "()\-./" - would end the pattern early.
     * Normalizing first keeps this idempotent for a class that already escaped
     * it.
     */
    private function characterClass(?string $parameter): string
    {
        return str_replace('/', '\\/', str_replace('\\/', '/', (string) $parameter));
    }

    private function callCustom(string $value, mixed $operation): mixed
    {
        if (is_string($operation) && class_exists($operation)) {
            $operation = app($operation);
        }

        if (! is_callable($operation)) {
            throw new InvalidArgumentException(
                'Sanitization operation [' . (is_string($operation) ? $operation : get_debug_type($operation))
                . '] is not a known operation, a callable, or an invokable class.'
            );
        }

        return $operation($value);
    }

    /**
     * Drop characters a form control never submits and a reader never sees:
     * C0/C1 controls other than tab and newline, zero-width characters, the
     * byte order mark, the soft hyphen, and the bidi overrides used to make a
     * value display as something other than what it is.
     */
    private function stripInvisible(string $value): string
    {
        return $this->replace(
            '/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}'
            . '\x{00AD}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{FEFF}]/u',
            '',
            $value
        );
    }

    private function normalizeNewlines(string $value): string
    {
        $value = $this->replace('/\r\n?/u', "\n", $value);

        return $this->replace('/\n{3,}/u', "\n\n", $value);
    }

    /**
     * Collapse runs of horizontal whitespace, leaving line breaks alone so a
     * textarea keeps its paragraphs.
     */
    private function collapseSpaces(string $value): string
    {
        $value = $this->replace('/[^\S\n]+/u', ' ', $value);

        return $this->replace('/[^\S\n]*\n[^\S\n]*/u', "\n", $value);
    }

    private function normalizeList(string $value): string
    {
        $items = array_filter(array_map(
            static fn (string $item): string => Str::trim($item),
            explode(',', $value)
        ), static fn (string $item): bool => $item !== '');

        return implode(', ', $items);
    }

    /**
     * Fold to ASCII, preferring ICU when intl is available: its Any-Latin step
     * transliterates scripts Str::ascii()'s static map does not cover.
     */
    private function ascii(string $value): string
    {
        static $transliterator = null;

        if ($transliterator === null && class_exists(Transliterator::class)) {
            $transliterator = Transliterator::create('Any-Latin; Latin-ASCII') ?: false;
        }

        if ($transliterator) {
            return (string) $transliterator->transliterate($value);
        }

        return Str::ascii($value);
    }

    private function replace(string $pattern, string $replacement, string $value, ?string $operation = null): string
    {
        $result = @preg_replace($pattern, $replacement, $value);

        if ($result === null) {
            throw new InvalidArgumentException(
                'Sanitization operation [' . ($operation ?? $pattern) . '] is not a valid pattern.'
            );
        }

        return $result;
    }

    /**
     * Normalize rules to arrays keyed by concrete attribute path, expanding
     * wildcards against the data as the validator itself does.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @return array<string, array<int, mixed>>
     */
    private function explodeRules(array $data, array $rules): array
    {
        if ($rules === []) {
            return [];
        }

        return (array) (new ValidationRuleParser($data))->explode($rules)->rules;
    }

    /**
     * Match a key against a map of field names, exact entries before globs.
     *
     * The name compared is the last segment that is not an array index, so
     * 'recipients.0' and 'items.3.to_email' are typed by 'recipients' and
     * 'to_email'. Str::snake() folds the camelCase spelling Livewire
     * properties use onto the snake_case one request fields use.
     *
     * @param  array<string, string>  $map
     */
    private function matchName(array $map, array $path): ?string
    {
        if ($map === []) {
            return null;
        }

        // The whole path, for a caller that named an exact property such as
        // 'formData.0'.
        if (is_string($map[implode('.', $path)] ?? null)) {
            return $map[implode('.', $path)];
        }

        // Then each segment from the deepest outwards. The innermost name that
        // means something wins, and a collection types the values it holds
        // when their own keys do not: 'items.0.email' is an e-mail, while
        // 'recipients.jan@example.com' takes its type from 'recipients'.
        foreach (array_reverse($path) as $segment) {
            if ($segment === '' || is_numeric($segment)) {
                continue;
            }

            // Both spellings, so a type may be declared under a config key in
            // snake_case or under a Livewire property name as it is written.
            foreach (array_unique([$this->normalizeName($segment), $segment]) as $name) {
                if (is_string($map[$name] ?? null)) {
                    return $map[$name];
                }

                foreach ($map as $pattern => $pipeline) {
                    if (is_string($pattern) && str_contains($pattern, '*') && Str::is($pattern, $name)) {
                        return $pipeline;
                    }
                }
            }
        }

        return null;
    }

    private function normalizeName(string $segment): string
    {
        return Str::lower(Str::snake($segment));
    }

    /**
     * Match the first of a field's rules that names a pipeline. String rules
     * are keyed by the name before their parameters; rule objects and class
     * strings by their class name, so `new Ulid32` and Ulid32::class agree.
     *
     * @param  array<string, string>  $map
     * @param  array<int, mixed>  $fieldRules
     */
    private function matchRules(array $map, array $fieldRules): ?string
    {
        foreach ($fieldRules as $rule) {
            // The parser wraps a ValidationRule implementation in
            // InvokableValidationRule, so the class to match on is the one it
            // holds rather than the wrapper.
            if ($rule instanceof InvokableValidationRule) {
                $rule = $rule->invokable();
            }

            $token = match (true) {
                is_object($rule) => $rule::class,
                is_string($rule) => explode(':', $rule, 2)[0],
                default => null,
            };

            if ($token !== null && is_string($map[$token] ?? null)) {
                return $map[$token];
            }
        }

        return null;
    }

    /**
     * A field is excepted by its full path or by any of its segments, so
     * 'password' covers 'user.password' and a locked array property covers
     * everything nested inside it. Globs are allowed, because secrets come
     * in families: '*_api_key', '*_secret'.
     *
     * @param  array<int, string>  $path
     * @param  array<int, string>  $except
     */
    private function isExcepted(array $path, array $except): bool
    {
        if (in_array(implode('.', $path), $except, true)) {
            return true;
        }

        foreach ($path as $segment) {
            // Both spellings: an entry may be a config key written in
            // snake_case, or the name of a Livewire property exactly as it is
            // declared - `translatedLanguages` never snake-cases to itself.
            $names = array_unique([$segment, $this->normalizeName($segment)]);

            foreach ($names as $name) {
                if (in_array($name, $except, true)) {
                    return true;
                }

                foreach ($except as $pattern) {
                    if (str_contains($pattern, '*') && Str::is($pattern, $name)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Fail loudly while developing, warn once in production.
     *
     * A gap in coverage is a config edit, and the throw is what makes it get
     * made. Production keeps the request working: the conservative pipeline
     * runs and the key is logged, once, so the gap is still findable.
     */
    private function reportUnmapped(string $key): void
    {
        if (config('app.debug') && config('ig-common.sanitize.unmapped.throw_when_debug', true)) {
            throw new UnmappedInputException($key);
        }

        if (isset($this->reported[$key])) {
            return;
        }

        $this->reported[$key] = true;

        Log::log(
            (string) config('ig-common.sanitize.unmapped.log_level', 'warning'),
            "Input [$key] has no sanitization pipeline; applied the unmapped fallback.",
            ['key' => $key]
        );
    }
}
