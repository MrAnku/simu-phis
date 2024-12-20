<?php

namespace App\View\Components\campaign;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NextButton extends Component
{
    public $label;
    public $id;
    /**
     * Create a new component instance.
     */
    public function __construct($label = "", $id = "")
    {
        $this->label = $label;
        $this->id = $id;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.campaign.next-button');
    }
}
