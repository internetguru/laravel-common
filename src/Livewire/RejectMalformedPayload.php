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
     * Reject an update that swaps a property between array and scalar.
     *
     * The snapshot is checksum-verified and fully hydrated before any update is
     * applied, so the property's current value is a trustworthy baseline for the
     * shape it is meant to hold. Nulls and objects are left alone: null carries
     * no shape, and objects (models, collections, uploaded files) are hydrated
     * by synthesizers that already validate them.
     *
     * Arguments are untyped because a numeric update path arrives as an int.
     */
    public function update(mixed $propertyName, mixed $fullPath, mixed $newValue): void
    {
        $path = (string) (is_scalar($fullPath) ? $fullPath : '?');
        $current = data_get($this->getProperties(), $path);

        if (is_array($current) && is_scalar($newValue)) {
            throw new BotPayloadException("[$path] holds an array, received " . gettype($newValue));
        }

        if (is_scalar($current) && is_array($newValue)) {
            throw new BotPayloadException("[$path] holds a " . gettype($current) . ', received array');
        }
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
