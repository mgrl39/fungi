<?php

namespace App\Controllers;

use App\Config\ErrorMessages;

/**
 * @class UserController
 * @brief Controlador para gestionar operaciones relacionadas con usuarios
 * 
 * Este controlador centraliza todas las funciones relacionadas con usuarios,
 * incluyendo perfiles, preferencias y administración de usuarios.
 */
class UserController {
    /**
     * @var DatabaseController $db Instancia del controlador de base de datos
     */
    private $db;
    
    /**
     * @var SessionController $session Instancia del controlador de sesiones
     */
    private $session;
    private $auth;
    
    /**
     * @brief Constructor del controlador de usuarios
     * 
     * @param DatabaseController $db Instancia del controlador de base de datos
     * @param SessionController $session Instancia del controlador de sesiones
     * @param AuthController $auth Instancia del controlador de autenticación
     */
    public function __construct(DatabaseController $db, SessionController $session, AuthController $auth = null) {
        $this->db = $db;
        $this->session = $session;
        $this->auth = $auth ?? new AuthController($db);
    }
    
    /**
     * @brief Maneja la visualización y edición del perfil del usuario
     * 
     * @details Permite al usuario modificar datos como nombre, biografía, imagen, etc.
     * 
     * @param array $params Parámetros de la solicitud
     * @return array Datos para la plantilla
     */
    public function profileHandler($params = []) {
        // Verificar si el usuario está autenticado
        if (!$this->session->isLoggedIn()) {
            header('Location: /login?redirect=/profile');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $userData = $this->session->getUserData();
        $errors = [];
        $success = false;
        
        // Obtener estadísticas del usuario
        $stats = $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM user_likes WHERE user_id = ?) as likes_count,
                (SELECT COUNT(*) FROM user_favorites WHERE user_id = ?) as favorites_count,
                (SELECT COUNT(*) FROM comments WHERE user_id = ?) as comments_count,
                (SELECT COUNT(*) FROM contributions WHERE user_id = ?) as contributions_count
        ", [$userId, $userId, $userId, $userId])[0] ?? [];
        
        // Actualizar el usuario con los contadores
        $userData['likes_count'] = $stats['likes_count'] ?? 0;
        $userData['favorites_count'] = $stats['favorites_count'] ?? 0;
        $userData['comments_count'] = $stats['comments_count'] ?? 0;
        $userData['contributions_count'] = $stats['contributions_count'] ?? 0;
        
        // Obtener acciones recientes del usuario
        $actions = $this->db->query("
            SELECT a.*, 'check' as icon, 'primary' as color
            FROM access_logs a
            WHERE a.user_id = ?
            ORDER BY a.access_time DESC
            LIMIT 5
        ", [$userId]);
        
        // Procesar formulario de actualización de perfil
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
            $errors = $this->validateProfileUpdate($_POST);
            
            if (empty($errors)) {
                $success = $this->updateUserProfile($userData['id'], $_POST);
                if ($success) {
                    $userData = $this->session->getUserData(); // Recargar datos actualizados
                }
            }
        }
        
        return [
            'title' => 'Perfil de ' . $userData['username'],
            'user' => $userData,
            'user_actions' => $actions,
            'errors' => $errors,
            'success' => $success,
            'is_own_profile' => true  // Variable crucial para mostrar opciones de configuración
        ];
    }
    
    /**
     * @brief Valida los datos de actualización de perfil
     * 
     * @details Verifica que los datos ingresados sean válidos para actualizar el perfil del usuario.
     * 
     * @param array $data Datos del formulario
     * @return array Errores de validación encontrados
     */
    private function validateProfileUpdate($data) {
        $errors = [];
        
        // Validar email
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = _('El formato del email no es válido');
        }
        
        // Otras validaciones según necesidades
        // ...
        
        return $errors;
    }
    
    /**
     * @brief Actualiza el perfil del usuario en la base de datos
     * 
     * @details Actualiza los datos del usuario en la base de datos según los datos proporcionados.
     * 
     * @param int $userId ID del usuario
     * @param array $data Datos a actualizar
     * @return bool Resultado de la operación
     */
    private function updateUserProfile($userId, $data) {
        try {
            $fields = [];
            $params = [];
            
            // Determinar qué campos actualizar
            if (!empty($data['email'])) {
                $fields[] = "email = :email";
                $params[':email'] = $data['email'];
            }
            
            if (!empty($data['display_name'])) {
                $fields[] = "display_name = :display_name";
                $params[':display_name'] = $data['display_name'];
            }
            
            // Solo actualizar si hay campos para actualizar
            if (!empty($fields)) {
                $params[':id'] = $userId;
                $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
                return $this->db->query($sql, $params);
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Error al actualizar perfil: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Maneja la página de administración de usuarios
     * 
     * @return array Datos para la plantilla
     */
    public function adminUsersHandler() {
        // Verificar si es administrador
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        // Obtener lista de usuarios
        $users = $this->getAllUsers();
        
        return [
            'title' => _('Administración de Usuarios'),
            'users' => $users
        ];
    }
    
    /**
     * @brief Obtiene todos los usuarios de la base de datos
     * 
     * @return array Lista de usuarios
     */
    private function getAllUsers() {
        try {
            $sql = "SELECT id, username, email, role, created_at, last_login FROM users ORDER BY id DESC";
            $result = $this->db->query($sql);
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @brief Registra un nuevo usuario en el sistema
     * 
     * @details Valida los datos de entrada, verifica que el email y username no existan,
     * y crea un nuevo registro de usuario con la contraseña hasheada.
     * 
     * @param string $username Nombre de usuario
     * @param string $email Correo electrónico
     * @param string $password Contraseña en texto plano
     * @param string $confirmPassword Confirmación de contraseña
     * 
     * @return array Resultado de la operación con estado y mensaje
     */
    public function register($username, $email, $password, $confirmPassword) {
        // ... existing code ...
    }

    /**
     * @brief Autentica a un usuario en el sistema
     * 
     * @details Verifica las credenciales del usuario contra la base de datos,
     * e inicia sesión si son correctas.
     * 
     * @param string $email Correo electrónico
     * @param string $password Contraseña
     * @param bool $remember Indica si se debe mantener la sesión (cookie persistente)
     * 
     * @return array Resultado de la operación con estado y mensaje
     */
    public function login($email, $password, $remember = false) {
        // ... existing code ...
    }

    /**
     * @brief Cierra la sesión del usuario actual
     * 
     * @details Elimina todos los datos de sesión y cookies relacionadas con la autenticación
     * 
     * @return bool TRUE si la operación fue exitosa
     */
    public function logout() {
        // ... existing code ...
    }

    /**
     * @brief Actualiza la información del perfil de usuario
     * 
     * @details Permite al usuario modificar datos como nombre, biografía, imagen, etc.
     * 
     * @param int $userId ID del usuario
     * @param array $data Datos a actualizar en formato clave-valor
     * 
     * @return array Resultado de la operación con estado y mensaje
     */
    public function updateProfile($userId, $data) {
        // ... existing code ...
    }

    /**
     * @brief Cambia la contraseña de un usuario
     * 
     * @details Verifica la contraseña actual y actualiza a la nueva contraseña
     * 
     * @param int $userId ID del usuario
     * @param string $currentPassword Contraseña actual
     * @param string $newPassword Nueva contraseña
     * @param string $confirmPassword Confirmación de nueva contraseña
     * 
     * @return array Resultado de la operación con estado y mensaje
     */
    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        // ... existing code ...
    }

    /**
     * @brief Obtiene la información de un usuario por ID
     * 
     * @param int $userId ID del usuario
     * @param bool $includePrivate Indica si se deben incluir datos privados
     * 
     * @return array|null Datos del usuario o null si no existe
     */
    public function getUserById($userId, $includePrivate = false) {
        // ... existing code ...
    }

    /**
     * @brief Verifica si un email ya está registrado
     * 
     * @param string $email Correo electrónico a verificar
     * 
     * @return bool TRUE si el email existe, FALSE en caso contrario
     */
    public function emailExists($email) {
        // ... existing code ...
    }

    /**
     * @brief Verifica si un nombre de usuario ya está registrado
     * 
     * @param string $username Nombre de usuario a verificar
     * 
     * @return bool TRUE si el username existe, FALSE en caso contrario
     */
    public function usernameExists($username) {
        // ... existing code ...
    }
} 