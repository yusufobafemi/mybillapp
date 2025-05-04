<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Flutterwave Payment Test</title>
  <script src="https://checkout.flutterwave.com/v3.js"></script>
</head>
<body>
  <h2>Test Flutterwave Payment</h2>
  <button onclick="makePayment()">Pay Now</button>

  <script>
    function makePayment() {
      FlutterwaveCheckout({
        public_key: "FLWPUBK_TEST-4680b6c537a7d0003ac847159f903391-X",
        tx_ref: "tx-" + Date.now(),
        amount: 1000,
        currency: "NGN",
        payment_options: "card,ussd,banktransfer",
        customer: {
          email: "test@example.com",
          phone_number: "08012345678",
          name: "Test User",
        },
        callback: function (data) {
          console.log("Payment complete:", data);
          alert("Payment status: " + data.status);
        },
        onclose: function () {
          console.log("Payment closed");
        },
        customizations: {
          title: "Spruce Payment",
          description: "Test payment from Spruce Landing",
          logo: "https://yourdomain.com/logo.png",
        },
      });
    }
  </script>
</body>
</html>
