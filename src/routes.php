<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Eliminar la barra final si existe
$uri = rtrim($uri, '/');

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

// Separar las rutas de API en su propio bloque

switch (true) {
    case preg_match('#^/api#', $uri):
        $apiController = new \App\Controllers\ApiController($db);
        $apiController->handleRequest();
        break;
    case '':
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
    default:
        header('HTTP/1.1 404 Not Found');
        renderTemplate('/404', ['title' => _('Página no encontrada')]);
        break;
}
