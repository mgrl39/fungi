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
     * @var \PDO $db Conexión a la base de datos
     */
    private $db;
    
    /**
     * @var SessionController $session Controlador de sesión
     */
    private $session;
    
    /**
     * @var StatsController $statsController Controlador de estadísticas
     */
    private $statsController;

    /**
     * @brief Constructor del controlador de administración
     * 
     * @param \PDO $db Instancia de la conexión a la base de datos
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
     * @param object $twig Instancia de Twig
     * @param object $db Instancia de la conexión a base de datos
     * @param object $session Controlador de sesión
     * @return array Datos para la plantilla del dashboard
     */
    public function dashboardHandler($twig, $db, $session)
    {
        // Verificar si el usuario es administrador
        if (!$session->isAdmin()) {
            header('Location: /');
            exit;
        }

        return [
            'title' => _('Panel de Administración'),
            'stats' => $this->statsController->getDashboardStats()
        ];
    }
}
