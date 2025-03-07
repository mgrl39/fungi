<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host;
}

// Ruteo básico
function getRouteTemplate($route) {
    $routesMap = [
        '/' => 'fungi/fungi_list.twig',
        '/index' => 'fungi/fungi_list.twig', 
        '/login' => 'auth/login.twig',
        '/register' => 'auth/register.twig',
        '/about' => 'pages/about.twig',
        '/contact' => 'pages/contact.twig',
        '/terms' => 'legal/terms.twig',
        '/faq' => 'faq.twig',
        '/profile' => 'auth/profile.twig',
        '/favorites' => 'favorites.twig',
        '/statistics' => 'pages/statistics.twig',
        '/admin' => 'admin.twig',
        '/fungus' => 'fungi/fungus_detail.twig',
        '/random' => 'fungi/random_fungi.twig',
        '/404' => 'errors/404.twig',
    ];

    return $routesMap[$route] ?? null;
}

function getRouteComponents($route) {
    $routesMap = [
        'footer' => 'components/footer.twig',
        'header' => 'components/header.twig',
        'random_fungi' => 'fungi/random_fungi.twig',
    ];

    return $routesMap[$route] ?? null;
}

function renderTemplate($route, $data = []) {
    global $twig; // Hacer que la variable $twig sea accesible
    
    // Obtener la ruta de la plantilla basada en la ruta proporcionada
    $templatePath = getRouteTemplate($route);
    
    if ($templatePath) {
        echo $twig->render($templatePath, $data);
    } else {
        // Si la ruta no existe en el mapa, verificar si es una ruta de componente
        $componentPath = getRouteComponents($route);
        if ($componentPath) {
            echo $twig->render($componentPath, $data);
        } else {
            // Si no existe ni como ruta ni como componente, mostrar 404
            echo $twig->render(getRouteTemplate('/404'), ['title' => _('Página no encontrada')]);
        }
    }
}

switch ($uri) {
    case '/':
        case '/index':
            renderTemplate('/', [
                'title' => _('Todos los Fungis'),
                'fungis' => $db->getFungisPaginated(20, 0),
                'session' => $session
            ]);
            break;
    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $authController->handleRegistration($_POST, $twig);
        } else {
            renderTemplate('auth/register.twig', [
                'title' => _('Registro')
            ]);
        }
        break;

    case '/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $authController->login(
                $_POST['username'] ?? '',
                $_POST['password'] ?? ''
            );
            
            if ($result['success']) {
                header('Location: /');
                exit;
            } else {
                renderTemplate('login', [
                    'title' => _('Iniciar Sesión'),
                    'error' => $result['message']
                ]);
            }
        } else {
            $registered = isset($_GET['registered']) ? true : false;
            renderTemplate('login', [
                'title' => _('Iniciar Sesión'),
                'success' => $registered ? _('Usuario registrado exitosamente. Por favor inicia sesión.') : null
            ]);
        }
        break;



    case '/fungus':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        // Consumir la API interna usando la URL base dinámica
        $baseUrl = getBaseUrl();
        $apiResponse = file_get_contents("{$baseUrl}/api/fungi/{$id}");
        $responseData = json_decode($apiResponse, true);
        
        if ($responseData['success']) {
            $fungus = $responseData['data'];
            // Incrementar las vistas del hongo
            $fungiController->incrementFungiViews($id);
            
            renderTemplate('fungi/fungus_detail.twig', [
                'title' => $fungus['name'],
                'fungus' => $fungus
            ]);
        } else {
            renderTemplate('404', ['title' => _('Fungus no encontrado')]);
        }
        break;

    case '/fungus/like':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $session->isLoggedIn()) {
            $fungiId = $_POST['fungi_id'] ?? 0;
            $fungiController->likeFungi($_SESSION['user_id'], $fungiId);
            header('Location: /fungus?id=' . $fungiId);
            exit;
        }
        break;

    case preg_match('#^/api(/.*)?$#', $uri) ? true : false:
        header('Content-Type: application/json');
        
        // Extraer la ruta de la API
        $apiPath = str_replace('/api', '', $uri);
        
        // Manejar rutas de la API
        switch ($apiPath) {
            // Obtener todos los hongos (paginado)
            case '/fungi':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
                    $offset = ($page - 1) * $limit;
                    
                    $fungi = $db->getFungisPaginated($limit, $offset);
                    $total = $db->getTotalFungi();
                    
                    echo json_encode([
                        'data' => $fungi,
                        'pagination' => [
                            'total' => $total,
                            'page' => $page,
                            'limit' => $limit,
                            'pages' => ceil($total / $limit)
                        ]
                    ]);
                }
                break;
                
            // Obtener un hongo específico
            case preg_match('#^/fungi/(\d+)$#', $apiPath, $matches) ? true : false:
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $id = (int)$matches[1];
                    $fungus = $db->getFungusById($id);
                    
                    if ($fungus) {
                        echo json_encode([
                            'data' => $fungus,
                            'success' => true
                        ]);
                    } else {
                        http_response_code(404);
                        echo json_encode([
                            'error' => 'Hongo no encontrado',
                            'success' => false
                        ]);
                    }
                }
                break;
                
            // Obtener hongo aleatorio
            case '/fungi/random':
                if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    $fungus = $db->getRandomFungus();
                    if ($session->isLoggedIn()) {
                        $fungus = $fungiController->getFungusWithLikeStatus($fungus, $_SESSION['user_id']);
                    }
                    echo json_encode([
                        'data' => $fungus,
                        'success' => true
                    ]);
                }
                break;
                
            // Manejar likes de hongos
            case '/fungi/like':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && $session->isLoggedIn()) {
                    $fungiId = $_POST['fungi_id'] ?? 0;
                    $result = $fungiController->likeFungi($_SESSION['user_id'], $fungiId);
                    echo json_encode([
                        'success' => $result,
                        'message' => $result ? 'Like agregado exitosamente' : 'Error al agregar like'
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode([
                        'error' => 'Usuario no autorizado',
                        'success' => false
                    ]);
                }
                break;
                
            default:
                http_response_code(404);
                echo json_encode([
                    'error' => 'Endpoint no encontrado',
                    'success' => false
                ]);
                break;
        }
        exit;
        break;

    case '/random':
        $fungus = $db->getRandomFungus();
        if ($session->isLoggedIn()) {
            $fungus = $fungiController->getFungusWithLikeStatus($fungus, $_SESSION['user_id']);
        }
        renderTemplate('fungi/random_fungi.twig', [
            'title' => _('Hongo aleatorio'),
            'fungus' => $fungus,
            'session' => $session
        ]);
        break;

    case '/profile':
        if (!$session->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $db->updateUserProfile($_SESSION['user_id'], $_POST);
            renderTemplate('profile.twig', [
                'title' => 'Mi Perfil',
                'user' => $db->getUserById($_SESSION['user_id']),
                'message' => $result ? 'Perfil actualizado' : 'Error al actualizar'
            ]);
        } else {
            renderTemplate('profile.twig', [
                'title' => 'Mi Perfil',
                'user' => $db->getUserById($_SESSION['user_id'])
            ]);
        }
        break;

    case '/favorites':
        if (!$session->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        
        renderTemplate('favorites.twig', [
            'title' => 'Mis Hongos Favoritos',
            'fungi' => $db->getUserFavorites($_SESSION['user_id'])
        ]);
        break;

    case '/api/favorites':
        if (!$session->isLoggedIn()) {
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fungiId = $_POST['fungi_id'] ?? 0;
            $action = $_POST['action'] ?? '';
            
            if ($action === 'add') {
                $result = $db->addFavorite($_SESSION['user_id'], $fungiId);
            } else if ($action === 'remove') {
                $result = $db->removeFavorite($_SESSION['user_id'], $fungiId);
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $result]);
        }
        break;

    case '/logout':
        session_start();
        session_destroy();
        header('Location: /');
        exit;
        break;

    case '/about':
        renderTemplate('/about', ['title' => 'Acerca de']);
        break;

    case '/contact':
        renderTemplate('/contact', ['title' => 'Contacto']);
        break;

    case '/admin': 
        renderTemplate('admin.twig', ['title' => 'Admin']); 
        break;

    case '/reset_password': 
        renderTemplate('reset_password.twig', ['title' => 'Recuperar contraseña']); 
        break;

    case '/terms': 
        renderTemplate('/terms', ['title' => 'Términos y condiciones']); 
        break;

    case '/faq': 
        renderTemplate('faq.twig', ['title' => 'Preguntas frecuentes']); 
        break;

    case '/statistics':
        if (!$session->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        
        $stats = $statsController->getFungiStats();
        renderTemplate('/statistics', [
            'title' => _('Estadísticas'),
            'stats' => $stats
        ]);
        break;

    case '/api/stats':
        if (!$session->isLoggedIn()) {
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }

        header('Content-Type: application/json');
        
        // Obtener el rango temporal del query parameter
        $timeRange = $_GET['timeRange'] ?? 'all';
        
        // Obtener estadísticas filtradas
        $stats = $statsController->getFungiStats($timeRange);
        echo json_encode($stats);
        break;

    case '/api/load':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 20; // Cantidad de registros por "página"
            $offset = ($page - 1) * $limit;
            $fungis = $db->getFungisPaginated($limit, $offset);
            header('Content-Type: application/json');
            echo json_encode($fungis);
            exit;
        }
        break;

    default:
        header('HTTP/1.1 404 Not Found');
        renderTemplate('/404', ['title' => _('Página no encontrada')]);
        break;
}
