<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use InternetGuru\LaravelCommon\Support\Helpers;

class Breadcrumb extends Component
{
    public string $divider;

    public array $items = [];

    /**
     * Create the component instance.
     */
    public function __construct(string $divider = '›', $skipFirst = true)
    {
        $this->divider = $divider;
        $this->items = Helpers::parseUrlPath(skipFirst: $skipFirst);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('package::components.breadcrumb', [
            'divider' => $this->divider,
            'items' => $this->items,
        ]);
    }
}
