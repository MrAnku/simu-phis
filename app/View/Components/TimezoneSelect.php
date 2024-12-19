<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TimezoneSelect extends Component
{
    public $id;
    public $name;
    /**
     * Create a new component instance.
     */
    public function __construct(string $id, string $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.timezone-select');
    }
}
