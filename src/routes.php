<?php

// Añadir en la parte superior del archivo
require_once __DIR__ . '/controllers/StatsController.php';
require_once __DIR__ . '/controllers/DebugController.php';
require_once __DIR__ . '/controllers/DocsController.php';

// O asegúrate de que el autoloader está configurado correctamente
// require_once __DIR__ . '/../vendor/autoload.php';

// IMPORTANTE: Detectar si es una solicitud API y evitar imprimir HTML
$isApiRequest = preg_match('#^/api#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// IMPORTANTE: Inicializar el controlador de idiomas ANTES de definir las rutas
$langController = new \App\Controllers\LangController();
$currentLanguage = $langController->initializeLanguage();

// Cargar dominios adicionales para diferentes secciones
$langController->loadTextDomain('navbar');
$langController->loadTextDomain('about');
$langController->loadTextDomain('fungi');
$langController->loadTextDomain('admin');
$langController->loadTextDomain('user');
$langController->loadTextDomain('api');

// Obtenemos la URI desde la solicitud
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
// Si la URI está vacía, la tratamos como la raíz
$uri = $uri === '' ? '/' : $uri;
if (!$isApiRequest) (new \App\Controllers\DebugController())->mostrarDebugTraducciones();

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host;
}

// Lista de componentes reutilizables
$components = [
    'footer' => 'components/footer.twig',
    'header' => 'components/header.twig',
    'navbar' => 'components/navbar.twig',
    'sidebar' => 'components/sidebar.twig',
    'fungi/card' => 'components/fungi/card.twig',
    'fungi/form' => 'components/fungi/form.twig'
];

/**
 * Renderiza una plantilla con los datos proporcionados
 * 
 * @param string $templatePath Ruta de la plantilla
 * @param array $data Datos para pasar a la plantilla
 * @return void
 */
function renderTemplate($templatePath, $data = []) {
    global $twig, $uri, $components, $session, $langController;
    
    // NUEVO: Determinar qué dominio usar según la plantilla
    $originalDomain = textdomain(NULL);
    
    if (strpos($templatePath, 'navbar') !== false) textdomain('navbar');
    else if (strpos($templatePath, 'about') !== false) textdomain('about');
    else textdomain('messages');
    
    // Aseguramos que los componentes estén disponibles en todas las plantillas
    $data['components'] = $components;
    
    // Agregamos información sobre la sesión actual si está disponible
    if (isset($session)) {
        $data['is_logged_in'] = $session->isLoggedIn();
        if ($session->isLoggedIn()) $data['user'] = $session->getUserData();
    } else $data['is_logged_in'] = false;
    
    // Añadimos la ruta actual para poder marcar elementos activos en el menú
    $data['current_route'] = $uri;
    
    // NUEVO: Asegurar que se pase el idioma actual a todas las plantillas
    $data['idioma_actual'] = $_SESSION['idioma'] ?? 'es';
    
    // Renderizamos la plantilla
    try {
        $result = $twig->render($templatePath, $data);
        
        // Restaurar el dominio original
        textdomain($originalDomain);
        
        echo $result;
    } catch (Exception $e) {
        // Si hay un error al renderizar, mostramos un mensaje de error
        echo "<h1>Error al renderizar la plantilla</h1>";
        echo "<p>{$e->getMessage()}</p>";
        
        // En modo desarrollo, mostramos información detallada del error
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            echo "<pre>";
            print_r($e);
            echo "</pre>";
        }
    }
}

// Inicializar controladores
$db = new \App\Controllers\DatabaseController();
$session = new \App\Controllers\SessionController($db);
$authController = new \App\Controllers\AuthController($db, $session);
$userController = new \App\Controllers\UserController($db, $session);
$fungiController = new \App\Controllers\FungiController($db);
$statsController = new \App\Controllers\StatsController($db);
$docsController = new \App\Controllers\DocsController($db, $session);
$homeController = new \App\Controllers\HomeController($db);

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
    '/profile/([^/]+)' => [ 'redirect' => '/profile' ],
    '/profile' => ['template' => 'pages/profile.twig', 'auth_required' => true, 'handler' => [$userController, 'profileHandler']],
    '/docs/api' => ['template' => 'pages/api/api_docs.twig', 'auth_required' => false, 'handler' => [$docsController, 'apiDocsHandler']],
    '/change-language' => ['handler' => [$langController, 'changeLanguage']],
    '/register' => ['template' => 'components/auth/register_form.twig', 'auth_required' => false, 'handler' => [$authController, 'registerHandler']],
    '/logout' => ['handler' => [$authController, 'logoutAndRedirect']],
    '/statistics' => ['template' => 'pages/statistics.twig', 'title' => _('Estadísticas'), 'handler' => [$statsController, 'statisticsPageHandler']],
    '/fungi/random' => ['template' => 'pages/fungi_detail.twig', 'title' => _('Hongo aleatorio'), 'auth_required' => false, 'handler' => [$fungiController, 'randomFungusHandler']],
    '/' => ['template' => 'pages/home.twig', 'title' => _('Hongos'), 'auth_required' => false, 'handler' => [$homeController, 'indexHandler']],
    '/login' => [
        'template' => 'components/auth/login_form.twig',
        'title' => _('Iniciar Sesión'),
        'auth_required' => false,
        'handler' => function($twig, $db, $session, $authController) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $authController->login(
                    $_POST['username'] ?? '',
                    $_POST['password'] ?? ''
                );
                
                if ($result['success']) {
                    header('Location: /');
                    exit;
                } else {
                    return [
                        'title' => _('Iniciar Sesión'),
                        'error' => $result['message']
                    ];
                }
            } else {
                $registered = isset($_GET['registered']) ? true : false;
                return [
                    'title' => _('Iniciar Sesión'),
                    'success' => $registered ? _('Usuario registrado exitosamente. Por favor inicia sesión.') : null
                ];
            }
        }
    ],
    '/dashboard' => [
        'template' => 'pages/admin.twig',
        'title' => _('Administración'),
        'auth_required' => true,
        'admin_required' => true,
        'handler' => function($twig, $db, $session) {
            // Verificar si el usuario es administrador
            if (!$session->isAdmin()) {
                header('Location: /');
                exit;
            }
            
            return [
                'title' => _('Panel de Administración'),
                'stats' => $statsController->getDashboardStats()
            ];
        }
    ],
    '/fungi/(\d+)' => ['template' => 'pages/fungi_detail.twig', 'auth_required' => false, 'handler' => [$fungiController, 'detailFungusHandler']],
    '/admin/users' => ['template' => 'admin/users.twig', 'auth_required' => true, 'admin_required' => true, 'handler' => [$userController, 'adminUsersHandler']],
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
