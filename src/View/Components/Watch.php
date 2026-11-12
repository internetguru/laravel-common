<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Registers the surrounding Livewire component, which uses WatchesForChanges,
 * with the shared watch clock.
 */
class Watch extends Component
{
    /** @var list<string> Public methods Blade would otherwise hand to the view, shadowing its data */
    protected $except = ['messages'];

    public function __construct(
        public int $interval = 15,
    ) {}

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'offline' => __('ig-common::watch.offline'),
            'expired' => __('ig-common::watch.expired'),
            'signedOut' => __('ig-common::watch.signed-out'),
            'updated' => __('ig-common::watch.updated'),
            'reload' => __('ig-common::watch.reload'),
        ];
    }

    public function render(): View
    {
        return view('ig-common::components.watch', ['messages' => self::messages()]);
    }
}
