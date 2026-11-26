<?php

namespace InternetGuru\LaravelCommon\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

class Messages extends Component
{
    /** @var array<int, array{type: string, content: string}> */
    public $messages = [];

    /** @return void */
    public function mount()
    {
        $this->loadSessionMessages();
    }

    /** @return void */
    protected function loadSessionMessages()
    {
        // Add error messages
        if (session()->has('errors')) {
            $errors = session('errors');
            if ($errors instanceof ViewErrorBag) {
                foreach ($errors->getBags() as $bag) {
                    foreach ($bag->all() as $error) {
                        $this->addMessage('danger', $error);
                    }
                }
            }
        }

        $this->addSessionMessages('success');
        $this->addSessionMessages('warning');
    }

    private function addSessionMessages(string $type): void
    {
        $messages = session($type, []);
        foreach (is_array($messages) ? $messages : [$messages] as $message) {
            $this->addMessage($type, $message);
        }
    }

    /**
     * @param  int  $index
     * @return void
     */
    public function removeMessage($index)
    {
        if (isset($this->messages[$index])) {
            unset($this->messages[$index]);
        }
    }

    /**
     * @param  int  $index
     * @return void
     */
    #[Renderless]
    public function removeMessageQuietly($index)
    {
        $this->removeMessage($index);
    }

    /**
     * @param  string  $type
     * @param  string  $content
     * @return void
     */
    public function addMessage($type, $content)
    {
        $this->messages[] = [
            'type' => $type,
            'content' => $content,
        ];
    }

    /**
     * @param  string  $type
     * @param  string  $message
     * @return void
     */
    #[On('ig-message')]
    public function handleMessage($type, $message)
    {
        $this->addMessage($type, $message);
    }

    /** @return View */
    public function render()
    {
        return view('ig-common::livewire.messages');
    }
}
