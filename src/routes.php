<?php

// Obtenemos la URI desde la solicitud
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
// Si la URI está vacía, la tratamos como la raíz
$uri = $uri === '' ? '/' : $uri;

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
    'fungi/form' => 'components/fungi/form.twig',
    'ui/alert' => 'components/ui/alert.twig',
    'ui/modal' => 'components/ui/modal.twig',
    'ui/pagination' => 'components/ui/pagination.twig'
];

/**
 * Obtiene un componente por su nombre
 * 
 * @param string $componentName Nombre del componente
 * @return string|null Ruta de la plantilla del componente o null si no existe
 */
function getComponent($componentName) {
    global $components;
    return $components[$componentName] ?? null;
}

/**
 * Renderiza una plantilla con los datos proporcionados
 * 
 * @param string $templatePath Ruta de la plantilla
 * @param array $data Datos para pasar a la plantilla
 * @return void
 */
function renderTemplate($templatePath, $data = []) {
    global $twig, $uri, $components, $session;
    
    // Aseguramos que los componentes estén disponibles en todas las plantillas
    $data['components'] = $components;
    
    // Agregamos información sobre la sesión actual si está disponible
    if (isset($session)) {
        $data['is_logged_in'] = $session->isLoggedIn();
        if ($session->isLoggedIn()) {
            $data['user'] = $session->getUserData();
        }
    } else {
        $data['is_logged_in'] = false;
    }
    
    // Añadimos la ruta actual para poder marcar elementos activos en el menú
    $data['current_route'] = $uri;
    
    // Renderizamos la plantilla
    try {
        echo $twig->render($templatePath, $data);
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

// Definición de rutas y sus plantillas correspondientes
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
    '/index' => [
        'template' => 'pages/home.twig',
        'redirect' => '/'
    ],
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
    '/about' => [
        'template' => 'pages/about.twig',
        'title' => _('Acerca de'),
        'auth_required' => false
    ],

    '/profile' => [
        'template' => 'pages/profile.twig',
        'title' => _('Mi Perfil'),
        'auth_required' => true,
        'handler' => function($twig, $db, $session) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $db->updateUserProfile($_SESSION['user_id'], $_POST);
                return [
                    'title' => _('Mi Perfil'),
                    'user' => $db->getUserById($_SESSION['user_id']),
                    'message' => $result ? _('Perfil actualizado') : _('Error al actualizar')
                ];
            } else {
                return [
                    'title' => _('Mi Perfil'),
                    'user' => $db->getUserById($_SESSION['user_id'])
                ];
            }
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
        'auth_required' => true,
        'handler' => function($twig, $db, $session, $statsController = null) {
            // Verificar si existe la clase StatsController
            if (class_exists('\App\Controllers\StatsController')) {
                if (!$statsController) {
                    $statsController = new \App\Controllers\StatsController($db);
                }
                $stats = $statsController->getFungiStats();
            } else {
                // Datos de ejemplo si no existe el controlador
                $stats = [
                    'total' => $db->query("SELECT COUNT(*) as total FROM fungi")->fetch()['total'] ?? 0,
                    'by_category' => []
                ];
            }
            
            return [
                'title' => _('Estadísticas'),
                'stats' => $stats
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
            if (DEBUG_MODE) {
                echo "<pre>";
                var_dump($fungus);
                echo "</pre>";
            }
            return [
                'title' => _('Hongo aleatorio'),
                'fungi' => $fungus
            ];
        }
    ],
    '/admin' => [
        'template' => 'layouts/admin.twig',
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
                'stats' => [
                    'total_fungi' => $db->query("SELECT COUNT(*) as total FROM fungi")->fetch()['total'] ?? 0,
                    'total_users' => $db->query("SELECT COUNT(*) as total FROM users")->fetch()['total'] ?? 0
                ]
            ];
        }
    ],
    '/404' => [
        'template' => 'pages/404.twig',
        'title' => _('Página no encontrada'),
        'auth_required' => false
    ],
    '/docs/api' => [
        'template' => 'pages/api_docs.twig',
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
        'title' => _('Detalles del Hongo'),
        'auth_required' => false,
        'handler' => function($twig, $db, $session, $fungiController = null) {
            // Obtener el ID desde la URL actual
            $uri = $_SERVER['REQUEST_URI'];
            preg_match('/\/fungi\/(\d+)/', $uri, $matches);
            $id = (int)$matches[1];
            
            // Log para depuración
            error_log("Intentando acceder al hongo con ID: " . $id);
            
            $fungus = $db->getFungusById($id);
            
            // Log para verificar si se encontró el hongo
            error_log("Resultado de búsqueda: " . ($fungus ? "Encontrado" : "No encontrado"));
            
            if (!$fungus) {
                error_log("Redirigiendo a 404 porque no se encontró el hongo ID: " . $id);
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
                'similar_fungi' => $similarFungi
            ];
        }
    ],
    '/random' => [
        'template' => null,
        'redirect' => '/fungi/random'
    ],
];

// Manejo de rutas de API
if (preg_match('#^/api#', $uri)) {
    if (class_exists('\App\Controllers\ApiController')) {
        $apiController = new \App\Controllers\ApiController($db);
        $apiController->handleRequest();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API no implementada']);
    }
    exit;
}

// Procesamiento de rutas normales
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
    // Ruta no encontrada - usamos la ruta definida para 404
    header('HTTP/1.1 404 Not Found');
    
    // Obtener datos para la página 404
    $data = isset($routes['/404']['handler']) 
        ? $routes['/404']['handler']($twig, $db, $session, $authController ?? null) 
        : ['title' => _('Página no encontrada')];
        
    // Renderizar la plantilla 404
    renderTemplate($routes['/404']['template'], $data);
}
