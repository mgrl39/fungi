document.addEventListener('DOMContentLoaded', function() {
    // Obtener todos los botones de cambio de idioma
    const langButtons = document.querySelectorAll('.lang-switch');
    
    // Añadir listener a cada botón
    langButtons.forEach(button => {
        button.addEventListener('click', function() {
            const lang = this.getAttribute('data-lang');
            
            // Crear una solicitud AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/change-language', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            // Manejar la respuesta
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Recargar los componentes de la página sin recargar completamente
                    // Esto se puede mejorar con una respuesta JSON más detallada
                    location.reload();
                }
            };
            
            // Enviar la solicitud
            xhr.send('lang=' + lang);
        });
    });
});