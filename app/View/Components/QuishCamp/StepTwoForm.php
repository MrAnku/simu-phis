<?php

namespace App\View\Components\QuishCamp;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StepTwoForm extends Component
{
    public $quishingEmails;
    /**
     * Create a new component instance.
     */
    public function __construct($quishingEmails)
    {
        $this->quishingEmails = $quishingEmails;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.quish-camp.step-two-form');
    }
}
