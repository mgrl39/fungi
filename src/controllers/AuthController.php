<?php

class AuthController {
    private $db;
    
    public function __construct(DatabaseController $db) {
        $this->db = $db;
    }
    
    public function register($username, $email, $password, $confirm_password) {
        // Validaciones básicas
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Todos los campos son requeridos'];
        }
        
        // Validar longitud del username
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'message' => 'El nombre de usuario debe tener entre 3 y 50 caracteres'];
        }
        
        if ($password !== $confirm_password) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden'];
        }
        
        // Validar longitud del password
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        // Hash del password antes de guardarlo
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Pasar el password ya hasheado
            if ($this->db->createUser($username, $email, $hashed_password)) {
                return ['success' => true, 'message' => 'Usuario registrado exitosamente'];
            } else {
                return ['success' => false, 'message' => 'El usuario o email ya existe'];
            }
        } catch (Exception $e) {
            // Log del error para el administrador
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar el usuario'];
        }
    }
    
    public function login($username, $password) {
        $user = $this->db->verifyUser($username, $password);
        
        // Debug logs (solo para desarrollo)
        error_log('Login attempt for username: ' . $username);
        if (!$user) {
            error_log('User not found or invalid password');
        }
        
        if ($user) {
            // Iniciar sesión
            session_start();
            $_SESSION['user'] = $user;
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Credenciales inválidas'];
    }
    
    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }
}