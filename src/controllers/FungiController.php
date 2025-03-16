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
            $fungus = $result->fetch(\PDO::FETCH_ASSOC) ?: null;
        } else {
            // Si ya es un array con índices numéricos (múltiples filas)
            if (is_array($result) && !empty($result) && isset($result[0])) {
                $fungus = $result[0];
            } else {
                // Si ya es un array asociativo simple o está vacío
                $fungus = $result ?: null;
            }
        }
        
        // Obtener las imágenes relacionadas
        if ($fungus) {
            $fungus = $this->loadFungusImages($fungus);
        }
        
        return $fungus;
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
            
            // Cargar las imágenes relacionadas
            $fungus = $this->loadFungusImages($fungus);
            
            // Registrar para depuración
            error_log("Cargando hongo aleatorio: " . json_encode($fungus['name'] ?? 'No encontrado'));
        }
        
        return $fungus;
    }
    
    /**
     * @brief Carga las imágenes relacionadas con un hongo desde la tabla de relaciones
     * 
     * @param array $fungus Datos del hongo
     * @return array Datos del hongo con las URLs de imágenes añadidas
     */
    private function loadFungusImages($fungus)
    {
        // Consultar las imágenes relacionadas a través de la tabla de relación
        $imagesResult = $this->db->query(
            "SELECT i.filename, i.config_key 
             FROM images i 
             JOIN fungi_images fi ON i.id = fi.image_id 
             WHERE fi.fungi_id = ?",
            [$fungus['id']]
        );
        
        $imageUrls = [];
        
        // Si hay resultados, procesarlos
        if ($imagesResult) {
            if ($imagesResult instanceof \PDOStatement) {
                $images = $imagesResult->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $images = is_array($imagesResult) ? $imagesResult : [$imagesResult];
            }
            
            foreach ($images as $image) {
                // Construir la URL completa de la imagen basada en la config_key
                $basePath = $this->getImageBasePath($image['config_key']);
                $imageUrls[] = $basePath . '/' . $image['filename'];
            }
        }
        
        // Si no hay imágenes, usar una por defecto
        if (empty($imageUrls)) {
            $imageUrls[] = '/assets/images/placeholder.jpg';
            error_log("No se encontraron imágenes para el hongo ID: " . $fungus['id']);
        }
        
        // Guardar las URLs como una cadena separada por comas (para mantener compatibilidad)
        $fungus['image_urls'] = implode(',', $imageUrls);
        
        // Para depuración
        error_log("URLs de imágenes para hongo ID " . $fungus['id'] . ": " . $fungus['image_urls']);
        
        return $fungus;
    }
    
    /**
     * @brief Obtiene la ruta base para las imágenes según la clave de configuración
     * 
     * @param string $configKey Clave de configuración
     * @return string Ruta base para las imágenes
     */
    private function getImageBasePath($configKey)
    {
        // Mapeo de claves de configuración a rutas base
        $pathMap = [
            'fungi_upload_path' => '/assets/images',
            'user_upload_path' => '/assets/images/users',
            'system_images' => '/assets/images/system'
        ];
        
        // Devolver la ruta correspondiente o una ruta predeterminada
        return $pathMap[$configKey] ?? '/assets/images';
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
     * @param object $twig Instancia de Twig
     * @param object $db Instancia de la base de datos
     * @param object $session Controlador de sesión
     * @param array $params Parámetros de la ruta
     * @return array Datos para la plantilla
     */
    public function detailFungusHandler($twig, $db, $session, $params = [])
    {
        $id = null;
        
        if (is_array($params) && !empty($params)) {
            $id = $params[0] ?? null;
        }
        
        if (empty($id) && isset($_SERVER['REQUEST_URI'])) {
            preg_match('#^/fungi/(\d+)$#', $_SERVER['REQUEST_URI'], $matches);
            $id = $matches[1] ?? null;
        }
        
        error_log("FungiController::detailFungusHandler - ID: " . ($id ?? 'no ID'));       
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