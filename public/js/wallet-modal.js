$(document).ready(function () {
  // DOM References
  const $modalOverlay = $(".wallet-modal-overlay");
  const $modalCard = $(".wallet-modal");
  const $amountInput = $("#amount");
  const $amountError = $("#amountError");
  const $proceedBtn = $("#proceedBtn");
  const $closeModalBtn = $("#closeModal");
  const $cancelBtn = $("#cancelBtn");
  const $quickAmounts = $(".wallet-quick-amount");
  const $paymentOptions = $(".wallet-payment-option");

  // Ensure critical elements exist
  if (!$modalOverlay.length || !$modalCard.length || !$amountInput.length) {
      console.error("Required DOM elements are missing.");
      return;
  }

  // Get CSRF token from meta tag (essential for POST requests in Laravel)
  const csrfToken = $('meta[name="csrf-token"]').attr("content");
  if (!csrfToken) {
      console.error("CSRF token meta tag not found!");
      // You might want to disable the proceed button or show an error if no token
      $proceedBtn.prop("disabled", true);
  }


  // Store original button text
  let originalButtonText = $proceedBtn.html();

  // Toggle modal visibility
  function toggleModal() {
      $modalOverlay.toggleClass("hide");
  }

  // Validate amount input
  function validateAmount() {
      const amount = parseFloat($amountInput.val());
      const isValid = !isNaN(amount) && amount >= 1000; // Your validation logic
      $amountInput.toggleClass("error", !isValid);
      $amountError.toggleClass("visible", !isValid);
      // Only enable if amount is valid and payment option is selected (optional, can add later)
      // For now, just validate amount
      $proceedBtn.prop("disabled", !isValid);
      return isValid;
  }

  // Deselect all quick amount buttons
  function deselectQuickAmounts() {
      $quickAmounts.removeClass("selected");
  }

  // Show processing state on button
  function showProcessingState() {
      originalButtonText = $proceedBtn.html(); // Capture current text just in case
      $proceedBtn.prop("disabled", true);
      $proceedBtn.html(
          '<i class="fas fa-spinner fa-spin"></i> Processing...'
      );
  }

  // Restore button state
  function restoreButtonState() {
       $proceedBtn.prop("disabled", false);
       $proceedBtn.html(originalButtonText); // Restore saved text
       // Re-validate amount to ensure disabled state is correct after restoring
       validateAmount();
  }


  // Event Handlers
  // Open modal
  $("#addmoney").on("click", function (e) {
      e.preventDefault();
      toggleModal();
      // Reset state when opening modal
      $amountInput.val(''); // Clear amount
      deselectQuickAmounts();
      validateAmount(); // Validate empty state (should disable button)
      restoreButtonState(); // Ensure button is not stuck in processing if modal was closed unexpectedly
  });

  // Close modal
  $closeModalBtn.add($cancelBtn).on("click", toggleModal);

  // Close modal when clicking outside
  $modalOverlay.on("click", function (e) {
      if (e.target === $modalOverlay[0]) {
          toggleModal();
      }
  });

  // Prevent closing when clicking inside modal
  $modalCard.on("click", function (e) {
      e.stopPropagation();
  });

  // Amount input handling
  $amountInput.on("input blur", function () {
      validateAmount();
      deselectQuickAmounts();
  });

  // Quick amount buttons
  $quickAmounts.on("click", function () {
      $amountInput.val($(this).data("amount"));
      deselectQuickAmounts();
      $(this).addClass("selected");
      validateAmount();
  });

  // Proceed button click handler
  $proceedBtn.on("click", function () {
      if (!validateAmount()) {
           $.elegantToastr.error("Error!", "Please enter a valid amount.");
           return; // Stop if amount is not valid
      }

      // Get amount and selected payment (if needed for backend)
      const amount = parseFloat($amountInput.val());
      // const selectedPayment = $('.wallet-payment-option.selected span').text(); // Not strictly needed for prepareTopUp endpoint as implemented

      // Show processing state immediately
      showProcessingState();


      // --- Step 1: Make AJAX call to backend to prepare the transaction ---
      $.ajax({
          type: "POST",
          url: "/prepare-topup", // New route for preparing the top-up
          data: {
              amount: amount, // Send the amount to the backend
              _token: csrfToken // Include CSRF token
          },
          success: function (response) {
              // --- Step 2: Backend prepared successfully, initiate Flutterwave Checkout ---
              console.log("Prepare TopUp Response:", response);

              if (!response.tx_ref || !response.customer || !response.amount) {
                   $.elegantToastr.error("Error!", "Invalid response from server.");
                   restoreButtonState();
                   return;
              }

              FlutterwaveCheckout({
                  public_key: "FLWPUBK_TEST-4680b6c537a7d0003ac847159f903391-X", // Your public key
                  tx_ref: response.tx_ref, // Use the tx_ref from backend
                  amount: response.amount, // Use the amount from backend response (or validated amount)
                  currency: "NGN",
                  payment_options: "card,banktransfer,ussd",
                  customer: response.customer, // Use customer details from backend response
                  customizations: window.flutterwaveCustomization, // Keep your customizations
                  redirect_url: window.location.origin + "/verify-payment", // Keep the redirect URL

                  // --- Step 3: Handle Flutterwave Callbacks (Client-Side) ---
                  // Note: The main verification happens on the backend via redirect_url.
                  // This callback is mostly for client-side UI updates or logging.
                  callback: function (response) {
                      console.log('Flutterwave client-side callback:', response);
                      // Avoid doing server-side verification AJAX here.
                      // The backend /verify-payment route handles verification after redirect.
                       if (response.status === 'successful') {
                           // Optional: Show a quick success message in the modal
                          // $.elegantToastr.success("Payment initiated!", "Finalizing your transaction...");
                       } else {
                           // Optional: Show failure message if status isn't successful
                           // $.elegantToastr.error("Payment Failed", "Please try again.");
                           // restoreButtonState(); // May need this depending on Flutterwave behavior
                       }
                       // The modal will likely close or the page will redirect after this.
                       // Final result is shown after the backend redirect.
                  },
                  onclose: function () {
                      console.log('Flutterwave modal closed');
                      // Restore button state if user closes the modal manually
                      restoreButtonState();
                  },
              });

          },
          error: function (xhr, status, error) {
              // --- Handle AJAX error from prepare-topup endpoint ---
              console.error("Prepare TopUp AJAX Error:", xhr.responseText);
              const errorMsg = xhr.responseJSON && xhr.responseJSON.error
                               ? xhr.responseJSON.error
                               : "Failed to prepare transaction.";
              $.elegantToastr.error("Error!", errorMsg);
              restoreButtonState(); // Re-enable button on AJAX error
          }
      });
  });

  // Initial validation on page load (optional)
  // validateAmount(); // Might want to start with button disabled if amount is empty
});