        // Inicializar AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Resaltar la navegación al hacer scroll
        window.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('h2[id]');
            const navLinks = document.querySelectorAll('.list-group-item');
            
            window.addEventListener('scroll', () => {
                let current = '';
                
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    if (pageYOffset >= sectionTop - 200) {
                        current = section.getAttribute('id');
                    }
                });
                
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${current}`) {
                        link.classList.add('active');
                    }
                });
            });
            
            // Función para formatear JSON
            function formatJSON(json) {
                try {
                    return JSON.stringify(JSON.parse(json), null, 2);
                } catch (e) {
                    return json;
                }
            }
            
            // Manejador para los botones de prueba de API
            document.querySelectorAll('.test-api').forEach(button => {
                button.addEventListener('click', async function() {
                    const method = this.getAttribute('data-method');
                    let endpoint = this.getAttribute('data-endpoint');
                    const responseContainer = this.parentElement.querySelector('.api-response');
                    const responseContent = responseContainer.querySelector('.response-content');
                    
                    // Mostrar el contenedor de respuesta
                    responseContainer.classList.remove('d-none');
                    responseContent.textContent = 'Cargando...';
                    
                    // Reemplazar parámetros en la URL si existen
                    if (endpoint.includes('{id}')) {
                        const paramId = this.getAttribute('data-param-id');
                        const idValue = document.getElementById(paramId).value;
                        endpoint = endpoint.replace('{id}', idValue);
                    }
                    
                    if (endpoint.includes('{page}') && endpoint.includes('{limit}')) {
                        const pageValue = document.getElementById(this.getAttribute('data-param-page')).value;
                        const limitValue = document.getElementById(this.getAttribute('data-param-limit')).value;
                        endpoint = endpoint.replace('{page}', pageValue).replace('{limit}', limitValue);
                    }
                    
                    // Configurar opciones de fetch
                    const options = {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    };
                    
                    // Añadir body para POST y PUT
                    if (method === 'POST' || method === 'PUT') {
                        const bodyId = this.getAttribute('data-body');
                        options.body = document.getElementById(bodyId).value;
                    }
                    
                    try {
                        const response = await fetch(endpoint, options);
                        const data = await response.text();
                        
                        // Mostrar la respuesta formateada
                        responseContent.textContent = formatJSON(data);
                        
                        // Añadir clase de estado HTTP
                        if (response.ok) {
                            responseContent.classList.add('text-success');
                            responseContent.classList.remove('text-danger');
                        } else {
                            responseContent.classList.add('text-danger');
                            responseContent.classList.remove('text-success');
                        }
                    } catch (error) {
                        responseContent.textContent = `Error: ${error.message}`;
                        responseContent.classList.add('text-danger');
                    }
                });
            });
        });