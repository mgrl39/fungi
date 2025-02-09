<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';

// Configurar Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

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
            'title' => 'Todos los Fungis',
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
            echo $twig->render('404.twig', ['title' => 'Fungus no encontrado']);
        } else {
            echo $twig->render('fungus_detail.twig', [
                'title' => $fungus['name'],
                'fungus' => $fungus
            ]);
        }
        break;

    // Otras rutas...
    default:
        echo $twig->render('404.twig', ['title' => 'Página no encontrada']);
        break;
}

