<?php

namespace App\View\Components\Employees;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DomainVerification extends Component
{
    public $allDomains;
    /**
     * Create a new component instance.
     */
    public function __construct($allDomains)
    {
        $this->allDomains = $allDomains;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.employees.domain-verification');
    }
}
