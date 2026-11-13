<?php

namespace InternetGuru\LaravelCommon\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;
use InternetGuru\LaravelCommon\Contracts\HasPageFingerprint;
use InvalidArgumentException;

/**
 * What the current page shows, so `x-ig::page-watch` can tell when it changed elsewhere.
 *
 * A source must be rebuildable in a later request, because the check runs long after the
 * page was rendered: an Eloquent model, or a static callable with scalar arguments.
 */
class WatchedPage
{
    /** @var list<array{type: 'model', class: class-string<Model>, key: mixed}|array{type: 'callable', class: class-string, method: string, arguments: list<scalar|null>}> */
    private array $sources = [];

    /** @var list<mixed> */
    private array $values = [];

    /** @var string|array<string, mixed>|null */
    private string|array|null $message = null;

    /**
     * @param  Model|array{0: class-string, 1: string, 2?: list<scalar|null>}  ...$sources
     */
    public static function add(Model|array ...$sources): void
    {
        $page = app(self::class);
        foreach ($sources as $source) {
            $description = self::describe($source);
            $page->sources[] = $description;
            // Taken from what the page renders now, so a change before the watcher mounts still counts
            $page->values[] = $source instanceof Model ? self::modelValue($source) : self::read($description);
        }
    }

    /**
     * What the notice and the flash after a reload say instead of the default text: a text,
     * or a static callable with scalar arguments, called when the change is found.
     *
     * @param  string|array{0: class-string, 1: string, 2?: list<scalar|null>}  $message
     */
    public static function message(string|array $message): void
    {
        app(self::class)->message = is_array($message) ? self::describe($message) : $message;
    }

    public function isEmpty(): bool
    {
        return $this->sources === [];
    }

    /** @return list<array<string, mixed>> */
    public function sources(): array
    {
        return $this->sources;
    }

    /** @return string|array<string, mixed>|null */
    public function customMessage(): string|array|null
    {
        return $this->message;
    }

    /** @param string|array<string, mixed>|null $message */
    public static function resolveMessage(string|array|null $message): string
    {
        $text = is_array($message) ? self::read($message) : $message;

        return is_string($text) && $text !== '' ? $text : __('ig-common::watch.updated');
    }

    public function fingerprint(): string
    {
        return md5(json_encode($this->values, JSON_THROW_ON_ERROR));
    }

    /** @param list<array<string, mixed>> $sources */
    public static function currentFingerprint(array $sources): string
    {
        return md5(json_encode(array_map(self::read(...), $sources), JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private static function describe(Model|array $source): array
    {
        if ($source instanceof Model) {
            return ['type' => 'model', 'class' => $source::class, 'key' => $source->getKey()];
        }

        [$class, $method, $arguments] = $source + [2 => []];
        if ($class instanceof Closure || ! is_string($class) || ! is_string($method) || ! is_callable([$class, $method])) {
            throw new InvalidArgumentException('A watched source must be a model or a static [Class::class, \'method\'] callable.');
        }
        foreach ($arguments as $argument) {
            if (! is_scalar($argument) && $argument !== null) {
                throw new InvalidArgumentException('The arguments of a watched callable must be scalar.');
            }
        }

        return ['type' => 'callable', 'class' => $class, 'method' => $method, 'arguments' => array_values($arguments)];
    }

    /** @param array<string, mixed> $source */
    private static function read(array $source): mixed
    {
        if ($source['type'] === 'callable') {
            return call_user_func([$source['class'], $source['method']], ...$source['arguments']);
        }

        $model = $source['class']::find($source['key']);

        return $model ? self::modelValue($model) : 'deleted';
    }

    /** @return array{0: mixed, 1: mixed, 2: ?string} */
    private static function modelValue(Model $model): array
    {
        return [
            $model->getKey(),
            $model->usesTimestamps() ? $model->getAttribute($model->getUpdatedAtColumn()) : null,
            $model instanceof HasPageFingerprint ? $model->pageFingerprint() : null,
        ];
    }
}
