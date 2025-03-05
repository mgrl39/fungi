<?php

namespace App\Controllers;

require_once __DIR__ . '/../../vendor/autoload.php';

class StatsController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getFungiStats($timeRange = 'all')
    {
        return [
            'edibility' => $this->getEdibilityStats($timeRange),
            'families' => $this->getFamilyStats($timeRange),
            'popular' => $this->getPopularFungi($timeRange),
            'divisions' => $this->getDivisionStats($timeRange),
            'classes' => $this->getClassStats($timeRange),
            'orders' => $this->getOrderStats($timeRange),
            'userActivity' => $this->getUserActivityStats($timeRange)
        ];
    }

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
} 