$(document).ready(function() {
    // Get references to the modal elements
    const modalOverlay = $(".wallet-modal-overlay");
    const modalCard = $(".wallet-modal");
    const closeModalButton = $("#closeModal");
    const cancelButton = $("#cancelBtn");

    // Function to open the modal
    function openModal() {
        modalOverlay.toggleClass('hide'); // Fade in the overlay
    }
  
    // Event listener to open the modal (you'll need to trigger this based on your application logic)
    // For example, if you have a button with the ID 'openWalletModal':
    // $("#openWalletModal").on("click", openModal);
  
    // Event listeners to close the modal
    // closeModalButton.on("click", closeModalFunc);
    // cancelButton.on("click", closeModalFunc);
  
    // $('#addmoney').click(function (e) { 
    //     e.preventDefault();
    //     openModal();
    // });
  });