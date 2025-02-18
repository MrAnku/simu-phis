<?php

namespace App\View\Components\DarkWeb;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BreachedCard extends Component
{
    public $employee;
    public $breachDetail;
    /**
     * Create a new component instance.
     */
    public function __construct($employee, $breachDetail)
    {
        $this->employee = $employee;
        $this->breachDetail = $breachDetail;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dark-web.breached-card');
    }
}
