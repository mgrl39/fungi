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
    private $db;
    private $session;
    private $auth;
    
    /**
     * Constructor del controlador de usuarios
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
     * Maneja la visualización y edición del perfil del usuario
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
        
        $userData = $this->session->getUserData();
        $errors = [];
        $success = false;
        
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
            'title' => _('Mi Perfil'),
            'user' => $userData,
            'errors' => $errors,
            'success' => $success
        ];
    }
    
    /**
     * Valida los datos de actualización de perfil
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
     * Actualiza el perfil del usuario en la base de datos
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
     * Maneja la página de administración de usuarios
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
     * Obtiene todos los usuarios de la base de datos
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
} 