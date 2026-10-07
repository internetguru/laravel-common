<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use InternetGuru\LaravelCommon\Support\Helpers;

class Breadcrumb extends Component
{
    public string $divider;

    public array $items = [];

    public ?string $mark;

    /**
     * Create the component instance.
     *
     * @param  string|null  $mark  URL of an image drawn beside the first item and as the menu toggle
     * @param  array<int, array{uri: ?string, translation: string}>|null  $items  items replacing those parsed from the URL
     */
    public function __construct(string $divider = '›', int $skipFirst = 0, ?string $mark = null, ?array $items = null)
    {
        $this->divider = $divider;
        $this->mark = $mark;
        $this->items = $items ?? Helpers::parseUrlPath(skipFirst: $skipFirst);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('ig-common::components.breadcrumb', [
            'divider' => $this->divider,
            'items' => $this->items,
            'mark' => $this->mark,
        ]);
    }
}
