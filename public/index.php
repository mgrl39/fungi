<?php
// Incluir el archivo de configuración
require_once __DIR__ . '/../src/config/defaults.inc.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (DEBUG_MODE === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    echo '<link rel="stylesheet" href="/assets/twbs/bootstrap.min.css">';
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

use App\Config\AppInitializer;

// Inicializar la aplicación
list($db, $authController, $session, $twig) = AppInitializer::initialize();

// Cargar el archivo de rutas y pasar las dependencias
require_once __DIR__ . '/../src/routes.php';