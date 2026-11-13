<?php

namespace Tests\Unit\Livewire;

use InternetGuru\LaravelCommon\Livewire\Messages;
use Livewire\Livewire;
use Tests\TestCase;

class MessagesTest extends TestCase
{
    public function test_it_offers_the_offline_bar_its_text_in_the_page_language()
    {
        app()->setLocale('cs');

        Livewire::test(Messages::class)
            ->assertSeeHtml('data-ig-offline="' . e(__('ig-common::watch.offline-page')) . '"')
            ->assertSee('Jste offline');
    }

    public function test_it_renders_the_template_of_the_watcher_toast()
    {
        Livewire::test(Messages::class)
            ->assertSeeHtml('<template data-ig-toast>')
            ->assertSeeHtml('data-ig-client-toasts')
            ->assertSeeHtml('bg-warning');
    }
}
