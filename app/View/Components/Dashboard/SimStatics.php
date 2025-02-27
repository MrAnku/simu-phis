<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SimStatics extends Component
{
    public $sdata;
    /**
     * Create a new component instance.
     */
    public function __construct($sdata)
    {
        $this->sdata = $sdata;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.sim-statics');
    }
}
