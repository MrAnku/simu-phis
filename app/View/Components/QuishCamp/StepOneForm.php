<?php

namespace App\View\Components\QuishCamp;

use App\Models\Users;
use App\Models\UsersGroup;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

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
        return view('components.quish-camp.step-one-form');
    }
}
