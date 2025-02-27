<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PackageCard extends Component
{

    public $package;
    public $upgrade;
    /**
     * Create a new component instance.
     */
    public function __construct($package, $upgrade)
    {
        $this->package = $package;
        $this->upgrade = $upgrade;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.package-card');
    }
}
