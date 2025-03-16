<?php

namespace App\Controllers\Api;

use App\Config\ErrorMessages;

/**
 * @class ApiPostController
 * @brief Controlador para manejar solicitudes POST a la API.
 *
 * Esta clase proporciona métodos para procesar diferentes tipos de solicitudes
 * POST a la API, como creación de usuarios, autenticación, likes y favoritos.
 *
 * @package App\Controllers\Api
 */
class ApiPostController
{
    private $pdo;
    private $db;

    /**
     * Constructor del controlador
     * 
     * @param \PDO $pdo Conexión PDO a la base de datos
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Maneja el registro de un nuevo usuario
     * 
     * @param array $data Datos del usuario a registrar
     * @return array Respuesta con resultado del registro
     */
    public function registerUser($data)
    {
        $requiredFields = ['username', 'email', 'password'];
        if (!$this->validateRequiredFields($data, $requiredFields)) {
            http_response_code(400);
            return [
                'success' => false,
                'error' => ErrorMessages::format(ErrorMessages::VALIDATION_REQUIRED_FIELD, 'username, email, password')
            ];
        }
        
        // Validación de formato de email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return [
                'success' => false,
                'error' => ErrorMessages::VALIDATION_INVALID_EMAIL
            ];
        }
        
        // Verificar que el nombre de usuario no exista
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            return [
                'success' => false,
                'error' => ErrorMessages::format(ErrorMessages::VALIDATION_VALUE_ALREADY_EXISTS, 'username')
            ];
        }
        
        // Verificar que el email no exista
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            return [
                'success' => false,
                'error' => ErrorMessages::format(ErrorMessages::VALIDATION_VALUE_ALREADY_EXISTS, 'email')
            ];
        }
        
        // Generar hash de la contraseña
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Insertar el nuevo usuario
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
        $stmt->execute([$data['username'], $data['email'], $passwordHash]);
        
        $userId = $this->pdo->lastInsertId();
        
        return [
            'success' => true,
            'id' => $userId,
            'message' => 'Usuario registrado exitosamente'
        ];
    }

    /**
     * Maneja el proceso de likes a hongos
     * 
     * @param int $userId ID del usuario
     * @param int $fungiId ID del hongo
     * @return array Respuesta con resultado de la operación
     */
    public function likeFungi($userId, $fungiId)
    {
        $fungiController = new \App\Controllers\FungiController($this->db);
        $result = $fungiController->likeFungi($userId, $fungiId);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Hongo marcado como "me gusta"'
            ];
        } else {
            http_response_code(500);
            return [
                'success' => false,
                'error' => ErrorMessages::DB_QUERY_ERROR
            ];
        }
    }

    /**
     * Maneja el proceso de agregar hongos a favoritos
     * 
     * @param int $userId ID del usuario
     * @param int $fungiId ID del hongo
     * @return array Respuesta con resultado de la operación
     */
    public function addFavorite($userId, $fungiId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO user_favorites (user_id, fungi_id) VALUES (?, ?)");
        $result = $stmt->execute([$userId, $fungiId]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Hongo añadido a favoritos'
            ];
        } else {
            http_response_code(500);
            return [
                'success' => false,
                'error' => ErrorMessages::DB_QUERY_ERROR
            ];
        }
    }

    /**
     * Maneja el proceso de autenticación
     * 
     * @param array $data Credenciales del usuario
     * @param callable $loginCallback Función para autenticar
     * @param callable $generateTokenCallback Función para generar token
     * @return array Respuesta con token y datos del usuario
     */
    public function handleLogin($data, $loginCallback, $generateTokenCallback)
    {
        $requiredFields = ['username', 'password'];
        if (!$this->validateRequiredFields($data, $requiredFields)) {
            http_response_code(400);
            return [
                'success' => false, 
                'error' => ErrorMessages::format(ErrorMessages::VALIDATION_REQUIRED_FIELD, 'username, password')
            ];
        }

        // Autenticar usuario usando el callback
        $user = $loginCallback($data['username'], $data['password']);
        
        if ($user) {
            // Generar token JWT usando el callback
            $token = $generateTokenCallback($user);
            
            return [
                'success' => true, 
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        } else {
            http_response_code(401);
            return [
                'success' => false, 
                'error' => ErrorMessages::AUTH_INVALID_CREDENTIALS
            ];
        }
    }

    /**
     * Maneja el proceso de cierre de sesión
     * 
     * @return array Respuesta con resultado de la operación
     */
    public function handleLogout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Limpiar sesión
        $_SESSION = array();
        
        // Eliminar cookies
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        setcookie('token', '', time() - 42000, '/');
        setcookie('jwt', '', time() - 42000, '/');
        
        // Destruir sesión
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ];
    }

    /**
     * Validación de campos requeridos.
     * 
     * @param array $data Los datos a validar
     * @param array $requiredFields Lista de campos requeridos
     * @return bool Verdadero si todos los campos requeridos están presentes
     */
    private function validateRequiredFields(array $data, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }
} 