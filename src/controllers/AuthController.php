<?php

require_once __DIR__ . '/SessionController.php';

class AuthController {
    private $db;
    private $session;
    
    public function __construct(DatabaseController $db) {
        $this->db = $db;
        $this->session = new SessionController($db);
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
        
        if (!$user) {
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }
        
        // Usar SessionController para manejar la sesión
        if (!$this->session->createSession($user)) {
            return ['success' => false, 'message' => 'Error al crear la sesión'];
        }
        
        return ['success' => true, 'user' => $user];
    }
    
    public function logout() {
        session_start();
        session_destroy();
        
        // Eliminar cookies
        setcookie("token", "", time() - 3600, "/");
        setcookie("jwt", "", time() - 3600, "/");
        
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }

    public function verifyToken($token) {
        try {
            // Verificar si el token existe y no está revocado
            $tokenData = $this->db->query(
                "SELECT * FROM jwt_tokens 
                 WHERE token = ? AND is_revoked = FALSE 
                 AND expires_at > NOW()",
                [$token]
            )->fetch();

            if (!$tokenData) {
                return false;
            }

            // Continuar con la verificación JWT normal
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return $decoded;
        } catch (Exception $e) {
            return false;
        }
    }

    public function handleRegistration($postData, $twig) {
        $result = $this->register(
            $postData['username'] ?? '',
            $postData['email'] ?? '',
            $postData['password'] ?? '',
            $postData['confirm_password'] ?? ''
        );
        
        if ($result['success']) {
            header('Location: /login?registered=1');
            exit;
        } else {
            echo $twig->render('register.twig', [
                'title' => _('Registro'),
                'error' => $result['message']
            ]);
        }
    }
}