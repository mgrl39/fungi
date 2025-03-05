<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/controllers/DatabaseController.php';
require_once __DIR__ . '/src/controllers/AuthController.php';
require_once __DIR__ . '/src/controllers/SessionController.php';

// Inicializar controladores
$db = new DatabaseController();
$authController = new AuthController($db);
$session = new SessionController($db);

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
    case '/login':
        if ($session->isLoggedIn()) {
            // Si ya está logueado, redirigir al home con mensaje
            header('Location: /?already_logged=1');
            exit;
        }
        break;

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

    case '/':
        // Tu código existente para la página principal
        $fungi = $db->getAllData('fungi');
        echo $twig->render('fungi_list.twig', [
            'title' => 'Base de Datos de Hongos',
            'fungis' => $fungi,
            'already_logged' => isset($_GET['already_logged']) ? true : false
        ]);
        break;

    // Ruta para cargar más hongos (AJAX)
    if (isset($_GET['action']) && $_GET['action'] === 'load') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 12;
        $offset = ($page - 1) * $limit;
        
        $fungi = $db->getFungisPaginated($limit, $offset);
        
        header('Content-Type: application/json');
        echo json_encode($fungi);
        exit;
    }
} 