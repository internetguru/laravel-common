<?php

namespace InternetGuru\LaravelCommon\Livewire;

use InternetGuru\LaravelCommon\Exceptions\BotPayloadException;
use Livewire\ComponentHook;

/**
 * Rejects Livewire payloads no browser could have produced, at the point they
 * enter the component - before the values reach a method call or the render
 * pass, where they would otherwise fail with an unbounded variety of messages.
 *
 * Registered globally in CommonServiceProvider, so it covers every component in
 * the application without per-property annotation.
 */
class RejectMalformedPayload extends ComponentHook
{
    /**
     * Leading argument types for Livewire's client-callable upload methods.
     *
     * These four are callable by name on any component and pass their arguments
     * straight into array and string operations. Only the positions that crash
     * on the wrong type are listed; trailing arguments are left alone.
     *
     * @var array<string, array<int, string>>
     */
    private const UPLOAD_SIGNATURES = [
        '_startUpload' => ['string', 'array'],
        '_finishUpload' => ['string', 'array'],
        '_uploadErrored' => ['string', '?string'],
        '_removeUpload' => ['string', 'string'],
    ];

    /**
     * Reject an update that swaps a value between array and scalar.
     *
     * The snapshot is checksum-verified and fully hydrated before any update is
     * applied, so the property's current value is a trustworthy baseline for the
     * shape it is meant to hold.
     *
     * Arguments are untyped because a numeric update path arrives as an int.
     */
    public function update(mixed $propertyName, mixed $fullPath, mixed $newValue): void
    {
        $path = (string) (is_scalar($fullPath) ? $fullPath : '?');

        $this->assertShapeMatches(data_get($this->getProperties(), $path), $newValue, $path);
    }

    /**
     * Reject a value whose shape contradicts the property's current value, at
     * any depth.
     *
     * Checking only the top level lets the swap through one level down - an
     * array of field definitions arriving as `[1]` still passes for an array,
     * and fails later in the view that iterates it. Every position the current
     * value describes is compared, so the whole payload is refused up front.
     *
     * Positions the current value says nothing about are left alone: a key it
     * does not hold has no baseline to contradict, a null carries no shape, and
     * objects (models, collections, uploaded files) are hydrated by
     * synthesizers that already validate them. The one exception is an appended
     * element of a uniform list - one whose elements are all arrays, or all
     * scalars - where the elements it already holds are that baseline. Keys of a
     * map are named rather than positional, so its values describe only
     * themselves.
     */
    private function assertShapeMatches(mixed $current, mixed $new, string $path): void
    {
        if (is_array($current) && is_scalar($new)) {
            throw new BotPayloadException("[$path] holds an array, received " . gettype($new));
        }

        if (is_scalar($current) && is_array($new)) {
            throw new BotPayloadException("[$path] holds a " . gettype($current) . ', received array');
        }

        if (! is_array($current) || ! is_array($new)) {
            return;
        }

        $elementShape = array_is_list($current) ? $this->uniformElementShape($current) : null;

        foreach ($new as $key => $value) {
            if (array_key_exists($key, $current)) {
                $this->assertShapeMatches($current[$key], $value, "$path.$key");

                continue;
            }

            $shape = $this->shapeOf($value);

            if ($elementShape !== null && $shape !== null && $shape !== $elementShape) {
                throw new BotPayloadException("[$path] is a list of {$elementShape}s, received $shape at [$key]");
            }
        }
    }

    /**
     * The shape shared by every element of a non-empty list, or null when they
     * differ - a mixed list describes no shape for an element appended to it.
     */
    private function uniformElementShape(array $values): ?string
    {
        if ($values === []) {
            return null;
        }

        $shapes = array_map(fn (mixed $value): ?string => $this->shapeOf($value), $values);

        return count(array_unique($shapes, SORT_REGULAR)) === 1 ? reset($shapes) : null;
    }

    /**
     * The only distinction worth defending: a value that indexes versus one that
     * does not. Nulls and objects carry no shape.
     */
    private function shapeOf(mixed $value): ?string
    {
        return match (true) {
            is_array($value) => 'array',
            is_scalar($value) => 'scalar',
            default => null,
        };
    }

    /**
     * Reject a call to one of Livewire's upload endpoints whose arguments are
     * not the types the trait expects. Real uploads are unaffected.
     */
    public function call(mixed $method, mixed $params, mixed $returnEarly, mixed $metadata, mixed $componentContext): void
    {
        if (! is_string($method) || ! isset(self::UPLOAD_SIGNATURES[$method])) {
            return;
        }

        $expected = self::UPLOAD_SIGNATURES[$method];

        if (! is_array($params) || count($params) < count($expected)) {
            throw new BotPayloadException("$method() expects at least " . count($expected) . ' arguments');
        }

        foreach ($expected as $position => $type) {
            if (! $this->matchesType($params[$position] ?? null, $type)) {
                throw new BotPayloadException("$method() argument $position must be $type");
            }
        }
    }

    /**
     * A leading `?` marks the type as nullable.
     */
    private function matchesType(mixed $value, string $type): bool
    {
        if (str_starts_with($type, '?')) {
            if ($value === null) {
                return true;
            }

            $type = substr($type, 1);
        }

        return match ($type) {
            'array' => is_array($value),
            'string' => is_string($value),
            default => false,
        };
    }
}
