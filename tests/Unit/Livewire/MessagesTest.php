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

    public function test_it_shows_session_success_and_warning_messages()
    {
        session()->flash('success', 'Saved');
        session()->flash('warning', ['First warning', 'Second warning']);

        Livewire::test(Messages::class)
            ->assertSet('messages', [
                ['type' => 'success', 'content' => 'Saved'],
                ['type' => 'warning', 'content' => 'First warning'],
                ['type' => 'warning', 'content' => 'Second warning'],
            ]);
    }

    public function test_it_renders_a_link_in_a_warning_as_html()
    {
        session()->flash('warning', 'Signed out. <a href="https://example.com/login">Sign in again</a>.');

        Livewire::test(Messages::class)
            ->assertSeeHtml('Signed out. <a href="https://example.com/login">Sign in again</a>.');
    }
}
