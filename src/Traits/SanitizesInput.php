<?php

namespace InternetGuru\LaravelCommon\Traits;

use InternetGuru\LaravelCommon\Support\Sanitizer;

/**
 * Lets a Livewire component name the type of properties the configuration
 * cannot describe, and cleans them up on the way into validation.
 *
 * Most properties need nothing: the validator resolver types them by name or by
 * their validation rules. A dynamic form is the exception - laravel-feedback
 * builds `formData.0 … formData.N`, so the name carries no meaning and the
 * rules are a generic `string|max:255`, while the real type sits in
 * configuration only the component can reach.
 *
 * Declaring a type here means `validate()` returns the cleaned values, so a
 * component reads its result rather than its own properties. The properties are
 * deliberately left as the person typed them: rewriting a field mid-edit, when
 * validation has just failed on some other field, is a surprise nobody asked
 * for.
 */
trait SanitizesInput
{
    /**
     * Property path => pipeline name, for properties whose type cannot be read
     * from their name or their validation rules.
     *
     * @return array<string, string>
     */
    protected function sanitizeTypes(): array
    {
        return [];
    }

    /**
     * Livewire's own hook, called with the property values on their way into
     * both validate() and validateOnly().
     *
     * Cleaning here rather than letting the resolver do it is what keeps the
     * properties untouched: by the time the validator runs there is nothing
     * left to change, so nothing is written back to the component.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function prepareForValidation($attributes)
    {
        $types = $this->sanitizeTypes();

        if ($types === []) {
            return $attributes;
        }

        $sanitizer = app(Sanitizer::class);

        // Also tells the resolver what these are, so validating them right
        // after does not report them as unmapped.
        $sanitizer->declareTypes($types);

        foreach ($sanitizer->changesFor($attributes, $types) as $change) {
            $sanitizer->pathSet($attributes, $change['path'], $change['value']);
        }

        return $attributes;
    }
}
