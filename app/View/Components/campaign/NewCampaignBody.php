<?php

namespace App\View\Components\campaign;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NewCampaignBody extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public $usersGroups, public $phishingEmails, public $trainingModules)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.campaign.new-campaign-body');
    }
}
