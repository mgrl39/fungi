<?php
// src/controllers/DatabaseController.php
// hagamos el .env para la conexión a la base de datos

class DatabaseController {
	private $pdo;

	public function __construct() {
		$host = $_ENV['DB_HOST'];
		$dbname = $_ENV['DB_NAME'];
		$user = $_ENV['DB_USER'];
		$pass = $_ENV['DB_PASS'];

		try {
			$this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			die("Error de conexión: " . $e->getMessage());
		}
	}

	// Método para obtener todos los registros (para pruebas o sin paginar)
	public function getAllData($table) {
		$stmt = $this->pdo->prepare("SELECT * FROM $table");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Método para obtener fungis paginados
	public function getFungisPaginated($limit, $offset) {
		$stmt = $this->pdo->prepare("SELECT * FROM fungi LIMIT :limit OFFSET :offset");
		$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Método para obtener los detalles de un fungus por id
	public function getFungusById($id) {
		$stmt = $this->pdo->prepare("SELECT * FROM fungi WHERE id = :id");
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

}