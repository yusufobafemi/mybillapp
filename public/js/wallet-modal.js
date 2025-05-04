$(document).ready(function () {
    // DOM References
    const $modalOverlay = $('.wallet-modal-overlay');
    const $modalCard = $('.wallet-modal');
    const $amountInput = $('#amount');
    const $amountError = $('#amountError');
    const $proceedBtn = $('#proceedBtn');
    const $closeModalBtn = $('#closeModal');
    const $cancelBtn = $('#cancelBtn');
    const $quickAmounts = $('.wallet-quick-amount');
    const $paymentOptions = $('.wallet-payment-option');
  
    // Ensure critical elements exist
    if (!$modalOverlay.length || !$modalCard.length || !$amountInput.length) {
      console.error('Required DOM elements are missing.');
      return;
    }
  
    // Toggle modal visibility
    function toggleModal() {
      $modalOverlay.toggleClass('hide');
    }
  
    // Validate amount input
    function validateAmount() {
      const amount = parseFloat($amountInput.val());
      const isValid = !isNaN(amount) && amount >= 1000;
      $amountInput.toggleClass('error', !isValid);
      $amountError.toggleClass('visible', !isValid);
      $proceedBtn.prop('disabled', !isValid);
      return isValid;
    }
  
    // Deselect all quick amount buttons
    function deselectQuickAmounts() {
      $quickAmounts.removeClass('selected');
    }
  
    // Event Handlers
    // Open modal
    $('#addmoney').on('click', function (e) {
      e.preventDefault();
      toggleModal();
    });
  
    // Close modal
    $closeModalBtn.add($cancelBtn).on('click', toggleModal);
  
    // Close modal when clicking outside
    $modalOverlay.on('click', function (e) {
      if (e.target === $modalOverlay[0]) {
        toggleModal();
      }
    });
  
    // Prevent closing when clicking inside modal
    $modalCard.on('click', function (e) {
      e.stopPropagation();
    });
  
    // Amount input handling
    $amountInput.on('input blur', function () {
      validateAmount();
      deselectQuickAmounts();
    });
  
    // Quick amount buttons
    $quickAmounts.on('click', function () {
      $amountInput.val($(this).data('amount'));
      deselectQuickAmounts();
      $(this).addClass('selected');
      validateAmount();
    });
  
    // Proceed button
    $proceedBtn.on('click', function () {
        if (validateAmount()) {
            const amount = parseFloat($amountInput.val());
            const selectedPayment = $('.wallet-payment-option.selected span').text();
    
            if (!selectedPayment) {
                $.elegantToastr.error('Error!', 'Please select a payment option.');
                return;
            }
    
            $proceedBtn.prop('disabled', true);
            const originalButtonText = $proceedBtn.html();
            $proceedBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            const randomDigits = Math.random().toString().substring(2, 8); // Gets 6 digits after the decimal
            console.log("Flutterwave Checkout Configuration:", {
              public_key: "FLWPUBK_TEST-...",
              tx_ref: "TXN_...",
              amount: amount,
              currency: "NGN",
              payment_options: "card,banktransfer,ussd",
              customer: window.flutterwaveCustomer,
              customizations: window.flutterwaveCustomization,
              redirect_url: '...',
              // ... other parameters
          });
            // Initialize payment via Flutterwave Inline
            FlutterwaveCheckout({
                public_key: "FLWPUBK_TEST-4680b6c537a7d0003ac847159f903391-X",
                tx_ref: "TXN_" + Date.now() +  randomDigits,
                amount: amount,
                currency: "NGN",
                payment_options: "card,banktransfer,ussd", 
                customer: window.flutterwaveCustomer,
                customizations: window.flutterwaveCustomization,
                redirect_url: 'http://127.0.0.1:8000/dashboard',
                callback: function (response) {
                    // console.log(response);
                    if (response.status === 'successful') {
                        $.elegantToastr.success('Success!', 'Please select a payment option.');
                        // You can call your server to verify transaction and update user balance
                    } else {
                        $.elegantToastr.error('Error!', 'Payment Failed or Cancelled.');
                    }
                    // Restore button
                    $proceedBtn.prop('disabled', false);
                    $proceedBtn.html(originalButtonText);
                },
                onclose: function () {
                    console.log('Modal closed');
                    $proceedBtn.prop('disabled', false);
                    $proceedBtn.html(originalButtonText);
                },
            });
        }
    });
    
    
  });