@extends('layouts.app')
@section('styles')
    <link rel="stylesheet" href="{{ asset('css/user-dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/wallet-modal.css') }}">
@endsection
{{-- @section('scripts')
    <script src="https://checkout.flutterwave.com/v3.js"></script>
@endsection --}}
@section('content')
    <main class="dashboard-container">
        <!-- Welcome Section -->
        <section class="welcome-section">
            <div style="display:none;">
                <p style="display:none;" id="getUserName">{{ auth()->user()->name }}</p>
                <p style="display:none;" id="getUserEmail">{{ auth()->user()->email }}</p>
            </div>
            <div class="welcome-text">
                <h1>Welcome back, <span class="highlight">Alex</span></h1>
                <p>Manage your payments and transactions</p>
            </div>
            <div class="date-time">
                <p id="current-date">Monday, 27 April</p>
            </div>
        </section>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Balance Card -->
            {{-- @auth
                <x-balance-card 
                    title="Account Balance" 
                    icon="fas fa-wallet" 
                    :amount="'₦' . number_format(auth()->user()->balance, 2)" 
                    change-text="+₦15,000 this week" 
                    change-type="positive" 
                    add-button-text="Add Money"
                />
            @endauth --}}

            {{-- test service modal --}}
            <x-service-modal />

            <!-- Quick Actions -->
            <x-quick-actions />

            <!-- Upcoming Bills -->
            {{-- <x-upcoming-bills /> --}}

            {{-- this is the modal view for deposit --}}
            <x-wallet-modal />

            
            <!-- Saved Payment Methods -->
            {{-- <div class="card payment-methods-card">
                <div class="card-header">
                    <h3>Payment Methods</h3>
                    <button class="btn small-btn"><i class="fas fa-plus"></i> Add</button>
                </div>
                <div class="payment-methods-list">
                    <div class="payment-method-item default">
                        <div class="payment-method-icon">
                            <i class="fab fa-cc-visa"></i>
                        </div>
                        <div class="payment-method-details">
                            <h4>Visa Card</h4>
                            <p>**** **** **** 4587</p>
                        </div>
                        <div class="payment-method-badge">Default</div>
                    </div>
                    <div class="payment-method-item">
                        <div class="payment-method-icon">
                            <i class="fab fa-cc-mastercard"></i>
                        </div>
                        <div class="payment-method-details">
                            <h4>Mastercard</h4>
                            <p>**** **** **** 8724</p>
                        </div>
                    </div>
                    <div class="payment-method-item">
                        <div class="payment-method-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="payment-method-details">
                            <h4>Bank Account</h4>
                            <p>First Bank - **** 5632</p>
                        </div>
                    </div>
                </div>
            </div> --}}
        </div>

        <!-- Full Transactions History Section -->
        <section id="transactions-section" class="transactions-section">
            <div class="section-header">
                <h2>Transaction History</h2>
            </div>

            <div class="transactions-table-container">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th class="sortable">
                                Date <i class="fas fa-sort"></i>
                            </th>
                            <th>Transaction ID</th>
                            <th class="sortable">
                                Description <i class="fas fa-sort"></i>
                            </th>
                            <th>Category</th>
                            <th class="sortable">
                                Amount <i class="fas fa-sort-down"></i>
                            </th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <x-transaction-row 
                                :date="$transaction->created_at->format('d M Y')" 
                                :time="$transaction->created_at->format('h:i A')" 
                                :transactionId="$transaction->reference ?? 'TXN' . str_pad($transaction->id, 6, '0', STR_PAD_LEFT)"
                                :icon="getTransactionIcon($transaction->transaction_type_id)"
                                :iconClass="strtolower(getTransactionTypeName($transaction->transaction_type_id))" 
                                :primaryText="$transaction->description ?? 'Transaction'" 
                                :category="strtoupper(getTransactionTypeName($transaction->transaction_type_id))" 
                                :categoryClass="strtolower(getTransactionTypeName($transaction->transaction_type_id))" 
                                :amount="'-' .formatAmount($transaction->amount)"
                                :amountClass= "'debit'" 
                                :status="$transaction->status" 
                                :statusClass="strtolower($transaction->status)" 
                            />
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    {{-- this is where the transactions cards starts --}}
                    {{-- <tbody>
                        <x-transaction-row date="27 Apr 2023" time="10:30 AM" transactionId="TXN123456789"
                            icon="fa-mobile-alt" iconClass="airtime" primaryText="Airtime Recharge"
                            secondaryText="MTN - 08012345678" category="Airtime" categoryClass="airtime" amount="-₦1,000.00"
                            amountClass="debit" status="Successful" statusClass="success" />

                        <x-transaction-row date="26 Apr 2023" time="2:15 PM" transactionId="TXN123456788" icon="fa-tv"
                            iconClass="cable" primaryText="DSTV Subscription" secondaryText="Premium - 1 Month"
                            category="Cable TV" categoryClass="cable" amount="-₦24,500.00" amountClass="debit"
                            status="Successful" statusClass="success" />

                        <x-transaction-row date="25 Apr 2023" time="9:45 AM" transactionId="TXN123456787"
                            icon="fa-arrow-down" iconClass="deposit" primaryText="Account Deposit"
                            secondaryText="Bank Transfer" category="Deposit" categoryClass="deposit" amount="+₦50,000.00"
                            amountClass="credit" status="Successful" statusClass="success" />

                        <x-transaction-row date="23 Apr 2023" time="11:20 AM" transactionId="TXN123456786" icon="fa-bolt"
                            iconClass="electricity" primaryText="Electricity Bill" secondaryText="EKEDC - Prepaid"
                            category="Electricity" categoryClass="electricity" amount="-₦15,000.00" amountClass="debit"
                            status="Successful" statusClass="success" />

                        <x-transaction-row date="20 Apr 2023" time="3:45 PM" transactionId="TXN123456785" icon="fa-wifi"
                            iconClass="data" primaryText="Data Bundle" secondaryText="MTN - 10GB" category="Data"
                            categoryClass="data" amount="-₦5,000.00" amountClass="debit" status="Successful"
                            statusClass="success" />

                        <x-transaction-row date="18 Apr 2023" time="1:30 PM" transactionId="TXN123456784" icon="fa-globe"
                            iconClass="internet" primaryText="Internet Subscription" secondaryText="Spectranet - 1 Month"
                            category="Internet" categoryClass="internet" amount="-₦12,000.00" amountClass="debit"
                            status="Pending" statusClass="pending" />

                        <x-transaction-row date="15 Apr 2023" time="5:20 PM" transactionId="TXN123456783"
                            icon="fa-mobile-alt" iconClass="airtime" primaryText="Airtime Recharge"
                            secondaryText="Airtel - 09087654321" category="Airtime" categoryClass="airtime"
                            amount="-₦2,000.00" amountClass="debit" status="Failed" statusClass="failed" />

                    </tbody> --}}
                    {{-- this is were the transaction cards ends --}}
                </table>
            </div>

            <div class="pagination-container">
                <div class="pagination-info">
                    <div class="pagination-info">
                        Showing 
                        <span class="highlight">{{ $transactions->firstItem() }}</span> 
                        to 
                        <span class="highlight">{{ $transactions->lastItem() }}</span> 
                        of 
                        <span class="highlight">{{ $transactions->total() }}</span> 
                        transactions
                    </div>
                </div>
                <div class="pagination-controls">
                    {{ $transactions->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </section>


        <!-- Activity Timeline -->
        <section class="activity-timeline-section">
            <div class="section-header">
                <h2>Recent Activities</h2>
                <a href="#" class="view-all">View All</a>
            </div>
            <div class="timeline-container">
                <div class="timeline-item">
                    <div class="timeline-icon login">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Account Login</h4>
                        <p>You logged in from a new device</p>
                        <span class="timeline-time">Today, 8:15 AM</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon transaction">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Transaction Completed</h4>
                        <p>Airtime recharge of ₦1,000 to 08012345678</p>
                        <span class="timeline-time">Today, 10:30 AM</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon profile">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Profile Updated, wow ir works</h4>
                        <p>You updated your contact information</p>
                        <span class="timeline-time">Yesterday, 11:45 AM</span>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-icon transaction">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Transaction Completed</h4>
                        <p>DSTV subscription payment of ₦24,500</p>
                        <span class="timeline-time">Yesterday, 2:15 PM</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Notifications Section -->
        <section class="notifications-section">
            <div class="notification-item promo">
                <div class="notification-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="notification-content">
                    <h4>Special Offer!</h4>
                    <p>Get 10% cashback on all data purchases this weekend.</p>
                </div>
                <button class="btn outline-btn small-btn">View</button>
            </div>
            {{-- <div class="notification-item alert">
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="notification-content">
                    <h4>Subscription Reminder</h4>
                    <p>Your DSTV subscription will expire in 5 days. Renew now to avoid service interruption.</p>
                </div>
                <button class="btn primary-btn small-btn">Renew</button>
            </div> --}}
        </section>
    </main>
@endsection
@section('scripts')
    <script>
        window.flutterwaveCustomer = {
            email: "{{ auth()->user()->email }}",
            phone_number: "{{ auth()->user()->phone ?? '' }}",
            name: "{{ auth()->user()->name }}",
        };

        window.flutterwaveCustomization = {
            title: "Add Money to Wallet",
            description: "Top up your wallet to enjoy services",
            logo: "{{ asset('images/logo.png') }}", // or your logo url
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script src="{{ asset('js/user-dashboard.js') }}"></script>
    <script src="{{ asset('js/wallet-modal.js') }}"></script>
    <script src="{{ asset('js/user-dashboard-component/make-payment-card.js') }}"></script>
@endsection