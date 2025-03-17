<?php

namespace App\Controllers;

/**
 * @brief Controlador de documentación de la API
 * 
 * Maneja la solicitud de documentación de la API
 * 
 * @author mgrl39
 */
class DocsController
{
    protected $db;
    protected $session;

    /**
     * Constructor del controlador de documentación
     * 
     * @param \App\Controllers\DatabaseController $db Controlador de base de datos
     * @param \App\Controllers\SessionController $session Controlador de sesión
     */
    public function __construct($db, $session)
    {
        $this->db = $db;
        $this->session = $session;
    }

    /**
     * Maneja la solicitud de documentación de la API
     * Obtiene la documentación de la API directamente desde el método getApiInfo()
     * 
     * @return array Datos para la plantilla
     */
    public function apiDocsHandler()
    {
        $apiDocs = $this->getApiInfo();
        return [ 'title' => _('Documentación de la API'), 'api_docs' => $apiDocs ];
    }

    /**
     * Obtiene la información de la API en formato de array
     * 
     * @return array Información de la API
     */
    private function getApiInfo()
    {
        return [
            'name' => 'FungiAPI',
            'description' => 'API RESTful para acceder a datos de hongos y usuarios',
            'version' => '1.0.0',
            'contact' => [
                'name' => 'Administrador',
                'email' => 'admin@fungiapi.com'
            ],
            'endpoints' => [
                // Endpoints de hongos
                [
                    'path' => '/api/fungi',
                    'method' => 'GET',
                    'description' => 'Obtener lista de hongos con paginación opcional',
                    'parameters' => [
                        'page' => 'Número de página (por defecto: 1)',
                        'limit' => 'Número de registros por página (por defecto: 10)',
                        'search' => 'Término de búsqueda opcional'
                    ]
                ],
                [
                    'path' => '/api/fungi/{id}',
                    'method' => 'GET',
                    'description' => 'Obtener detalles de un hongo específico por ID'
                ],
                [
                    'path' => '/api/fungi/{id}/like',
                    'method' => 'POST',
                    'description' => 'Dar like a un hongo (requiere autenticación)',
                    'requires_auth' => true
                ],
                [
                    'path' => '/api/fungi/{id}/like',
                    'method' => 'DELETE',
                    'description' => 'Quitar like de un hongo (requiere autenticación)',
                    'requires_auth' => true
                ],
                
                // Endpoints de usuario
                [
                    'path' => '/api/user/favorites',
                    'method' => 'GET',
                    'description' => 'Obtener hongos favoritos del usuario (requiere autenticación)',
                    'requires_auth' => true
                ],
                [
                    'path' => '/api/user/favorites/{id}',
                    'method' => 'POST',
                    'description' => 'Añadir un hongo a favoritos (requiere autenticación)',
                    'requires_auth' => true
                ],
                [
                    'path' => '/api/user/favorites/{id}',
                    'method' => 'DELETE',
                    'description' => 'Eliminar un hongo de favoritos (requiere autenticación)',
                    'requires_auth' => true
                ],
                [
                    'path' => '/api/user/likes',
                    'method' => 'GET',
                    'description' => 'Obtener hongos que le gustan al usuario (requiere autenticación)',
                    'requires_auth' => true
                ],
                
                // Endpoints de estadísticas
                [
                    'path' => '/api/statistics',
                    'method' => 'GET',
                    'description' => 'Obtener estadísticas generales de la aplicación'
                ],
                [
                    'path' => '/api/statistics/user_activity',
                    'method' => 'GET',
                    'description' => 'Obtener estadísticas de actividad de usuarios (solo administradores)',
                    'requires_admin' => true
                ],
                
                // Endpoints adicionales para la gestión de hongos
                [
                    'path' => '/api/fungi',
                    'method' => 'POST',
                    'description' => 'Crear un nuevo hongo (requiere autenticación de administrador)',
                    'requires_admin' => true,
                    'parameters' => [
                        'name' => 'Nombre científico del hongo (obligatorio)',
                        'common_name' => 'Nombre común del hongo',
                        'edibility' => 'Comestibilidad del hongo (obligatorio)',
                        'habitat' => 'Hábitat del hongo (obligatorio)',
                        'observations' => 'Observaciones adicionales',
                        'synonym' => 'Sinónimos científicos'
                    ]
                ],
                [
                    'path' => '/api/fungi/{id}',
                    'method' => 'PUT',
                    'description' => 'Actualizar información de un hongo existente (requiere autenticación de administrador)',
                    'requires_admin' => true
                ],
                [
                    'path' => '/api/fungi/{id}',
                    'method' => 'DELETE',
                    'description' => 'Eliminar un hongo (requiere autenticación de administrador)',
                    'requires_admin' => true
                ],
                
                // Endpoints de autenticación
                [
                    'path' => '/api/auth/login',
                    'method' => 'POST',
                    'description' => 'Iniciar sesión de usuario',
                    'parameters' => [
                        'username' => 'Nombre de usuario (obligatorio)',
                        'password' => 'Contraseña (obligatorio)'
                    ]
                ],
                [
                    'path' => '/api/auth/logout',
                    'method' => 'POST',
                    'description' => 'Cerrar sesión de usuario',
                    'requires_auth' => true
                ],
                [
                    'path' => '/api/auth/register',
                    'method' => 'POST',
                    'description' => 'Registrar nuevo usuario',
                    'parameters' => [
                        'username' => 'Nombre de usuario (obligatorio)',
                        'email' => 'Correo electrónico (obligatorio)',
                        'password' => 'Contraseña (obligatorio)'
                    ]
                ]
            ]
        ];
    }

    /**
     * Muestra solo los endpoints de la API en formato JSON
     * 
     * @return void
     */
    public static function show()
    {
        $instance = new self(null, null);
        $apiInfo = $instance->getApiInfo();
        
        $endpointsOnly = [
            'name' => $apiInfo['name'],
            'version' => $apiInfo['version'],
            'endpoints' => array_map(function($endpoint) {
                return [ 'path' => $endpoint['path'], 'method' => $endpoint['method'],
                    'requires_auth' => $endpoint['requires_auth'] ?? false,
                    'requires_admin' => $endpoint['requires_admin'] ?? false,
                    'parameters' => isset($endpoint['parameters']) ? array_keys($endpoint['parameters']) : []
                ];
            }, $apiInfo['endpoints'])
        ];
        http_response_code(200);
        echo json_encode($endpointsOnly, JSON_PRETTY_PRINT);
    }
} 