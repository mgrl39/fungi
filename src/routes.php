<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Asegúrate de que el autoload de Composer esté incluido

// Importar el controlador
require_once __DIR__ . '/controllers/FungiController.php';
require_once __DIR__ . '/controllers/StatsController.php';

use App\Controllers\FungiController;
use App\Controllers\StatsController;

// Inicializar el controlador de hongos
$fungiController = new FungiController($db);
$statsController = new StatsController($db);

// Obtener la ruta solicitada
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Ruteo básico
switch ($uri) {
    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $authController->handleRegistration($_POST, $twig);
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

    case '/':
    case '/index':
        echo $twig->render('fungi_list.twig', [
            'title' => _('Todos los Fungis'),
            'fungis' => $db->getFungisPaginated(20, 0),
            'session' => $session
        ]);
        break;

    case '/fungus':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $fungus = $db->getFungusById($id);
        
        if (!$fungus) {
            echo $twig->render('404.twig', ['title' => _('Fungus no encontrado')]);
        } else {
            // Incrementar las vistas del hongo
            $fungiController->incrementFungiViews($id);

            echo $twig->render('fungus_detail.twig', [
                'title' => $fungus['name'],
                'fungus' => $fungus
            ]);
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
        require_once __DIR__ . '/../public/api.php';
        break;

    case '/random':
        $fungus = $db->getRandomFungus();
        if ($session->isLoggedIn()) {
            $fungus = $fungiController->getFungusWithLikeStatus($fungus, $_SESSION['user_id']);
        }
        echo $twig->render('random_fungi.twig', [
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
        echo $twig->render('about.twig', ['title' => 'Acerca de']);
        break;

    case '/contact':
        echo $twig->render('contact.twig', ['title' => 'Contacto']);
        break;

    case '/admin': 
        echo $twig->render('admin.twig', ['title' => 'Admin']); 
        break;

    case '/reset_password': 
        echo $twig->render('reset_password.twig', ['title' => 'Recuperar contraseña']); 
        break;

    case '/terms': 
        echo $twig->render('terms.twig', ['title' => 'Términos y condiciones']); 
        break;

    case '/faq': 
        echo $twig->render('faq.twig', ['title' => 'Preguntas frecuentes']); 
        break;

    case '/statistics':
        if (!$session->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        
        $stats = $statsController->getFungiStats();
        echo $twig->render('statistics.twig', [
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
        echo $twig->render('404.twig', ['title' => _('Página no encontrada')]);
        break;
}
