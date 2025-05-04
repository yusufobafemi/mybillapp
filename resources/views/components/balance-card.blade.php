<!-- resources/views/components/balance-card.blade.php -->

<div class="card balance-card">
    <div class="card-header">
        <h3>{{ $title }}</h3>
        <span class="card-icon"><i class="{{ $icon }}"></i></span>
    </div>
    <div class="balance-amount">
        <h2>{{ $amount }}</h2>
        <span class="balance-change {{ $changeType }}">{{ $changeText }}</span>
    </div>
    <div class="action-buttons">
        <button class="btn primary-btn" id="addmoney">
            <i class="fas fa-plus"></i> {{ $addButtonText }}
        </button>
        <button class="btn outline-btn">
            <i class="fas fa-arrow-right"></i> {{ $transferButtonText }}
        </button>
    </div>
</div>
