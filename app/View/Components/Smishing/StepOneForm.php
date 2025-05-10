<?php

namespace App\View\Components\Smishing;

use Closure;
use App\Models\UsersGroup;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class StepOneForm extends Component
{
    public $empGroups;
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->empGroups = UsersGroup::where('company_id', auth()->user()->company_id)->where('users', '!=', null)->get();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.smishing.step-one-form');
    }
}
