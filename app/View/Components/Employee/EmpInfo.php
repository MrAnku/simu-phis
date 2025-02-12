<?php

namespace App\View\Components\Employee;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EmpInfo extends Component
{
    public $employee;
    public $linkClicks;
    public $totalCampaigns;
    public $totalTrainings;
    /**
     * Create a new component instance.
     */
    public function __construct($employee, $linkClicks = null, $totalCampaigns = null, $totalTrainings = null)
    {
        $this->employee = $employee;
        $this->linkClicks = $linkClicks;
        $this->totalCampaigns = $totalCampaigns;
        $this->totalTrainings = $totalTrainings;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.employee.emp-info');
    }
}
