<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;

class Messages extends Component
{
    public function render()
    {
        return view('common::components.messages');
    }
}
