<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;

class Form extends Component
{
    public function render()
    {
        return view('package::components.forms.form');
    }
}
