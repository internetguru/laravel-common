<?php

namespace InternetGuru\LaravelCommon\Livewire;

use Illuminate\Contracts\View\View;
use InternetGuru\LaravelCommon\Support\WatchedPage;
use InternetGuru\LaravelCommon\View\Components\Watch;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * Watches a page not drawn by one Livewire component. On a change, the browser reloads
 * the page when nobody would notice, and shows a notice with a Reload button otherwise.
 */
class PageWatch extends Component
{
    /** @var list<array<string, mixed>> */
    #[Locked]
    public array $sources = [];

    /** @var string|array<string, mixed>|null */
    #[Locked]
    public string|array|null $message = null;

    #[Locked]
    public string $url = '';

    #[Locked]
    public int $interval = 15;

    #[Locked]
    public string $fingerprint = '';

    /**
     * @param  list<array<string, mixed>>  $sources
     * @param  string|array<string, mixed>|null  $message
     */
    public function mount(array $sources, string $fingerprint, string|array|null $message = null, int $interval = 15): void
    {
        $this->sources = $sources;
        $this->fingerprint = $fingerprint;
        $this->message = $message;
        $this->interval = $interval;
        $this->url = request()->fullUrl();
    }

    /** @return array{changed: bool, message: ?string} */
    #[Renderless]
    public function watchCheck(): array
    {
        $changed = WatchedPage::currentFingerprint($this->sources) !== $this->fingerprint;

        return [
            'changed' => $changed,
            'message' => $changed ? WatchedPage::resolveMessage($this->message) : null,
        ];
    }

    public function watchReload(): void
    {
        session()->flash('success', WatchedPage::resolveMessage($this->message));
        $this->redirect($this->url);
    }

    public function render(): View
    {
        return view('ig-common::livewire.page-watch', ['messages' => Watch::messages()]);
    }
}
