<?php

// Añadir en la parte superior del archivo
require_once __DIR__ . '/controllers/StatsController.php';

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

// SOLO mostrar depuración si NO es una solicitud API
if (!$isApiRequest) {
    // Al inicio del archivo, después de las primeras líneas
    // Depuración de archivos de traducción
    $idioma_actual = $_SESSION['idioma'] ?? 'ca';
    $dominios = ['messages', 'navbar', 'about'];
    echo "<!-- DEBUG TRADUCCIONES: -->\n";
    echo "<!-- Idioma actual: $idioma_actual -->\n";

    foreach ($dominios as $dominio) {
        $ruta_po = __DIR__ . "/../locales/{$idioma_actual}_ES/LC_MESSAGES/{$dominio}.po";
        $ruta_mo = __DIR__ . "/../locales/{$idioma_actual}_ES/LC_MESSAGES/{$dominio}.mo";
        
        echo "<!-- Dominio $dominio: -->\n";
        echo "<!--   .po: " . (file_exists($ruta_po) ? "EXISTE" : "NO EXISTE") . " -->\n";
        echo "<!--   .mo: " . (file_exists($ruta_mo) ? "EXISTE" : "NO EXISTE") . " -->\n";
        
        if (file_exists($ruta_mo)) {
            // Prueba la traducción
            echo "<!--   Prueba: " . dgettext($dominio, $dominio === 'navbar' ? 'Inicio' : 'Acerca de Nosotros') . " -->\n";
        }
    }
}

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
$userController = new \App\Controllers\UserController($db, $session, $authController);
$fungiController = new \App\Controllers\FungiController($db);

// Definir rutas
$routes = [
    '/' => [
        'template' => 'pages/home.twig',
        'title' => _('Hongos'),
        'auth_required' => false,
        'handler' => function($twig, $db, $session, $fungiController = null) {
            // Ya no obtenemos los hongos directamente, se cargarán vía AJAX
            return [
                'title' => _('Hongos'),
                'api_url' => getBaseUrl() . '/api/fungi/page/1/limit/12', // URL para la solicitud AJAX
                'use_ajax' => true  // Indicador para la plantilla
            ];
        }
    ],
    '/index' => ['template' => 'pages/home.twig', 'redirect' => '/'],
    '/about' => ['template' => 'pages/about.twig', 'title' => _('Acerca de'), 'auth_required' => false],
    '/404' => ['template' => 'pages/404.twig', 'title' => _('Página no encontrada'), 'auth_required' => false],
    '/random' => ['template' => null, 'redirect' => '/fungi/random'],
    '/home' => ['template' => null, 'redirect' => '/'],
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
    '/register' => [
        'template' => 'components/auth/register_form.twig',
        'title' => _('Registro'),
        'auth_required' => false,
        'handler' => function($twig, $db, $session, $authController) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $authController->register(
                    $_POST['username'] ?? '',
                    $_POST['email'] ?? '',
                    $_POST['password'] ?? '',
                    $_POST['confirm_password'] ?? ''
                );
                
                if ($result['success']) {
                    header('Location: /login?registered=1');
                    exit;
                } else {
                    return [
                        'title' => _('Registro'),
                        'error' => $result['message']
                    ];
                }
            } else {
                return [
                    'title' => _('Registro')
                ];
            }
        }
    ],
    
    '/profile' => [
        'template' => 'pages/profile.twig',
        'title' => _('Mi Perfil'),
        'auth_required' => true,
        'handler' => function($twig, $db, $session) {
            if (!$session->isLoggedIn()) {
                header('Location: /login');
                exit;
            }
            
            $userId = $_SESSION['user_id'];
            
            // Obtener información del usuario
            $user = $db->query("SELECT * FROM users WHERE id = ?", [$userId])[0] ?? null;
            
            if (!$user) {
                header('Location: /login');
                exit;
            }
            
            // Obtener estadísticas del usuario (estas se mostrarán de inmediato)
            $stats = $db->query("
                SELECT 
                    (SELECT COUNT(*) FROM user_likes WHERE user_id = ?) as likes_count,
                    (SELECT COUNT(*) FROM user_favorites WHERE user_id = ?) as favorites_count,
                    (SELECT COUNT(*) FROM comments WHERE user_id = ?) as comments_count,
                    (SELECT COUNT(*) FROM contributions WHERE user_id = ?) as contributions_count
            ", [$userId, $userId, $userId, $userId])[0] ?? [];
            
            // Últimas acciones (ejemplo simplificado)
            $actions = $db->query("
                SELECT a.*, 'check' as icon, 'primary' as color
                FROM access_logs a
                WHERE a.user_id = ?
                ORDER BY a.access_time DESC
                LIMIT 5
            ", [$userId]);
            
            // Añadir descripciones a las acciones
            foreach ($actions as &$action) {
                $action['description'] = match($action['action']) {
                    'login' => 'Inicio de sesión',
                    'logout' => 'Cierre de sesión',
                    'view_fungi' => 'Visualización de hongo',
                    'like_fungi' => 'Me gusta en hongo',
                    'favorite_fungi' => 'Añadido a favoritos',
                    'edit_fungi' => 'Edición de hongo',
                    default => 'Acción no reconocida'
                };
                $action['date'] = date('d/m/Y H:i', strtotime($action['access_time']));
            }
            
            // Actualizar el usuario con los contadores
            $user['likes_count'] = $stats['likes_count'] ?? 0;
            $user['favorites_count'] = $stats['favorites_count'] ?? 0;
            $user['comments_count'] = $stats['comments_count'] ?? 0;
            $user['contributions_count'] = $stats['contributions_count'] ?? 0;
            
            return [
                'title' => 'Perfil de ' . $user['username'],
                'user' => $user,
                'user_actions' => $actions,
                'is_own_profile' => true
            ];
        }
    ],
    '/logout' => [
        'template' => null,
        'auth_required' => false,
        'handler' => function($twig, $db, $session, $authController) {
            session_start();
            session_destroy();
            setcookie("token", "", time() - 3600, "/");
            header('Location: /');
            exit;
        }
    ],
    '/statistics' => [
        'template' => 'pages/statistics.twig',
        'title' => _('Estadísticas'),
        'handler' => function($twig, $db, $session) {
            $statsController = new \App\Controllers\StatsController($db);
            
            // Obtener conteo total de hongos
            $totalFungi = $db->query("SELECT COUNT(*) as total FROM fungi")->fetch()['total'] ?? 0;
            
            // Obtener estadísticas de comestibilidad
            $edibilityStats = $statsController->getEdibilityStats();
            
            // Mapear las estadísticas de comestibilidad al formato esperado por la plantilla
            $edibilityFormatted = [
                'edible_fungi' => 0,
                'non_edible_fungi' => 0,
                'toxic_fungi' => 0,
                'unknown_edibility_fungi' => 0
            ];
            
            foreach ($edibilityStats as $stat) {
                if ($stat['edibility'] === 'edible') {
                    $edibilityFormatted['edible_fungi'] = $stat['count'];
                } else if ($stat['edibility'] === 'non_edible') {
                    $edibilityFormatted['non_edible_fungi'] = $stat['count'];
                } else if ($stat['edibility'] === 'toxic') {
                    $edibilityFormatted['toxic_fungi'] = $stat['count'];
                } else {
                    $edibilityFormatted['unknown_edibility_fungi'] = $stat['count'];
                }
            }
            
            // Obtener datos para las demás secciones
            $topFamilies = $statsController->getTopFamilies(10);
            $mostViewed = $statsController->getMostViewedFungi(5);
            $topFavorites = $statsController->getTopFavorites(5);
            $topLiked = $statsController->getTopLiked(5);
            $trends = $statsController->getAdditionTrends();
            $habitats = $statsController->getHabitatDistribution();
            
            // Combinar todos los datos para la plantilla
            return [
                'title' => _('Estadísticas'),
                'stats' => array_merge($edibilityFormatted, [
                    'total_fungi' => $totalFungi,
                    'total_users' => $db->query("SELECT COUNT(*) as total FROM users")->fetch()['total'] ?? 0,
                    'top_families' => $topFamilies,
                    'most_viewed' => $mostViewed,
                    'top_favorites' => $topFavorites,
                    'top_liked' => $topLiked,
                    'trends' => $trends,
                    'habitats' => $habitats
                ])
            ];
        }
    ],
    '/fungi/random' => [
        'template' => 'pages/fungi_detail.twig',
        'title' => _('Hongo aleatorio'),
        'auth_required' => false,
        'handler' => function($twig, $db, $session, $fungiController = null) {
            $fungus = $db->getRandomFungus();
            if ($session->isLoggedIn() && method_exists($fungiController, 'getFungusWithLikeStatus')) {
                $fungus = $fungiController->getFungusWithLikeStatus($fungus, $_SESSION['user_id']);
            }
            // Añadir esta línea para depuración
            error_log("Cargando hongo aleatorio: " . json_encode($fungus['name'] ?? 'No encontrado'));
            // print_r($fungus);
            return [
                'title' => _('Hongo aleatorio'),
                'fungi' => $fungus
            ];
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
            
            // Crear instancia del controlador de estadísticas
            $statsController = new \App\Controllers\StatsController($db);
            
            // Obtener actividad reciente de usuarios desde la base de datos
            $queryResult = $db->query(
                "SELECT u.username, a.action, a.item, a.access_time as date 
                 FROM access_logs a
                 JOIN users u ON a.user_id = u.id
                 ORDER BY a.access_time DESC
                 LIMIT 10"
            );
            $recentActivity = $queryResult ? $queryResult->fetchAll(\PDO::FETCH_ASSOC) : [];
            
            // Obtener estadísticas generales
            $fungiStats = $statsController->getFungiStats('all');
            
            return [
                'title' => _('Panel de Administración'),
                'stats' => [
                    'total_fungi' => $db->query("SELECT COUNT(*) as total FROM fungi")->fetch()['total'] ?? 0,
                    'total_users' => $db->query("SELECT COUNT(*) as total FROM users")->fetch()['total'] ?? 0,
                    'recent_activity' => $recentActivity,
                    'popular_fungi' => $fungiStats['popular'] ?? [],
                    'edibility_stats' => $fungiStats['edibility'] ?? [],
                    'family_stats' => $fungiStats['families'] ?? []
                ]
            ];
        }
    ],
    '/docs/api' => [
        'template' => 'pages/api/api_docs.twig',
        'title' => _('Documentación de la API'),
        'auth_required' => false,
        'handler' => function($twig, $db, $session) {
            // Obtener la documentación de la API directamente desde el endpoint /api
            $apiUrl = getBaseUrl() . '/api';
            
            // Configurar opciones para la petición
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3, // Timeout de 3 segundos
                    'ignore_errors' => true
                ]
            ]);
            
            $apiResponse = @file_get_contents($apiUrl, false, $context);
            $apiDocs = json_decode($apiResponse, true);
            
            if (!$apiDocs) {
                // Si hay un error al obtener los datos, usar información básica
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
    ],
    '/fungi/(\d+)' => [
        'template' => 'pages/fungi_detail.twig',
        'handler' => function($twig, $db, $session, $authController = null) use ($fungiController) {
            // Obtener el ID del hongo desde la URL
            preg_match('#^/fungi/(\d+)$#', $_SERVER['REQUEST_URI'], $matches);
            $id = $matches[1] ?? null;
            
            // Obtener datos del hongo específico
            $fungus = $db->getFungusById($id);
            
            // Si no se encuentra el hongo, redirigir a 404
            if (!$fungus) {
                header('Location: /404');
                exit;
            }
            
            // Añadir información de favorito si el usuario está logueado
            if ($session->isLoggedIn() && $fungiController) {
                $fungus = $fungiController->getFungusWithLikeStatus($fungus, $_SESSION['user_id']);
            }
            
            // Intentar obtener hongos similares
            $similarFungi = [];
            try {
                $similarFungi = $db->getSimilarFungi($id, 4);
            } catch (Exception $e) {
                // Si falla, simplemente no mostraremos hongos similares
            }
            
            return [
                'title' => $fungus['name'] ?? _('Detalles del Hongo'),
                'fungi' => $fungus,
                'similar_fungi' => $similarFungi,
                'is_logged_in' => $session->isLoggedIn()
            ];
        }
    ],
    '/change-language' => [
        'template' => null,
        'auth_required' => false,
        'handler' => function($twig, $db, $session) {
            $langController = new \App\Controllers\LangController();
            return $langController->changeLanguage();
        }
    ],
    // Ruta para ver perfil por nombre de usuario
    '/profile/([^/]+)' => [
        'template' => 'pages/profile.twig',
        'title' => _('Perfil de Usuario'),
        'auth_required' => false,
        'handler' => function($twig, $db, $session, $authController = null) {
            // Obtener el nombre de usuario desde la URL
            preg_match('#^/profile/([^/]+)$#', $_SERVER['REQUEST_URI'], $matches);
            $username = $matches[1] ?? null;
            
            // Obtener datos del usuario solicitado por nombre de usuario
            $profileUserData = $db->getUserByUsername($username);
            
            // Si no se encuentra el usuario, mostrar mensaje de error
            if (!$profileUserData) {
                return [
                    'title' => _('Usuario no encontrado'),
                    'error' => _('El usuario solicitado no existe'),
                    'user_not_found' => true
                ];
            }
            
            // Obtener el ID del usuario actual (si está autenticado)
            $currentUserId = $session->isLoggedIn() ? $_SESSION['user_id'] : null;
            
            // Obtener datos públicos del perfil
            $favoriteFungi = $db->getUserFavorites($profileUserData['id']);
            $contributions = $db->getUserContributions($profileUserData['id']);
            
            return [
                'title' => sprintf(_('Perfil de %s'), $profileUserData['username']),
                'user' => $profileUserData,
                'favorite_fungi' => $favoriteFungi,
                'contributions' => $contributions,
                'is_own_profile' => ($currentUserId == $profileUserData['id'])
            ];
        }
    ],
];

// Rutas relacionadas con usuarios
$routes['/profile'] = [ 'handler' => [$userController, 'profileHandler'], 'template' => 'pages/profile.twig' ];
$routes['/admin/users'] = [ 'handler' => [$userController, 'adminUsersHandler'], 'template' => 'admin/users.twig' ];

// Manejo de rutas de API
if (preg_match('#^/api#', $uri)) {
    // IMPORTANTE: Asegurarse de que no hay salidas previas antes del JSON
    ob_clean(); // Limpia el buffer de salida
    header('Content-Type: application/json');
    
    if (class_exists('\App\Controllers\Api\ApiRequestHandler')) {
        $db = new \App\Controllers\DatabaseController();
        $apiHandler = new \App\Controllers\Api\ApiRequestHandler($db);
        $apiHandler->handleRequest();
    } else if (class_exists('\App\Controllers\ApiController')) {
        // Fallback al controlador original si el nuevo no está disponible
        $apiController = new \App\Controllers\ApiController($db);
        $apiController->handleRequest();
    } else {
        echo json_encode(['error' => 'API no implementada']);
    }
    exit;
}

// Procesamiento de rutas

// 1. Intentar coincidencia exacta primero
if (isset($routes[$uri])) {
    $route = $routes[$uri];
    
    // Si la ruta tiene una redirección configurada
    if (isset($route['redirect'])) {
        header('Location: ' . $route['redirect']);
        exit;
    }
    
    // Verificar si se requiere autenticación para esta ruta
    if (isset($route['auth_required']) && $route['auth_required'] && !$session->isLoggedIn()) {
        header('Location: /login');
        exit;
    }
    
    // Verificar si se requiere rol de administrador
    if (isset($route['admin_required']) && $route['admin_required'] && !$session->isAdmin()) {
        header('Location: /');
        exit;
    }
    
    // Obtener datos para la vista usando el manejador personalizado
    $data = isset($route['handler']) ? $route['handler']($twig, $db, $session, $authController ?? null) : [];
    
    // Si no hay datos pero hay título, usar el título como datos
    if (empty($data) && isset($route['title'])) {
        $data = ['title' => $route['title']];
    }
    
    // Renderizar la plantilla
    if (isset($route['template']) && $route['template'] !== null) {
        renderTemplate($route['template'], $data);
    }
} else {
    // 2. Intentar coincidencia con patrones
    $patternMatched = false;
    
    foreach ($routes as $pattern => $route) {
        // Si el patrón contiene caracteres especiales como paréntesis (indicando expresión regular)
        if (strpos($pattern, '(') !== false) {
            if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                // Verificar si se requiere autenticación para esta ruta
                if (isset($route['auth_required']) && $route['auth_required'] && !$session->isLoggedIn()) {
                    header('Location: /login');
                    exit;
                }
                
                // Verificar si se requiere rol de administrador
                if (isset($route['admin_required']) && $route['admin_required'] && !$session->isAdmin()) {
                    header('Location: /');
                    exit;
                }
                
                // Obtener datos para la vista usando el manejador personalizado
                $data = isset($route['handler']) ? $route['handler']($twig, $db, $session, $authController ?? null) : [];
                
                // Si no hay datos pero hay título, usar el título como datos
                if (empty($data) && isset($route['title'])) {
                    $data = ['title' => $route['title']];
                }
                
                // Renderizar la plantilla
                if (isset($route['template']) && $route['template'] !== null) {
                    renderTemplate($route['template'], $data);
                }
                
                $patternMatched = true;
                break;
            }
        }
    }
    
    // 3. Si no se encontró ninguna coincidencia, mostrar 404
    if (!$patternMatched) {
        header('HTTP/1.1 404 Not Found');
        
        // Obtener datos para la página 404
        $data = isset($routes['/404']['handler']) 
            ? $routes['/404']['handler']($twig, $db, $session, $authController ?? null) 
            : ['title' => _('Página no encontrada')];
            
        // Renderizar la plantilla 404
        renderTemplate($routes['/404']['template'], $data);
    }
}
