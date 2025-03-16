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
     * Obtener la documentación de la API directamente desde el endpoint /api
     * Configurar opciones para la petición con timeout de 3 segundos
     * Si hay un error al obtener los datos, usar información básica
     * 
     * @return array Datos para la plantilla
     */
    public function apiDocsHandler()
    {
        $apiUrl = $this->getBaseUrl() . '/api';
        $context = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
        $apiResponse = @file_get_contents($apiUrl, false, $context);
        $apiDocs = json_decode($apiResponse, true);
        if (!$apiDocs) {
            $apiDocs = [ 'api_version' => 'v1', 'available_endpoints' => [ 'Error' => ['GET /api' => 'No se pudo obtener la documentación de la API. Por favor, intente más tarde.'] ] ];
        }
        return [ 'title' => _('Documentación de la API'), 'api_docs' => $apiDocs ];
    }

    /**
     * Obtiene la URL base del sitio construyendo la URL a partir del protocolo y host
     * 
     * @return string URL base
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . $host;
    }

    /**
     * Muestra la documentación general de la API en formato JSON
     * 
     * @return void
     */
    public static function show()
    {
        $apiInfo = [
            'name' => 'FungiAPI',
            'description' => 'API RESTful para acceder a datos de hongos y usuarios',
            'version' => '1.0.0',
            'contact' => [
                'name' => 'Administrador',
                'email' => 'admin@fungiapi.com'
            ],
            'endpoints' => [
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
                ]
            ]
        ];
        
        http_response_code(200);
        echo json_encode($apiInfo, JSON_PRETTY_PRINT);
    }
} 