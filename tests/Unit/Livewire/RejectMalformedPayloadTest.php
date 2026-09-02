<?php

namespace Tests\Unit\Livewire;

use Livewire\Component;
use Livewire\Livewire;
use Livewire\WithFileUploads;
use Tests\TestCase;

class RejectMalformedPayloadTest extends TestCase
{
    public function test_array_property_rejects_a_scalar_update()
    {
        Livewire::test(PayloadComponent::class)->set('monthNames', 8)->assertStatus(419);
    }

    public function test_scalar_property_rejects_an_array_update()
    {
        Livewire::test(PayloadComponent::class)->set('currentYear', ['x' => 1])->assertStatus(419);
    }

    public function test_typed_scalar_property_rejects_an_array_update()
    {
        Livewire::test(PayloadComponent::class)->set('label', [])->assertStatus(419);
    }

    public function test_nested_path_into_an_array_property_is_checked_against_its_leaf()
    {
        Livewire::test(PayloadComponent::class)->set('availableHours.0', ['nested'])->assertStatus(419);
    }

    public function test_element_of_an_array_update_rejects_a_type_swap()
    {
        Livewire::test(PayloadComponent::class)->set('fields', [1])->assertStatus(419);
    }

    public function test_deeply_nested_element_of_an_array_update_rejects_a_type_swap()
    {
        Livewire::test(PayloadComponent::class)
            ->set('fields', [['name' => ['message']]])
            ->assertStatus(419);
    }

    public function test_appended_element_is_checked_against_the_shape_the_others_share()
    {
        Livewire::test(PayloadComponent::class)
            ->set('fields', [['name' => 'message'], ['name' => 'email'], 1])
            ->assertStatus(419);
    }

    public function test_scalar_to_scalar_update_is_allowed()
    {
        Livewire::test(PayloadComponent::class)
            ->set('currentYear', '2027')
            ->assertSet('currentYear', 2027);
    }

    public function test_array_to_array_update_is_allowed()
    {
        Livewire::test(PayloadComponent::class)
            ->set('monthNames', ['Leden', 'Únor'])
            ->assertSet('monthNames', ['Leden', 'Únor']);
    }

    public function test_null_property_has_no_shape_to_defend_and_accepts_anything()
    {
        Livewire::test(PayloadComponent::class)
            ->set('selectedHour', ['whatever'])
            ->assertSet('selectedHour', ['whatever']);
    }

    public function test_nested_path_into_an_array_property_is_allowed_when_shapes_match()
    {
        Livewire::test(PayloadComponent::class)
            ->set('availableHours.0', 19)
            ->assertSet('availableHours.0', 19);
    }

    public function test_array_of_arrays_update_is_allowed_when_every_element_matches()
    {
        Livewire::test(PayloadComponent::class)
            ->set('fields', [['name' => 'message'], ['name' => 'email'], ['name' => 'fullname']])
            ->assertSet('fields.2.name', 'fullname');
    }

    public function test_appended_element_of_a_mixed_array_has_no_shape_to_match()
    {
        Livewire::test(PayloadComponent::class)
            ->set('formData', ['', [], 'text'])
            ->assertSet('formData.2', 'text');
    }

    public function test_key_the_current_value_does_not_hold_is_allowed()
    {
        Livewire::test(PayloadComponent::class)
            ->set('fields', [['name' => 'message', 'label' => ['a', 'b']]])
            ->assertSet('fields.0.label', ['a', 'b']);
    }

    public function test_start_upload_rejects_a_non_array_file_info()
    {
        Livewire::test(UploadComponent::class)->call('_startUpload', 'photo', 1, false)->assertStatus(419);
    }

    public function test_upload_errored_rejects_a_non_string_error_payload()
    {
        Livewire::test(UploadComponent::class)->call('_uploadErrored', 'photo', ['boom'], false)->assertStatus(419);
    }

    public function test_remove_upload_rejects_a_missing_argument()
    {
        Livewire::test(UploadComponent::class)->call('_removeUpload', 'photo')->assertStatus(419);
    }

    public function test_upload_calls_with_the_expected_types_reach_the_component()
    {
        Livewire::test(UploadComponent::class)
            ->call('_startUpload', 'photo', [['name' => 'a.png', 'size' => 100, 'type' => 'image/png']], false)
            ->assertDispatched('upload:generatedSignedUrl');
    }

    public function test_unrelated_method_calls_are_left_alone()
    {
        Livewire::test(PayloadComponent::class)
            ->call('bump')
            ->assertSet('currentYear', 2027);
    }
}

class PayloadComponent extends Component
{
    public $currentYear = 2026;

    public string $label = 'reservation';

    public $selectedHour = null;

    public $monthNames = ['January', 'February'];

    public $availableHours = [18, 20];

    public $fields = [['name' => 'message'], ['name' => 'email']];

    public $formData = ['', []];

    public function bump(): void
    {
        $this->currentYear++;
    }

    public function render()
    {
        return '<div></div>';
    }
}

class UploadComponent extends Component
{
    use WithFileUploads;

    public $photo = null;

    public function render()
    {
        return '<div></div>';
    }
}
