<?php

namespace App\Controllers\Api;

use App\Config\ErrorMessages;

/**
 * @class ApiDeleteController
 * @brief Controlador para manejar solicitudes DELETE a la API.
 *
 * Esta clase proporciona métodos para procesar diferentes tipos de solicitudes
 * DELETE a la API, como eliminar hongos, quitar likes y favoritos.
 *
 * @package App\Controllers\Api
 */
class ApiDeleteController
{
    private $pdo;
    private $db;

    /**
     * Constructor del controlador
     * 
     * @param \PDO $pdo Conexión PDO a la base de datos
     * @param \App\Controllers\DatabaseController $db Modelo de base de datos
     */
    public function __construct($pdo, $db)
    {
        $this->pdo = $pdo;
        $this->db = $db;
    }

    /**
     * Elimina un hongo específico
     * 
     * @param int $fungiId ID del hongo a eliminar
     * @param array $user Usuario que realiza la operación
     * @return array Resultado de la operación
     */
    public function deleteFungi($fungiId, $user)
    {
        // Verificar permisos de administrador
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            http_response_code(403);
            return [
                'success' => false,
                'error' => ErrorMessages::AUTH_UNAUTHORIZED
            ];
        }
        
        try {
            // Eliminar imágenes asociadas
            $this->pdo->prepare("DELETE FROM fungi_images WHERE fungi_id = ?")->execute([$fungiId]);
            
            // Eliminar estadísticas
            $this->pdo->prepare("DELETE FROM fungi_popularity WHERE fungi_id = ?")->execute([$fungiId]);
            
            // Eliminar likes
            $this->pdo->prepare("DELETE FROM user_likes WHERE fungi_id = ?")->execute([$fungiId]);
            
            // Eliminar favoritos
            $this->pdo->prepare("DELETE FROM user_favorites WHERE fungi_id = ?")->execute([$fungiId]);
            
            // Eliminar el hongo
            $stmt = $this->pdo->prepare("DELETE FROM fungi WHERE id = ?");
            $result = $stmt->execute([$fungiId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Hongo eliminado correctamente'
                ];
            } else {
                http_response_code(404);
                return [
                    'success' => false,
                    'error' => ErrorMessages::DB_RECORD_NOT_FOUND
                ];
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            return [
                'success' => false,
                'error' => ErrorMessages::format(ErrorMessages::DB_DELETE_ERROR, $e->getMessage())
            ];
        }
    }

    /**
     * Quita el like de un hongo
     * 
     * @param int $userId ID del usuario
     * @param int $fungiId ID del hongo
     * @return array Resultado de la operación
     */
    public function unlikeFungi($userId, $fungiId)
    {
        try {
            $fungiController = new \App\Controllers\FungiController($this->db);
            $result = $fungiController->unlikeFungi($userId, $fungiId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Hongo eliminado de "me gusta"'
                ];
            } else {
                http_response_code(404);
                return [
                    'success' => false,
                    'error' => 'No se encontró el like para eliminar'
                ];
            }
        } catch (\Exception $e) {
            http_response_code(500);
            return [
                'success' => false,
                'error' => ErrorMessages::format(ErrorMessages::DB_DELETE_ERROR, $e->getMessage())
            ];
        }
    }

    /**
     * Quita un hongo de favoritos
     * 
     * @param int $userId ID del usuario
     * @param int $fungiId ID del hongo
     * @return array Resultado de la operación
     */
    public function removeFavorite($userId, $fungiId)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_favorites WHERE user_id = ? AND fungi_id = ?");
            $result = $stmt->execute([$userId, $fungiId]);
            
            if ($result && $stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'Hongo eliminado de favoritos'
                ];
            } else {
                http_response_code(404);
                return [
                    'success' => false,
                    'error' => 'No se encontró el favorito para eliminar'
                ];
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            return [
                'success' => false,
                'error' => ErrorMessages::format(ErrorMessages::DB_DELETE_ERROR, $e->getMessage())
            ];
        }
    }
}
