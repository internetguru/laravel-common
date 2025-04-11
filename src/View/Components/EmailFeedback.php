<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use InternetGuru\LaravelCommon\Support\Helpers;

class EmailFeedback extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('ig-common::components.email-feedback');
    }
}
