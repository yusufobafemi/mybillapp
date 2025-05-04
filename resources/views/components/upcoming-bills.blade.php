<div class="card upcoming-bills-card">
    <div class="card-header">
        <h3>Upcoming Bills</h3>
        <button class="btn small-btn"><i class="fas fa-plus"></i> Add</button>
    </div>
    <div class="bills-list">
        @foreach($bills as $bill)
            <div class="bill-item">
                <div class="bill-icon {{ $bill['class'] }}">
                    <i class="{{ $bill['icon'] }}"></i>
                </div>
                <div class="bill-details">
                    <h4>{{ $bill['title'] }}</h4>
                    <p>{{ $bill['due'] }}</p>
                </div>
                <div class="bill-amount">
                    <span>{{ $bill['amount'] }}</span>
                    <button class="btn small-btn primary-btn">Pay Now</button>
                </div>
            </div>
        @endforeach
    </div>
</div>
