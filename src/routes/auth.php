<?php

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DatabaseController.php';

$db = new DatabaseController();
$auth = new AuthController($db);

// Endpoint para registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $response = $auth->register(
        $_POST['username'] ?? '',
        $_POST['email'] ?? '',
        $_POST['password'] ?? '',
        $_POST['confirm_password'] ?? ''
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Endpoint para login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $response = $auth->login(
        $_POST['username'] ?? '',
        $_POST['password'] ?? ''
    );
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Endpoint para logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $response = $auth->logout();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}