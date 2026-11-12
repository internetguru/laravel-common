<?php

namespace InternetGuru\LaravelCommon\Traits;

use Livewire\Attributes\Locked;

use function Livewire\store;

// Refreshes a component on the x-ig::watch clock only when watchFingerprint() changed; see the README
trait WatchesForChanges
{
    #[Locked]
    public string $watchedFingerprint = '';

    private ?string $currentWatchFingerprint = null;

    private bool $watchMustRender = false;

    private bool $watchSkippedRender = false;

    abstract protected function watchFingerprint(): string;

    public function watchRefresh(): void
    {
        if ($this->currentWatchFingerprint() !== $this->watchedFingerprint) {
            $this->watchChanged();

            return;
        }

        if (! $this->watchMustRender) {
            $this->skipRender();
            $this->watchSkippedRender = true;
        }
    }

    public function updatingWatchesForChanges(): void
    {
        $this->watchMustRender = true;
    }

    public function callWatchesForChanges(string $methodName, mixed ...$details): void
    {
        if ($methodName === 'watchRefresh' || $this->isRenderlessMethod($methodName)) {
            return;
        }

        $this->watchMustRender = true;
        if ($this->watchSkippedRender) {
            store($this)->unset('skipRender');
            $this->watchSkippedRender = false;
        }
    }

    public function renderingWatchesForChanges(): void
    {
        $this->watchedFingerprint = $this->currentWatchFingerprint();
    }

    // Runs before the render a found change causes, for state the render depends on
    protected function watchChanged(): void {}

    protected function currentWatchFingerprint(): string
    {
        return $this->currentWatchFingerprint ??= $this->watchFingerprint();
    }
}
