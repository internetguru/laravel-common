<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use InternetGuru\LaravelCommon\Support\QrCode;

class SharePage extends Component
{
    public string $url;

    public string $svg;

    public string $id;

    public function __construct(
        ?string $url = null,
        public ?string $title = null,
        public string $icon = 'fa-regular fa-fw fa-share-from-square',
        int $size = 240,
        ?string $id = null,
    ) {
        $this->url = $url ?? url()->full();
        $this->title = $title ?? __('ig-common::layouts.share.title');
        $this->svg = QrCode::svg($this->url, $size);
        $this->id = $id ?? Modal::idFor($this->title);
    }

    public function render(): View
    {
        return view('ig-common::components.share-page');
    }
}
