/**
 * Login Form Validation and Enhancement
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    // Auto-fill admin credentials on page load
    if (emailInput && passwordInput) {
        // Find admin card and auto-fill
        const adminCard = document.querySelector('.credential-card[data-email="admin@hospital.com"]');
        if (adminCard) {
            const adminEmail = adminCard.getAttribute('data-email');
            const adminPassword = adminCard.getAttribute('data-password');
            emailInput.value = adminEmail;
            passwordInput.value = adminPassword;
        }
    }
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Disable submit button to prevent double submission
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Logging in...';
            }
        });
        
        // Basic client-side validation
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const email = this.value.trim();
                if (email && !isValidEmail(email)) {
                    this.setCustomValidity('Please enter a valid email address');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                if (this.value.length > 0 && this.value.length < 8) {
                    this.setCustomValidity('Password must be at least 8 characters');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }
    
    // Handle credential card clicks
    const credentialCards = document.querySelectorAll('.credential-card');
    credentialCards.forEach(function(card) {
        card.addEventListener('click', function() {
            const email = this.getAttribute('data-email');
            const password = this.getAttribute('data-password');
            
            if (emailInput && passwordInput) {
                emailInput.value = email;
                passwordInput.value = password;
                
                // Add visual feedback
                card.style.borderColor = 'var(--primary-color)';
                card.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.25)';
                
                setTimeout(function() {
                    card.style.borderColor = '';
                    card.style.boxShadow = '';
                }, 500);
                
                // Focus on email field
                emailInput.focus();
            }
        });
    });
});

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

