<?php

namespace App\Controllers;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @brief Controlador para la gestión de hongos (fungi) en la aplicación
 * 
 * @details Esta clase maneja las operaciones relacionadas con los hongos,
 * incluyendo likes, vistas y consultas de información
 */
class FungiController
{
    /** @var object $db Instancia del controlador de base de datos */
    private $db;

    /**
     * @brief Constructor de la clase FungiController
     * 
     * @param object $db Instancia del controlador de base de datos
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @brief Registra que un usuario ha dado like a un hongo
     * 
     * @param int $userId ID del usuario
     * @param int $fungiId ID del hongo
     * 
     * @return mixed Resultado de la consulta a la base de datos
     */
    public function likeFungi($userId, $fungiId)
    {
        return $this->db->query(
            "INSERT INTO user_likes (user_id, fungi_id) VALUES (?, ?)",
            [$userId, $fungiId]
        );
    }

    /**
     * @brief Elimina el like de un usuario a un hongo
     * 
     * @param int $userId ID del usuario
     * @param int $fungiId ID del hongo
     * 
     * @return mixed Resultado de la consulta a la base de datos
     */
    public function unlikeFungi($userId, $fungiId)
    {
        return $this->db->query(
            "DELETE FROM user_likes WHERE user_id = ? AND fungi_id = ?",
            [$userId, $fungiId]
        );
    }

    /**
     * @brief Actualiza el contador de likes de un hongo
     * 
     * @param int $fungiId ID del hongo
     * @param int $increment Valor de incremento (positivo) o decremento (negativo)
     * 
     * @return mixed Resultado de la consulta a la base de datos
     */
    private function updateFungiLikes($fungiId, $increment)
    {
        return $this->db->query(
            "UPDATE fungi_popularity SET likes = likes + ? WHERE fungi_id = ?",
            [$increment, $fungiId]
        );
    }

    /**
     * @brief Incrementa el contador de vistas de un hongo
     * 
     * @param int $fungiId ID del hongo
     * 
     * @return mixed Resultado de la consulta a la base de datos
     */
    public function incrementFungiViews($fungiId)
    {
        return $this->db->query(
            "INSERT INTO fungi_popularity (fungi_id, views, likes) 
             VALUES (?, 1, 0) 
             ON DUPLICATE KEY UPDATE views = views + 1",
            [$fungiId]
        );
    }

    /**
     * @brief Verifica si un usuario ha dado like a un hongo
     * 
     * @param int $userId ID del usuario
     * @param int $fungiId ID del hongo
     * 
     * @return bool TRUE si el usuario ha dado like, FALSE en caso contrario
     */
    public function hasUserLikedFungi($userId, $fungiId)
    {
        $result = $this->db->query(
            "SELECT 1 FROM user_likes WHERE user_id = ? AND fungi_id = ?",
            [$userId, $fungiId]
        );
        return !empty($result);
    }

    /**
     * @brief Añade el estado de like de un usuario a la información de un hongo
     * 
     * @param array $fungus Datos del hongo
     * @param int $userId ID del usuario
     * 
     * @return array Datos del hongo con el estado de like añadido
     */
    public function getFungusWithLikeStatus($fungus, $userId)
    {
        if ($fungus && $userId) {
            $fungus['is_liked'] = $this->hasUserLikedFungi($userId, $fungus['id']);
        }
        return $fungus;
    }
    
    /**
     * @brief Obtiene un hongo por su ID
     * 
     * @param int $id ID del hongo a buscar
     * @return array|null Datos del hongo o null si no se encuentra
     */
    public function getFungusById($id)
    {
        $result = $this->db->query(
            "SELECT * FROM fungi WHERE id = ? LIMIT 1",
            [$id]
        );
        
        // Si el resultado es un objeto PDOStatement, convertirlo a array
        if ($result instanceof \PDOStatement) {
            return $result->fetch(\PDO::FETCH_ASSOC) ?: null;
        }
        
        // Si el resultado es un array con índices numéricos (múltiples filas)
        if (is_array($result) && !empty($result) && isset($result[0])) {
            return $result[0];
        }
        
        // Si ya es un array asociativo simple o está vacío
        return $result ?: null;
    }
    
    /**
     * @brief Busca hongos similares al hongo especificado
     * 
     * @param int $id ID del hongo para el que buscar similares
     * @param int $limit Número máximo de hongos similares a devolver (por defecto 4)
     * @return array Lista de hongos similares
     */
    public function getSimilarFungi($id, $limit = 4)
    {
        // Primero obtenemos los datos del hongo para el que queremos buscar similares
        $fungi = $this->getFungusById($id);
        
        if (empty($fungi) || !is_array($fungi)) {
            return [];
        }
        
        // Buscar hongos con características similares
        $query = "SELECT f.* FROM fungi f 
                  WHERE f.id != ? 
                  AND (f.family = ? OR f.genus = ? OR f.habitat LIKE ?)
                  ORDER BY RAND() 
                  LIMIT ?";
                  
        $habitat = isset($fungi['habitat']) ? $fungi['habitat'] : '';
        $family = isset($fungi['family']) ? $fungi['family'] : '';
        $genus = isset($fungi['genus']) ? $fungi['genus'] : '';
        
        $similarFungi = $this->db->query(
            $query,
            [$id, $family, $genus, "%$habitat%", $limit]
        );
        
        // Si el resultado es un objeto PDOStatement, convertirlo a array
        if ($similarFungi && is_object($similarFungi) && method_exists($similarFungi, 'fetchAll')) {
            $similarFungi = $similarFungi->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        // Si no hay resultados, intentamos una búsqueda más amplia
        if (empty($similarFungi)) {
            $query = "SELECT f.* FROM fungi f 
                      WHERE f.id != ? 
                      ORDER BY RAND() 
                      LIMIT ?";
                      
            $similarFungi = $this->db->query(
                $query,
                [$id, $limit]
            );
            
            // Si el resultado es un objeto PDOStatement, convertirlo a array
            if ($similarFungi && is_object($similarFungi) && method_exists($similarFungi, 'fetchAll')) {
                $similarFungi = $similarFungi->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
        
        return $similarFungi ?: [];
    }

    /**
     * @brief Obtiene un hongo aleatorio de la base de datos
     * 
     * @return array|null Datos del hongo aleatorio o null si no se encuentra ninguno
     */
    public function getRandomFungus()
    {
        $query = "SELECT * FROM fungi ORDER BY RAND() LIMIT 1";
        $randomResult = $this->db->query($query);
        
        // Procesar el resultado según el tipo de retorno de la DB
        if ($randomResult instanceof \PDOStatement) {
            $fungus = $randomResult->fetch(\PDO::FETCH_ASSOC);
        } else if (is_array($randomResult) && !empty($randomResult)) {
            // Si ya es un array (quizás el método query ya procesa el resultado)
            $fungus = isset($randomResult[0]) ? $randomResult[0] : $randomResult;
        } else {
            $fungus = null;
        }
        
        if ($fungus) {
            // Incrementar el contador de vistas para este hongo
            $this->incrementFungiViews($fungus['id']);
            
            // Registrar para depuración
            error_log("Cargando hongo aleatorio: " . json_encode($fungus['name'] ?? 'No encontrado'));
        }
        
        return $fungus;
    }
    
    /**
     * @brief Manejador para la ruta de hongo aleatorio
     * 
     * @param object $twig Instancia de Twig
     * @param object $db Instancia de la base de datos
     * @param object $session Controlador de sesión
     * @return array Datos para la plantilla
     */
    public function randomFungusHandler($twig, $db, $session)
    {
        // Obtener un hongo aleatorio
        $fungus = $this->getRandomFungus();
        
        if (!$fungus) {
            header('Location: /404');
            exit;
        }
        
        // Añadir información de like si el usuario está logueado
        if ($session->isLoggedIn()) {
            $fungus = $this->getFungusWithLikeStatus($fungus, $_SESSION['user_id']);
        }
        
        return [
            'title' => _('Hongo aleatorio'),
            'fungi' => $fungus
        ];
    }

    /**
     * @brief Manejador para la ruta de detalles del hongo
     * 
     * @param object $twig Instancia de Twig
     * @param object $db Instancia de la base de datos
     * @param object $session Controlador de sesión
     * @param array $params Parámetros de la ruta
     * @return array Datos para la plantilla
     */
    public function detailFungusHandler($twig, $db, $session, $params = [])
    {
        // Primero asegúrate de obtener el ID correctamente del parámetro
        $id = null;
        
        // Verificar si hay parámetros en el array numérico
        if (is_array($params) && !empty($params)) {
            $id = $params[0] ?? null;
        }
        
        // Si no hay ID aún, intenta obtenerlo de la URL
        if (empty($id) && isset($_SERVER['REQUEST_URI'])) {
            preg_match('#^/fungi/(\d+)$#', $_SERVER['REQUEST_URI'], $matches);
            $id = $matches[1] ?? null;
        }
        
        error_log("FungiController::detailFungusHandler - ID: " . ($id ?? 'no ID'));
        
        // El resto de tu lógica...
        $fungus = $this->getFungusById($id);
        
        if (!$fungus) {
            header('Location: /404');
            exit;
        }
      
        return [
            'title' => $fungus['name'] ?? _('Detalles del Hongo'),
            'fungi' => $fungus,
            'similar_fungi' => $similarFungi ?? [],
            'is_logged_in' => $session->isLoggedIn()
        ];
    }
} 