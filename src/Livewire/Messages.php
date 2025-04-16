<?php

namespace Internetguru\LaravelCommon\Livewire;

use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class Messages extends Component
{
    public $messages = [];

    public function mount()
    {
        $this->loadSessionMessages();
    }

    protected function loadSessionMessages()
    {
        // Add error messages
        if (session()->has('errors')) {
            $errors = session('errors');
            if ($errors && method_exists($errors, 'all')) {
                foreach ($errors->all() as $error) {
                    $this->addMessage('danger', $error);
                }
            }
            // foreach error bag
            if ($errors && method_exists($errors, 'getBags')) {
                foreach ($errors->getBags() as $bag) {
                    foreach ($bag->all() as $error) {
                        $this->addMessage('danger', $error);
                    }
                }
            }
        }

        // Add success messages
        if (session()->has('success')) {
            $data = session('success');
            if (! is_array($data)) {
                $data = [$data];
            }

            foreach ($data as $message) {
                $this->addMessage('success', $message);
            }
        }
    }

    public function removeMessage($index)
    {
        if (isset($this->messages[$index])) {
            unset($this->messages[$index]);
        }
    }

    #[Renderless]
    public function removeMessageQuietly($index)
    {
        $this->removeMessage($index);
    }

    public function addMessage($type, $content)
    {
        $this->messages[] = [
            'type' => $type,
            'content' => $content,
        ];
    }

    #[On('ig-message')]
    public function handleMessage($type, $message)
    {
        $this->addMessage($type, $message);
    }

    public function render()
    {
        return view('ig-common::livewire.messages');
    }
}
