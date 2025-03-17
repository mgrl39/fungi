<?php


// IMPORTANTE: Detectar si es una solicitud API y evitar imprimir HTML
$isApiRequest = preg_match('#^/api#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Inicializar el controlador de idiomas ANTES de definir las rutas
$langController = new \App\Controllers\LangController();
$currentLanguage = $langController->initializeLanguage();

// Cargar dominios adicionales para diferentes secciones
$langController->loadTextDomain('navbar');
$langController->loadTextDomain('stats');
$langController->loadTextDomain('about');
$langController->loadTextDomain('fungi');
$langController->loadTextDomain('admin');
$langController->loadTextDomain('user');
$langController->loadTextDomain('api');

// Obtenemos la URI desde la solicitud, si la URI está vacía, la tratamos como la raíz
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$uri = $uri === '' ? '/' : $uri;

// Detectar si es una solicitud API y evitar imprimir HTML
$isApiRequest = preg_match('#^/api#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (!$isApiRequest) (new \App\Controllers\DebugController())->mostrarDebugTraducciones();

function getBaseUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host;
}

// Lista de componentes reutilizables
$components = [
    'footer' => 'components/footer.twig',
    'header' => 'components/header.twig',
    'navbar' => 'components/navbar.twig',
    'sidebar' => 'components/sidebar.twig'
];

/**
 * Renderiza una plantilla con los datos proporcionados
 * 
 * @param string $templatePath Ruta de la plantilla
 * @param array $data Datos para pasar a la plantilla
 * @return void
 * 
 * @details Determina el dominio de traducción según la plantilla.
 * Asegura que los componentes estén disponibles.
 * Agrega información de sesión si está disponible.
 * Añade la ruta actual para marcar elementos activos.
 * Asegura que se pase el idioma actual.
 * Renderiza la plantilla y maneja errores.
 */
function renderTemplate($templatePath, $data = [])
{
    global $twig, $uri, $components, $session, $langController;

    $originalDomain = textdomain(NULL);

    if (strpos($templatePath, 'navbar') !== false) textdomain('navbar');
    else if (strpos($templatePath, 'about') !== false) textdomain('about');
    else if (strpos($templatePath, 'stats') !== false) textdomain('stats');
    else textdomain('messages');

    $data['components'] = $components;

    if (isset($session)) {
        $data['is_logged_in'] = $session->isLoggedIn();
        if ($session->isLoggedIn()) $data['user'] = $session->getUserData();
    } else $data['is_logged_in'] = false;

    $data['current_route'] = $uri;
    $data['idioma_actual'] = $_SESSION['idioma'] ?? 'es';

    try {
        $result = $twig->render($templatePath, $data);
        textdomain($originalDomain);
        echo $result;
    } catch (Exception $e) {
        echo "<h1>Error al renderizar la plantilla</h1>";
        echo "<p>{$e->getMessage()}</p>";
        if (defined('DEBUG_MODE') && DEBUG_MODE) echo "<pre>"; print_r($e); echo "</pre>";
    }
}

// Inicializar controladores
$db = new \App\Controllers\DatabaseController();
$session = new \App\Controllers\SessionController($db);
$authController = new \App\Controllers\AuthController($db);
$userController = new \App\Controllers\UserController($db, $session);
$fungiController = new \App\Controllers\FungiController($db);
$statsController = new \App\Controllers\StatsController($db);
$docsController = new \App\Controllers\DocsController($db, $session);
$homeController = new \App\Controllers\HomeController($db);
$adminController = new \App\Controllers\AdminController($db, $session, $statsController);

// Crear el controlador de rutas
$routeController = new \App\Controllers\RouteController($twig, $db, $session, [
    'auth' => $authController,
    'fungi' => $fungiController,
    'stats' => $statsController,
    'docs' => $docsController,
    'lang' => $langController
]);

// Definir rutas
$routes = [
    '/home' => ['template' => null, 'redirect' => '/'],
    '/index' => ['template' => 'pages/home.twig', 'redirect' => '/'],
    '/about' => ['template' => 'pages/about.twig', 'title' => _('Acerca de'), 'auth_required' => false],
    '/404' => ['template' => 'pages/404.twig', 'title' => _('Página no encontrada'), 'auth_required' => false],
    '/random' => ['template' => null, 'redirect' => '/fungi/random'],
    '/profile/([^/]+)' => ['redirect' => '/profile'],
    '/profile' => ['template' => 'pages/profile.twig', 'auth_required' => true, 'handler' => [$userController, 'profileHandler']],
    '/docs/api' => ['template' => 'pages/api/api_docs.twig', 'auth_required' => false, 'handler' => [$docsController, 'apiDocsHandler']],
    '/change-language' => ['handler' => [$langController, 'changeLanguage']],
    '/register' => ['template' => 'components/auth/register_form.twig', 'auth_required' => false, 'handler' => [$authController, 'registerHandler']],
    '/logout' => ['handler' => [$authController, 'logoutAndRedirect']],
    '/statistics' => ['template' => 'pages/statistics.twig', 'title' => _('Estadísticas'), 'handler' => [$statsController, 'statisticsPageHandler']],
    '/fungi/random' => ['template' => 'pages/fungi_detail.twig', 'title' => _('Hongo aleatorio'), 'auth_required' => false, 'handler' => [$fungiController, 'randomFungusHandler']],
    '/login' => ['template' => 'components/auth/login_form.twig', 'title' => _('Iniciar Sesión'), 'auth_required' => false, 'handler' => [$authController, 'loginHandler']],
    '/' => ['template' => 'pages/home.twig', 'title' => _('Hongos'), 'auth_required' => false, 'handler' => [$homeController, 'indexHandler']],
    '/dashboard' => ['template' => 'pages/admin.twig', 'title' => _('Administración'), 'auth_required' => true, 'admin_required' => true, 'handler' => [$adminController, 'dashboardHandler']],
    '/fungi/(\d+)' => ['template' => 'pages/fungi_detail.twig', 'auth_required' => false, 'handler' => [$fungiController, 'detailFungusHandler']],
];

// Añadir rutas al controlador
$routeController->addRoutes($routes);
/**
 * @brief Manejo de rutas de la API y rutas normales
 * 
 * @details Este código maneja dos tipos de rutas:
 * 1. Rutas de API que comienzan con /api - Estas son manejadas por ApiController
 * 2. Rutas normales - Manejadas por RouteController
 * 
 * Para las rutas de API:
 * - Limpia cualquier salida previa
 * - Establece el header de Content-Type a JSON
 * - Instancia ApiController y delega el manejo
 * 
 * Para rutas normales:
 * - Delega al RouteController para procesar la ruta
 */
if (preg_match('#^/api#', $uri)) {
    ob_clean();
    header('Content-Type: application/json');
    (new \App\Controllers\ApiController($db))->handleRequest();
    exit;
} else $routeController->handleRequest($uri);
