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
     * @param string $timeRange Período de tiempo ('week', 'month', 'year', 'all')
     * @return string Cláusula WHERE SQL para el filtrado por tiempo
     */
    private function getTimeRangeCondition($timeRange)
    {
        switch ($timeRange) {
            case 'week':
                return "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            case 'month':
                return "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'year':
                return "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "";
        }
    }

    /**
     * @brief Obtiene estadísticas sobre la comestibilidad de los hongos
     * 
     * @param string $timeRange Período de tiempo para filtrar los resultados
     * @return array Datos de comestibilidad agrupados y contados
     */
    private function getEdibilityStats($timeRange)
    {
        $timeCondition = $this->getTimeRangeCondition($timeRange);
        return $this->db->query(
            "SELECT edibility, COUNT(*) as count 
             FROM fungi 
             $timeCondition
             GROUP BY edibility 
             ORDER BY count DESC",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);
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
     * Obtiene los hongos más visitados
     * 
     * @param int $limit Número máximo de hongos a retornar
     * @return array Lista de hongos más visitados
     */
    public function getMostViewedFungi($limit = 5)
    {
        return $this->db->query(
            "SELECT f.id, f.name, COUNT(av.id) as views,
             CONCAT(ic.path, i.filename) as image_url
             FROM fungi f
             JOIN access_logs av ON f.id = av.item AND av.action = 'view_fungi'
             LEFT JOIN fungi_images fi ON f.id = fi.fungi_id AND fi.is_primary = 1
             LEFT JOIN images i ON fi.image_id = i.id
             LEFT JOIN image_config ic ON i.config_key = ic.config_key
             GROUP BY f.id
             ORDER BY views DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Obtiene los hongos más favoritos
     * 
     * @param int $limit Número máximo de hongos a retornar
     * @return array Lista de hongos más añadidos a favoritos
     */
    public function getTopFavorites($limit = 5)
    {
        return $this->db->query(
            "SELECT f.id, f.name, COUNT(uf.id) as favorites,
             CONCAT(ic.path, i.filename) as image_url
             FROM fungi f
             JOIN user_favorites uf ON f.id = uf.fungi_id
             LEFT JOIN fungi_images fi ON f.id = fi.fungi_id AND fi.is_primary = 1
             LEFT JOIN images i ON fi.image_id = i.id
             LEFT JOIN image_config ic ON i.config_key = ic.config_key
             GROUP BY f.id
             ORDER BY favorites DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Obtiene los hongos más likeados
     * 
     * @param int $limit Número máximo de hongos a retornar
     * @return array Lista de hongos con más likes
     */
    public function getTopLiked($limit = 5)
    {
        return $this->db->query(
            "SELECT f.id, f.name, COUNT(ul.id) as likes,
             CONCAT(ic.path, i.filename) as image_url
             FROM fungi f
             JOIN user_likes ul ON f.id = ul.fungi_id
             LEFT JOIN fungi_images fi ON f.id = fi.fungi_id AND fi.is_primary = 1
             LEFT JOIN images i ON fi.image_id = i.id
             LEFT JOIN image_config ic ON i.config_key = ic.config_key
             GROUP BY f.id
             ORDER BY likes DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Obtiene la distribución de hongos por hábitat
     * 
     * @return array Estadísticas de distribución por hábitat
     */
    public function getHabitatDistribution()
    {
        return $this->db->query(
            "SELECT habitat as name, COUNT(*) as count
             FROM fungi
             WHERE habitat IS NOT NULL AND habitat != ''
             GROUP BY habitat
             ORDER BY count DESC
             LIMIT 10",
            []
        );
    }
} 