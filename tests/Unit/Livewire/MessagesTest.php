<?php

namespace Tests\Unit\Livewire;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
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

    public function test_it_shows_each_error_from_every_bag_once()
    {
        $errors = (new ViewErrorBag)
            ->put('default', new MessageBag(['email' => ['Default bag error']]))
            ->put('profile', new MessageBag(['name' => ['Named bag error']]));
        session()->flash('errors', $errors);

        Livewire::test(Messages::class)
            ->assertSet('messages', [
                ['type' => 'danger', 'content' => 'Default bag error'],
                ['type' => 'danger', 'content' => 'Named bag error'],
            ]);
    }
}
