/**
 * Login Page JavaScript
 * Script untuk validasi form dan interaksi halaman login
 */

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const errorMessage = document.getElementById('error-message');
    
    if (!loginForm) return;
    
    // Form submit validation
    loginForm.addEventListener('submit', function(e) {
        const username = usernameInput.value.trim();
        const password = passwordInput.value;
        
        if (!username || !password) {
            e.preventDefault();
            if (errorMessage) {
                errorMessage.textContent = 'Mohon isi Username dan Password!';
                errorMessage.style.display = 'block';
            } else {
                alert('Mohon isi Username dan Password!');
            }
            return false;
        }
    });
    
    // Auto-focus on username if empty
    if (usernameInput && !usernameInput.value) {
        usernameInput.focus();
    }
    
    // Add visual feedback on input focus
    [usernameInput, passwordInput].forEach(input => {
        if (!input) return;
        
        input.addEventListener('focus', function() {
            this.style.borderColor = '#6BA3E3';
            // Hide error message when user starts typing
            if (errorMessage) {
                errorMessage.style.display = 'none';
            }
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.style.borderColor = '#E0E0E0';
            }
        });
    });
});

