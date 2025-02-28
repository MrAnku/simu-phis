<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BrowserUsage extends Component
{
    public $usageCounts;
    /**
     * Create a new component instance.
     */
    public function __construct($usageCounts)
    {
        $this->usageCounts = $usageCounts;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.browser-usage');
    }
}
