<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use InternetGuru\LaravelCommon\Support\WatchedPage;

/**
 * Placed once in the layout; mounts the page watcher only when the page registered
 * what it shows (WatchedPage::add()), so other pages carry nothing.
 */
class PageWatch extends Component
{
    public function __construct(
        public int $interval = 15,
    ) {}

    public function shouldRender(): bool
    {
        return ! app(WatchedPage::class)->isEmpty();
    }

    public function render(): View
    {
        $page = app(WatchedPage::class);

        return view('ig-common::components.page-watch', [
            'sources' => $page->sources(),
            'fingerprint' => $page->fingerprint(),
            'message' => $page->customMessage(),
        ]);
    }
}
