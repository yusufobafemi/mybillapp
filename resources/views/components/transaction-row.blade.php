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
            @if($status !== 'Failed')
                <button class="btn icon-btn small-btn"><i class="fas fa-redo"></i></button>
            @endif
        </div>
    </td>
</tr>
