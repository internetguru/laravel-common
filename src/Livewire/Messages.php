<?php

namespace Internetguru\LaravelCommon\Livewire;

use Livewire\Attributes\On;
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

    public function addMessage($type, $content)
    {
        $this->messages[] = [
            'type' => $type,
            'content' => $content,
        ];
    }

    #[On('ig-message')]
    public function handleMessage($data)
    {
        // Process the message data
        if (isset($data['type']) && isset($data['message'])) {
            $this->addMessage($data['type'], $data['message']);
        }
    }

    public function render()
    {
        return view('ig-common::livewire.messages');
    }
}
