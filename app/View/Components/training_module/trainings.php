<?php

namespace App\View\Components\training_module;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class trainings extends Component
{
    public $trainingModules;
    /**
     * Create a new component instance.
     */
    public function __construct($trainingModules)
    {
        $this->trainingModules = $trainingModules;
    }
    
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.training_module.trainings');
    }
}
