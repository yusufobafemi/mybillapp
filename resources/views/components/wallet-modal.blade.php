<!-- Modal Overlay -->
<div class="wallet-modal-overlay hide">
    <!-- Modal Container -->
    <div class="wallet-modal">
        <!-- Modal Header -->
        <div class="wallet-modal-header">
            <h2 class="wallet-modal-title">Add Money to Your Wallet</h2>
            <p class="wallet-modal-subtitle">Top up your account to make payments</p>
            <button class="wallet-modal-close" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="wallet-modal-body">
            <div class="wallet-form-group">
                <label for="amount" class="wallet-form-label">Enter Amount</label>
                <div class="wallet-input-wrapper">
                    <span class="wallet-currency-symbol">₦</span>
                    <input 
                        type="number" 
                        id="amount" 
                        class="wallet-form-input" 
                        placeholder="0.00" 
                        min="100"
                        step="100"
                    >
                </div>
                <div class="wallet-error-message" id="amountError">
                    Amount must be at least ₦1,000
                </div>
            </div>

            <div class="wallet-quick-amounts">
                <button class="wallet-quick-amount" data-amount="1000">₦1,000</button>
                <button class="wallet-quick-amount" data-amount="2000">₦2,000</button>
                <button class="wallet-quick-amount" data-amount="5000">₦5,000</button>
                <button class="wallet-quick-amount" data-amount="10000">₦10,000</button>
            </div>

            <div class="wallet-payment-methods">
                <h3 class="wallet-payment-title">Payment Method</h3>
                <div class="wallet-payment-options">
                    <div class="wallet-payment-option selected">
                        <i class="fas fa-credit-card"></i>
                        <span>Card</span>
                    </div>
                    <div class="wallet-payment-option selected">
                        <i class="fas fa-university"></i>
                        <span>Bank Transfer</span>
                    </div>
                    <div class="wallet-payment-option selected">
                        <i class="fas fa-qrcode"></i>
                        <span>USSD</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="wallet-modal-footer">
            <button class="wallet-btn wallet-btn-secondary" id="cancelBtn">Cancel</button>
            <button class="wallet-btn wallet-btn-primary" id="proceedBtn" disabled>Proceed to Payment</button>
        </div>
    </div>
</div>