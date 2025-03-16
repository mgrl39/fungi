<?php

namespace App\Controllers;

/**
 * @class HomeController
 * @brief Controlador para la página principal
 * 
 * Este controlador maneja la lógica específica para la página de inicio
 */
class HomeController
{
    private $db;
    
    /**
     * Constructor del controlador
     * 
     * @param object $db Instancia del controlador de base de datos
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * @brief Manejador para la página principal
     * 
     * @param object $twig Instancia de Twig
     * @param object $db Instancia de la base de datos
     * @param object $session Controlador de sesión
     * @return array Datos para la plantilla principal
     */
    public function indexHandler($twig, $db, $session)
    {
        // La página principal usa AJAX para cargar los hongos
        return [
            'title' => _('Hongos'),
            'api_url' => $this->getBaseUrl() . '/api/fungi/page/1/limit/12',
            'use_ajax' => true
        ];
    }
    
    /**
     * @brief Obtiene la URL base del sitio
     * 
     * @return string URL base del sitio
     */
    private function getBaseUrl() 
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . $host;
    }
} 