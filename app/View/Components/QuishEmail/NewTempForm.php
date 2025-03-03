<?php

namespace App\View\Components\QuishEmail;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NewTempForm extends Component
{
    public $senderProfiles; 
    public $phishingWebsites;
    /**
     * Create a new component instance.
     */
    public function __construct($senderProfiles, $phishingWebsites)
    {
        $this->senderProfiles = $senderProfiles;
        $this->phishingWebsites = $phishingWebsites;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.quish-email.new-temp-form');
    }
}
