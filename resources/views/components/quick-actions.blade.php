<div class="card quick-actions">
    <div class="card-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="actions-grid">
        @foreach($actions as $action)
            <div class="action-item">
                <div class="action-icon {{ $action['class'] }}">
                    <i class="{{ $action['icon'] }}"></i>
                </div>
                <span>{{ $action['name'] }}</span>
            </div>
        @endforeach
    </div>
</div>
