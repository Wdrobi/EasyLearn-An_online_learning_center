// Theme Toggle
const themeToggle = document.querySelector('.theme-toggle');
const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
}

// Initialize theme
const savedTheme = localStorage.getItem('theme');
if (savedTheme) {
    setTheme(savedTheme);
} else {
    setTheme(prefersDarkScheme.matches ? 'dark' : 'light');
}

if (themeToggle) {
    themeToggle.addEventListener('click', toggleTheme);
}

// Form Validation
function validatePassword(password) {
    const minLength = 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

    return {
        isValid: password.length >= minLength && hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChar,
        errors: {
            length: password.length < minLength,
            upperCase: !hasUpperCase,
            lowerCase: !hasLowerCase,
            numbers: !hasNumbers,
            specialChar: !hasSpecialChar
        }
    };
}

// Password Strength Meter
function updatePasswordStrength(password) {
    const strengthMeter = document.querySelector('.password-strength');
    if (!strengthMeter) return;

    const validation = validatePassword(password);
    let strength = 0;
    
    if (password.length >= 8) strength += 25;
    if (validation.errors.upperCase === false) strength += 25;
    if (validation.errors.numbers === false) strength += 25;
    if (validation.errors.specialChar === false) strength += 25;

    strengthMeter.style.width = `${strength}%`;
    
    if (strength <= 25) {
        strengthMeter.style.backgroundColor = 'var(--error-color)';
    } else if (strength <= 50) {
        strengthMeter.style.backgroundColor = 'var(--warning-color)';
    } else {
        strengthMeter.style.backgroundColor = 'var(--success-color)';
    }
}

// Show/Hide Password Toggle
function togglePasswordVisibility(inputId, toggleId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = document.getElementById(toggleId);
    
    if (passwordInput && toggleButton) {
        toggleButton.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            toggleButton.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
        });
    }
}

// Form Toggle (Login/Register)
function toggleForms() {
    const loginForm = document.querySelector('.login-form');
    const registerForm = document.querySelector('.register-form');
    const toggleButton = document.querySelector('.form-toggle');
    
    if (loginForm && registerForm && toggleButton) {
        toggleButton.addEventListener('click', () => {
            loginForm.classList.toggle('hidden');
            registerForm.classList.toggle('hidden');
            toggleButton.textContent = loginForm.classList.contains('hidden') ? 
                'Already have an account? Login' : 
                'Need an account? Register';
        });
    }
}

// Progress Bar Animation
function animateProgressBar(element, targetProgress) {
    if (!element) return;
    
    const progressBar = element.querySelector('.progress-bar-fill');
    if (!progressBar) return;

    progressBar.style.width = '0%';
    setTimeout(() => {
        progressBar.style.width = `${targetProgress}%`;
    }, 100);
}

// Course Card Hover Effects
function initializeCourseCards() {
    const cards = document.querySelectorAll('.course-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
}

// Mobile Navigation Toggle
function initializeMobileNav() {
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileNavToggle && navLinks) {
        mobileNavToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileNavToggle.classList.toggle('active');
        });
    }
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    toggleForms();
    initializeCourseCards();
    initializeMobileNav();
    
    // Initialize password fields
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            updatePasswordStrength(e.target.value);
        });
    });
    
    // Initialize password visibility toggles
    togglePasswordVisibility('password', 'password-toggle');
    togglePasswordVisibility('confirm-password', 'confirm-password-toggle');
}); 