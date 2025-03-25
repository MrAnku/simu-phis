<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AttackVector extends Component
{
    public $activeAIVishing;
    public $activeTprm;
    /**
     * Create a new component instance.
     */
    public function __construct($activeAIVishing, $activeTprm)
    {
        $this->activeAIVishing = $activeAIVishing;
        $this->activeTprm = $activeTprm;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.attack-vector');
    }
}
