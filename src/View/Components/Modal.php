<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Modal whose markup is rendered up front and only hidden, so it can be shown
 * without a round trip. Open and close it with `window.igModal`, passing the id.
 *
 * `hash` deep-links the modal: it opens when the URL fragment matches, and the
 * fragment follows it afterwards. `wireOpen` names the Livewire property holding
 * the open state, which is kept in sync so a re-render does not close the modal.
 */
class Modal extends Component
{
    public string $id;

    public function __construct(
        ?string $id = null,
        public ?string $title = null,
        public bool $open = false,
        public bool $centered = false,
        public ?string $hash = null,
        public ?string $wireOpen = null,
    ) {
        $this->id = $id ?? self::idFor($title);
    }

    /**
     * Readable id derived from the title, so the modal can be linked to.
     */
    public static function idFor(?string $title): string
    {
        return Str::slug((string) $title) ?: 'modal';
    }

    public function render(): View
    {
        return view('ig-common::components.modal');
    }
}
