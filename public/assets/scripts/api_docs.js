/**
 * Script para la documentación de la API
 * Maneja la interactividad y las pruebas de endpoints
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const endpointLinks = document.querySelectorAll('.endpoint-link');
    const endpointContent = document.getElementById('endpointContent');
    const searchInput = document.getElementById('apiSearchInput');
    
    // Inicializar búsqueda de endpoints
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.endpoint-link').forEach(link => {
                const method = link.querySelector('.api-sidebar-method').textContent.toLowerCase();
                const path = link.querySelector('.endpoint-label').textContent.toLowerCase();
                const isVisible = method.includes(searchTerm) || path.includes(searchTerm);
                link.parentElement.style.display = isVisible ? 'block' : 'none';
            });
        });
    }
    
    // Manejar clics en los enlaces de endpoints
    endpointLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const endpointId = this.getAttribute('data-endpoint');
            
            // Actualizar clase active
            document.querySelector('.endpoint-link.active')?.classList.remove('active');
            this.classList.add('active');
            
            // Mostrar contenido del endpoint seleccionado
            showEndpointContent(endpointId, this);
        });
    });
    
    // Función para mostrar el contenido del endpoint
    function showEndpointContent(endpointId, linkElement) {
        // Construir contenido basado en el endpoint seleccionado
        const method = linkElement.querySelector('.api-sidebar-method').textContent;
        const path = linkElement.querySelector('.endpoint-label').textContent;
        const methodClass = linkElement.querySelector('.api-sidebar-method').classList[1];
        
        // Generar descripción basada en el ID del endpoint
        let description = '';
        if (endpointId.includes('get')) {
            description = 'Obtiene';
        } else if (endpointId.includes('post')) {
            description = 'Crea';
        } else if (endpointId.includes('put')) {
            description = 'Actualiza';
        } else if (endpointId.includes('delete')) {
            description = 'Elimina';
        }
        
        let resourceType = '';
        if (endpointId.includes('fungi')) {
            resourceType = 'hongos';
            if (endpointId.includes('id')) {
                resourceType = 'un hongo específico por ID';
            } else if (endpointId.includes('random')) {
                resourceType = 'un hongo aleatorio';
            } else if (endpointId.includes('page')) {
                resourceType = 'hongos de forma paginada';
            } else if (endpointId.includes('search')) {
                resourceType = 'hongos que coinciden con criterios de búsqueda';
            }
        } else if (endpointId.includes('users')) {
            resourceType = 'usuarios';
        } else if (endpointId.includes('auth')) {
            if (endpointId.includes('login')) {
                description = 'Autentica';
                resourceType = 'un usuario y devuelve un token JWT';
            } else if (endpointId.includes('logout')) {
                description = 'Cierra';
                resourceType = 'la sesión actual del usuario';
            } else if (endpointId.includes('verify')) {
                description = 'Verifica';
                resourceType = 'si un usuario está autenticado';
            }
        }
        
        // Generar código de ejemplo JSON para cada tipo de endpoint
        let requestExample = '';
        let responseExample = '';
        
        switch (endpointId) {
            case 'fungi-post':
                requestExample = `{
  "name": "Amanita muscaria",
  "edibility": "Tóxico",
  "habitat": "Bosques de coníferas",
  "observations": "Seta característica por su sombrero rojo con manchas blancas",
  "common_name": "Matamoscas",
  "synonym": "Agaricus muscarius",
  "title": "Falsa oronja"
}`;
                responseExample = `{
  "id": 123,
  "success": true
}`;
                break;
            case 'fungi-put':
                requestExample = `{
  "name": "Amanita muscaria var. flavivolvata",
  "edibility": "Tóxico",
  "habitat": "Bosques mixtos"
}`;
                responseExample = `{
  "success": true
}`;
                break;
            case 'auth-login':
                requestExample = `{
  "username": "usuario_ejemplo",
  "password": "contraseña_segura"
}`;
                responseExample = `{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 42,
    "username": "usuario_ejemplo",
    "email": "usuario@ejemplo.com",
    "role": "user"
  }
}`;
                break;
            case 'users-post':
                requestExample = `{
  "username": "nuevo_usuario",
  "email": "nuevo@ejemplo.com",
  "password": "contraseña_segura"
}`;
                responseExample = `{
  "success": true,
  "id": 43,
  "message": "Usuario registrado exitosamente"
}`;
                break;
            default:
                if (endpointId.includes('get')) {
                    responseExample = endpointId.includes('id') || endpointId.includes('random') ? 
                    `{
  "success": true,
  "data": {
    "id": 42,
    "name": "Amanita muscaria",
    "edibility": "Tóxico",
    "habitat": "Bosques de coníferas",
    "image_urls": "/assets/images/fungi/amanita_muscaria_1.jpg,/assets/images/fungi/amanita_muscaria_2.jpg"
  }
}` : 
                    `{
  "success": true,
  "data": [
    {
      "id": 42,
      "name": "Amanita muscaria",
      "edibility": "Tóxico"
    },
    {
      "id": 43,
      "name": "Boletus edulis",
      "edibility": "Comestible"
    }
  ]
}`;
                }
                break;
        }
        
        // Generar parámetros si es necesario
        let parametersSection = '';
        if (path.includes('{id}')) {
            parametersSection = `
            <div class="endpoint-parameters">
                <h3>Parámetros</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Ubicación</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>id</td>
                            <td>path</td>
                            <td>integer</td>
                            <td>ID único del hongo</td>
                        </tr>
                    </tbody>
                </table>
            </div>`;
        } else if (path.includes('{page}') && path.includes('{limit}')) {
            parametersSection = `
            <div class="endpoint-parameters">
                <h3>Parámetros</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Ubicación</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>page</td>
                            <td>path</td>
                            <td>integer</td>
                            <td>Número de página</td>
                        </tr>
                        <tr>
                            <td>limit</td>
                            <td>path</td>
                            <td>integer</td>
                            <td>Número de elementos por página</td>
                        </tr>
                    </tbody>
                </table>
            </div>`;
        } else if (path.includes('{param}') && path.includes('{value}')) {
            parametersSection = `
            <div class="endpoint-parameters">
                <h3>Parámetros</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Ubicación</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>param</td>
                            <td>path</td>
                            <td>string</td>
                            <td>Campo de búsqueda (name, edibility, habitat, common_name)</td>
                        </tr>
                        <tr>
                            <td>value</td>
                            <td>path</td>
                            <td>string</td>
                            <td>Valor a buscar</td>
                        </tr>
                    </tbody>
                </table>
            </div>`;
        }
        
        // Generar sección de cuerpo de solicitud si es necesario
        let requestBodySection = '';
        if (method === 'POST' || method === 'PUT') {
            requestBodySection = `
            <div class="endpoint-request-body">
                <h3>Cuerpo de la solicitud</h3>
                <div class="code-block">
                    <pre>${requestExample}</pre>
                </div>
            </div>`;
        }
        
        // Construir el formulario de prueba
        let testFormFields = '';
        if (path.includes('{id}')) {
            testFormFields = `
            <div class="mb-3">
                <label for="param-id-${endpointId}" class="form-label">ID</label>
                <input type="number" class="form-control" id="param-id-${endpointId}" placeholder="Ingrese ID" value="1">
            </div>`;
        } else if (path.includes('{page}') && path.includes('{limit}')) {
            testFormFields = `
            <div class="mb-3">
                <label for="param-page-${endpointId}" class="form-label">Página</label>
                <input type="number" class="form-control" id="param-page-${endpointId}" placeholder="Número de página" value="1">
            </div>
            <div class="mb-3">
                <label for="param-limit-${endpointId}" class="form-label">Límite</label>
                <input type="number" class="form-control" id="param-limit-${endpointId}" placeholder="Elementos por página" value="10">
            </div>`;
        } else if (path.includes('{param}') && path.includes('{value}')) {
            testFormFields = `
            <div class="mb-3">
                <label for="param-type-${endpointId}" class="form-label">Parámetro</label>
                <select class="form-control" id="param-type-${endpointId}">
                    <option value="name">name</option>
                    <option value="edibility">edibility</option>
                    <option value="habitat">habitat</option>
                    <option value="common_name">common_name</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="param-value-${endpointId}" class="form-label">Valor</label>
                <input type="text" class="form-control" id="param-value-${endpointId}" placeholder="Valor a buscar">
            </div>`;
        } else if (method === 'POST' || method === 'PUT') {
            if (endpointId === 'auth-login') {
                testFormFields = `
                <div class="mb-3">
                    <label for="param-username-${endpointId}" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="param-username-${endpointId}" placeholder="Nombre de usuario">
                </div>
                <div class="mb-3">
                    <label for="param-password-${endpointId}" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="param-password-${endpointId}" placeholder="Contraseña">
                </div>`;
            } else if (endpointId === 'users-post') {
                testFormFields = `
                <div class="mb-3">
                    <label for="param-username-${endpointId}" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="param-username-${endpointId}" placeholder="Nombre de usuario">
                </div>
                <div class="mb-3">
                    <label for="param-email-${endpointId}" class="form-label">Email</label>
                    <input type="email" class="form-control" id="param-email-${endpointId}" placeholder="Correo electrónico">
                </div>
                <div class="mb-3">
                    <label for="param-password-${endpointId}" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="param-password-${endpointId}" placeholder="Contraseña">
                </div>`;
            } else if (endpointId === 'fungi-post' || endpointId === 'fungi-put') {
                testFormFields = `
                <div class="mb-3">
                    <label for="param-name-${endpointId}" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="param-name-${endpointId}" placeholder="Nombre científico">
                </div>
                <div class="mb-3">
                    <label for="param-edibility-${endpointId}" class="form-label">Comestibilidad</label>
                    <select class="form-control" id="param-edibility-${endpointId}">
                        <option value="Comestible">Comestible</option>
                        <option value="No comestible">No comestible</option>
                        <option value="Tóxico">Tóxico</option>
                        <option value="Mortal">Mortal</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="param-habitat-${endpointId}" class="form-label">Hábitat</label>
                    <input type="text" class="form-control" id="param-habitat-${endpointId}" placeholder="Hábitat">
                </div>`;
                
                if (endpointId === 'fungi-put') {
                    testFormFields = `
                    <div class="mb-3">
                        <label for="param-id-${endpointId}" class="form-label">ID</label>
                        <input type="number" class="form-control" id="param-id-${endpointId}" placeholder="ID del hongo" value="1">
                    </div>` + testFormFields;
                }
            }
        }
        
        // Construir contenido completo del endpoint
        const endpointHtml = `
        <div class="endpoint-card">
            <div class="endpoint-header">
                <span class="endpoint-method ${methodClass}">${method}</span>
                <span class="endpoint-path">/api${path}</span>
            </div>
            <div class="endpoint-content">
                <div class="endpoint-description">
                    <h3>Descripción</h3>
                    <p>${description} ${resourceType}.</p>
                </div>
                
                ${parametersSection}
                
                ${requestBodySection}
                
                <div class="endpoint-responses">
                    <h3>Respuestas</h3>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>200</td>
                                <td>Éxito</td>
                            </tr>
                            ${method === 'POST' ? '<tr><td>201</td><td>Creado correctamente</td></tr>' : ''}
                            <tr>
                                <td>400</td>
                                <td>Solicitud incorrecta</td>
                            </tr>
                            <tr>
                                <td>404</td>
                                <td>Recurso no encontrado</td>
                            </tr>
                            ${endpointId.includes('auth') ? '<tr><td>401</td><td>No autorizado</td></tr>' : ''}
                        </tbody>
                    </table>
                    
                    <h4>Ejemplo de respuesta</h4>
                    <div class="code-block">
                        <pre>${responseExample}</pre>
                    </div>
                </div>
                
                <div class="try-api">
                    <h3>Probar API</h3>
                    <form id="form-${endpointId}" class="mb-3">
                        ${testFormFields}
                        <button type="button" class="btn btn-primary test-api" 
                                data-method="${method}" 
                                data-endpoint="/api${path}" 
                                data-endpoint-id="${endpointId}">
                            Ejecutar
                        </button>
                    </form>
                    
                    <div class="api-response d-none mt-3">
                        <h4>Respuesta</h4>
                        <div class="code-block">
                            <pre class="response-content">Esperando respuesta...</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
        
        // Actualizar el contenido
        endpointContent.innerHTML = endpointHtml;
        
        // Inicializar botones de prueba
        initializeTestButtons();
    }

    /**
     * Inicializa los botones de prueba de la API
     */
    function initializeTestButtons() {
        const testButtons = document.querySelectorAll('.test-api');
        
        testButtons.forEach(button => {
            button.addEventListener('click', async function() {
                const endpointId = this.getAttribute('data-endpoint-id');
                const method = this.getAttribute('data-method');
                let endpoint = this.getAttribute('data-endpoint');
                const form = document.getElementById(`form-${endpointId}`);
                const responseContainer = this.closest('.try-api').querySelector('.api-response');
                const responseContent = responseContainer.querySelector('.response-content');
                
                // Mostrar el contenedor de respuesta
                responseContainer.classList.remove('d-none');
                responseContent.textContent = 'Procesando solicitud...';
                
                try {
                    // Reemplazar parámetros en la URL si existen
                    if (endpoint.includes('{id}')) {
                        const idValue = document.getElementById(`param-id-${endpointId}`).value;
                        endpoint = endpoint.replace('{id}', idValue);
                    }
                    
                    if (endpoint.includes('{page}') && endpoint.includes('{limit}')) {
                        const pageValue = document.getElementById(`param-page-${endpointId}`).value;
                        const limitValue = document.getElementById(`param-limit-${endpointId}`).value;
                        endpoint = endpoint.replace('{page}', pageValue).replace('{limit}', limitValue);
                    }
                    
                    // Para endpoints de búsqueda
                    if (endpoint.includes('{param}') && endpoint.includes('{value}')) {
                        const paramValue = document.getElementById(`param-type-${endpointId}`).value;
                        const searchValue = document.getElementById(`param-value-${endpointId}`).value;
                        endpoint = endpoint.replace('{param}', paramValue).replace('{value}', searchValue);
                    }
                    
                    // Configurar opciones de fetch
                    const options = {
                        method: method,
                        headers: {
                            'Accept': 'application/json'
                        }
                    };
                    
                    // Para métodos que requieren cuerpo
                    if (method === 'POST' || method === 'PUT') {
                        options.headers['Content-Type'] = 'application/json';
                        
                        // Construir el cuerpo de la solicitud según el endpoint
                        let body = {};
                        
                        if (endpointId === 'auth-login') {
                            const username = document.getElementById(`param-username-${endpointId}`).value;
                            const password = document.getElementById(`param-password-${endpointId}`).value;
                            body = { username, password };
                        } else if (endpointId === 'users-post') {
                            const username = document.getElementById(`param-username-${endpointId}`).value;
                            const email = document.getElementById(`param-email-${endpointId}`).value;
                            const password = document.getElementById(`param-password-${endpointId}`).value;
                            body = { username, email, password };
                        } else if (endpointId === 'fungi-post' || endpointId === 'fungi-put') {
                            const name = document.getElementById(`param-name-${endpointId}`).value;
                            const edibility = document.getElementById(`param-edibility-${endpointId}`).value;
                            const habitat = document.getElementById(`param-habitat-${endpointId}`).value;
                            body = { name, edibility, habitat };
                            
                            // Añadir campos adicionales para fungi-post
                            if (endpointId === 'fungi-post') {
                                body.observations = '';
                                body.common_name = '';
                                body.synonym = '';
                                body.title = '';
                            }
                        }
                        
                        options.body = JSON.stringify(body);
                    }
                    
                    // Ejecutar la solicitud
                    const response = await fetch(endpoint, options);
                    const data = await response.json();
                    
                    // Mostrar la respuesta formateada
                    responseContent.textContent = JSON.stringify(data, null, 2);
                    
                    // Cambiar el color según el código de respuesta
                    if (response.ok) {
                        responseContainer.classList.add('success-response');
                        responseContainer.classList.remove('error-response');
                    } else {
                        responseContainer.classList.add('error-response');
                        responseContainer.classList.remove('success-response');
                    }
                } catch (error) {
                    responseContent.textContent = `Error: ${error.message}`;
                    responseContainer.classList.add('error-response');
                    responseContainer.classList.remove('success-response');
                }
            });
        });
    }
    
    // Inicializar botones de prueba para el contenido inicial
    initializeTestButtons();
});