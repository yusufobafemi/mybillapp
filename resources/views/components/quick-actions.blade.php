<div class="card quick-actions">
    <div class="card-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="actions-grid">
        @foreach($service_config as $key => $service)
            <div class="action-item srv-grid-item-card" data-service="{{  $key  }}">
                <div class="action-icon {{ $service['class'] }}">
                    <i class="{{ $service['icon'] }}"></i>
                </div>
                <span>{{ $service['title'] }}</span>
            </div>
        @endforeach
    </div>
</div>
