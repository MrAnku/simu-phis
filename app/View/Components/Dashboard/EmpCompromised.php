<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EmpCompromised extends Component
{
    public $campaignsWithReport;
    public $totalEmpCompromised;
    /**
     * Create a new component instance.
     */
    public function __construct($campaignsWithReport, $totalEmpCompromised)
    {
        $this->campaignsWithReport = $campaignsWithReport;
        $this->totalEmpCompromised = $totalEmpCompromised;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.emp-compromised');
    }
}
