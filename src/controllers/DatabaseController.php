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

    /**
     * Ejecuta una consulta SQL preparada en la base de datos
     * 
     * @param string $sql     Consulta SQL para ejecutar
     * @param array  $params  Parámetros para vincular a la consulta [opcional, predeterminado=[]]
     * 
     * @return \PDOStatement|false Devuelve el objeto PDOStatement en caso de éxito o false si falla
     * 
     * @throws \PDOException Las excepciones son capturadas internamente y registradas
     */
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