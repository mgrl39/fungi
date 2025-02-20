<?php

namespace App\Controllers;

use PDO;

class ApiController
{
	private $pdo;

	public function __construct()
	{
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

	/**
	 * Manejador de solicitudes HTTP.
	 */
	public function handleRequest()
	{
		header('Content-Type: application/json');
		$method = $_SERVER['REQUEST_METHOD'];
		$endpoint = $_GET['endpoint'] ?? '';

		try {
			switch ($method) {
			case 'GET':
				$this->handleGet($endpoint);
				break;
			case 'POST':
				$this->handlePost($endpoint);
				break;
			default:
				http_response_code(405); // Método no permitido
				echo json_encode(['error' => 'Método no permitido']);
				break;
			}
		} catch (\Exception $e) {
			http_response_code(500); // Error interno del servidor
			echo json_encode(['error' => 'Error interno del servidor', 'message' => $e->getMessage()]);
		}
	}

	/**
	 * Manejador de solicitudes GET.
	 */
	private function handleGet($endpoint)
	{
		if ($endpoint === 'fungi') {
			$stmt = $this->pdo->query("SELECT * FROM fungi");
			echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
		} elseif (preg_match('/^fungi\/(\d+)$/', $endpoint, $matches)) {
			$id = $matches[1];
			$stmt = $this->pdo->prepare("SELECT * FROM fungi WHERE id = ?");
			$stmt->execute([$id]);
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($result) {
				echo json_encode($result);
			} else {
				http_response_code(404);
				echo json_encode(['error' => 'Hongo no encontrado']);
			}
		} elseif ($endpoint === 'users') {
			$stmt = $this->pdo->query("SELECT id, username, email, role, created_at FROM users");
			echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Endpoint no encontrado']);
		}
	}

	/**
	 * Manejador de solicitudes POST.
	 */
	private function handlePost($endpoint)
	{
		$data = json_decode(file_get_contents('php://input'), true);

		if ($endpoint === 'fungi') {
			$requiredFields = ['name', 'author', 'edibility', 'habitat', 'observations', 'common_name', 'synonym', 'title'];
			if (!$this->validateRequiredFields($data, $requiredFields)) {
				http_response_code(400);
				echo json_encode(['error' => 'Faltan campos obligatorios']);
				return;
			}

			$stmt = $this->pdo->prepare("INSERT INTO fungi (name, author, edibility, habitat, observations, common_name, synonym, title) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->execute([
				$data['name'],
				$data['author'],
				$data['edibility'],
				$data['habitat'],
				$data['observations'],
				$data['common_name'],
				$data['synonym'],
				$data['title']
			]);

			echo json_encode(['id' => $this->pdo->lastInsertId()]);
		} elseif ($endpoint === 'users') {
			$requiredFields = ['username', 'email', 'password'];
			if (!$this->validateRequiredFields($data, $requiredFields)) {
				http_response_code(400);
				echo json_encode(['error' => 'Faltan campos obligatorios']);
				return;
			}

			$passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
			$stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
			$stmt->execute([$data['username'], $data['email'], $passwordHash]);

			echo json_encode(['id' => $this->pdo->lastInsertId()]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Endpoint no encontrado']);
		}
	}

	/**
	 * Validación de campos requeridos.
	 */
	private function validateRequiredFields(array $data, array $requiredFields): bool
	{
		foreach ($requiredFields as $field) {
			if (!isset($data[$field]) || empty($data[$field])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Autenticación de usuario.
	 */
	public function login(string $username, string $password): ?array
	{
		$stmt = $this->pdo->prepare("SELECT id, username, email, role FROM users WHERE username = ?");
		$stmt->execute([$username]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($user && password_verify($password, $user['password_hash'])) {
			unset($user['password_hash']); // No devolvemos el hash de la contraseña
			return $user;
		}

		return null;
	}
}
