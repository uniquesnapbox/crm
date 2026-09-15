<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Str;

class MenuItem extends Component
{

    public $icon;
    public $text;
    public $link;
    public $active;
    public $addon;
    public $count;
    public $accordionId;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($icon, $text, $link = null, $active = false, $addon = false, $count = 0)
    {
        $this->text = $text;
        $this->icon = $icon;
        $this->link = $link;
        $this->active = (bool) $active || (!is_null($link) && url()->current() === $link);
        $this->addon = $addon;
        $this->count = $count;
        $this->accordionId = 'sidebar-accordion-' . Str::slug($icon . '-' . $text);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        return view('components.menu-item');
    }

}
