<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/controllers/DatabaseController.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/SessionController.php';

// Configuración de internacionalización
$locale = 'es_ES.UTF-8';
putenv("LC_ALL=$locale");
setlocale(LC_ALL, $locale);
bindtextdomain('messages', __DIR__ . '/../locale');
textdomain('messages');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Inicializar controladores
$db = new DatabaseController();
$authController = new AuthController($db);
$session = new SessionController($db);

// Configurar Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

// Agregar variable global para el tema
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
$twig->addGlobal('theme', $theme);

// Manejar la acción de carga asíncrona (infinite scroll)
if (isset($_GET['action']) && $_GET['action'] == 'load') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20; // Cantidad de registros por "página"
    $offset = ($page - 1) * $limit;
    $fungis = $db->getFungisPaginated($limit, $offset);
    header('Content-Type: application/json');
    echo json_encode($fungis);
    exit;
}

// Obtener la ruta solicitada
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Ruteo básico
switch ($uri) {
    case '/register':
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
                echo $twig->render('register.twig', [
                    'title' => _('Registro'),
                    'error' => $result['message']
                ]);
            }
        } else {
            echo $twig->render('register.twig', [
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
                echo $twig->render('login.twig', [
                    'title' => _('Iniciar Sesión'),
                    'error' => $result['message']
                ]);
            }
        } else {
            $registered = isset($_GET['registered']) ? true : false;
            echo $twig->render('login.twig', [
                'title' => _('Iniciar Sesión'),
                'success' => $registered ? _('Usuario registrado exitosamente. Por favor inicia sesión.') : null
            ]);
        }
        break;

    // Página principal (listing de fungis)
    case '/':
    case '/index':
        $fungis = $db->getFungisPaginated(20, 0);
        echo $twig->render('fungi_list.twig', [
            'title' => _('Todos los Fungis'),
            'fungis' => $fungis
        ]);
        break;

    // Página de detalle para un hongo
    case '/fungus':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $fungus = $db->getFungusById($id);
        if (!$fungus) {
            echo $twig->render('404.twig', ['title' => _('Fungus no encontrado')]);
        } else {
            echo $twig->render('fungus_detail.twig', [
                'title' => $fungus['name'],
                'fungus' => $fungus
            ]);
        }
        break;
	
	case preg_match('#^/api(/.*)?$#', $uri) ? true : false:
		// Redirigir todas las solicitudes a /api/ hacia api.php
		require_once __DIR__ . '/api.php';
        break;

    case '/random':
            $fungus = $db->getRandomFungus();
            // Debug: Verificar el valor de $fungus
            echo $twig->render('random_fungi.twig', [
                'title' => _('Hongo aleatorio'),
                'fungus' => $fungus
            ]);
            break;
    case '/profile':
        if (!$session->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $db->updateUserProfile($_SESSION['user_id'], $_POST);
            echo $twig->render('profile.twig', [
                'title' => 'Mi Perfil',
                'user' => $db->getUserById($_SESSION['user_id']),
                'message' => $result ? 'Perfil actualizado' : 'Error al actualizar'
            ]);
        } else {
            echo $twig->render('profile.twig', [
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
        
        echo $twig->render('favorites.twig', [
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
        // Página de "Acerca de"
        echo $twig->render('about.twig', ['title' => 'Acerca de']);
        break;
    case '/contact':
        // Página de contacto
        echo $twig->render('contact.twig', ['title' => 'Contacto']);
        break;
    // Otras rutas...
    case '/admin': 
        echo $twig->render('admin.twig', ['title' => 'Admin']); 
        break;
    case '/reset_password': 
        echo $twig->render('reset_password.twig', ['title' => 'Recuperar contraseña']); 
        break;
    case '/terms': // 
        echo $twig->render('terms.twig', ['title' => 'Términos y condiciones']); 
        break;
    case '/faq': 
        echo $twig->render('faq.twig', ['title' => 'Preguntas frecuentes']); 
        break;
    default:
        echo $twig->render('404.twig', ['title' => _('Página no encontrada')]);
        break;
}

