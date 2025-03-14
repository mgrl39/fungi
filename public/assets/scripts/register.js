// Inicializar AOS
 AOS.init({
    duration: 500,
    easing: 'ease-in-out'
});

function validateForm() {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    if (username === '') {
        usernameInput.classList.add('shake-animation', 'is-invalid');
        setTimeout(() => {
            usernameInput.classList.remove('shake-animation');
        }, 500);
        return false;
    }

    if (email === '' || !email.includes('@')) {
        emailInput.classList.add('shake-animation', 'is-invalid');
        setTimeout(() => {
            emailInput.classList.remove('shake-animation');
        }, 500);
        return false;
    }

    if (password === '') {
        passwordInput.classList.add('shake-animation', 'is-invalid');
        setTimeout(() => {
            passwordInput.classList.remove('shake-animation');
        }, 500);
        return false;
    }

    if (password !== confirmPassword) {
        passwordInput.classList.add('shake-animation', 'is-invalid');
        confirmPasswordInput.classList.add('shake-animation', 'is-invalid');
        setTimeout(() => {
            passwordInput.classList.remove('shake-animation');
            confirmPasswordInput.classList.remove('shake-animation');
        }, 500);
        return false;
    }

    return true;
}

// Eliminar la clase de animación cuando el usuario empiece a escribir
document.getElementById('username').addEventListener('input', function() {
    this.classList.remove('shake-animation', 'is-invalid');
});

document.getElementById('email').addEventListener('input', function() {
    this.classList.remove('shake-animation', 'is-invalid');
});

document.getElementById('password').addEventListener('input', function() {
    this.classList.remove('shake-animation', 'is-invalid');
});

document.getElementById('confirm_password').addEventListener('input', function() {
    this.classList.remove('shake-animation', 'is-invalid');
});

// Nueva función para manejar el registro con API
function handleRegister(event) {
    event.preventDefault();
    
    if (!validateForm()) {
        return false;
    }
    
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('registerError');
    const errorMessage = document.getElementById('errorMessage');
    const successDiv = document.getElementById('registerSuccess');
    
    // Ocultar mensajes previos
    errorDiv.classList.add('d-none');
    successDiv.classList.add('d-none');
    
    // Realizar petición a la API
    fetch('/api/users', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            username: username,
            email: email,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.id) {
            // Registro exitoso, mostrar mensaje
            successDiv.classList.remove('d-none');
            
            // Desactivar el formulario
            document.getElementById('registerForm').querySelectorAll('input, button').forEach(element => {
                element.disabled = true;
            });
            
            // Redirigir a login después de 2 segundos
            setTimeout(() => {
                window.location.href = '/login?registered=true';
            }, 2000);
        } else if (data.error) {
            // Error en el registro
            errorMessage.textContent = data.error;
            errorDiv.classList.remove('d-none');
        } else {
            // Error desconocido
            errorMessage.textContent = 'Error en el registro. Inténtalo nuevamente.';
            errorDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        errorMessage.textContent = 'Error de conexión. Inténtalo de nuevo más tarde.';
        errorDiv.classList.remove('d-none');
    });
    
    return false;
}