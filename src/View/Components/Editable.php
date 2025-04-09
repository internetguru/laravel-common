<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;

class Editable extends Component
{
    public function render()
    {
        return '<div x-data="editable"></div>';
    }
}
