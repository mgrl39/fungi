<?php

namespace App\Controllers\Api;

use App\Config\ErrorMessages;

/**
 * @class ApiGetController
 * @brief Controlador para manejar solicitudes GET a la API.
 *
 * Esta clase proporciona métodos para procesar diferentes tipos de solicitudes
 * GET a la API, como listar hongos, obtener detalles y estadísticas.
 *
 * @package App\Controllers\Api
 */
class ApiGetController
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
     * Verifica la autenticación del usuario
     * 
     * @param string $token Token JWT
     * @return array|bool Información del usuario o false
     */
    public function verifyAuthToken($token, $verifyCallback)
    {
        $payload = $verifyCallback($token);
        if ($payload) {
            return [
                'id' => $payload['sub'],
                'username' => $payload['username'],
                'role' => $payload['role']
            ];
        }
        return false;
    }

    /**
     * Obtiene la lista completa de hongos
     * 
     * @return array Lista de hongos
     */
    public function getAllFungi()
    {
        $stmt = $this->pdo->query("SELECT * FROM fungi");
        return [
            'success' => true, 
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
        ];
    }

    /**
     * Obtiene hongos con paginación
     * 
     * @param int $page Número de página
     * @param int $limit Límite de registros por página
     * @return array Resultados paginados
     */
    public function getFungiPaginated($page, $limit)
    {
        $offset = ($page - 1) * $limit;
        
        // Consulta para obtener los hongos con sus imágenes
        $sql = "SELECT f.*, GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                FROM fungi f 
                LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                LEFT JOIN images i ON fi.image_id = i.id 
                LEFT JOIN image_config ic ON i.config_key = ic.config_key
                GROUP BY f.id
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        // Consulta para contar el total de hongos
        $countStmt = $this->pdo->query("SELECT COUNT(*) FROM fungi");
        $totalCount = $countStmt->fetchColumn();
        
        // Calcular la información de paginación
        $totalPages = ceil($totalCount / $limit);
        
        return [
            'success' => true, 
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'records_per_page' => $limit,
                'total_records' => $totalCount
            ]
        ];
    }

    /**
     * Obtiene detalles de un hongo específico
     * 
     * @param int $id ID del hongo
     * @param int|null $userId ID del usuario actual (para likes)
     * @return array Detalles del hongo
     */
    public function getFungusById($id, $userId = null)
    {
        // Consulta para obtener datos básicos del hongo
        $sql = "SELECT f.*, 
                GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                FROM fungi f 
                LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                LEFT JOIN images i ON fi.image_id = i.id 
                LEFT JOIN image_config ic ON i.config_key = ic.config_key
                WHERE f.id = :id
                GROUP BY f.id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $fungus = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$fungus) {
            http_response_code(404);
            return [
                'success' => false, 
                'error' => ErrorMessages::DB_RECORD_NOT_FOUND
            ];
        }
        
        // Obtener estadísticas: vistas y likes
        $statsSql = "SELECT views, likes FROM fungi_popularity WHERE fungi_id = :id";
        $statsStmt = $this->pdo->prepare($statsSql);
        $statsStmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($stats) {
            $fungus['views'] = $stats['views'];
            $fungus['likes'] = $stats['likes'];
        } else {
            $fungus['views'] = 0;
            $fungus['likes'] = 0;
        }
        
        // Si hay un usuario autenticado, verificar si le dio like
        if ($userId) {
            $likeSql = "SELECT 1 FROM user_likes WHERE user_id = :userId AND fungi_id = :fungiId";
            $likeStmt = $this->pdo->prepare($likeSql);
            $likeStmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $likeStmt->bindParam(':fungiId', $id, \PDO::PARAM_INT);
            $likeStmt->execute();
            
            $fungus['is_liked'] = (bool) $likeStmt->fetchColumn();
        }
        
        // Incrementar contador de vistas
        $this->incrementFungiViews($id);
        
        return [
            'success' => true,
            'data' => $fungus
        ];
    }

    /**
     * Busca hongos por parámetro y valor
     * 
     * @param string $param Parámetro de búsqueda
     * @param string $value Valor a buscar
     * @return array Resultados de la búsqueda
     */
    public function searchFungi($param, $value)
    {
        $allowedParams = ['name', 'edibility', 'habitat', 'common_name'];
        
        if (!in_array($param, $allowedParams)) {
            http_response_code(400);
            return [
                'success' => false,
                'error' => 'Parámetro de búsqueda no válido. Parámetros permitidos: ' . implode(', ', $allowedParams)
            ];
        }
        
        try {
            // Construir la consulta de búsqueda
            $sql = "SELECT f.*, GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                    FROM fungi f 
                    LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                    LEFT JOIN images i ON fi.image_id = i.id 
                    LEFT JOIN image_config ic ON i.config_key = ic.config_key
                    WHERE f.{$param} LIKE :value 
                    GROUP BY f.id
                    LIMIT 50";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':value' => '%' . $value . '%']);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'count' => count($results),
                'data' => $results
            ];
        } catch (\PDOException $e) {
            http_response_code(500);
            return [
                'success' => false,
                'error' => 'Error en la búsqueda: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene un hongo aleatorio
     * 
     * @return array Datos del hongo aleatorio
     */
    public function getRandomFungus($userId = null)
    {
        $sql = "SELECT f.*, GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                FROM fungi f 
                LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                LEFT JOIN images i ON fi.image_id = i.id 
                LEFT JOIN image_config ic ON i.config_key = ic.config_key
                GROUP BY f.id
                ORDER BY RAND() 
                LIMIT 1";
        
        $stmt = $this->pdo->query($sql);
        $fungus = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$fungus) {
            return [
                'success' => false, 
                'error' => 'No hay hongos disponibles'
            ];
        }
        
        // Incrementar contador de vistas
        $this->incrementFungiViews($fungus['id']);
        
        // Si hay un usuario autenticado, verificar si le dio like
        if ($userId) {
            $fungiController = new \App\Controllers\FungiController($this->db);
            $fungus = $fungiController->getFungusWithLikeStatus($fungus, $userId);
        }
        
        return [
            'success' => true,
            'data' => $fungus
        ];
    }

    /**
     * Incrementa el contador de vistas de un hongo
     * 
     * @param int $fungiId ID del hongo
     * @return bool Resultado de la operación
     */
    private function incrementFungiViews($fungiId)
    {
        try {
            $sql = "INSERT INTO fungi_popularity (fungi_id, views, likes) 
                    VALUES (?, 1, 0) 
                    ON DUPLICATE KEY UPDATE views = views + 1";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$fungiId]);
        } catch (\PDOException $e) {
            error_log("Error incrementando vistas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene hongos filtrados por comestibilidad
     * 
     * @param string $edibility Tipo de comestibilidad
     * @return array Lista de hongos filtrados
     */
    public function getFungiByEdibility($edibility)
    {
        $sql = "SELECT f.*, GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                FROM fungi f 
                LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                LEFT JOIN images i ON fi.image_id = i.id 
                LEFT JOIN image_config ic ON i.config_key = ic.config_key
                WHERE f.edibility LIKE :edibility
                GROUP BY f.id
                LIMIT 50";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':edibility' => '%' . $edibility . '%']);
        
        return [
            'success' => true,
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
        ];
    }

    /**
     * Obtiene los hongos favoritos de un usuario
     * 
     * @param int $userId ID del usuario
     * @return array Lista de hongos favoritos
     */
    public function getUserFavorites($userId)
    {
        $sql = "SELECT f.*, GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                FROM fungi f 
                JOIN user_favorites uf ON f.id = uf.fungi_id 
                LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                LEFT JOIN images i ON fi.image_id = i.id 
                LEFT JOIN image_config ic ON i.config_key = ic.config_key
                WHERE uf.user_id = :userId 
                GROUP BY f.id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        
        return [
            'success' => true,
            'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
        ];
    }

    /**
     * Verifica autenticación del usuario actual
     * 
     * @param array $user Datos del usuario
     * @return array Estado de autenticación
     */
    public function verifyAuth($user)
    {
        if ($user) {
            return [
                'success' => true,
                'authenticated' => true,
                'user' => $user
            ];
        } else {
            return [
                'success' => true,
                'authenticated' => false
            ];
        }
    }
}
