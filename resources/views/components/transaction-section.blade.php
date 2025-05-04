<section id="transactions-section" class="transactions-section">
{{-- Section Header --}}
<div class="section-header">
    <h2>Transaction History</h2>
    <div class="xai-section-actions">
        @foreach (['airtime' => 'mobile-alt', 'data' => 'wifi', 'cable' => 'tv', 'internet' => 'globe', 'electricity' => 'bolt'] as $type => $icon)
            <button class="xai-btn xai-outline-btn xai-filter-btn" data-type="{{ $type }}">
                <i class="fas fa-{{ $icon }}"></i>
                <span>{{ ucfirst($type) }}</span>
            </button>
        @endforeach
    </div>
</div>

{{-- Transactions Table --}}
<div class="transactions-table-container">
    <table class="transactions-table">
        <thead>
            <tr>
                <th class="sortable">Date <i class="fas fa-sort"></i></th>
                <th>Transaction ID</th>
                <th class="sortable">Description <i class="fas fa-sort"></i></th>
                <th>Category</th>
                <th class="sortable">Amount <i class="fas fa-sort-down"></i></th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="pagination-container">
    <div class="pagination-info">
        Showing <span class="highlight">1-7</span> of <span class="highlight">24</span> transactions
    </div>
    <div class="pagination-controls">
        <button class="pagination-btn" disabled><i class="fas fa-chevron-left"></i></button>
        <button class="pagination-btn active">1</button>
        <button class="pagination-btn">2</button>
        <button class="pagination-btn">3</button>
        <button class="pagination-btn">4</button>
        <button class="pagination-btn"><i class="fas fa-chevron-right"></i></button>
    </div>
</div>
</section>