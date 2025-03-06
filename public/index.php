<?php
// Incluir el archivo de configuración
require_once __DIR__ . '/../src/config/defaults.inc.php';

// Configuración de errores basada en el modo de depuración
if (DEBUG_MODE === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';
/*
require_once __DIR__ . '/../src/controllers/DatabaseController.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/SessionController.php';
require_once __DIR__ . '/../src/config/i18n.php';
require_once __DIR__ . '/../src/config/AppInitializer.php';
*/
use App\Config\AppInitializer;

/*
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
*/

// Inicializar la aplicación
list($db, $authController, $session, $twig) = AppInitializer::initialize();

// Cargar el archivo de rutas y pasar las dependencias
require_once __DIR__ . '/../src/routes.php';