<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * GuestLayout is a view component that represents the layout for guest users.
 * 
 * @extends Component
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class GuestLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     * 
     * @access public
     * @return View
     */
    public function render() : View {
        return view('layouts.guest');
    }
}
