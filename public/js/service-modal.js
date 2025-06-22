// Set up CSRF token for all AJAX requests
$.ajaxSetup({
    headers: {
        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
});

function generateTransactionCode() {
    const randomNumber = Math.floor(100000000 + Math.random() * 900000000); // ensures 9-digit number
    return `TXN${randomNumber}`;
}

$(document).ready(function () {
    // get current service
    let currentService = null;
    let isSmartCardVerified = false;

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
                        <option value="mtn" data-billercode="BIL108">MTN</option>
                        <option value="airtel" data-billercode="BIL110">Airtel</option>
                        <option value="glo" data-billercode="BIL109">Glo</option>
                        <option value="9mobile" data-billercode="BIL111">9Mobile</option>
                    </select>
                </div>
                <div id="data-plans" class="srv-form-group disabled-select">
                    <label class="srv-form-label">Data Plan</label>
                    <div style="display: flex; justify-content: center;">
                        <span id="data-plans-loader" class="srv-loader" style="display: none; font-size: 0.9em;">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </span>
                    </div>
                    <select class="srv-form-select" disabled>
                        <option value="">Select data plan</option>
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
                    <label class="srv-form-label">Smart Card / IUC Number</label>
                    <input type="text" class="srv-form-input" placeholder="Enter smart card number">
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Provider</label>
                    <select class="srv-form-select">
                        <option value="">Select provider</option>
                        <!-- MODIFIED: Added data-billercode attributes -->
                        <option value="dstv" data-billercode="BIL121">DSTV</option>
                        <option value="gotv" data-billercode="BIL122">GOTV</option>
                        <option value="startimes" data-billercode="BIL123">StarTimes</option>
                    </select>
                </div>
                <!-- MODIFIED: Added ID for easier selection and loader -->
                <div id="cable-packages" class="srv-form-group disabled-select">
                    <label class="srv-form-label">Package</label>
                     <!-- ADDED: Loader -->
                     <div style="display: flex; justify-content: center;">
                        <span id="cable-packages-loader" class="srv-loader" style="display: none; font-size: 0.9em;">
                            <i class="fas fa-spinner fa-spin"></i> Loading packages...
                        </span>
                    </div>
                    <!-- MODIFIED: Initial placeholder -->
                    <select class="srv-form-select" disabled>
                        <option value="" disabled selected>Select provider first</option>
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
                    <select class="srv-form-select" id='meter_disco'>
                        <option value="">Select company</option>
                        <option value="eko" data-billercode="BIL112">EKO DISCO</option>
                        <option value="ikeja" data-billercode="BIL113">IKEJA DISCO</option>
                        <option value="ibadan" data-billercode="BIL114">IBADAN DISCO</option>
                        <option value="enugu" data-billercode="BIL115">ENUGU DISCO</option>
                        <option value="ph" data-billercode="BIL116">PORT HARCOURT DISCO</option>
                        <option value="benin" data-billercode="BIL117">BENIN DISCO</option>
                        <option value="yola" data-billercode="BIL118">YOLA DISCO</option>
                        <option value="kaduna" data-billercode="BIL119">KADUNA DISCO</option>
                        <option value="kano" data-billercode="BIL120">KANO DISCO</option>
                        <option value="lekki" data-billercode="BIL127">LEKKI CONCESSION CO.</option>
                        <option value="abuja" data-billercode="BIL204">ABUJA DISCO</option>
                    </select>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Meter Type</label>
                    <select class="srv-form-select" id='meter_type'>
                        <option value="">Select meter type</option>
                        <option value="prepaid">Prepaid</option>
                        <option value="postpaid">Postpaid</option>
                    </select>
                </div>
                <div class="srv-form-group">
                    <label class="srv-form-label">Meter Number</label>
                    <input type="text" class="srv-form-input" placeholder="Enter meter number" id='meter_number_input'>
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

        // Initialize form interactions ONLY AFTER content is loaded
        setupFormInteractions();
    }

    // Sets up dynamic form interactions based on the current service
    // ADDED/MODIFIED: This function now contains the logic for the Data Bundle dynamic selects.
    function setupFormInteractions() {
        // --- Specific interaction for Data Bundle service ---
        if (currentService === "data") {
            const $modalContent = $("#modalContent");
            const $networkSelect = $modalContent.find("select").eq(0); // Network select
            const $dataPlanSelect = $modalContent.find("select").eq(1); // Data Plan select
            const $dataPlansGroup = $("#data-plans"); // The div containing the data plan select
            const $loader = $("#data-plans-loader"); // Get the loader element

            // Add change event listener to the Network Provider select
            $networkSelect.on("change", function () {
                const selectedNetwork = $(this).val();
                // GET THE BILLER CODE
                const getbillercode = $(this)
                    .find("option:selected")
                    .data("billercode");

                // --- Always reset and disable data plan select when network changes ---
                // Clear existing options (except maybe a placeholder if desired before fetch)
                $dataPlanSelect.empty();
                // Add a temporary placeholder or keep empty while loading
                $dataPlanSelect.append(
                    '<option disabled selected value="">Loading plans...</option>'
                ); // Indicate loading
                $dataPlanSelect.prop("disabled", true); // Disable
                $dataPlansGroup.addClass("disabled-select"); // Add disabled styling class
                $dataPlanSelect.show(); // Ensure it's visible with the loading text

                if (selectedNetwork) {
                    // Show the loader before starting the fetch
                    $loader.show();
                    // Optional: Hide the select element while loading (already showing 'Loading plans...')

                    // --- AJAX CALL TO FETCH DATA PLANS ---
                    $.ajax({
                        url: "/get-data-info", // Replace with your actual endpoint
                        method: "POST",
                        data: { billercode: getbillercode },
                        beforeSend: function () {
                            // Done before $.ajax call now, but can keep here too
                            $loader.show();
                            // $dataPlanSelect.hide(); // Decided to show 'Loading plans...' text instead
                        },
                        success: function (response) {
                            $dataPlanSelect.empty(); // Clear 'Loading plans...'

                            if (
                                response.status === "success" &&
                                response.data &&
                                response.data.length > 0
                            ) {
                                $dataPlanSelect.append(
                                    '<option disabled selected value="">Select a plan</option>'
                                );

                                response.data.forEach(function (item) {
                                    $dataPlanSelect.append(
                                        $("<option></option>")
                                            .val(item.item_code)
                                            .text(
                                                item.name + " - ₦" + item.amount
                                            )
                                            // Keep data attributes if needed later
                                            .attr(
                                                "data-billercode",
                                                item.biller_code
                                            ) // Or item.billercode if available
                                            .attr(
                                                "data-itemcode",
                                                item.item_code
                                            )
                                            .attr("data-dataplan", item.name)
                                    );
                                });

                                // --- ENABLE THE SELECT AND REMOVE DISABLED CLASS ---
                                $dataPlanSelect.prop("disabled", false); // <-- ADDED: Enable the select
                                $dataPlansGroup.removeClass("disabled-select"); // <-- ADDED: Remove the disabled styling class
                                // $dataPlanSelect.show(); // Select is already visible
                            } else {
                                // Handle case where no plans are returned or response is empty
                                $dataPlanSelect.append(
                                    '<option disabled selected value="">No data plans found</option>'
                                );
                                // --- ENSURE IT STAYS DISABLED AND STYLED AS DISABLED ---
                                $dataPlanSelect.prop("disabled", true); // <-- ADDED: Explicitly keep/set disabled
                                $dataPlansGroup.addClass("disabled-select"); // <-- ADDED: Explicitly keep/set disabled styling
                                // $dataPlanSelect.show(); // Select is already visible

                                // Optional: Show an info message
                                $.elegantToastr.info(
                                    "Info",
                                    `No data plans found for the selected network.` // Or use network name if available
                                );
                            }
                        },
                        error: function () {
                            // Handle AJAX error
                            $dataPlanSelect.empty(); // Clear 'Loading plans...'
                            $dataPlanSelect.html(
                                '<option disabled selected value="">Failed to load data plans</option>'
                            );
                            // --- ENSURE IT STAYS DISABLED AND STYLED AS DISABLED ---
                            $dataPlanSelect.prop("disabled", true); // <-- ADDED: Explicitly keep/set disabled
                            $dataPlansGroup.addClass("disabled-select"); // <-- ADDED: Explicitly keep/set disabled styling
                            // $dataPlanSelect.show(); // Select is already visible

                            // Optional: Show an error message
                            $.elegantToastr.error(
                                "Error",
                                "Failed to load data plans."
                            );
                        },
                        complete: function () {
                            // Hide the loader regardless of success or error
                            $loader.hide();
                        },
                    });
                } else {
                    // --- If network is cleared (back to "Select provider") ---
                    // Reset, disable, hide loader
                    $loader.hide();
                    $dataPlanSelect.empty(); // Clear any loaded options
                    $dataPlanSelect.append(
                        '<option disabled selected value="">Select a network first</option>'
                    ); // Placeholder when no network selected
                    $dataPlanSelect.prop("disabled", true); // <-- Keep/set disabled
                    $dataPlansGroup.addClass("disabled-select"); // <-- Keep/set disabled styling
                    $dataPlanSelect.show(); // Ensure it's visible with the placeholder
                }
            });

            // --- Initial state setup when the modal is opened ---
            // This runs once when setupFormInteractions is called
            if (!$networkSelect.val()) {
                // If no network is pre-selected, ensure the data plan select is disabled initially
                $dataPlanSelect.empty(); // Start empty or add an initial placeholder
                $dataPlanSelect.append(
                    '<option disabled selected value="">Select a network first</option>'
                ); // Initial placeholder
                $dataPlanSelect.prop("disabled", true);
                $dataPlansGroup.addClass("disabled-select");
                $loader.hide(); // Ensure loader is hidden initially
                $dataPlanSelect.show(); // Ensure the placeholder is visible
            } else {
                // If a network *is* pre-selected (e.g., on edit)
                // You might want to trigger the change event here
                // $networkSelect.trigger('change'); // Uncomment this line if you want to load plans on modal open if a network is already selected
                // If you don't trigger change, ensure the data plan select is either disabled or populated based on your app's logic
            }
        } else if (currentService === "cable") {
            let verificationTimeout;
            const $modalContent = $("#modalContent");
            const $providerSelect = $modalContent.find("select").eq(0); // Provider select
            const $packageSelect = $modalContent.find("select").eq(1); // Package select
            const $smartCardInput = $modalContent
                .find('input[type="text"]')
                .eq(0); // Smart Card input
            const $packagesGroup = $("#cable-packages"); // The div containing the package select
            const $loader = $("#cable-packages-loader"); // Loader element

            // Add change event listener to the Provider select
            $providerSelect.on("change", function () {
                const selectedProvider = $(this).val();
                const billerCode = $(this)
                    .find("option:selected")
                    .data("billercode");

                // Reset and disable package select when provider changes
                $packageSelect.empty();
                $packageSelect.append(
                    '<option disabled selected value="">Loading packages...</option>'
                );
                $packageSelect.prop("disabled", true);
                $packagesGroup.addClass("disabled-select");
                $packageSelect.show();

                if (selectedProvider && billerCode) {
                    // Show loader before fetching
                    $loader.show();
                    const getCableItemcode = "cable_tv_items";
                    // AJAX call to fetch cable packages
                    $.ajax({
                        url: "/get-cable-packages",
                        method: "POST",
                        data: {
                            billercode: billerCode,
                            service: currentService,
                            type: getCableItemcode,
                        },
                        beforeSend: function () {
                            $loader.show();
                        },
                        success: function (response) {
                            $packageSelect.empty();

                            if (
                                response.status === "success" &&
                                response.data &&
                                response.data.length > 0
                            ) {
                                $packageSelect.append(
                                    '<option disabled selected value="">Select a package</option>'
                                );

                                // Nigeria-specific package filtering
                                const excludedPackages = [
                                    "Compact + Asia",
                                    "Compact + French Touch",
                                    "Compact + French Touch + Xtraview",
                                    "Compact + Asia + Xtraview",
                                    "Compact + French Plus",
                                    "DStv French Touch Add-on Bouquet E36",
                                    "DStv Asian Add-on Bouquet E36",
                                    "DStv French Plus Add-on Bouquet E36",
                                    "Dstv Great Wall standalone Bouquet",
                                    "French 11 Bouquet E36",
                                    "French 11",
                                    "Premium + French",
                                    "Premium + French + Xtraview",
                                    "Premium + French Touch + HD/ExtraView",
                                    "PremiumFrench + Showmax",
                                    "Premium Asia + HD/ExtraView",
                                    "Asian + HD/ExtraView",
                                    "Asian + Showmax",
                                    "Great Wall Standalone Bouquet E36 + Showmax",
                                    "PremiumAsia + Xtraview",
                                ];

                                const filteredData = response.data.filter(
                                    (item) =>
                                        !excludedPackages.some(
                                            (excluded) =>
                                                excluded.toLowerCase() ===
                                                item.name.toLowerCase()
                                        )
                                );

                                if (filteredData.length === 0) {
                                    $packageSelect.append(
                                        '<option disabled selected value="">No packages found</option>'
                                    );
                                    $packageSelect.prop("disabled", true);
                                    $packagesGroup.addClass("disabled-select");
                                    $.elegantToastr.info(
                                        "Info",
                                        `No relevant packages found for ${selectedProvider.toUpperCase()}.`
                                    );
                                    return;
                                }

                                filteredData.forEach(function (item) {
                                    $packageSelect.append(
                                        $("<option></option>")
                                            .val(item.item_code)
                                            .text(
                                                item.name +
                                                    " - ₦" +
                                                    item.amount.toLocaleString()
                                            )
                                            .attr(
                                                "data-billercode",
                                                item.biller_code
                                            )
                                            .attr(
                                                "data-itemcode",
                                                item.item_code
                                            )
                                            .attr("data-packagename", item.name)
                                    );
                                });

                                // Enable the select and remove disabled styling
                                $packageSelect.prop("disabled", false);
                                $packagesGroup.removeClass("disabled-select");
                            } else {
                                $packageSelect.append(
                                    '<option disabled selected value="">No packages found</option>'
                                );
                                $packageSelect.prop("disabled", true);
                                $packagesGroup.addClass("disabled-select");

                                $.elegantToastr.info(
                                    "Info",
                                    `No packages found for ${selectedProvider.toUpperCase()}.`
                                );
                            }
                        },
                        error: function (xhr) {
                            $packageSelect.empty();
                            $packageSelect.append(
                                '<option disabled selected value="">Failed to load packages</option>'
                            );
                            $packageSelect.prop("disabled", true);
                            $packagesGroup.addClass("disabled-select");

                            let errorMessage = "Failed to load packages.";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            $.elegantToastr.error("Error", errorMessage);
                        },
                        complete: function () {
                            $loader.hide();
                        },
                    });
                } else {
                    // If no provider is selected
                    $loader.hide();
                    $packageSelect.empty();
                    $packageSelect.append(
                        '<option disabled selected value="">Select a provider first</option>'
                    );
                    $packageSelect.prop("disabled", true);
                    $packagesGroup.addClass("disabled-select");
                    $packageSelect.show();
                }
            });

            // Add change event listener to the Package select for smart card verification
            $packageSelect.on("change", function () {
                clearTimeout(verificationTimeout);
                verificationTimeout = setTimeout(() => {
                    const selectedPackage = $(this).val(); // item_code, e.g., CB177
                    const smartCard = $smartCardInput.val(); // e.g., 8252678520
                    const itemCode = $(this)
                        .find("option:selected")
                        .data("itemcode"); // e.g., CB177
                    const packageName = $(this)
                        .find("option:selected")
                        .data("packagename"); // e.g., DSTV COMPACT

                    // Validate inputs
                    if (!smartCard || !selectedPackage || !itemCode) {
                        $.elegantToastr.error(
                            "Warning!",
                            "Please enter a smart card number and select a package."
                        );
                        return;
                    }

                    // Validate smart card format (numeric)
                    if (!/^[0-9]+$/.test(smartCard)) {
                        $.elegantToastr.error(
                            "Invalid Smart Card",
                            "Smart card number must contain only digits."
                        );
                        return;
                    }

                    // Show loading SweetAlert
                    Swal.fire({
                        title: "Verifying Smart Card...",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });

                    // AJAX call to verify smart card
                    $.ajax({
                        url: "/verify-cable",
                        method: "POST",
                        data: {
                            smart_card: smartCard,
                            item_code: itemCode,
                            package_name: packageName, // Optional, for display purposes
                            _token: $('meta[name="csrf-token"]').attr(
                                "content"
                            ),
                        },
                        success: function (response) {
                            Swal.close();
                            if (
                                response.status === "success" &&
                                response.data
                            ) {
                                isSmartCardVerified = true;
                                Swal.fire({
                                    icon: "success",
                                    title: "Customer Found",
                                    html: `
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <p style="flex-direction: row;display: flex;justify-content: space-between;padding: 0 20px;"><strong>Customer Name:</strong><span style="margin-left: 10px;">${response.data.customer_name || "Not provided"}</span></p>
                                        <p style="flex-direction: row;display: flex;justify-content: space-between;padding: 0 20px;"><strong>Smart Card:</strong><span style="margin-left: 10px;">${response.data.smart_card}</span></p>
                                        <p style="flex-direction: row;display: flex;justify-content: space-between;padding: 0 20px;"><strong>Package:</strong><span style="margin-left: 10px;">${packageName}</span></p>
                                        <p style="flex-direction: row;display: flex;justify-content: space-between;padding: 0 20px;"><strong>Status:</strong><span style="margin-left: 10px;">${response.data.status || "Unknown"}</span></p>
                                    </div>
                                    `,
                                });
                            } else {
                                isSmartCardVerified = false;
                                Swal.fire({
                                    icon: "error",
                                    title: "Verification Failed",
                                    text: "Could not verify Smart Card Number, please verify Smart Card Number.",
                                });
                            }
                        },
                        error: function (xhr) {
                            Swal.close();
                            isSmartCardVerified = false;
                            let errorMessage = "Could not verify Smart Card Number, please verify Smart Card Number.";
                            Swal.fire({
                                icon: "error",
                                title: "Verification Failed", // 👈 Change title here
                                text: errorMessage,
                            });
                        },
                    });
                }, 300);
            });

            // Initial state setup when modal opens
            if (!$providerSelect.val()) {
                $packageSelect.empty();
                $packageSelect.append(
                    '<option disabled selected value="">Select a provider first</option>'
                );
                $packageSelect.prop("disabled", true);
                $packagesGroup.addClass("disabled-select");
                $loader.hide();
                $packageSelect.show();
            }
        }
        // --- Add other service-specific form interactions here if needed ---
        // ...
    }

    // Closes the service modal
    // Removes active class to trigger closing animation
    function closeServiceModal() {
        $("#serviceModal").removeClass("active");
        // Clear all inputs and selects inside the modal
        $("#modalContent")
            .find("input, select, textarea")
            .val("") // clears value
            .prop("selectedIndex", 0); // resets selects

        // ADDED: Remove the change event listeners specifically added to the modal content
        // This helps prevent duplicate listeners if the modal is opened/closed many times.
        $("#modalContent").off("change");

        // Note: We don't necessarily need to trigger change here on closing,
        // as setupFormInteractions will configure the state correctly when the modal re-opens.
        // .trigger("change"); // triggers change event if needed
    }

    //verify meter number
    $(document).on("blur", "#meter_number_input", function () {
        const meterNumber = $(this).val();
        const meterType = $("#meter_type").val();
        const disco = $("#meter_disco option:selected");
        const billerCode = disco.data("billercode");

        console.log(
            "meter number:" + meterNumber,
            "meter type:" + meterType,
            "billerCode" + billerCode
        );

        if (meterNumber && meterType && billerCode) {
            Swal.fire({
                title: "Verifying Meter...",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            $.ajax({
                url: "/verify-meter",
                method: "POST",
                data: {
                    meter_number: meterNumber,
                    meter_type: meterType,
                    biller_code: billerCode,
                    _token: $("meta[name='csrf-token']").attr("content"),
                },
                success: function (response) {
                    Swal.close();
                    if (response.status === "success") {
                        Swal.fire({
                            icon: "success",
                            title: "Customer Found",
                            text: response.data.customer_name,
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Verification Failed",
                            text: response.message,
                        });
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Could not verify meter number.",
                    });
                },
            });
        }
    });

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
        const tx_ref = generateTransactionCode(); //generte transaction code
        const customerName = $('#getUserName').text();
        const customerEmail = $('#getUserEmail').text();
        // Depending on the service, validate and collect form data
        switch (service) {
            case "airtime":
                // ... (Airtime logic remains mostly the same, refined error handling/balance check)
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

                // const balanceText = $("#userBalance").text();
                // const cleanedText = balanceText.replace(/[₦,]/g, "");
                // const userBalance = parseFloat(cleanedText);

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
                if (parseFloat(formData.amount) <= 0) {
                    $.elegantToastr.error(
                        "Invalid Amount",
                        "Please enter a valid amount."
                    );
                    return;
                }
                // if (parseFloat(formData.amount) > userBalance) {
                //     $.elegantToastr.error(
                //         "Insufficient Balance",
                //         "Your balance is insufficient for this recharge."
                //     );
                //     return;
                // }

                Swal.fire({
                    title: "Confirm Airtime Purchase",
                    html: `Are you sure you want to purchase ₦${formData.amount} airtime for ${formData.phoneNumber}`,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Yes, continue",
                    cancelButtonText: "Cancel",
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Processing...",
                            text: "Loading payment page",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });
                        formData.service = "airtime";
                        formData.tx_ref = tx_ref;
                        const flutterwaveModal = FlutterwaveCheckout({
                            //FLWPUBK-6ecd4d6df722528e0cf41c5fad8552bb-X
                            public_key: "FLWPUBK-6ecd4d6df722528e0cf41c5fad8552bb-X", // Your public key
                            tx_ref: formData.tx_ref, // Use the tx_ref from backend
                            amount: formData.amount, // Use the amount from backend response (or validated amount)
                            currency: "NGN",
                            payment_options: "card,banktransfer,ussd",
                            customer: {
                                "name": customerName,
                                "email": customerEmail,
                            }, // Use customer details from backend response
                            customizations: {
                                title: 'The Titanic Store',
                                description: 'Payment for an awesome cruise',
                                logo: 'https://www.logolynx.com/images/logolynx/22/2239ca38f5505fbfce7e55bbc0604386.jpeg',
                            }, // Keep your customizations
                            // redirect_url: window.location.origin + "/verify-payment", // Keep the redirect URL
                            // --- Step 3: Handle Flutterwave Callbacks (Client-Side) ---
                            // Note: The main verification happens on the backend via redirect_url.
                            // This callback is mostly for client-side UI updates or logging.
                            callback: function (response) {
                                console.log("Flutterwave client-side callback:", response);
                                if (response.status === "completed" && response.charge_response_message === "Approved Successful") {
                                    Swal.fire({
                                        title: "Verifying Payment...",
                                        text: "Please wait while we confirm your payment.",
                                        allowOutsideClick: false,
                                        didOpen: () => {
                                            Swal.showLoading();
                                        },
                                    });

                                    // Verify payment server-side to prevent redirection
                                    $.ajax({
                                        url: "/verify-payment-inline", // New endpoint
                                        method: "POST",
                                        data: {
                                            tx_ref: response.tx_ref,
                                            transaction_id: response.transaction_id,
                                            service: "airtime",
                                            phoneNumber: formData.phoneNumber,
                                            network: formData.network,
                                            amount: formData.amount,
                                        },
                                        success: function (verifyResponse) {
                                            console.log('response on successful download:',verifyResponse);
                                            Swal.close(); // Close any previous loading Swal if it's still open

                                            // ✅ Show success message
                                            Swal.fire({
                                                title: "Airtime Purchase Successful!",
                                                html: `₦${formData.amount} airtime for <b>${formData.phoneNumber}</b> was successful.`,
                                                icon: "success",
                                                confirmButtonText: "OK",
                                            });

                                            // ✅ Optionally close the custom modal if open
                                            closeServiceModal();
                                        },
                                        error: function (xhr) {
                                            Swal.close();
                                            let errorMessage = "Error verifying payment. Please contact support.";
                                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                                errorMessage = xhr.responseJSON.message;
                                            }
                                            Swal.fire({
                                                title: "Error!",
                                                text: errorMessage,
                                                icon: "error",
                                            });
                                            $("#proceedServiceModalBtn").prop("disabled", false);
                                        },
                                    });
                                } else {
                                    Swal.close();
                                    $.elegantToastr.error("Payment Failed", "Your payment was not successful. Please try again.");
                                    $("#proceedServiceModalBtn").prop("disabled", false);
                                }
                            },
                            onclose: function () {
                                console.log('Flutterwave modal closed');
                                flutterwaveModal.close();
                                Swal.close();
                            },
                        }); 
                    }
                });
                break;

            case "data":
                // MODIFIED: Data collection and validation for submission
                // The dynamic behavior (enabling/disabling select, populating options)
                // was moved to the setupFormInteractions function.
                // This block now only focuses on collecting final values and submitting.
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
                    .val(); // Get the selected data plan value

                // Validate form data BEFORE submitting
                if (
                    !formData.phoneNumber ||
                    !formData.network ||
                    !formData.dataPlan // Ensure a data plan is actually selected
                ) {
                    $.elegantToastr.error(
                        "Warning!",
                        "Please fill all inputs..."
                    );
                    return;
                }

                // ADDED: Extract amount from the selected plan text for balance check
                const selectedPlanText = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .find("option:selected")
                    .text();
                const amountMatch = selectedPlanText.match(/₦([\d,]+)/); // Use regex to find the price
                formData.amount = amountMatch
                    ? parseFloat(amountMatch[1].replace(/,/g, ""))
                    : 0; // Store amount
                const selectedBillerCode = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .find("option:selected")
                    .data("billercode");
                const selectedItemCode = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .find("option:selected")
                    .data("itemcode");
                const selectedDataName = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .find("option:selected")
                    .data("dataplan");
                formData.biller_code = selectedBillerCode;
                formData.item_code = selectedItemCode;
                // const balanceTextData = $("#userBalance").text();
                // const cleanedTextData = balanceTextData.replace(/[₦,]/g, "");
                // const userBalanceData = parseFloat(cleanedTextData);
                const match = selectedPlanText.match(
                    /^(.+?\d+(?:\.\d+)?\s*(?:GB|MB))/i
                );
                const planShortName = match ? match[1] : selectedPlanText;
                formData.shortplan = planShortName;
                // ADDED: Validate parsed amount and check balance
                if (formData.amount <= 0) {
                    $.elegantToastr.error(
                        "Invalid Plan",
                        "Could not determine plan amount or amount is invalid."
                    );
                    return;
                }
                if (formData.amount > userBalanceData) {
                    $.elegantToastr.error(
                        "Insufficient Balance",
                        "Your balance is insufficient for this data plan."
                    );
                    return;
                }

                // --- AJAX submission for data would go here, similar to Airtime ---
                Swal.fire({
                    title: "Confirm Data Purchase",
                    html: `Purchase ${selectedPlanText} for ${formData.phoneNumber}`,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Yes, continue",
                    cancelButtonText: "Cancel",
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while we subscribe your line.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });
                        formData.service = "data"; // Add service type for backend
                        formData.planname = selectedDataName; // Add service type for backend

                        $.ajax({
                            url: "/process-service", // Replace with your actual endpoint
                            method: "POST",
                            data: formData,
                            success: function (response) {
                                /* ... success handling ... */
                                if (response && response.status === "success") {
                                    Swal.fire({
                                        title: "Success!",
                                        text: response.message || "...",
                                        icon: "success",
                                    });
                                    if (
                                        response.new_balance !== undefined &&
                                        $("#userBalance").length
                                    ) {
                                        $("#userBalance").text(
                                            `₦${Number(
                                                response.new_balance
                                            ).toLocaleString()}`
                                        );
                                    }
                                    closeServiceModal();
                                } else {
                                    Swal.fire({
                                        title: "Failed!",
                                        text: response.message || "...",
                                        icon: "error",
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                /* ... error handling ... */
                                console.error(
                                    "AJAX Error:",
                                    status,
                                    error,
                                    xhr.responseText
                                );
                                let errorMessage =
                                    "Something went wrong. Please try again.";
                                if (
                                    xhr.responseJSON &&
                                    xhr.responseJSON.message
                                ) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.responseText) {
                                    errorMessage =
                                        "Error: " +
                                        xhr.responseText.substring(0, 100) +
                                        "...";
                                }
                                Swal.fire({
                                    title: "Error!",
                                    text: errorMessage,
                                    icon: "error",
                                });
                            },
                        });
                    }
                });

                break;

            case "cable":
                // Collect form data
                formData.smartCard = $("#modalContent")
                    .find('input[type="text"]')
                    .eq(0)
                    .val();
                formData.provider = $("#modalContent")
                    .find("select")
                    .eq(0)
                    .val();
                formData.package = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .val();
                formData.billerCode = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .find("option:selected")
                    .data("billercode");
                formData.itemCode = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .find("option:selected")
                    .data("itemcode");
                formData.packageName = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .find("option:selected")
                    .data("packagename");

                // Extract amount
                const cableSelectedPackageText = $("#modalContent")
                    .find("select")
                    .eq(1)
                    .find("option:selected")
                    .text();
                const cableAmountMatch =
                    cableSelectedPackageText.match(/₦([\d,]+)/);
                formData.amount = cableAmountMatch
                    ? parseFloat(cableAmountMatch[1].replace(/,/g, ""))
                    : 0;

                // Check if type is getCableItemcode
                formData.type = $("#modalContent").find("select").eq(1).val()
                    ? ""
                    : "getCableItemcode";

                // Validate inputs
                if (
                    !formData.smartCard ||
                    !formData.provider ||
                    !formData.billerCode ||
                    !formData.itemCode ||
                    !formData.packageName ||
                    formData.amount <= 0
                ) {
                    $.elegantToastr.error(
                        "Warning!",
                        "Please fill all inputs and select a valid package."
                    );
                    return;
                }

                // Validate smart card format
                if (!/^[0-9]+$/.test(formData.smartCard) || !isSmartCardVerified) {
                    $.elegantToastr.error(
                        "Invalid Smart Card",
                        "Invalid smart card number."
                    );
                    return;
                }

                // Check user balance
                const cableBalanceText = $("#userBalance").text();
                const cableCleanedText = cableBalanceText.replace(/[₦,]/g, "");
                const cableUserBalance = parseFloat(cableCleanedText);

                if (formData.amount > cableUserBalance) {
                    $.elegantToastr.error(
                        "Insufficient Balance",
                        "Your balance is insufficient for this subscription."
                    );
                    return;
                }

                // If type is getCableItemcode, fetch packages
                if (formData.type === "getCableItemcode") {
                    const $modalContent = $("#modalContent");
                    const $packageSelect = $modalContent.find("select").eq(1);
                    const $packagesGroup = $("#cable-packages");
                    const $loader = $("#cable-packages-loader");

                    $loader.show();
                    $packageSelect.empty();
                    $packageSelect.append(
                        '<option disabled selected value="">Loading packages...</option>'
                    );
                    $packageSelect.prop("disabled", true);
                    $packagesGroup.addClass("disabled-select");

                    $.ajax({
                        url: "/get-cable-packages",
                        method: "POST",
                        data: {
                            billercode: formData.billerCode,
                            service: "cable",
                            type: "getCableItemcode",
                        },
                        beforeSend: function () {
                            $loader.show();
                        },
                        success: function (response) {
                            $packageSelect.empty();
                            if (
                                response.status === "success" &&
                                response.data &&
                                response.data.length > 0
                            ) {
                                $packageSelect.append(
                                    '<option disabled selected value="">Select a package</option>'
                                );
                                // Nigeria-specific filtering
                                const excludedPackages = [
                                    "Compact + Asia",
                                    "Compact + French Touch",
                                    "Compact + French Touch + Xtraview",
                                    "Compact + Asia + Xtraview",
                                    "Compact + French Plus",
                                    "DStv French Touch Add-on Bouquet E36",
                                    "DStv Asian Add-on Bouquet E36",
                                    "DStv French Plus Add-on Bouquet E36",
                                    "Dstv Great Wall standalone Bouquet",
                                    "French 11 Bouquet E36",
                                    "French 11",
                                    "Premium + French",
                                    "Premium + French + Xtraview",
                                    "Premium + French Touch + HD/ExtraView",
                                    "PremiumFrench + Showmax",
                                    "Premium Asia + HD/ExtraView",
                                    "Asian + HD/ExtraView",
                                    "Asian + Showmax",
                                    "Great Wall Standalone Bouquet E36 + Showmax",
                                    "PremiumAsia + Xtraview",
                                ];
                                const filteredData = response.data.filter(
                                    (item) =>
                                        !excludedPackages.some(
                                            (excluded) =>
                                                excluded.toLowerCase() ===
                                                item.name.toLowerCase()
                                        )
                                );

                                if (filteredData.length === 0) {
                                    $packageSelect.append(
                                        '<option disabled selected value="">No packages found</option>'
                                    );
                                    $packageSelect.prop("disabled", true);
                                    $packagesGroup.addClass("disabled-select");
                                    $.elegantToastr.info(
                                        "Info",
                                        `No relevant packages found for ${formData.provider.toUpperCase()}.`
                                    );
                                    return;
                                }

                                filteredData.forEach(function (item) {
                                    $packageSelect.append(
                                        $("<option></option>")
                                            .val(item.item_code)
                                            .text(
                                                item.name +
                                                    " - ₦" +
                                                    item.amount.toLocaleString()
                                            )
                                            .attr(
                                                "data-billercode",
                                                item.biller_code
                                            )
                                            .attr(
                                                "data-itemcode",
                                                item.item_code
                                            )
                                            .attr("data-packagename", item.name)
                                    );
                                });
                                $packageSelect.prop("disabled", false);
                                $packagesGroup.removeClass("disabled-select");
                            } else {
                                $packageSelect.append(
                                    '<option disabled selected value="">No packages found</option>'
                                );
                                $packageSelect.prop("disabled", true);
                                $packagesGroup.addClass("disabled-select");
                                $.elegantToastr.info(
                                    "Info",
                                    `No packages found for ${formData.provider.toUpperCase()}.`
                                );
                            }
                        },
                        error: function (xhr) {
                            $packageSelect.empty();
                            $packageSelect.append(
                                '<option disabled selected value="">Failed to load packages</option>'
                            );
                            $packageSelect.prop("disabled", true);
                            $packagesGroup.addClass("disabled-select");
                            let errorMessage = "Failed to load packages.";
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            $.elegantToastr.error("Error", errorMessage);
                        },
                        complete: function () {
                            $loader.hide();
                        },
                    });
                    return; // Stop further processing
                }

                // Proceed with payment
                Swal.fire({
                    title: "Confirm Cable Subscription",
                    html: `Subscribe to ${
                        formData.packageName
                    } for Smart Card ${
                        formData.smartCard
                    }? Amount: ₦${formData.amount.toLocaleString()}`,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Yes, continue",
                    cancelButtonText: "Cancel",
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while we process your subscription.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        });

                        formData.service = "cable";

                        $.ajax({
                            url: "/process-service",
                            method: "POST",
                            data: formData,
                            success: function (response) {
                                if (response && response.status === "success") {
                                    Swal.fire({
                                        title: "Success!",
                                        text:
                                            response.message ||
                                            "Cable subscription successful!",
                                        icon: "success",
                                    });
                                    if (
                                        response.new_balance !== undefined &&
                                        $("#userBalance").length
                                    ) {
                                        $("#userBalance").text(
                                            `₦${Number(
                                                response.new_balance
                                            ).toLocaleString()}`
                                        );
                                    }
                                    closeServiceModal();
                                } else {
                                    Swal.fire({
                                        title: "Failed!",
                                        text:
                                            response.message ||
                                            "Cable subscription failed.",
                                        icon: "error",
                                    });
                                }
                            },
                            error: function (xhr) {
                                let errorMessage =
                                    "Something went wrong. Please try again.";
                                if (
                                    xhr.responseJSON &&
                                    xhr.responseJSON.message
                                ) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                Swal.fire({
                                    title: "Error!",
                                    text: errorMessage,
                                    icon: "error",
                                });
                            },
                        });
                    }
                });
                break;
            case "electricity":
                // ... (Data collection, validation, and AJAX structure similar to Airtime/Data)
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
                    .eq(0)
                    .val(); // Correct index
                formData.amount = $("#modalContent")
                    .find('input[type="number"]')
                    .val();
                if (
                    !formData.company ||
                    !formData.meterType ||
                    !formData.meterNumber ||
                    !formData.amount ||
                    parseFloat(formData.amount) <= 0
                ) {
                    $.elegantToastr.error(
                        "Warning!",
                        "Please fill all inputs and provide a valid amount..."
                    );
                    return;
                }
                // Add balance check here
                // alert("Processing Electricity Bill Payment...\n" + JSON.stringify(formData)); // Replace with actual AJAX
                // Implement SweetAlert confirmation and AJAX call
                break;

            case "internet":
                // ... (Data collection, validation, and AJAX structure similar to Airtime/Data)
                formData.provider = $("#modalContent")
                    .find("select")
                    .eq(0)
                    .val();
                formData.customerId = $("#modalContent")
                    .find('input[type="text"]')
                    .eq(0)
                    .val(); // Correct index
                formData.plan = $("#modalContent").find("select").eq(1).val();
                if (
                    !formData.provider ||
                    !formData.customerId ||
                    !formData.plan
                ) {
                    $.elegantToastr.error(
                        "Warning!",
                        "Please fill all inputs..."
                    );
                    return;
                }
                // Add amount validation and balance check here
                // alert("Processing Internet Subscription...\n" + JSON.stringify(formData)); // Replace with actual AJAX
                // Implement SweetAlert confirmation and AJAX call
                break;

            case "bills":
                // ... (Data collection, validation, and AJAX structure similar to Airtime/Data)
                formData.billType = $("#modalContent")
                    .find("select")
                    .eq(0)
                    .val();
                formData.biller = $("#modalContent")
                    .find('input[type="text"]')
                    .eq(0)
                    .val(); // Correct index
                formData.customerId = $("#modalContent")
                    .find('input[type="text"]')
                    .eq(1)
                    .val(); // Correct index
                formData.reference = $("#modalContent")
                    .find('input[type="text"]')
                    .eq(2)
                    .val(); // Correct index
                formData.amount = $("#modalContent")
                    .find('input[type="number"]')
                    .val();
                if (
                    !formData.billType ||
                    !formData.biller ||
                    !formData.customerId ||
                    !formData.reference ||
                    !formData.amount ||
                    parseFloat(formData.amount) <= 0
                ) {
                    $.elegantToastr.error(
                        "Warning!",
                        "Please fill all inputs and provide a valid amount..."
                    );
                    return;
                }
                // Add balance check here
                // alert("Processing Bill Payment...\n" + JSON.stringify(formData)); // Replace with actual AJAX
                // Implement SweetAlert confirmation and AJAX call
                break;

            default:
                $.elegantToastr.error("Error!", "Service not supported.");
                break;
        }
    }

    // Event listener for proceed button
    $("#proceedServiceModalBtn").on("click", function () {
        // Validate form inputs based on the selected service
        if (currentService) {
            // Call the function based on the service type
            processServiceForm(currentService);
        } else {
            $.elegantToastr.error("Error!", "No service selected.");
        }
    });
}); // End of document ready
