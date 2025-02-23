<?php

// Configuración de Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/public/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true
]);

// Ruta principal
if (!isset($_GET['action'])) {
    // Obtener hongos de la base de datos
    $fungi = $db->query("SELECT * FROM fungi LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    
    echo $twig->render('fungi_list.twig', [
        'title' => 'Base de Datos de Hongos',
        'fungis' => $fungi
    ]);
}

// Ruta para cargar más hongos (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'load') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 12;
    $offset = ($page - 1) * $limit;
    
    $fungi = $db->query("SELECT * FROM fungi LIMIT $limit OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($fungi);
    exit;
} 