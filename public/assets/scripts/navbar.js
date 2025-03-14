document.addEventListener('DOMContentLoaded', function() {
    const loadingIndicator = document.getElementById('auth-status');
    const userDropdown = document.getElementById('user-dropdown');
    const loginButton = document.getElementById('login-button');
    const registerButton = document.getElementById('register-button');
    const adminOption = document.getElementById('admin-option');
    const usernameDisplay = document.getElementById('username-display');
    
    loadingIndicator.classList.remove('d-none');
    
    async function checkAuthentication() {
        try {
            const response = await fetch('/api/auth/verify', { 
                method: 'GET',
                credentials: 'include'
            });
            
            const data = await response.json();
            
            loadingIndicator.classList.add('d-none');
            
            if (data.success && data.authenticated) {
                userDropdown.classList.remove('d-none');
                usernameDisplay.textContent = data.user.username;
                
                if (data.user.role === 'admin') {
                    adminOption.classList.remove('d-none');
                }
            } else {
                loginButton.classList.remove('d-none');
                registerButton.classList.remove('d-none');
            }
        } catch (error) {
            console.error('Error al verificar autenticación:', error);
            loadingIndicator.classList.add('d-none');
            
            loginButton.classList.remove('d-none');
            registerButton.classList.remove('d-none');
        }
    }
    
    window.logout = function() {
        fetch('/api/auth/logout', {
            method: 'POST',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            window.location.href = '/';
        })
        .catch(error => {
            console.error('Error en logout:', error);
            window.location.href = '/';
        });
    }
    
    checkAuthentication();
});