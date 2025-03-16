<?php

namespace App\Controllers;

require_once __DIR__ . '/../../vendor/autoload.php';

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
     * @var \PDO $db Conexión a la base de datos
     */
    private $db;

    /**
     * @brief Constructor del controlador de estadísticas
     * 
     * @param \PDO $db Instancia de la conexión a la base de datos
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @brief Obtiene todas las estadísticas relacionadas con hongos
     * 
     * @param string $timeRange Período de tiempo para las estadísticas ('all', 'week', 'month', 'year')
     * @return array Conjunto completo de estadísticas organizadas por categorías
     */
    public function getFungiStats($timeRange = 'all')
    {
        return [
            'edibility' => $this->getEdibilityStats($timeRange),
            'families' => $this->getFamilyStats($timeRange),
            'popular' => $this->getPopularFungi($timeRange),
            'divisions' => $this->getDivisionStats($timeRange),
            'classes' => $this->getClassStats($timeRange),
            'orders' => $this->getOrderStats($timeRange),
        ];
    }

    /**
     * @brief Genera la condición SQL para filtrar por rango de tiempo
     * 
     * @param string $timeRange Rango de tiempo (all, week, month, year)
     * @return string Condición SQL para la consulta
     */
    private function getTimeRangeCondition($timeRange)
    {
        if ($timeRange === 'all') {
            return '';
        }
        
        $interval = match($timeRange) {
            'week' => '7 DAY',
            'month' => '30 DAY',
            'year' => '365 DAY',
            default => '0 DAY'
        };
        
        return "WHERE created_at >= DATE_SUB(NOW(), INTERVAL $interval)";
    }

    /**
     * @brief Obtiene estadísticas de comestibilidad de hongos
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Estadísticas de comestibilidad
     */
    public function getEdibilityStats($timeRange = 'all')
    {
        $timeCondition = $this->getTimeRangeCondition($timeRange);
        
        try {
            $result = $this->db->query(
                "SELECT edibility, COUNT(*) as count
                 FROM fungi
                 $timeCondition
                 GROUP BY edibility
                 ORDER BY count DESC",
                []
            );
            
            if ($result === false) {
                error_log("Error al obtener estadísticas de comestibilidad: Consulta fallida");
                return [];
            }
            
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener estadísticas de comestibilidad: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @brief Obtiene estadísticas sobre las familias de hongos más comunes
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Las 10 familias de hongos más comunes con sus recuentos
     */
    private function getFamilyStats($timeRange)
    {
        $timeCondition = $this->getTimeRangeCondition($timeRange);
        return $this->db->query(
            "SELECT t.family, COUNT(*) as count 
             FROM taxonomy t
             JOIN fungi f ON f.id = t.fungi_id
             $timeCondition
             WHERE t.family IS NOT NULL 
             GROUP BY t.family 
             ORDER BY count DESC 
             LIMIT 10",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @brief Obtiene los hongos más populares basados en vistas
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Los 10 hongos más vistos con sus contadores de vistas
     */
    private function getPopularFungi($timeRange)
    {
        $timeCondition = str_replace('created_at', 'fp.last_view', $this->getTimeRangeCondition($timeRange));
        return $this->db->query(
            "SELECT f.name, fp.views 
             FROM fungi f 
             JOIN fungi_popularity fp ON f.id = fp.fungi_id 
             $timeCondition
             ORDER BY fp.views DESC 
             LIMIT 10",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @brief Obtiene estadísticas sobre las divisiones taxonómicas de hongos
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Datos de divisiones agrupados y contados
     */
    private function getDivisionStats($timeRange)
    {
        $timeCondition = $this->getTimeRangeCondition($timeRange);
        return $this->db->query(
            "SELECT division, COUNT(*) as count 
             FROM taxonomy 
             WHERE division IS NOT NULL 
             GROUP BY division 
             ORDER BY count DESC",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @brief Obtiene estadísticas sobre las clases taxonómicas de hongos
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Las 10 clases más comunes con sus recuentos
     */
    private function getClassStats($timeRange) 
    {
        $timeCondition = $this->getTimeRangeCondition($timeRange);
        return $this->db->query(
            "SELECT class, COUNT(*) as count 
             FROM taxonomy 
             WHERE class IS NOT NULL 
             GROUP BY class 
             ORDER BY count DESC 
             LIMIT 10",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @brief Obtiene estadísticas sobre los órdenes taxonómicos de hongos
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Los 10 órdenes más comunes con sus recuentos
     */
    private function getOrderStats($timeRange)
    {
        $timeCondition = $this->getTimeRangeCondition($timeRange);
        return $this->db->query(
            "SELECT ordo, COUNT(*) as count 
             FROM taxonomy 
             WHERE ordo IS NOT NULL 
             GROUP BY ordo 
             ORDER BY count DESC 
             LIMIT 10",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @brief Obtiene estadísticas sobre la actividad de los usuarios
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Datos de actividad de usuario agrupados por acción y fecha
     */
    private function getUserActivityStats($timeRange)
    {
        $interval = match($timeRange) {
            'week' => '7 DAY',
            'month' => '30 DAY',
            'year' => '365 DAY',
            default => '30 DAY'
        };

        return $this->db->query(
            "SELECT action, COUNT(*) as count,
             DATE_FORMAT(access_time, '%Y-%m-%d') as date
             FROM access_logs
             WHERE access_time >= DATE_SUB(NOW(), INTERVAL $interval)
             GROUP BY action, date
             ORDER BY date ASC",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @brief Obtiene estadísticas generales sobre los hongos en la base de datos
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Estadísticas generales sobre los hongos
     */
    public function getGeneralStats($timeRange = 'all')
    {
        // Aquí puedes agregar estadísticas adicionales que necesites
        return [
            'total_count' => $this->getTotalFungiCount($timeRange),
            'edibility_summary' => $this->getEdibilityStats($timeRange),
            'recent_additions' => $this->getRecentAdditions($timeRange),
        ];
    }

    /**
     * @brief Obtiene el número total de hongos en la base de datos
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return int Número total de hongos
     */
    private function getTotalFungiCount($timeRange)
    {
        $timeCondition = $this->getTimeRangeCondition($timeRange);
        $result = $this->db->query(
            "SELECT COUNT(*) as count 
             FROM fungi 
             $timeCondition",
            []
        )->fetch(\PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
    }

    /**
     * @brief Obtiene los hongos añadidos recientemente
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Los 5 hongos más recientes
     */
    private function getRecentAdditions($timeRange)
    {
        $timeCondition = $this->getTimeRangeCondition($timeRange);
        
        try {
            $result = $this->db->query(
                "SELECT id, name, date_added as created_at
                 FROM fungi 
                 $timeCondition
                 ORDER BY date_added DESC 
                 LIMIT 5",
                []
            );
            
            if ($result === false) {
                error_log("Error al obtener hongos recientes: Consulta fallida");
                return [];
            }
            
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener hongos recientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @brief Obtiene las tendencias de adición de hongos en diferentes períodos
     * 
     * @param string $timeRange Período de tiempo para filtrar resultados
     * @return array Datos de tendencias de adición agrupados por período
     */
    public function getAdditionTrends($timeRange = 'year')
    {
        $groupFormat = match($timeRange) {
            'week' => '%Y-%m-%d',    // Diario para semana
            'month' => '%Y-%m-%d',   // Diario para mes
            'year' => '%Y-%m',       // Mensual para año
            default => '%Y-%m'       // Por defecto mensual
        };
        
        $interval = match($timeRange) {
            'week' => '7 DAY',
            'month' => '30 DAY',
            'year' => '365 DAY',
            default => '365 DAY'
        };
        
        try {
            $result = $this->db->query(
                "SELECT DATE_FORMAT(date_added, ?) as period, 
                 COUNT(*) as count
                 FROM fungi 
                 WHERE date_added >= DATE_SUB(NOW(), INTERVAL ?)
                 GROUP BY period
                 ORDER BY date_added ASC",
                [$groupFormat, $interval]
            );
            
            if ($result === false) {
                error_log("Error al obtener tendencias de adición: Consulta fallida");
                return [];
            }
            
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener tendencias de adición: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @brief Obtiene las familias de hongos más comunes
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @param int $limit Número máximo de familias a retornar
     * @return array Las familias de hongos más comunes con sus recuentos
     */
    public function getTopFamilies($timeRange = 'all', $limit = 10)
    {
        $timeCondition = $this->getTimeRangeCondition($timeRange);
        
        try {
            $result = $this->db->query(
                "SELECT t.family, COUNT(*) as count 
                 FROM taxonomy t
                 JOIN fungi f ON f.id = t.fungi_id
                 $timeCondition
                 AND t.family IS NOT NULL 
                 GROUP BY t.family 
                 ORDER BY count DESC 
                 LIMIT ?",
                [$limit]
            );
            
            if ($result === false) {
                error_log("Error al obtener familias más comunes: Consulta fallida");
                return [];
            }
            
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener familias más comunes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @brief Obtiene los hongos más visitados
     * 
     * @param int $limit Número máximo de resultados a retornar
     * @return array Los hongos más visitados con sus contadores
     */
    public function getMostViewedFungi($limit = 5)
    {
        try {
            $result = $this->db->query(
                "SELECT f.id, f.name, f.view_count as views
                 FROM fungi f
                 WHERE f.view_count > 0
                 ORDER BY f.view_count DESC
                 LIMIT ?",
                [$limit]
            );
            
            if ($result === false) {
                error_log("Error al obtener hongos más vistos: Consulta fallida");
                return [];
            }
            
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener hongos más vistos: " . $e->getMessage());
            return [];
        }
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
                 LIMIT ?",
                [$limit]
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
                 LIMIT ?",
                [$limit]
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
     * @brief Obtiene la distribución de hongos por hábitat
     * 
     * @param int $limit Número máximo de resultados a retornar
     * @return array Distribución de hongos por hábitat
     */
    public function getHabitatDistribution($limit = 10)
    {
        try {
            $result = $this->db->query(
                "SELECT habitat as name, COUNT(*) as count
                 FROM fungi
                 WHERE habitat IS NOT NULL AND habitat != ''
                 GROUP BY habitat
                 ORDER BY count DESC
                 LIMIT ?",
                [$limit]
            );
            
            if ($result === false) {
                error_log("Error al obtener distribución por hábitat: Consulta fallida");
                return [];
            }
            
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error al obtener distribución por hábitat: " . $e->getMessage());
            return [];
        }
    }
} 