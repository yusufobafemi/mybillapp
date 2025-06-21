{{-- At the top of resources/views/components/transaction-row.blade.php --}}
@props([
    'date' => 'N/A',
    'time' => 'N/A',
    'transactionId' => 'N/A',
    'icon' => 'fa-question-circle', // A default Font Awesome icon
    'iconClass' => '',
    'primaryText' => 'Transaction',
    'secondaryText' => '', // Often optional, so empty string is a good default
    'category' => 'Uncategorized',
    'categoryClass' => '',
    'amount' => '0.00',
    'amountClass' => '',
    'status' => 'Unknown',
    'statusClass' => '',
])

{{-- Your existing component HTML structure remains the same --}}
<tr class="transaction-row">
    <td>
        <div class="cell-content">
            <span class="date">{{ $date }}</span>
            <span class="time">{{ $time }}</span>
        </div>
    </td>
    <td>
        <div class="cell-content">
            <span class="transaction-id">{{ $transactionId }}</span>
        </div>
    </td>
    <td>
        <div class="cell-content">
            <div class="transaction-icon-small {{ $iconClass }}">
                <i class="fas {{ $icon }}"></i>
            </div>
            <div class="description-text">
                <span class="primary-text">{{ $primaryText }}</span>
                <span class="secondary-text">{{ $secondaryText }}</span>
            </div>
        </div>
    </td>
    <td>
        <div class="cell-content">
            <span class="category-badge {{ $categoryClass }}">{{ $category }}</span>
        </div>
    </td>
    <td>
        <div class="cell-content">
            <span class="amount {{ $amountClass }}">{{ $amount }}</span>
        </div>
    </td>
    <td>
        <div class="cell-content">
            <span class="status-badge {{ $statusClass }}">{{ $status }}</span>
        </div>
    </td>
    <td>
        <div class="cell-content">
            <button class="btn icon-btn small-btn"><i class="fas fa-receipt"></i></button>
            @if($status !== 'Failed' && $status !== 'Unknown') {{-- Adjusted condition --}}
                <button class="btn icon-btn small-btn"><i class="fas fa-redo"></i></button>
            @endif
        </div>
    </td>
</tr>