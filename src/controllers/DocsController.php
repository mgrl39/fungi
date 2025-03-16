<?php

namespace App\Controllers;

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
     * 
     * @return array Datos para la plantilla
     */
    public function apiDocsHandler()
    {
        // Obtener la documentación de la API directamente desde el endpoint /api
        $apiUrl = $this->getBaseUrl() . '/api';
        
        // Configurar opciones para la petición
        $context = stream_context_create([
            'http' => [
                'timeout' => 3, // Timeout de 3 segundos
                'ignore_errors' => true
            ]
        ]);
        
        $apiResponse = @file_get_contents($apiUrl, false, $context);
        $apiDocs = json_decode($apiResponse, true);

        // Si hay un error al obtener los datos, usar información básica
        if (!$apiDocs) {
            $apiDocs = [
                'api_version' => 'v1',
                'available_endpoints' => [
                    'Error' => ['GET /api' => 'No se pudo obtener la documentación de la API. Por favor, intente más tarde.']
                ]
            ];
        }
        
        return [
            'title' => _('Documentación de la API'),
            'api_docs' => $apiDocs
        ];
    }

    /**
     * Obtiene la URL base del sitio
     * 
     * @return string URL base
     */
    private function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . $host;
    }
} 