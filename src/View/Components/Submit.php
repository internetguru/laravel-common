<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;

class Submit extends Component
{
    public function render()
    {
        return view('package::components.forms.submit');
    }
}
