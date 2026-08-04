<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class CopyUrl extends Component
{
    public string $url;

    public function __construct(
        ?string $url = null,
        public string $icon = '',
    ) {
        $this->url = $url ?? url()->full();
    }

    public function render(): View
    {
        return view('ig-common::components.copy-url');
    }
}
