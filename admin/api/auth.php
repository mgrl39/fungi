<?php
header('Content-Type: application/json');
require_once '../../config/database.php';

// Función para generar JWT
function generateJWT($user) {
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    $payload = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'role' => 'admin',
        'exp' => time() + (60 * 60) // Token válido por 1 hora
    ];
} 