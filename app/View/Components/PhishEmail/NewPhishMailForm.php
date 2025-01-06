<?php

namespace App\View\Components\PhishEmail;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NewPhishMailForm extends Component
{
    public $phishingWebsites;
    public $senderProfiles;
    /**
     * Create a new component instance.
     */
    public function __construct($phishingWebsites, $senderProfiles)
    {
        $this->phishingWebsites = $phishingWebsites;
        $this->senderProfiles = $senderProfiles;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.phish-email.new-phish-mail-form');
    }
}
