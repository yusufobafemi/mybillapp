<?php

namespace App\View\Components;

use Illuminate\View\Component;

class BalanceCard extends Component
{
    public $title;
    public $icon;
    public $amount;
    public $changeText;
    public $changeType;
    public $addButtonText;

    public function __construct(
        $title,
        $icon,
        $amount,
        $changeText,
        $changeType,
        $addButtonText
    ) {
        $this->title = $title;
        $this->icon = $icon;
        $this->amount = $amount;
        $this->changeText = $changeText;
        $this->changeType = $changeType;
        $this->addButtonText = $addButtonText;
    }

    public function render()
    {
        return view('components.balance-card');
    }
}
