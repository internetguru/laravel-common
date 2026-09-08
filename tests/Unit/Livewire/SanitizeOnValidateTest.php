<?php

namespace Tests\Unit\Livewire;

use Illuminate\Validation\ValidationException;
use InternetGuru\LaravelCommon\Traits\SanitizesInput;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class SanitizeOnValidateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);
    }

    public function test_validate_writes_the_sanitized_value_back_onto_the_property()
    {
        Livewire::test(OrderComponent::class)
            ->set('email', '  Fóo@Example.COM  ')
            ->set('name', '  Jan   Novák ')
            ->call('submit')
            ->assertSet('email', 'foo@example.com')
            ->assertSet('name', 'Jan Novák');
    }

    public function test_a_multi_line_property_keeps_its_line_breaks()
    {
        Livewire::test(OrderComponent::class)
            ->set('email', 'a@b.com')
            ->set('note', "First  line\r\n\r\nSecond   line")
            ->call('submit')
            ->assertSet('note', "First line\n\nSecond line");
    }

    public function test_a_validation_exception_still_propagates()
    {
        Livewire::test(OrderComponent::class)
            ->set('email', 'not-an-email')
            ->call('submit')
            ->assertHasErrors(['email' => 'email'])
            ->assertSet('submitted', false);
    }

    public function test_the_trait_cleans_the_values_validate_returns()
    {
        Livewire::test(FeedbackComponent::class)
            ->set('formData.0', '  Fóo@Example.COM  ')
            ->set('formData.1', "a  note\r\n\r\nsecond  line")
            ->call('send')
            ->assertSet('validated', [
                'formData' => ['foo@example.com', "a note\n\nsecond line"],
            ]);
    }

    public function test_the_trait_leaves_the_properties_as_they_were_typed()
    {
        // Rewriting a field mid-edit, when validation has just failed on some
        // other field, is a surprise nobody asked for.
        Livewire::test(FeedbackComponent::class)
            ->set('formData.0', '  Fóo@Example.COM  ')
            ->set('formData.1', 'note')
            ->call('send')
            ->assertSet('formData.0', '  Fóo@Example.COM  ');
    }

    public function test_a_property_typed_by_the_trait_is_not_reported_as_unmapped()
    {
        config(['app.debug' => true]);

        // Would throw UnmappedInputException if the declaration did not reach
        // the validator resolver.
        Livewire::test(FeedbackComponent::class)
            ->set('formData.0', ' A@B.COM ')
            ->set('formData.1', 'note')
            ->call('send')
            ->assertSet('validated.formData.0', 'a@b.com');
    }

    public function test_a_component_without_declared_types_is_unaffected()
    {
        Livewire::test(OrderComponent::class)
            ->set('email', ' A@B.COM ')
            ->call('submit')
            ->assertSet('email', 'a@b.com');
    }

    public function test_locked_properties_are_neither_sanitized_nor_reported()
    {
        // Would throw UnmappedInputException for $serverOwned otherwise: it is
        // not declared anywhere, and debug is on here.
        config(['app.debug' => true]);

        Livewire::test(LockedComponent::class)
            ->set('email', ' A@B.COM ')
            ->call('submit')
            ->assertSet('email', 'a@b.com')
            ->assertSet('serverOwned', '  Left   Alone  ');
    }

    public function test_the_trait_exposes_nothing_the_client_can_call()
    {
        // Public methods on a Livewire component are callable by the client,
        // and both of these decide what gets written where.
        foreach (['sanitizeTypes', 'prepareForValidation'] as $method) {
            $this->assertTrue(
                (new \ReflectionMethod(FeedbackComponent::class, $method))->isProtected(),
                "$method must not be callable from the browser"
            );
        }

        $this->expectException(MethodNotFoundException::class);

        Livewire::test(FeedbackComponent::class)->call('sanitizeTypes');
    }

    public function test_a_locked_array_property_is_skipped_at_every_depth()
    {
        config(['app.debug' => true]);

        // The nested key is what a locked array reaches the walker as, and it
        // is not the property name - so the skip has to hold for the whole
        // branch, not just its root.
        Livewire::test(LockedComponent::class)
            ->set('email', ' A@B.COM ')
            ->call('submit')
            ->assertSet('email', 'a@b.com')
            ->assertSet('translatedLanguages', ['en' => '  English  ']);
    }

    public function test_nothing_is_changed_when_the_feature_is_disabled()
    {
        config(['ig-common.sanitize.enabled' => false]);

        Livewire::test(OrderComponent::class)
            ->set('email', ' A@B.COM ')
            ->call('submit')
            ->assertSet('email', ' A@B.COM ');
    }
}

class OrderComponent extends Component
{
    public string $email = '';

    public string $name = '';

    public string $note = '';

    public bool $submitted = false;

    public function submit(): void
    {
        try {
            $this->validate([
                'email' => 'required|email',
                'name' => 'nullable|string|max:255',
                'note' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            $this->dispatch('focus-error');

            throw $e;
        }

        $this->submitted = true;
    }

    public function render()
    {
        return '<div></div>';
    }
}

class LockedComponent extends Component
{
    #[Locked]
    public string $serverOwned = '  Left   Alone  ';

    #[Locked]
    public array $translatedLanguages = ['en' => '  English  '];

    public string $email = '';

    public function submit(): void
    {
        $this->validate(['email' => 'required|email']);
    }

    public function render()
    {
        return '<div></div>';
    }
}

/**
 * Mirrors laravel-feedback: the property names carry no type and the rules are
 * generic, so only the component knows what each field holds.
 */
class FeedbackComponent extends Component
{
    use SanitizesInput;

    public array $formData = ['', ''];

    public array $validated = [];

    protected function sanitizeTypes(): array
    {
        return [
            'formData.0' => 'email',
            'formData.1' => 'text',
        ];
    }

    public function send(): void
    {
        $this->validated = $this->validate([
            'formData.0' => 'required|email',
            'formData.1' => 'nullable|string|max:255',
        ]);
    }

    public function render()
    {
        return '<div></div>';
    }
}
