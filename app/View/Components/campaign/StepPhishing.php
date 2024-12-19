<?php

namespace App\View\Components\campaign;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StepPhishing extends Component
{
    public $phishingEmails;
    /**
     * Create a new component instance.
     */
    public function __construct($phishingEmails)
    {
        $this->phishingEmails = $phishingEmails;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.campaign.step-phishing');
    }
}
