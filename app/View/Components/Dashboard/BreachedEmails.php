<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BreachedEmails extends Component
{
    public $breachedEmails;
    /**
     * Create a new component instance.
     */
    public function __construct($breachedEmails)
    {
        $this->breachedEmails = $breachedEmails;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.breached-emails');
    }
}
