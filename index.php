<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/controllers/DatabaseController.php';
require_once __DIR__ . '/src/controllers/AuthController.php';

// Inicializar controladores
$db = new DatabaseController();
$authController = new AuthController($db);

// Configuración de Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/public/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true
]);

// Obtener la ruta actual
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Router básico
switch ($uri) {
    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Procesar el formulario de registro
            $result = $authController->register(
                $_POST['username'] ?? '',
                $_POST['email'] ?? '',
                $_POST['password'] ?? '',
                $_POST['confirm_password'] ?? ''
            );
            
            if ($result['success']) {
                // Redirigir al login con mensaje de éxito
                header('Location: /login?registered=1');
                exit;
            } else {
                // Mostrar el formulario de nuevo con el error
                echo $twig->render('register.twig', [
                    'title' => 'Registro',
                    'error' => $result['message']
                ]);
            }
        } else {
            // Mostrar el formulario de registro
            echo $twig->render('register.twig', [
                'title' => 'Registro'
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
                    'title' => 'Iniciar Sesión',
                    'error' => $result['message']
                ]);
            }
        } else {
            $registered = isset($_GET['registered']) ? true : false;
            echo $twig->render('login.twig', [
                'title' => 'Iniciar Sesión',
                'success' => $registered ? 'Usuario registrado exitosamente. Por favor inicia sesión.' : null
            ]);
        }
        break;

    case '/':
        // Página principal
        $fungi = $db->getAllData('fungi');
        echo $twig->render('fungi_list.twig', [
            'title' => 'Base de Datos de Hongos',
            'fungis' => $fungi
        ]);
        break;

    case '/api/fungi':
        // Ruta para cargar más hongos (AJAX)
        if (isset($_GET['page'])) {
            $page = (int)$_GET['page'];
            $limit = 12;
            $offset = ($page - 1) * $limit;
            
            $fungi = $db->getFungisPaginated($limit, $offset);
            
            header('Content-Type: application/json');
            echo json_encode($fungi);
            exit;
        }
        break;

    default:
        // 404 - Página no encontrada
        header("HTTP/1.0 404 Not Found");
        echo $twig->render('404.twig', ['title' => 'Página no encontrada']);
        break;
} 