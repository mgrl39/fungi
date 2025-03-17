<?php

namespace App\Controllers;

/**
 * @class StatsController
 * @brief Controlador para generar estadísticas de hongos
 * 
 * Esta clase proporciona métodos para recuperar diferentes tipos de estadísticas
 * relacionadas con los hongos en la base de datos.
 */
class StatsController
{
    /**
     * @var \App\Controllers\DatabaseController $db Instancia del controlador de base de datos
     */
    private $db;

    /**
     * @brief Constructor del controlador de estadísticas
     * 
     * @param \App\Controllers\DatabaseController $db Instancia de la conexión a la base de datos
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
    /**
     * @brief Obtiene los hongos más likeados
     * 
     * @param int $limit Número máximo de resultados a retornar
     * @return array Los hongos con más likes
     */
    public function getTopLiked($limit = 5)
    {
        try {
            $result = $this->db->query(
                "SELECT f.id, f.name, COUNT(ul.id) as likes
                 FROM fungi f
                 JOIN user_likes ul ON f.id = ul.fungi_id
                 GROUP BY f.id
                 ORDER BY COUNT(ul.id) DESC
                 LIMIT $limit"
            );
            
            if ($result === false) {
                error_log("Error al obtener hongos más likeados: Consulta fallida");
                return [];
            }
            
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener hongos más likeados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @brief Obtiene los hongos más añadidos a favoritos
     * 
     * @param int $limit Número máximo de resultados a retornar
     * @return array Los hongos más añadidos a favoritos
     */
    public function getTopFavorites($limit = 5)
    {
        try {
            $result = $this->db->query(
                "SELECT f.id, f.name, COUNT(uf.id) as favorites
                 FROM fungi f
                 JOIN user_favorites uf ON f.id = uf.fungi_id
                 GROUP BY f.id
                 ORDER BY COUNT(uf.id) DESC
                 LIMIT $limit"
            );
            
            if ($result === false) {
                error_log("Error al obtener hongos favoritos: Consulta fallida");
                return [];
            }
            
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener hongos favoritos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @brief Obtiene la distribución de hongos por familia
     * 
     * @param int $limit Número máximo de familias a retornar
     * @return array Distribución de hongos por familia
     */
    public function getFamilyDistribution($limit = 10)
    {
        try {
            // Consulta optimizada que utiliza la tabla taxonomy para obtener datos más precisos
            $result = $this->db->query(
                "SELECT t.family as name, COUNT(*) as count FROM taxonomy t
                 JOIN fungi f ON t.fungi_id = f.id WHERE t.family IS NOT NULL AND t.family != '' 
                 AND t.family NOT LIKE '%unknown%' AND t.family NOT LIKE '%sin clasificar%'
                 GROUP BY t.family ORDER BY count DESC LIMIT $limit"
            );
            
            if ($result === false) {
                error_log("Error al obtener distribución por familia: Consulta fallida");
                return [];
            }
            
            $families = $result->fetchAll(\PDO::FETCH_ASSOC);
            
            // Procesar nombres de familia para mejorar visualización
            foreach ($families as &$family) {
                // Convertir primera letra en mayúscula y resto en minúscula para uniformidad
                if (isset($family['name'])) {
                    $family['name'] = ucfirst(strtolower($family['name']));
                }
            }
            
            return $families;
        } catch (\Exception $e) {
            error_log("Error al obtener distribución por familia: " . $e->getMessage());
            return [];
        }
    }
    /**
     * @brief Obtiene todas las estadísticas necesarias para la página de estadísticas
     * 
     * @return array Todas las estadísticas formateadas para la plantilla
     */
    public function getAllStatsForPage()
    {
        try {
            $totalFungiQuery = "SELECT COUNT(*) as count FROM fungi";
            $totalFungiResult = $this->db->query($totalFungiQuery);
            $totalFungi = ($totalFungiResult === false) ? 0 : $totalFungiResult->fetch(\PDO::FETCH_ASSOC)['count'];
            $totalUsersQuery = "SELECT COUNT(*) as count FROM users";
            $totalUsersResult = $this->db->query($totalUsersQuery);
            $totalUsers = ($totalUsersResult === false) ? 0 : $totalUsersResult->fetch(\PDO::FETCH_ASSOC)['count'];
            $edibilityQuery = "SELECT edibility, COUNT(*) as count 
                              FROM fungi 
                              WHERE edibility IS NOT NULL AND edibility != '' 
                              GROUP BY edibility 
                              ORDER BY count DESC";
            $edibilityResult = $this->db->query($edibilityQuery);
            $edibilityStats = ($edibilityResult === false) ? [] : $edibilityResult->fetchAll(\PDO::FETCH_ASSOC);
            $familiesStats = $this->getFamilyDistribution(10);
            $topLiked = $this->getTopLiked();
            $topFavorites = $this->getTopFavorites();
            return [
                'total_fungi' => $totalFungi, 'total_users' => $totalUsers, 'edibility' => $edibilityStats,
                'families' => $familiesStats, 'popular' => $topLiked, 'favorites' => $topFavorites
            ];
            
        } catch (\Exception $e) {
            error_log("Error al obtener estadísticas: " . $e->getMessage());
            return [
                'total_fungi' => 0, 'total_users' => 0, 'edibility' => [], 'families' => [],
                'popular' => [], 'favorites' => []
            ];
        }
    }

    /**
     * @brief Manejador de la página de estadísticas
     * 
     * Este método actúa como handler para la ruta de estadísticas,
     * devolviendo los datos necesarios para renderizar la página.
     * 
     * @param object $twig Instancia de Twig
     * @param object $db Instancia de la conexión a base de datos
     * @param object $session Controlador de sesión
     * @return array Datos para la plantilla de estadísticas
     */
    public function statisticsPageHandler($twig, $db, $session)
    {
        $stats = $this->getAllStatsForPage();
        if (empty($stats['families'])) error_log("Advertencia: No hay datos de familias disponibles para la vista de estadísticas");
        return [ 'title' => _('Estadísticas'), 'stats' => $stats,
            'api_url' => $this->getBaseUrl(), 'debug_families' => !empty($stats['families']) ];
    }

    /**
     * @brief Obtiene la URL base del sitio
     * 
     * @return string URL base del sitio
     */
    private function getBaseUrl() 
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . $host;
    }
} 