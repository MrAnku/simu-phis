<?php

namespace App\View\Components\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RecentCampaigns extends Component
{
    public $recentSixCampaigns;
    /**
     * Create a new component instance.
     */
    public function __construct($recentSixCampaigns)
    {
        $this->recentSixCampaigns = $recentSixCampaigns;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard.recent-campaigns');
    }
}
