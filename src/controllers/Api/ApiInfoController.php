<?php

namespace App\Controllers\Api;

/**
 * @class ApiInfoController
 * @brief Controlador para mostrar la documentación y la información de la API.
 *
 * Esta clase proporciona métodos para generar y mostrar la documentación
 * de la API RESTful, incluyendo endpoints disponibles y su uso.
 *
 * @package App\Controllers\Api
 */
class ApiInfoController
{
    /**
     * @brief Muestra la documentación general de la API.
     * 
     * Retorna una respuesta JSON con la descripción de la API,
     * versión, y endpoints disponibles.
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