<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri = rtrim($uri, '/');

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host;
}

// Ruteo básico
function getRouteTemplate($route) {
    $routesMap = [
        '/' => 'pages/home.twig',
        '/index' => 'pages/home.twig',
        '/login' => 'components/auth/login_form.twig',
        '/register' => 'components/auth/register_form.twig',
        '/about' => 'pages/about.twig',
        '/contact' => 'pages/contact.twig',
        '/terms' => 'pages/terms.twig',
        '/faq' => 'pages/faq.twig',
        '/profile' => 'pages/profile.twig',
        '/favorites' => 'pages/favorites.twig',
        '/statistics' => 'pages/statistics.twig',
        '/admin' => 'layouts/admin.twig',
        '/fungus' => 'components/fungi/detail.twig',
        '/random' => 'pages/random_fungi.twig',
        '/404' => 'pages/404.twig',
        '/docs/api' => 'pages/docs/api_docs.twig',
    ];

    return $routesMap[$route] ?? null;
}

function getRouteComponents($route) {
    $routesMap = [
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

    return $routesMap[$route] ?? null;
}

function renderTemplate($route, $data = []) {
    global $twig; // Hacer que la variable $twig sea accesible
    
    // Obtener la ruta de la plantilla basada en la ruta proporcionada
    $templatePath = getRouteTemplate($route);
    print_r($route);
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
            'title' => _('Hongos'),
            //'fungis' => $fungiController->getFungisPaginated(20, 0)
        ]);
        break;
    case '/docs':
        renderTemplate('/docs/api', [
            'title' => _('Documentación de API')
        ]);
        break;
    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $authController->handleRegistration($_POST, $twig);
        } else {
            renderTemplate('/register', [
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
                renderTemplate('/login', [
                    'title' => _('Iniciar Sesión'),
                    'error' => $result['message']
                ]);
            }
        } else {
            $registered = isset($_GET['registered']) ? true : false;
            renderTemplate('/login', [
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
        renderTemplate('/random', [
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
            renderTemplate('/profile', [
                'title' => 'Mi Perfil',
                'user' => $db->getUserById($_SESSION['user_id']),
                'message' => $result ? 'Perfil actualizado' : 'Error al actualizar'
            ]);
        } else {
            renderTemplate('/profile', [
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
        
        renderTemplate('/favorites', [
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
        renderTemplate('/admin', ['title' => 'Admin']); 
        break;

    case '/reset_password': 
        renderTemplate('/reset_password', ['title' => 'Recuperar contraseña']); 
        break;

    case '/terms': 
        renderTemplate('/terms', ['title' => 'Términos y condiciones']); 
        break;

    case '/faq': 
        renderTemplate('/faq', ['title' => 'Preguntas frecuentes']); 
        break;

    case '/statistics':
        print_r('statistics');
        die();
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
