document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    try {
        const response = await fetch('/admin/api/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Guardar el token
            localStorage.setItem('adminToken', data.token);
            // Redirigir al dashboard
            window.location.href = '/admin/dashboard.php';
        } else {
            alert('Error de autenticación: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al intentar iniciar sesión');
    }
});

// Función para hacer peticiones autenticadas
async function fetchAuth(url, options = {}) {
    const token = localStorage.getItem('adminToken');
    if (!token) {
        window.location.href = '/admin/login.php';
        return;
    }
    
    const headers = {
        'Authorization': `Bearer ${token}`,
        ...options.headers
    };
    
    const response = await fetch(url, { ...options, headers });
    
    if (response.status === 401) {
        localStorage.removeItem('adminToken');
        window.location.href = '/admin/login.php';
        return;
    }
    
    return response;
} 