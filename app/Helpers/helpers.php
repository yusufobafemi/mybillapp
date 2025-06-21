<?php
function formatAmount($amount) {
    $sign = '-';
    return $sign . '₦' . number_format(abs($amount), 2);
}

function getTransactionIcon($transaction_type_id) {
    return match(strtolower($transaction_type_id)) {
        '2' => 'fa-mobile-alt',
        '4' => 'fa-tv',
        '5' => 'fa-bolt',
        '1' => 'fa-arrow-down',
        '3' => 'fa-wifi',
        'internet' => 'fa-globe',
        default => 'fa-receipt',
    };
}

//this is to return the transaction type name fron the id
function getTransactionTypeName($transaction_type_id) {
    return match((string) $transaction_type_id) {
        '1' => 'Deposit',
        '2' => 'Airtime',
        '3' => 'Data',
        '4' => 'Cable TV',
        '5' => 'Electricity',
        '6' => 'Internet',
        default => 'Other',
    };
}

