<?php

namespace App\Controllers;

/**
 * @class AdminController
 * @brief Controlador para funciones administrativas
 * 
 * Esta clase proporciona métodos para gestionar las páginas y funcionalidades
 * de administración del sistema.
 */
class AdminController
{
    /**
     * @var \App\Controllers\DatabaseController $db Controlador de base de datos
     * @var SessionController $session Controlador de sesión
     * @var StatsController $statsController Controlador de estadísticas
     */
    private $db;
    private $session;
    private $statsController;

    /**
     * @brief Constructor del controlador de administración
     * 
     * @param \App\Controllers\DatabaseController $db Instancia del controlador de base de datos
     * @param SessionController $session Controlador de sesión
     * @param StatsController $statsController Controlador de estadísticas
     */
    public function __construct($db, $session, $statsController)
    {
        $this->db = $db;
        $this->session = $session;
        $this->statsController = $statsController;
    }

    /**
     * @brief Manejador para la página del panel de administración
     * 
     * Este método actúa como handler para la ruta dashboard.
     * Verifica que el usuario sea administrador y devuelve los datos
     * necesarios para renderizar el panel.
     * 
     * @param object $twig Instancia de Twig
     * @param object $db Instancia de la conexión a base de datos
     * @param object $session Controlador de sesión
     * @return array Datos para la plantilla del dashboard
     */
    public function dashboardHandler($twig, $db, $session)
    {
        if (!$session->isAdmin()) header('Location: /') && exit;
        return [
            'title' => _('Panel de Administración'),
            'stats' => $this->statsController->getDashboardStats()
        ];
    }
}
