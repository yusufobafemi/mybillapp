<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeTransactionSection extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:transaction-section';

    /**
     * The console command description.
     */
    protected $description = 'Create the TransactionSection Blade component';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = resource_path('views/components/transaction-section.blade.php');

        if (File::exists($path)) {
            $this->error('TransactionSection component already exists!');
            return 1;
        }

        // Ensure the components directory exists
        File::ensureDirectoryExists(resource_path('views/components'));

        // Content of the component
        $bladeContent = <<<BLADE
<section id="transactions-section" class="transactions-section">
{{-- Section Header --}}
<div class="section-header">
    <h2>Transaction History</h2>
    <div class="xai-section-actions">
        @foreach (['airtime' => 'mobile-alt', 'data' => 'wifi', 'cable' => 'tv', 'internet' => 'globe', 'electricity' => 'bolt'] as \$type => \$icon)
            <button class="xai-btn xai-outline-btn xai-filter-btn" data-type="{{ \$type }}">
                <i class="fas fa-{{ \$icon }}"></i>
                <span>{{ ucfirst(\$type) }}</span>
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
            {{ \$slot }}
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
BLADE;

        // Create the blade file
        File::put($path, $bladeContent);

        $this->info('TransactionSection component created successfully.');
        return 0;
    }
}
