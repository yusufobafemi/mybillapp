$(document).ready(function () {
    // get current service
    let currentService = null;
    // Configuration object containing all service definitions
    // Each service has a title, subtitle, icon, color scheme, and form content
    const serviceConfig = {
        airtime: {
            title: "Airtime Recharge",
            subtitle: "Top up your phone balance",
            icon: "fas fa-mobile-alt",
            color: "linear-gradient(135deg, #E67E00, #D67200)",
            content: `
                <div class="srv-form-group">
                    <label class="srv-form-label">Phone Number</label>
                    <input type="tel" class="srv-form-input" placeholder="Enter phone number">
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Network Provider</label>
                    <select class="srv-form-select">
                        <option value="">Select provider</option>
                        <option value="mtn">MTN</option>
                        <option value="airtel">Airtel</option>
                        <option value="glo">Glo</option>
                        <option value="9mobile">9Mobile</option>
                    </select>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Amount</label>
                    <input type="number" class="srv-form-input" placeholder="Enter amount">
                </div>
            `,
        },
        data: {
            title: "Data Bundle",
            subtitle: "Purchase internet data",
            icon: "fas fa-wifi",
            color: "linear-gradient(135deg, #3B82F6, #1D4ED8)",
            content: `
                <div class="srv-form-group">
                    <label class="srv-form-label">Phone Number</label>
                    <input type="tel" class="srv-form-input" placeholder="Enter phone number">
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Network Provider</label>
                    <select class="srv-form-select">
                        <option value="">Select provider</option>
                        <option value="mtn">MTN</option>
                        <option value="airtel">Airtel</option>
                        <option value="glo">Glo</option>
                        <option value="9mobile">9Mobile</option>
                    </select>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Data Plan</label>
                    <select class="srv-form-select">
                        <option value="">Select data plan</option>
                        <option value="daily">Daily (100MB - ₦100)</option>
                        <option value="weekly">Weekly (1GB - ₦500)</option>
                        <option value="monthly">Monthly (3GB - ₦1,000)</option>
                        <option value="monthly">Monthly (10GB - ₦3,000)</option>
                    </select>
                </div>
            `,
        },
        cable: {
            title: "Cable TV Subscription",
            subtitle: "Pay for your TV subscription",
            icon: "fas fa-tv",
            color: "linear-gradient(135deg, #8B5CF6, #6D28D9)",
            content: `
                <div class="srv-form-group">
                    <label class="srv-form-label">Provider</label>
                    <select class="srv-form-select">
                        <option value="">Select provider</option>
                        <option value="dstv">DSTV</option>
                        <option value="gotv">GOTV</option>
                        <option value="startimes">StarTimes</option>
                    </select>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Smart Card / IUC Number</label>
                    <input type="text" class="srv-form-input" placeholder="Enter smart card number">
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Package</label>
                    <select class="srv-form-select">
                        <option value="">Select package</option>
                        <option value="basic">Basic - ₦1,850</option>
                        <option value="standard">Standard - ₦5,300</option>
                        <option value="premium">Premium - ₦18,400</option>
                    </select>
                </div>
            `,
        },
        electricity: {
            title: "Electricity Bill",
            subtitle: "Pay for your electricity",
            icon: "fas fa-bolt",
            color: "linear-gradient(135deg, #F97316, #C2410C)",
            content: `
                <div class="srv-form-group">
                    <label class="srv-form-label">Distribution Company</label>
                    <select class="srv-form-select">
                        <option value="">Select company</option>
                        <option value="ekedc">Eko Electric (EKEDC)</option>
                        <option value="ikedc">Ikeja Electric (IKEDC)</option>
                        <option value="aedc">Abuja Electric (AEDC)</option>
                        <option value="phedc">Port Harcourt Electric (PHEDC)</option>
                    </select>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Meter Type</label>
                    <select class="srv-form-select">
                        <option value="">Select meter type</option>
                        <option value="prepaid">Prepaid</option>
                        <option value="postpaid">Postpaid</option>
                    </select>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Meter Number</label>
                    <input type="text" class="srv-form-input" placeholder="Enter meter number">
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Amount</label>
                    <input type="number" class="srv-form-input" placeholder="Enter amount">
                </div>
            `,
        },
        internet: {
            title: "Internet Subscription",
            subtitle: "Pay for your internet service",
            icon: "fas fa-globe",
            color: "linear-gradient(135deg, #10B981, #047857)",
            content: `
                <div class="srv-form-group">
                    <label class="srv-form-label">Internet Provider</label>
                    <select class="srv-form-select">
                        <option value="">Select provider</option>
                        <option value="spectranet">Spectranet</option>
                        <option value="smile">Smile</option>
                        <option value="swift">Swift</option>
                        <option value="tizeti">Tizeti</option>
                    </select>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Customer ID / Account Number</label>
                    <input type="text" class="srv-form-input" placeholder="Enter customer ID">
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Plan</label>
                    <select class="srv-form-select">
                        <option value="">Select plan</option>
                        <option value="basic">Basic (10Mbps) - ₦15,000</option>
                        <option value="standard">Standard (20Mbps) - ₦20,000</option>
                        <option value="premium">Premium (50Mbps) - ₦35,000</option>
                        <option value="unlimited">Unlimited (100Mbps) - ₦50,000</option>
                    </select>
                </div>
            `,
        },
        bills: {
            title: "Bill Payment",
            subtitle: "Pay your bills easily",
            icon: "fas fa-file-invoice",
            color: "linear-gradient(135deg, #EC4899, #BE185D)",
            content: `
                <div class="srv-form-group">
                    <label class="srv-form-label">Bill Type</label>
                    <select class="srv-form-select">
                        <option value="">Select bill type</option>
                        <option value="water">Water Bill</option>
                        <option value="tax">Tax Payment</option>
                        <option value="insurance">Insurance Premium</option>
                        <option value="education">School Fees</option>
                        <option value="other">Other Bills</option>
                    </select>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Biller / Institution</label>
                    <input type="text" class="srv-form-input" placeholder="Enter biller name">
                </div>
                <div class="srv-input-group">
                    <div class="srv-form-group">
                        <label class="srv-form-label">Customer ID</label>
                        <input type="text" class="srv-form-input" placeholder="Enter ID">
                    </div>
                    <div class="srv-form-group">
                        <label class="srv-form-label">Reference Number</label>
                        <input type="text" class="srv-form-input" placeholder="Enter reference">
                    </div>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Amount</label>
                    <input type="number" class="srv-form-input" placeholder="Enter amount">
                </div>
            `,
        },
    };

    // Opens the modal for a specific service
    // Updates modal content based on service configuration
    function openServiceModal(service) {
        // Get the configuration for the selected service
        currentService = service; // <-- Store the current service
        const config = serviceConfig[service];

        // Update modal header content
        $("#modalTitle").text(config.title); // Set modal title
        $("#modalSubtitle").text(config.subtitle); // Set modal subtitle
        $("#modalIcon i").attr("class", config.icon); // Update icon class
        $("#modalContent").html(config.content); // Insert form content
        $("#modalHeader").css("background", config.color); // Set header background color

        // Show modal with active class for animation
        $("#serviceModal").addClass("active");

        // Initialize form interactions
        setupFormInteractions();
        console.log(currentService);
    }

    // Sets up dynamic form interactions
    // Enables/disables select elements based on previous selections
    function setupFormInteractions() {
        // Find all select elements in the modal content
        const $selects = $("#modalContent").find(".srv-form-select");

        // Only proceed if there are multiple select elements
        if ($selects.length > 1) {
            // Add change event listener to the first select
            $selects.eq(0).on("change", function () {
                // Get the selected value
                const selectedValue = $(this).val();

                // Enable/disable the second select based on selection
                if (selectedValue) {
                    $selects.eq(1).prop("disabled", false);
                } else {
                    $selects.eq(1).prop("disabled", true);
                }
            });
        }
    }

    // Closes the service modal
    // Removes active class to trigger closing animation
    function closeServiceModal() {
        $("#serviceModal").removeClass("active");
    }

    // Event listener for service grid items
    // Opens modal when a service item is clicked
    $(".srv-grid-item-card").on("click", function () {
        // Get the service identifier from data attribute
        const service = $(this).data("service");
        openServiceModal(service);
    });

    // Event listeners for closing modal
    // Handles both close button and cancel button clicks
    $("#closeServiceModal, #cancelServiceModalBtn").on(
        "click",
        closeServiceModal
    );

    // Event listener for modal overlay
    // Closes modal when clicking outside the modal content
    $("#serviceModal").on("click", function (e) {
        // Check if the click target is the overlay itself
        if ($(e.target).is("#serviceModal")) {
            closeServiceModal();
        }
    });

    // Prevents modal from closing when clicking inside
    // Stops event propagation to the overlay
    $(".srv-modal").on("click", function (e) {
        e.stopPropagation();
    });

    // Function to process form submission based on service type
    function processServiceForm(service) {
        const formData = {}; // Object to store form values

        // Depending on the service, validate and collect form data
        switch (service) {
            case "airtime":
                // Get values from modal
                formData.phoneNumber = $("#modalContent")
                    .find('input[type="tel"]')
                    .val();
                formData.network = $("#modalContent")
                    .find("select")
                    .eq(0)
                    .val();
                formData.amount = $("#modalContent")
                    .find('input[type="number"]')
                    .val();

                // Get user balance from HTML and clean the text
                const balanceText = $("#userBalance").text();
                const cleanedText = balanceText.replace(/[₦,]/g, "");
                const userBalance = parseFloat(cleanedText);

                // Validate inputs
                if (
                    !formData.phoneNumber ||
                    !formData.network ||
                    !formData.amount
                ) {
                    $.elegantToastr.error(
                        "Warning!",
                        "Please fill all inputs..."
                    );
                    return;
                }

                if (parseFloat(formData.amount) > userBalance) {
                    $.elegantToastr.error(
                        "Insufficient Balance",
                        "Your balance is insufficient for this recharge."
                    );
                    return;
                }

                // Show SweetAlert confirmation before proceeding
                Swal.fire({
                    title: "Confirm Airtime Purchase",
                    html: `
                        <div style="text-align: left; font-size: 16px; line-height: 1.6; color: #333; padding: 12px; background-color: #f9f9f9; border-radius: 6px;">
                            <p style="margin: 8px 0;display: flex;justify-content: space-between;"><strong>Network:</strong> ${formData.network.toUpperCase()}</p>
                            <p style="margin: 8px 0;display: flex;justify-content: space-between;"><strong>Phone:</strong> ${formData.phoneNumber}</p>
                            <p style="margin: 8px 0;display: flex;justify-content: space-between;"><strong>Amount:</strong> ₦${Number(formData.amount).toLocaleString()}</p>
                        </div>
                    `,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Yes, continue",
                    cancelButtonText: "Cancel",
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show SweetAlert loading while processing
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while we recharge your line.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });

                        // Add the 'service' field
                        formData.service = 'airtime';

                        //AJAX request to process airtime
                        $.ajax({
                            url: "/api/process-service",
                            method: "POST",
                            data: formData,
                            success: function (response) {
                                if(response.status = "success"){
                                    Swal.fire({
                                        title: "Success!",
                                        text: "Airtime recharge completed.",
                                        icon: "success",
                                    });
                                    if (response.new_balance !== undefined) {
                                        const formattedBalance = `₦${Number(response.new_balance).toLocaleString()}`;
                                        $("#userBalance").text(formattedBalance);
                                    }
                                }
                                
                            },
                            error: function (error) {
                                Swal.fire({
                                    title: "Error!",
                                    text: "Something went wrong. Please try again.",
                                    icon: "error",
                                });
                            },
                        });
                    }
                });
                break;

            case "data":
                formData.phoneNumber = $("#modalContent")
                    .find('input[type="tel"]')
                    .val();
                formData.network = $("#modalContent")
                    .find("select")
                    .eq(0)
                    .val();
                formData.dataPlan = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .val();

                // Validate form data
                if (
                    !formData.phoneNumber ||
                    !formData.network ||
                    !formData.dataPlan
                ) {
                    alert("Please fill all fields.");
                    return;
                }

                // Proceed with data bundle logic (e.g., API call, form submission)
                alert("Processing Data Bundle Purchase...");
                break;

            case "cable":
                formData.provider = $("#modalContent")
                    .find("select")
                    .eq(0)
                    .val();
                formData.smartCard = $("#modalContent")
                    .find('input[type="text"]')
                    .val();
                formData.package = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .val();

                // Validate form data
                if (
                    !formData.provider ||
                    !formData.smartCard ||
                    !formData.package
                ) {
                    alert("Please fill all fields.");
                    return;
                }

                // Proceed with cable TV subscription logic
                alert("Processing Cable TV Subscription...");
                break;

            case "electricity":
                formData.company = $("#modalContent")
                    .find("select")
                    .eq(0)
                    .val();
                formData.meterType = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .val();
                formData.meterNumber = $("#modalContent")
                    .find('input[type="text"]')
                    .val();
                formData.amount = $("#modalContent")
                    .find('input[type="number"]')
                    .val();

                // Validate form data
                if (
                    !formData.company ||
                    !formData.meterType ||
                    !formData.meterNumber ||
                    !formData.amount
                ) {
                    alert("Please fill all fields.");
                    return;
                }

                // Proceed with electricity payment logic
                alert("Processing Electricity Bill Payment...");
                break;

            case "internet":
                formData.provider = $("#modalContent")
                    .find("select")
                    .eq(0)
                    .val();
                formData.customerId = $("#modalContent")
                    .find('input[type="text"]')
                    .val();
                formData.plan = $("#modalContent").find("select").eq(1).val();

                // Validate form data
                if (
                    !formData.provider ||
                    !formData.customerId ||
                    !formData.plan
                ) {
                    alert("Please fill all fields.");
                    return;
                }

                // Proceed with internet subscription logic
                alert("Processing Internet Subscription...");
                break;

            case "bills":
                formData.billType = $("#modalContent")
                    .find("select")
                    .eq(0)
                    .val();
                formData.biller = $("#modalContent")
                    .find('input[type="text"]')
                    .val();
                formData.customerId = $("#modalContent")
                    .find('input[type="text"]')
                    .eq(1)
                    .val();
                formData.reference = $("#modalContent")
                    .find('input[type="text"]')
                    .eq(2)
                    .val();
                formData.amount = $("#modalContent")
                    .find('input[type="number"]')
                    .val();

                // Validate form data
                if (
                    !formData.billType ||
                    !formData.biller ||
                    !formData.customerId ||
                    !formData.reference ||
                    !formData.amount
                ) {
                    alert("Please fill all fields.");
                    return;
                }

                // Proceed with bill payment logic
                alert("Processing Bill Payment...");
                break;

            default:
                alert("Service not supported.");
                break;
        }
    }

    // Event listener for proceed button
    // Currently shows a simple alert; can be extended for form submission
    $("#proceedServiceModalBtn").on("click", function () {
        // Validate form inputs based on the selected service
        if (currentService) {
            // Call the function based on the service type
            processServiceForm(currentService);
        } else {
            alert("No service selected.");
        }
    });
});
