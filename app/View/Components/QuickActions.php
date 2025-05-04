<?php

namespace App\View\Components;

use Illuminate\View\Component;

class QuickActions extends Component
{
    public $actions;

    public function __construct()
    {
        $this->actions = [
            ['class' => 'airtime', 'icon' => 'fas fa-mobile-alt', 'name' => 'Airtime'],
            ['class' => 'data', 'icon' => 'fas fa-wifi', 'name' => 'Data'],
            ['class' => 'cable', 'icon' => 'fas fa-tv', 'name' => 'Cable TV'],
            ['class' => 'electricity', 'icon' => 'fas fa-bolt', 'name' => 'Electricity'],
            ['class' => 'internet', 'icon' => 'fas fa-globe', 'name' => 'Internet'],
            ['class' => 'bills', 'icon' => 'fas fa-file-invoice', 'name' => 'Bills'],
        ];
    }

    public function render()
    {
        return view('components.quick-actions');
    }
}
