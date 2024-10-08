<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;

class Input extends Component
{
    public function render()
    {
        return view('package::components.forms.input');
    }
}
