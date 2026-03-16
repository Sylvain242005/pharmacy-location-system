// script.js - Main JavaScript Functions

// User authentication functions
async function signup(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('signup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message);
            window.location.href = 'login.html';
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Signup error:', error);
        alert('Registration failed. Please try again.');
    }
}

async function login(event) {
    event.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    try {
        const response = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Store user info
            localStorage.setItem('user', JSON.stringify(data.user));
            
            // Redirect based on role
            if (data.user.role === 'customer') {
                window.location.href = 'customer/dashboard.php';
            } else if (data.user.role === 'business_owner') {
                window.location.href = 'business/dashboard.php';
            } else if (data.user.role === 'admin') {
                window.location.href = 'admin/dashboard.php';
            }
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('Login failed. Please try again.');
    }
}

function logout() {
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

// Contact form
function sendMessage(event) {
    event.preventDefault();
    alert('Message sent successfully! We will contact you soon.');
    event.target.reset();
}

// Check if user is logged in
function checkAuth() {
    const user = localStorage.getItem('user');
    const currentPage = window.location.pathname.split('/').pop();
    
    // Pages that don't require authentication
    const publicPages = ['index.html', 'home.html', 'about.html', 'contact.html', 
                        'service.html', 'login.html', 'signup.php'];
    
    if (!user && !publicPages.includes(currentPage)) {
        window.location.href = 'login.html';
        return false;
    }
    
    return true;
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-CM', {
        style: 'currency',
        currency: 'XAF',
        minimumFractionDigits: 0
    }).format(amount);
}

// Show loading spinner
function showLoading(show = true) {
    let spinner = document.getElementById('loading-spinner');
    
    if (!spinner) {
        spinner = document.createElement('div');
        spinner.id = 'loading-spinner';
        spinner.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(spinner);
    }
    
    spinner.style.display = show ? 'flex' : 'none';
}

// Handle API errors
function handleApiError(error) {
    console.error('API Error:', error);
    alert('An error occurred. Please try again.');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
});