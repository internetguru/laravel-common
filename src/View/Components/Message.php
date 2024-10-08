<?php

namespace Internetguru\BladeComponents\View\Components;

use Illuminate\View\Component;

class Message extends Component
{
    public function render()
    {
        return view('package::components.message');
    }
}
