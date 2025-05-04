<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TransactionRow extends Component
{
    public $date;
    public $time;
    public $transactionId;
    public $icon;
    public $iconClass;
    public $primaryText;
    public $secondaryText;
    public $category;
    public $categoryClass;
    public $amount;
    public $amountClass;
    public $status;
    public $statusClass;

    public function __construct(
        $date,
        $time,
        $transactionId,
        $icon,
        $iconClass,
        $primaryText,
        $secondaryText,
        $category,
        $categoryClass,
        $amount,
        $amountClass,
        $status,
        $statusClass
    ) {
        $this->date = $date;
        $this->time = $time;
        $this->transactionId = $transactionId;
        $this->icon = $icon;
        $this->iconClass = $iconClass;
        $this->primaryText = $primaryText;
        $this->secondaryText = $secondaryText;
        $this->category = $category;
        $this->categoryClass = $categoryClass;
        $this->amount = $amount;
        $this->amountClass = $amountClass;
        $this->status = $status;
        $this->statusClass = $statusClass;
    }

    public function render()
    {
        return view('components.transaction-row');
    }
}
