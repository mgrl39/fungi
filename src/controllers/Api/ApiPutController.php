<?php

namespace App\Controllers\Api;

use App\Config\ErrorMessages;

/**
 * @class ApiPutController
 * @brief Controlador para manejar solicitudes PUT a la API.
 *
 * Esta clase proporciona métodos para procesar diferentes tipos de solicitudes
 * PUT a la API, como actualizar hongos y datos de usuarios.
 *
 * @package App\Controllers\Api
 */
class ApiPutController
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
     * Actualiza la información de un hongo
     * 
     * @param int $fungiId ID del hongo a actualizar
     * @param array $data Nuevos datos del hongo
     * @param array $user Usuario que realiza la operación
     * @return array Resultado de la operación
     */
    public function updateFungi($fungiId, $data, $user)
    {
        // Verificar permisos de administrador
        if (!isset($user['role']) || $user['role'] !== 'admin') {
            http_response_code(403);
            return [
                'success' => false,
                'error' => ErrorMessages::AUTH_UNAUTHORIZED
            ];
        }
        
        // Verificar que el hongo exista
        $checkStmt = $this->pdo->prepare("SELECT id FROM fungi WHERE id = ?");
        $checkStmt->execute([$fungiId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            return [
                'success' => false,
                'error' => ErrorMessages::DB_RECORD_NOT_FOUND
            ];
        }
        
        // Construir consulta dinámica
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            // Solo permitir campos válidos
            if (in_array($field, ['name', 'edibility', 'habitat', 'observations', 'common_name', 'synonym', 'title'])) {
                $fields[] = "$field = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) {
            http_response_code(400);
            return [
                'success' => false,
                'error' => 'No se proporcionaron campos válidos para actualizar'
            ];
        }
        
        // Agregar ID para la cláusula WHERE
        $values[] = $fungiId;
        
        try {
            $sql = "UPDATE fungi SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Hongo actualizado correctamente'
                ];
            } else {
                http_response_code(500);
                return [
                    'success' => false,
                    'error' => ErrorMessages::DB_UPDATE_ERROR
                ];
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            return [
                'success' => false,
                'error' => ErrorMessages::format(ErrorMessages::DB_UPDATE_ERROR, $e->getMessage())
            ];
        }
    }
} 