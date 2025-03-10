<?php

namespace App\Controllers;

require_once __DIR__ . '/SessionController.php';

/**
 * @class AuthController
 * @brief Controlador para gestionar la autenticación de usuarios
 * 
 * Esta clase maneja el registro, inicio de sesión, cierre de sesión
 * y verificación de tokens para los usuarios del sistema.
 */
class AuthController {
    private $db;
    private $session;
    
    /**
     * @brief Constructor del controlador de autenticación
     * 
     * @param DatabaseController $db Instancia del controlador de base de datos
     */
    public function __construct(DatabaseController $db) {
        $this->db = $db;
        $this->session = new SessionController($db);
    }
    
    /**
     * @brief Registra un nuevo usuario en el sistema
     * 
     * @param string $username Nombre de usuario
     * @param string $email Correo electrónico del usuario
     * @param string $password Contraseña del usuario
     * @param string $confirm_password Confirmación de la contraseña
     * 
     * @return array Arreglo con el resultado de la operación
     *         ['success' => bool, 'message' => string]
     */
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
    
    /**
     * @brief Inicia sesión de un usuario
     * 
     * @param string $username Nombre de usuario
     * @param string $password Contraseña del usuario
     * 
     * @return array Arreglo con el resultado de la operación
     *         ['success' => bool, 'message' => string] o ['success' => true, 'user' => array]
     */
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
    
    /**
     * @brief Cierra la sesión del usuario actual
     * 
     * @return array Arreglo con el resultado de la operación
     *         ['success' => bool, 'message' => string]
     */
    public function logout() {
        session_start();
        session_destroy();
        
        // Eliminar cookies
        setcookie("token", "", time() - 3600, "/");
        setcookie("jwt", "", time() - 3600, "/");
        
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }

    /**
     * @brief Verifica la validez de un token JWT
     * 
     * @param string $token Token JWT a verificar
     * 
     * @return mixed Objeto decodificado si el token es válido, false en caso contrario
     */
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

    /**
     * @brief Procesa el formulario de registro
     * 
     * @param array $postData Datos enviados en el formulario
     * @param Twig $twig Instancia del motor de plantillas Twig
     * 
     * @return void Redirige al usuario o muestra el formulario con errores
     */
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