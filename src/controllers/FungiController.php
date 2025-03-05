<?php

namespace App\Controllers;

require_once __DIR__ . '/../../vendor/autoload.php';

class FungiController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function likeFungi($userId, $fungiId)
    {
        return $this->db->query(
            "INSERT INTO user_likes (user_id, fungi_id) VALUES (?, ?)",
            [$userId, $fungiId]
        );
    }

    public function unlikeFungi($userId, $fungiId)
    {
        return $this->db->query(
            "DELETE FROM user_likes WHERE user_id = ? AND fungi_id = ?",
            [$userId, $fungiId]
        );
    }

    private function updateFungiLikes($fungiId, $increment)
    {
        return $this->db->query(
            "UPDATE fungi_popularity SET likes = likes + ? WHERE fungi_id = ?",
            [$increment, $fungiId]
        );
    }

    public function incrementFungiViews($fungiId)
    {
        return $this->db->query(
            "INSERT INTO fungi_popularity (fungi_id, views, likes) 
             VALUES (?, 1, 0) 
             ON DUPLICATE KEY UPDATE views = views + 1",
            [$fungiId]
        );
    }

    public function hasUserLikedFungi($userId, $fungiId)
    {
        $result = $this->db->query(
            "SELECT 1 FROM user_likes WHERE user_id = ? AND fungi_id = ?",
            [$userId, $fungiId]
        );
        return !empty($result);
    }

    public function getFungusWithLikeStatus($fungus, $userId)
    {
        if ($fungus && $userId) {
            $fungus['is_liked'] = $this->hasUserLikedFungi($userId, $fungus['id']);
        }
        return $fungus;
    }

    public function getFungiStats()
    {
        // Estadísticas de comestibilidad
        $edibilityStats = $this->db->query(
            "SELECT edibility, COUNT(*) as count 
             FROM fungi 
             GROUP BY edibility 
             ORDER BY count DESC",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Estadísticas de familias más comunes
        $familyStats = $this->db->query(
            "SELECT family, COUNT(*) as count 
             FROM taxonomy 
             WHERE family IS NOT NULL 
             GROUP BY family 
             ORDER BY count DESC 
             LIMIT 10",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Hongos más vistos
        $popularFungi = $this->db->query(
            "SELECT f.name, fp.views 
             FROM fungi f 
             JOIN fungi_popularity fp ON f.id = fp.fungi_id 
             ORDER BY fp.views DESC 
             LIMIT 10",
            []
        )->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'edibility' => $edibilityStats,
            'families' => $familyStats,
            'popular' => $popularFungi
        ];
    }
} 