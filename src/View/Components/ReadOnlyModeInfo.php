<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;

class ReadOnlyModeInfo extends Component
{
    public function render()
    {
        return view('ig-common::components.readonly-mode-info');
    }
}
