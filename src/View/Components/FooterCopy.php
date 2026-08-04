<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class FooterCopy extends Component
{
    /**
     * @param  string|null  $icon  Icon class, null for the bundled duotone seedling, empty string for no icon
     */
    public function __construct(
        public ?string $icon = null,
    ) {}

    public function render(): View
    {
        return view('ig-common::components.footer-copy');
    }
}
