<?php
// src/controllers/ApiController.php

// Asegúrate de instalar la librería "firebase/php-jwt" vía Composer para gestionar JWT:
// composer require firebase/php-jwt

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ApiController {

    private $jwtKey = "tu_clave_secreta";

    /**
     * Método para validar las credenciales y generar un JWT.
     */
    public function login($username, $password) {
        // Aquí deberías hacer la validación real de usuario y contraseña.
        if ($username === 'admin' && $password === '1234') {
            $payload = [
                'username' => $username,
                'iat' => time(),
                'exp' => time() + 3600 // El token expira en 1 hora.
            ];
            // Genera el token usando HS256.
            $jwt = JWT::encode($payload, $this->jwtKey, 'HS256');
            return ['token' => $jwt];
        }
        return false;
    }

    /**
     * Método para validar el token recibido.
     */
    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtKey, 'HS256'));
            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }
}

