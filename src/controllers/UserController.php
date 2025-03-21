<?php

namespace App\Controllers;
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
     * @var SessionController $session Instancia del controlador de sesiones
     * @var AuthController|null $auth Instancia del controlador de autenticación
     */
    private $db;
    private $session;
    private $auth;
    
    /**
     * @brief Constructor del controlador de usuarios
     * 
     * @param DatabaseController $db Instancia del controlador de base de datos
     * @param SessionController $session Instancia del controlador de sesiones
     * @param AuthController|null $auth Instancia del controlador de autenticación
     */
    public function __construct(DatabaseController $db, SessionController $session, ?AuthController $auth = null) {
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
                if ($success) $userData = $this->session->getUserData();
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
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = _('El formato del email no es válido');
        }
        return $errors;
    }
    
    /**
     * @brief Actualiza el perfil de un usuario
     * 
     * @param int $userId ID del usuario a actualizar
     * @param array $data Datos a actualizar (nombre, email, rol, etc.)
     * @return bool Resultado de la operación
     */
    public function updateUserProfile($userId, $data) {
        try {
            // Verificar si el usuario existe
            $checkUser = $this->db->query("SELECT id FROM users WHERE id = ?", [$userId]);
            $checkResult = $checkUser->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($checkResult)) return false;
            // Construir consulta dinámica para los campos que se actualizan
            $updateFields = [];
            $params = [];
            
            // Campos permitidos para actualización
            $allowedFields = ['username', 'email', 'role', 'bio', 'name', 'location'];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            
            // Si no hay campos para actualizar, terminar
            if (empty($updateFields)) return false;
            // Agregar el ID al final de los parámetros
            $params[] = $userId;
            // Ejecutar la consulta de actualización
            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $result = $this->db->query($sql, $params);

            return $result !== false;
        } catch (\Exception $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            return false;
        }
    }
       
    /**
     * @brief Obtiene todos los usuarios de la base de datos
     * 
     * @return array Lista de usuarios
     */
    public function getAllUsers() {
        try {
            $sql = "SELECT id, username, email, role, created_at, last_login FROM users ORDER BY id DESC";
            $result = $this->db->query($sql);
            
            // Manejar diferentes tipos de retorno del método query
            if (is_array($result)) return $result;
            if ($result instanceof \PDOStatement) return $result->fetchAll(\PDO::FETCH_ASSOC);
            if ($result === false) {
                error_log("La consulta de usuarios falló");
                return [];
            } else {
                error_log("Tipo de retorno inesperado en getAllUsers(): " . gettype($result));
                return [];
            }
        } catch (\Exception $e) {
            error_log("Error al obtener usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @brief Crea un nuevo usuario en el sistema
     * 
     * @param string $username Nombre de usuario
     * @param string $email Correo electrónico
     * @param string $password_hash Contraseña ya hasheada
     * @return bool Resultado de la operación
     */
    public function createUser($username, $email, $password_hash) {
        try {
            $sql = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";
            $params = [ ':username' => $username, ':email' => $email, ':password_hash' => $password_hash ];
            return $this->db->query($sql, $params) !== false;
        } catch (\Exception $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @brief Elimina un usuario del sistema
     * 
     * @param int $userId ID del usuario a eliminar
     * @return bool Resultado de la operación
     */
    public function deleteUser($userId) {
        try {
            // Verificar si el usuario existe
            $checkUser = $this->db->query("SELECT id FROM users WHERE id = ?", [$userId]);
            $checkResult = $checkUser->fetchAll(\PDO::FETCH_ASSOC);
            if (empty($checkResult)) return false;
            // Eliminar registros relacionados en otras tablas
            $this->db->query("DELETE FROM user_likes WHERE user_id = ?", [$userId]);
            $this->db->query("DELETE FROM user_favorites WHERE user_id = ?", [$userId]);
            $this->db->query("DELETE FROM comments WHERE user_id = ?", [$userId]);
            $this->db->query("DELETE FROM access_logs WHERE user_id = ?", [$userId]);
            
            // Finalmente eliminar el usuario
            $result = $this->db->query("DELETE FROM users WHERE id = ?", [$userId]);
            
            return $result !== false;
        } catch (\Exception $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            return false;
        }
    }
} 