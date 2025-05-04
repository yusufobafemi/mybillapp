document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordField = this.previousElementSibling;
            
            // Toggle the password field type
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Toggle the eye icon
            const eyeIcon = this.querySelector('i');
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });
    });
    
    // Password strength checker
    const passwordInput = document.querySelector('#password');
    const progressBar = document.querySelector('.password-strength .progress-bar');
    const passwordFeedback = document.querySelector('.password-feedback');
    
    if (passwordInput && progressBar && passwordFeedback) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = '';
            
            // Check password length
            if (password.length >= 8) {
                strength += 25;
            }
            
            // Check for lowercase and uppercase letters
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
                strength += 25;
            }
            
            // Check for numbers
            if (password.match(/\d/)) {
                strength += 25;
            }
            
            // Check for special characters
            if (password.match(/[^a-zA-Z0-9]/)) {
                strength += 25;
            }
            
            // Update progress bar
            progressBar.style.width = strength + '%';
            progressBar.setAttribute('aria-valuenow', strength);
            
            // Remove existing classes
            progressBar.classList.remove('weak', 'medium', 'strong');
            
            // Add appropriate class and feedback
            if (strength < 25) {
                feedback = 'Password strength: Too weak';
                passwordFeedback.style.color = 'var(--danger)';
            } else if (strength < 50) {
                progressBar.classList.add('weak');
                feedback = 'Password strength: Weak';
                passwordFeedback.style.color = 'var(--danger)';
            } else if (strength < 75) {
                progressBar.classList.add('medium');
                feedback = 'Password strength: Medium';
                passwordFeedback.style.color = 'var(--warning)';
            } else {
                progressBar.classList.add('strong');
                feedback = 'Password strength: Strong';
                passwordFeedback.style.color = 'var(--success)';
            }
            
            passwordFeedback.textContent = feedback;
        });
    }
    
    // Form validation
    const registerForm = document.querySelector('.register-form');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const firstName = document.querySelector('#firstName').value;
            const lastName = document.querySelector('#lastName').value;
            const email = document.querySelector('#email').value;
            const phone = document.querySelector('#phone').value;
            const password = document.querySelector('#password').value;
            const confirmPassword = document.querySelector('#confirmPassword').value;
            const termsCheck = document.querySelector('#termsCheck').checked;
            
            // Simple validation
            if (!firstName || !lastName || !email || !phone || !password || !confirmPassword) {
                showAlert('Please fill in all fields', 'danger');
                return;
            }
            
            // Email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showAlert('Please enter a valid email address', 'danger');
                return;
            }
            
            // Phone format validation (simple check)
            const phoneRegex = /^\d{10,15}$/;
            if (!phoneRegex.test(phone.replace(/[^0-9]/g, ''))) {
                showAlert('Please enter a valid phone number', 'danger');
                return;
            }
            
            // Password strength validation
            if (password.length < 8) {
                showAlert('Password must be at least 8 characters long', 'danger');
                return;
            }
            
            // Password match validation
            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'danger');
                return;
            }
            
            // Terms check validation
            if (!termsCheck) {
                showAlert('You must agree to the Terms of Service and Privacy Policy', 'danger');
                return;
            }
            
            // If validation passes, you would normally submit the form
            // For demo purposes, show success message
            showAlert('Registration successful! Redirecting to login...', 'success');
            
            // Simulate redirect after registration
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        });
    }
    
    // Function to show alert messages
    function showAlert(message, type) {
        // Remove any existing alerts
        const existingAlert = document.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insert alert before the form
        const form = document.querySelector('.register-form');
        form.parentNode.insertBefore(alertDiv, form);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
    
    // Google Sign Up button functionality
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
$(document).ready(function () {
    $("#registerForm").submit(function (e) {
        e.preventDefault(); // stop normal form submission

        // Get password and confirm password values
        let password = $("#password").val();
        let confirmPassword = $("#confirmPassword").val();

        // Check if passwords match
        if (password !== confirmPassword) {
            $.elegantToastr.error(
                "Validation Error",
                "Passwords do not match."
            );
            return; // stop submitting if passwords don't match
        }

        let submitButton = $(this).find('button[type="submit"]');
        submitButton.prop("disabled", true); // disable button
        submitButton.html(
            '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span> Creating...'
        );

        let formData = $(this).serialize(); // serialize all form fields including CSRF

        $.ajax({
            url: routes.register, // very important!
            method: "POST",
            data: formData,
            success: function (response) {
                $.elegantToastr.success(
                    "Success!",
                    "Account created successfully!"
                );
                // Redirect to dashboard or wherever
                window.location.href = response.redirect_to;
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessages = "";
                    $.each(errors, function (key, value) {
                        errorMessages += value[0] + "\n";
                    });
                    $.elegantToastr.error("Validation Error", errorMessages);
                } else {
                    $.elegantToastr.error(
                        "Error",
                        "Something went wrong, try again."
                    );
                }
            },
            complete: function () {
                // Always re-enable button
                submitButton.prop("disabled", false);
                submitButton.html("Create Account");
            },
        });
    });
    // Live password match checker
    $("#password, #confirmPassword").on("input", function () {
        let password = $("#password").val();
        let confirmPassword = $("#confirmPassword").val();

        if (confirmPassword.length > 0) {
            // only show if user has started typing confirm password
            if (password === confirmPassword) {
                $("#confirmPassword")
                    .removeClass("is-invalid")
                    .addClass("is-valid");
            } else {
                $("#confirmPassword")
                    .removeClass("is-valid")
                    .addClass("is-invalid");
            }
        } else {
            $("#confirmPassword").removeClass("is-valid is-invalid");
        }
    });
});
