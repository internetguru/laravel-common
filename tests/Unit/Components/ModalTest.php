<?php

namespace Tests\Unit\Components;

use InternetGuru\LaravelCommon\View\Components\Modal;
use Tests\TestCase;

class ModalTest extends TestCase
{
    public function test_the_id_defaults_to_a_slug_of_the_title()
    {
        $this->assertSame('share-page', (new Modal(title: 'Share page'))->id);
        $this->assertSame('modal', (new Modal)->id);
    }

    public function test_renders_hidden_with_the_slot_and_the_open_script()
    {
        $html = $this->blade('<x-ig::modal id="my-modal" title="My title">Body</x-ig::modal>');

        $html->assertSee('id="my-modal" class="ig-modal d-none"', false);
        $html->assertSee('<h5 class="modal-title" id="my-modal-label">My title</h5>', false);
        $html->assertSee('aria-labelledby="my-modal-label"', false);
        $html->assertSee('Body');
        $html->assertSee('modal-backdrop', false);
        $html->assertSee('data-testid="ig-modal-script"', false);
    }

    public function test_it_is_closed_by_the_close_button_and_the_backdrop()
    {
        $html = $this->blade('<x-ig::modal id="my-modal" />');

        $html->assertSee('onclick="window.igModal.close(\'my-modal\')"', false);
        $html->assertSee('if (event.target === this) { window.igModal.close(\'my-modal\'); }', false);
    }

    public function test_it_can_be_rendered_open_and_centered()
    {
        $html = $this->blade('<x-ig::modal id="my-modal" :open="true" centered />');

        $html->assertSee('id="my-modal" class="ig-modal"', false);
        $html->assertSee('modal-dialog modal-dialog-centered', false);
    }

    public function test_it_registers_itself_without_options()
    {
        $html = $this->blade('<x-ig::modal id="my-modal" />');

        $html->assertSee("window.igModal.register('my-modal', {});", false);
    }

    public function test_the_hash_and_the_livewire_property_are_passed_to_the_registration()
    {
        $html = $this->blade('<x-ig::modal id="my-modal" hash="support-form" wire-open="isOpen" />');

        $html->assertSee("window.igModal.register('my-modal', JSON.parse(", false);
        $html->assertSee('hash', false);
        $html->assertSee('support-form', false);
        $html->assertSee('isOpen', false);
    }

    public function test_attributes_are_merged_onto_the_wrapper()
    {
        $html = $this->blade('<x-ig::modal id="my-modal" class="extra" data-testid="my-testid" />');

        $html->assertSee('data-testid="my-testid"', false);
        $html->assertSee('class="ig-modal d-none extra"', false);
    }
}
