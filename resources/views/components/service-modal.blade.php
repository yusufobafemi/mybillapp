{{-- <div class="srv-grid-container">
    <h1 class="srv-grid-title">Select a Service</h1>
    <div class="srv-grid">
        @foreach($services as $key => $service)
            <div class="srv-grid-item" data-service="{{ $key }}">
                <div class="srv-icon-wrapper">
                    <i class="fas {{ $service['icon'] }}"></i>
                </div>
                <div class="srv-item-title">{{ $service['title'] }}</div>
            </div>
        @endforeach
    </div>
</div> --}}

<div class="srv-modal-overlay" id="serviceModal">
    <div class="srv-modal">
        <div class="srv-modal-header" id="modalHeader">
            <div class="srv-modal-icon" id="modalIcon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <div class="srv-modal-title-container">
                <h2 class="srv-modal-title" id="modalTitle">Airtime Recharge</h2>
                <p class="srv-modal-subtitle" id="modalSubtitle">Top up your phone balance</p>
            </div>
            <button class="srv-modal-close" id="closeServiceModal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="srv-modal-content" id="modalContent"></div>

        <div class="srv-modal-footer">
            <button class="srv-btn srv-btn-secondary" id="cancelServiceModalBtn">Cancel</button>
            <button class="srv-btn srv-btn-primary" id="proceedServiceModalBtn">Proceed</button>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/service-modal.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/service-modal.js') }}"></script>
@endpush