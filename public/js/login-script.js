// this is for authentication
$(document).ready(function() {
    $('#loginForm').submit(function(e) {
        e.preventDefault(); // stop normal form submit
    
        let formData = $(this).serialize(); // serialize form data
        let loginButton = $('#loginButton');
        let loginButtonText = $('#loginButtonText');
        let loginButtonSpinner = $('#loginButtonSpinner');
    
        // Disable button and show spinner
        loginButton.prop('disabled', true);
        loginButtonText.addClass('d-none');
        loginButtonSpinner.removeClass('d-none');
        $.ajax({
            url: routes.login,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // CSRF token
            },
            success: function(response) {
                if (response.email_verified) {
                    $.elegantToastr.success('Success!', 'Login Successful!');
                    setTimeout(function () {
                        window.location.href = response.redirect_to;
                    }, 1000); // slight delay to let user see the toast
                } else {
                    $.elegantToastr.info('Info!', 'Verify email to login, Redirecting!');
                    setTimeout(function () {
                        window.location.href = routes.verify;
                    }, 1000);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessages = '';
                    $.each(errors, function(key, value) {
                        errorMessages += value[0] + '\n';
                    });
                    $.elegantToastr.error('Error!', 'Invalid login details');
                } else {
                    $.elegantToastr.error('Error!', 'Invalid Details or something went wrong.');
                }
            },
            complete: function() {
                // Always re-enable button and reset spinner
                loginButton.prop('disabled', false);
                loginButtonText.removeClass('d-none');
                loginButtonSpinner.addClass('d-none');
            }
        });
    });    
});

// this is to toggle view in form
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.querySelector('#password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            // Toggle the password field type
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle the eye icon
            const eyeIcon = this.querySelector('i');
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });
    }
    
    // Google Sign In button functionality
    const googleButton = document.querySelector('.btn-google');
    
    if (googleButton) {
        googleButton.addEventListener('click', function() {
            // In a real application, this would trigger Google OAuth
            // For demo purposes, show a message
            showAlert('Redirecting to Google authentication...', 'info');
            
            // Simulate redirect
            setTimeout(() => {
                showAlert('Google authentication successful!', 'success');
            }, 2000);
        });
    }
});
