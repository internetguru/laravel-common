<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;

class LangSwitch extends Component
{
    public function render()
    {
        return view('ig-common::components.lang-switch');
    }
}
