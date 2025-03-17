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
        return $this->db->query("INSERT INTO user_likes (user_id, fungi_id) VALUES (?, ?)", [$userId, $fungiId]);
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
        return $this->db->query("DELETE FROM user_likes WHERE user_id = ? AND fungi_id = ?", [$userId, $fungiId]);
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
        return $this->db->query("UPDATE fungi_popularity SET likes = likes + ? WHERE fungi_id = ?", [$increment, $fungiId]);
    }

    /**
     * @brief Incrementa el contador de vistas de un hongo
     * 
     * @param int $fungiId ID del hongo
     * 
     * @return bool Resultado de la operación
     */
    public function incrementFungiViews($fungiId)
    {
        try {
            return $this->db->query("INSERT INTO fungi_popularity (fungi_id, views, likes) VALUES (?, 1, 0) ON DUPLICATE KEY UPDATE views = views + 1", [$fungiId]);
        } catch (\Exception $e) {
            error_log("Error incrementando vistas: " . $e->getMessage());
            return false;
        }
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
        $result = $this->db->query("SELECT 1 FROM user_likes WHERE user_id = ? AND fungi_id = ?", [$userId, $fungiId]);
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
        if ($fungus && $userId) $fungus['is_liked'] = $this->hasUserLikedFungi($userId, $fungus['id']);
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
        $result = $this->db->query("SELECT f.*, 
                                    c.cap, c.hymenium, c.stipe, c.flesh,
                                    t.division, t.subdivision, t.class, t.subclass, t.ordo as order_name, t.family,
                                    GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                                    FROM fungi f 
                                    LEFT JOIN characteristics c ON f.id = c.fungi_id
                                    LEFT JOIN taxonomy t ON f.id = t.fungi_id
                                    LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                                    LEFT JOIN images i ON fi.image_id = i.id 
                                    LEFT JOIN image_config ic ON i.config_key = ic.config_key
                                    WHERE f.id = :id
                                    GROUP BY f.id", ['id' => $id]);
        
        if ($result instanceof \PDOStatement) $fungus = $result->fetch(\PDO::FETCH_ASSOC) ?: null;
        else {
            if (is_array($result) && !empty($result) && isset($result[0])) $fungus = $result[0];
            else $fungus = $result;
        }
        if ($fungus) {
            $fungus = $this->loadFungusImages($fungus);
            
            // Formatear características para mostrar en la plantilla
            if (isset($fungus['cap']) || isset($fungus['hymenium']) || isset($fungus['stipe']) || isset($fungus['flesh'])) {
                $characteristics = [];
                if (!empty($fungus['cap'])) $characteristics[] = "Sombrero: " . $fungus['cap'];
                if (!empty($fungus['hymenium'])) $characteristics[] = "Himenio: " . $fungus['hymenium'];
                if (!empty($fungus['stipe'])) $characteristics[] = "Pie: " . $fungus['stipe'];
                if (!empty($fungus['flesh'])) $characteristics[] = "Carne: " . $fungus['flesh'];
                
                $fungus['characteristics'] = implode("\n", $characteristics);
            }
            
            // Para la descripción, usar el campo observations
            if (!empty($fungus['observations'])) {
                $fungus['description'] = $fungus['observations'];
            }
        }
        return $fungus;
    }

    // Procesar el resultado según el tipo de retorno de la DB
    // Incrementar el contador de vistas para este hongo
    // Cargar las imágenes relacionadas 
    // Registrar para depuración
    /**
     * @brief Obtiene un hongo aleatorio de la base de datos
     * 
     * @return array|null Datos del hongo aleatorio o null si no se encuentra ninguno
     */
    public function getRandomFungus()
    {
        $query = "SELECT f.*, 
                 c.cap, c.hymenium, c.stipe, c.flesh,
                 t.division, t.subdivision, t.class, t.subclass, t.ordo as order_name, t.family,
                 GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                 FROM fungi f LEFT JOIN characteristics c ON f.id = c.fungi_id
                 LEFT JOIN taxonomy t ON f.id = t.fungi_id LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                 LEFT JOIN images i ON fi.image_id = i.id LEFT JOIN image_config ic ON i.config_key = ic.config_key
                 GROUP BY f.id ORDER BY RAND() LIMIT 1";
                 
        $randomResult = $this->db->query($query);
        if ($randomResult instanceof \PDOStatement) $fungus = $randomResult->fetch(\PDO::FETCH_ASSOC);
        else if (is_array($randomResult) && !empty($randomResult)) $fungus = isset($randomResult[0]) ? $randomResult[0] : $randomResult;
        else $fungus = null;
        
        if ($fungus) {
            $this->incrementFungiViews($fungus['id']);
            $fungus = $this->loadFungusImages($fungus);
            
            // Formatear características para mostrar en la plantilla
            if (isset($fungus['cap']) || isset($fungus['hymenium']) || isset($fungus['stipe']) || isset($fungus['flesh'])) {
                $characteristics = [];
                if (!empty($fungus['cap'])) $characteristics[] = "Sombrero: " . $fungus['cap'];
                if (!empty($fungus['hymenium'])) $characteristics[] = "Himenio: " . $fungus['hymenium'];
                if (!empty($fungus['stipe'])) $characteristics[] = "Pie: " . $fungus['stipe'];
                if (!empty($fungus['flesh'])) $characteristics[] = "Carne: " . $fungus['flesh'];
                
                $fungus['characteristics'] = implode("\n", $characteristics);
            }
            
            // Para la descripción, usar el campo observations
            if (!empty($fungus['observations'])) {
                $fungus['description'] = $fungus['observations'];
            }
            
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
    // Consultar las imágenes relacionadas a través de la tabla de relación
    // Si hay resultados, procesarlos
    // Si no hay imágenes, usar una por defecto
    // Registrar para depuración
    // Si no hay imágenes, usar una por defecto
    // Guardar las URLs como una cadena separada por comas (para mantener compatibilidad)
    // Para depuración
    private function loadFungusImages($fungus)
    {
        $imagesResult = $this->db->query(
            "SELECT i.filename, i.config_key FROM images i JOIN fungi_images fi ON i.id = fi.image_id WHERE fi.fungi_id = ?",
            [$fungus['id']]
        );
        $imageUrls = [];
        if ($imagesResult) {
            if ($imagesResult instanceof \PDOStatement) $images = $imagesResult->fetchAll(\PDO::FETCH_ASSOC);
            else $images = is_array($imagesResult) ? $imagesResult : [$imagesResult];
            foreach ($images as $image) {
                $basePath = $this->getImageBasePath($image['config_key']);
                $imageUrls[] = $basePath . '/' . $image['filename'];
            }
        }
        if (empty($imageUrls)) {
            $imageUrls[] = '/assets/images/placeholder.jpg';
            error_log("No se encontraron imágenes para el hongo ID: " . $fungus['id']);
        }
        $fungus['image_urls'] = implode(',', $imageUrls);
        error_log("URLs de imágenes para hongo ID " . $fungus['id'] . ": " . $fungus['image_urls']);
        return $fungus;
    }
    
    /**
     * @brief Obtiene la ruta base para las imágenes según la clave de configuración
     * 
     * @param string $configKey Clave de configuración
     * @return string Ruta base para las imágenes
     */
     // Mapeo de claves de configuración a rutas base
     // Devolver la ruta correspondiente o una ruta predeterminada

    private function getImageBasePath($configKey)
    {
        $pathMap = ['fungi_upload_path' => '/assets/images', 'user_upload_path' => '/assets/images/users', 'system_images' => '/assets/images/system'];
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

    /**
     * @brief Actualiza la información de un hongo
     * 
     * @param int $fungiId ID del hongo a actualizar
     * @param array $data Nuevos datos del hongo
     * @param array $user Usuario que realiza la operación
     * @return array Resultado de la operación
     */
    public function updateFungi($fungiId, $data, $user = null)
    {
        // Verificar permisos de administrador si se proporciona usuario
        if ($user && (!isset($user['role']) || $user['role'] !== 'admin')) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para realizar esta operación'
            ];
        }
        
        // Verificar que el hongo exista
        $checkResult = $this->db->query("SELECT id FROM fungi WHERE id = ?", [$fungiId]);
        if (!$checkResult || ($checkResult instanceof \PDOStatement && !$checkResult->fetch())) {
            return [
                'success' => false,
                'error' => 'El hongo especificado no existe'
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
            return [
                'success' => false,
                'error' => 'No se proporcionaron campos válidos para actualizar'
            ];
        }
        
        // Agregar ID para la cláusula WHERE
        $values[] = $fungiId;
        
        try {
            $sql = "UPDATE fungi SET " . implode(', ', $fields) . " WHERE id = ?";
            $result = $this->db->query($sql, $values);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Hongo actualizado correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al actualizar el hongo en la base de datos'
                ];
            }
        } catch (\Exception $e) {
            error_log("Error actualizando hongo: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ];
        }
    }

    /**
     * @brief Añade un nuevo hongo a la base de datos
     * 
     * @param array $data Datos del nuevo hongo
     * @param array $user Usuario que realiza la operación
     * @return array Resultado de la operación
     */
    public function addFungi($data, $user = null)
    {
        // Verificar permisos de administrador si se proporciona usuario
        if ($user && (!isset($user['role']) || $user['role'] !== 'admin')) {
            return [
                'success' => false,
                'error' => 'No tienes permisos para crear nuevos hongos'
            ];
        }
        
        // Validar campos obligatorios (actualizados para coincidir con createFungi)
        $requiredFields = ['name', 'edibility', 'habitat'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "El campo '{$field}' es obligatorio"
                ];
            }
        }
        
        // Preparar campos y valores para la inserción
        $allowedFields = ['name', 'edibility', 'habitat', 'observations', 'common_name', 'synonym', 'title'];
        $fields = [];
        $values = [];
        $placeholders = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $fields[] = $field;
                $values[] = $value;
                $placeholders[] = '?';
            }
        }
        
        if (empty($fields)) {
            return [
                'success' => false,
                'error' => 'No se proporcionaron campos válidos para crear el hongo'
            ];
        }
        
        try {
            $sql = "INSERT INTO fungi (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $result = $this->db->query($sql, $values);
            
            if ($result) {
                // Obtener el ID del nuevo hongo insertado
                $newFungiId = $this->db->lastInsertId();
                
                // Inicializar registro en fungi_popularity
                $this->db->query(
                    "INSERT INTO fungi_popularity (fungi_id, views, likes) VALUES (?, 0, 0)",
                    [$newFungiId]
                );
                
                return [
                    'success' => true,
                    'message' => 'Hongo creado correctamente',
                    'id' => $newFungiId
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al crear el hongo en la base de datos'
                ];
            }
        } catch (\Exception $e) {
            error_log("Error creando hongo: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ];
        }
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
        
        // Registrar lo que estamos haciendo
        error_log("getFungiPaginated: Buscando página $page con límite $limit (offset $offset)");
        
        // Consulta para obtener los hongos con sus imágenes - usar query en lugar de prepare
        $sql = "SELECT f.*, GROUP_CONCAT(DISTINCT CONCAT(ic.path, '/', i.filename)) as image_urls 
                FROM fungi f LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                LEFT JOIN images i ON fi.image_id = i.id LEFT JOIN image_config ic ON i.config_key = ic.config_key
                GROUP BY f.id LIMIT ?, ?";
        $fungi = $this->db->query($sql, [$offset, $limit]);
        // Registrar lo que obtuvimos
        error_log("getFungiPaginated: Encontrados " . (is_array($fungi) ? count($fungi) : 'no') . " hongos");
        if (is_array($fungi) && count($fungi) > 0) {
            error_log("Primer hongo: " . json_encode($fungi[0]));
        }
        
        // Asegurarnos de que $fungi sea un array
        $fungi = is_array($fungi) ? $fungi : [];
        
        // Consulta para contar el total de hongos
        $totalCount = $this->db->query("SELECT COUNT(*) as total FROM fungi");
        if (is_array($totalCount) && !empty($totalCount)) $totalCount = $totalCount[0]['total'] ?? count($totalCount);
        else $totalCount = 0;
        error_log("getFungiPaginated: Total de hongos: $totalCount");
        
        // Calcular la información de paginación
        $totalPages = ceil($totalCount / $limit);
        
        return [
            'success' => true, 
            'data' => $fungi,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => (int)$totalPages,
                'records_per_page' => (int)$limit,
                'total_records' => (int)$totalCount
            ]
        ];
    }

    /**
     * Obtiene la lista completa de hongos
     * 
     * @return array Lista de hongos
     */
    public function getAllFungi()
    {
        $fungi = $this->db->query("SELECT * FROM fungi");
        return [
            'success' => true, 
            'data' => $fungi
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
            return [
                'success' => false,
                'error' => 'Parámetro de búsqueda no válido. Parámetros permitidos: ' . implode(', ', $allowedParams)
            ];
        }
        
        try {
            // Construir la consulta de búsqueda segura
            $sql = "SELECT f.*, GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                    FROM fungi f 
                    LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                    LEFT JOIN images i ON fi.image_id = i.id 
                    LEFT JOIN image_config ic ON i.config_key = ic.config_key
                    WHERE f.{$param} LIKE ?
                    GROUP BY f.id
                    LIMIT 50";
            
            $results = $this->db->query($sql, ['%' . $value . '%']);
            
            return [
                'success' => true,
                'count' => count($results),
                'data' => $results
            ];
        } catch (\Exception $e) {
            error_log("Error en búsqueda de hongos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en la búsqueda: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene un hongo aleatorio
     * 
     * @param int|null $userId ID del usuario actual (para likes)
     * @return array Datos del hongo aleatorio
     */
    public function getRandomFungusWithDetails($userId = null)
    {
        $sql = "SELECT f.*, GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                FROM fungi f 
                LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                LEFT JOIN images i ON fi.image_id = i.id 
                LEFT JOIN image_config ic ON i.config_key = ic.config_key
                GROUP BY f.id
                ORDER BY RAND() 
                LIMIT 1";
        
        $fungus = $this->db->query($sql);
        
        if (empty($fungus)) {
            return [
                'success' => false, 
                'error' => 'No hay hongos disponibles'
            ];
        }
        
        // Convertir resultado a un solo hongo
        if (is_array($fungus) && !empty($fungus)) {
            $fungus = $fungus[0];
        }
        
        // Incrementar contador de vistas
        $this->incrementFungiViews($fungus['id']);
        
        // Si hay un usuario autenticado, verificar si le dio like
        if ($userId) {
            $fungus = $this->getFungusWithLikeStatus($fungus, $userId);
        }
        
        return [
            'success' => true,
            'data' => $fungus
        ];
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
                WHERE f.edibility LIKE ?
                GROUP BY f.id
                LIMIT 50";
        
        $fungi = $this->db->query($sql, ['%' . $edibility . '%']);
        
        return [
            'success' => true,
            'data' => $fungi
        ];
    }

    /**
     * Obtiene detalles completos de un hongo específico, incluyendo estadísticas
     * 
     * @param int $id ID del hongo
     * @param int|null $userId ID del usuario actual (para likes)
     * @return array Detalles del hongo
     */
    public function getDetailedFungusById($id, $userId = null)
    {
        // Consulta para obtener datos básicos del hongo
        $sql = "SELECT f.*, 
                c.cap, c.hymenium, c.stipe, c.flesh,
                GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
                FROM fungi f 
                LEFT JOIN characteristics c ON f.id = c.fungi_id
                LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
                LEFT JOIN images i ON fi.image_id = i.id 
                LEFT JOIN image_config ic ON i.config_key = ic.config_key
                WHERE f.id = :id
                GROUP BY f.id";
        
        $fungi = $this->db->query($sql, ['id' => $id]);
        
        // Procesar resultado según el tipo de retorno de la DB
        if (is_array($fungi) && !empty($fungi)) {
            $fungus = $fungi[0];
        } else {
            return [
                'success' => false, 
                'error' => 'Registro no encontrado'
            ];
        }
        
        // Obtener estadísticas: vistas y likes
        $statsSql = "SELECT views, likes FROM fungi_popularity WHERE fungi_id = ?";
        $stats = $this->db->query($statsSql, [$id]);
        
        if (!empty($stats)) {
            $fungus['views'] = $stats[0]['views'] ?? 0;
            $fungus['likes'] = $stats[0]['likes'] ?? 0;
        } else {
            $fungus['views'] = 0;
            $fungus['likes'] = 0;
        }
        
        // Formatear características para mostrar en la plantilla
        if (isset($fungus['cap']) || isset($fungus['hymenium']) || isset($fungus['stipe']) || isset($fungus['flesh'])) {
            $characteristics = [];
            if (!empty($fungus['cap'])) $characteristics[] = "Sombrero: " . $fungus['cap'];
            if (!empty($fungus['hymenium'])) $characteristics[] = "Himenio: " . $fungus['hymenium'];
            if (!empty($fungus['stipe'])) $characteristics[] = "Pie: " . $fungus['stipe'];
            if (!empty($fungus['flesh'])) $characteristics[] = "Carne: " . $fungus['flesh'];
            
            $fungus['characteristics'] = implode("\n", $characteristics);
        }
        
        // Si hay un usuario autenticado, verificar si le dio like
        if ($userId) {
            $fungus = $this->getFungusWithLikeStatus($fungus, $userId);
        }
        
        // Incrementar contador de vistas
        $this->incrementFungiViews($id);
        
        return [
            'success' => true,
            'data' => $fungus
        ];
    }
} 