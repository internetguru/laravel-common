<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;

class Message extends Component
{
    public function render()
    {
        return view('ig-common::components.message');
    }
}
