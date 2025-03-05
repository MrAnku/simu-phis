<?php

namespace App\View\Components\QuishCamp;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NewCamp extends Component
{
    public $quishingEmails;
    public $trainingModules;
    /**
     * Create a new component instance.
     */
    public function __construct($quishingEmails, $trainingModules)
    {
        $this->quishingEmails = $quishingEmails;
        $this->trainingModules = $trainingModules;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.quish-camp.new-camp');
    }
}
