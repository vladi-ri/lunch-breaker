<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * AppLayout is a view component that represents the main application layout.
 * 
 * @extends Component
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     * 
     * @access public
     * @return View
     */
    public function render() : View {
        return view('layouts.app');
    }
}
