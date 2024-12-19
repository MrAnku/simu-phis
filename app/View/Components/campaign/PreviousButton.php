<?php

namespace App\View\Components\campaign;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PreviousButton extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public $label)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.campaign.previous-button');
    }
}
