<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

// Configuración de internacionalización
$locale = 'es_ES.UTF-8';
putenv("LC_ALL=$locale");
setlocale(LC_ALL, $locale);
bindtextdomain('messages', __DIR__ . '/../locale');
textdomain('messages');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

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
    require_once __DIR__ . '/../src/controllers/DatabaseController.php';
    $db = new DatabaseController();
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
    // Página principal (listing de fungis)
    case '/':
    case '/index':
        require_once __DIR__ . '/../src/controllers/DatabaseController.php';
        $db = new DatabaseController();
        // Cargar la primera página (20 registros)
        $fungis = $db->getFungisPaginated(20, 0);
        echo $twig->render('fungi_list.twig', [
            'title' => _('Todos los Fungis'),
            'fungis' => $fungis
        ]);
        break;

    // Página de detalle para un hongo
    case '/fungus':
        require_once __DIR__ . '/../src/controllers/DatabaseController.php';
        $db = new DatabaseController();
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
            require_once __DIR__ . '/../src/controllers/DatabaseController.php';
            $db = new DatabaseController();
            $fungus = $db->getRandomFungus();
            // Debug: Verificar el valor de $fungus
            echo $twig->render('random_fungi.twig', [
                'title' => _('Hongo aleatorio'),
                'fungus' => $fungus
            ]);
            break;
    case '/profile':
        require_once __DIR__ . '/../src/controllers/ApiController.php';
        $api = new ApiController();
        $api->profile();
        break;
    case '/login':
        require_once __DIR__ . '/../src/controllers/ApiController.php';
        $api = new ApiController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            $result = $api->login($username, $password);
            if ($result) {
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
            }
            http_response_code(401);
            echo json_encode(['error' => _('Credenciales inválidas')]);
            exit;
        }
        echo $twig->render('login.twig', ['title' => _('Iniciar sesión')]);
        break;
    case '/logout':
        // Simplemente redirigir a la página de inicio
        header('Location: /');
        break;
    case '/register':
        // Página de registro
        echo $twig->render('register.twig', ['title' => 'Registro']);
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
    case '/profile': 
        echo $twig->render('profile.twig', ['title' => 'Perfil']); 
        break;
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
        echo $twig->render('404.twig', ['title' => 'Página no encontrada']);
        break;
}

