<?php

namespace App\View\Components\Employees;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SyncDirectory extends Component
{
    public bool $hasOutlookToken;
    /**
     * Create a new component instance.
     */
    public function __construct($hasOutlookToken)
    {
        $this->hasOutlookToken = $hasOutlookToken;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.employees.sync-directory');
    }
}
