<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UpcomingBills extends Component
{
    public $bills;

    public function __construct()
    {
        $this->bills = [
            [
                'class' => 'electricity',
                'icon' => 'fas fa-bolt',
                'title' => 'Electricity Bill',
                'due' => 'Due in 3 days',
                'amount' => '₦15,000',
            ],
            [
                'class' => 'cable',
                'icon' => 'fas fa-tv',
                'title' => 'DSTV Subscription',
                'due' => 'Due in 5 days',
                'amount' => '₦24,500',
            ],
            [
                'class' => 'internet',
                'icon' => 'fas fa-wifi',
                'title' => 'Internet Subscription',
                'due' => 'Due in 10 days',
                'amount' => '₦12,000',
            ],
        ];
    }

    public function render()
    {
        return view('components.upcoming-bills');
    }
}
