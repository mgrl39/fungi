<?php
// src/controllers/DatabaseController.php
// hagamos el .env para la conexión a la base de datos

namespace App\Controllers;

use PDO;
use PDOException;

class DatabaseController {
    private $pdo;

    public function __construct() {
        $host = defined('DB_HOST') ? DB_HOST : getenv('DB_HOST');
        $dbname = defined('DB_NAME') ? DB_NAME : getenv('DB_NAME');
        $user = defined('DB_USER') ? DB_USER : getenv('DB_USER');
        $pass = defined('DB_PASS') ? DB_PASS : getenv('DB_PASS');

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    // Método para obtener fungis paginados
    public function getFungisPaginated($limit, $offset) {
        $stmt = $this->pdo->prepare("
            SELECT f.*, t.*, c.*,
                   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls
            FROM fungi f
            LEFT JOIN taxonomy t ON f.id = t.fungi_id
            LEFT JOIN characteristics c ON f.id = c.fungi_id
            LEFT JOIN fungi_images fi ON f.id = fi.fungi_id
            LEFT JOIN images i ON fi.image_id = i.id
            LEFT JOIN image_config ic ON i.config_key = ic.config_key
            GROUP BY f.id
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRandomFungus() {
        $stmt = $this->pdo->prepare("
            SELECT f.*, t.*, c.*, 
                   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls
            FROM fungi f
            LEFT JOIN taxonomy t ON f.id = t.fungi_id
            LEFT JOIN characteristics c ON f.id = c.fungi_id
            LEFT JOIN fungi_images fi ON f.id = fi.fungi_id
            LEFT JOIN images i ON fi.image_id = i.id
            LEFT JOIN image_config ic ON i.config_key = ic.config_key
            GROUP BY f.id
            ORDER BY RAND() LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

  
    public function createUser($username, $email, $password_hash) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash)
                VALUES (:username, :email, :password_hash)
            ");
            
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}