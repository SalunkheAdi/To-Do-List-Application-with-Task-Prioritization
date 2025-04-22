document.addEventListener('DOMContentLoaded', function() {
    const API_URL = 'http://localhost/Todo-App-main/api';

    // Handle registration form submission
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
                const response = await fetch(`${API_URL}/register.php`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                console.log('Registration response:', data); // Debug log
                
                if (data.status === 'success') {
                    alert('Registration successful! Please login.');
                    window.location.href = 'login.html';
                } else {
                    alert(data.message || 'Registration failed. Please try again.');
                }
            } catch (error) {
                console.error('Registration error:', error);
                alert('An error occurred during registration. Please try again.');
            }
        });
    }

    // Handle login form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
                const response = await fetch(`${API_URL}/login.php`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                console.log('Login response:', data); // Debug log
                
                if (data.status === 'success') {
                    // Store user data in localStorage for persistence
                    localStorage.setItem('user', JSON.stringify(data.user));
                    window.location.href = 'index.html';
                } else {
                    alert(data.message || 'Login failed. Please try again.');
                }
            } catch (error) {
                console.error('Login error:', error);
                alert('An error occurred during login. Please try again.');
            }
        });
    }

    // Add logout functionality
    const logoutBtn = document.createElement('a');
    logoutBtn.href = '#';
    logoutBtn.className = 'auth-btn login-btn';
    logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Logout';
    logoutBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        try {
            await fetch(`${API_URL}/logout.php`);
            localStorage.removeItem('user');
            window.location.href = 'login.html';
        } catch (error) {
            console.error('Logout error:', error);
            alert('An error occurred during logout. Please try again.');
        }
    });

    // Update auth buttons based on login state
    function updateAuthButtons() {
        const authButtons = document.querySelector('.auth-buttons');
        if (!authButtons) return;

        const user = localStorage.getItem('user');
        if (user) {
            authButtons.innerHTML = '';
            authButtons.appendChild(logoutBtn);
        }
    }

    // Check authentication status
    function checkAuth() {
        const user = localStorage.getItem('user');
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        
        // Clear any potentially corrupted user data
        if (user && typeof JSON.parse(user) !== 'object') {
            localStorage.removeItem('user');
            return;
        }

        // Handle page access
        if (currentPage === 'index.html') {
            if (!user) {
                window.location.href = 'login.html';
            }
        } else if ((currentPage === 'login.html' || currentPage === 'register.html') && user) {
            window.location.href = 'index.html';
        }
    }

    // Initialize
    checkAuth();
    updateAuthButtons();
}); 