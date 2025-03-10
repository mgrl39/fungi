<?php

namespace App\Controllers;

use PDO;
use PDOException;
use App\Config\ErrorMessages;

class ApiController
{
	private $pdo;
	private $db;
	private $session;

	public function __construct(DatabaseController $db)
	{
		$this->db = $db;
		$host = defined('DB_HOST') ? DB_HOST : getenv('DB_HOST');
		$dbname = defined('DB_NAME') ? DB_NAME : getenv('DB_NAME');
		$user = defined('DB_USER') ? DB_USER : getenv('DB_USER');
		$pass = defined('DB_PASS') ? DB_PASS : getenv('DB_PASS');

		try {
			$this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			die(ErrorMessages::format(ErrorMessages::DB_CONNECTION_ERROR, $e->getMessage()));
		}
	}

	/**
	 * Manejador de solicitudes HTTP.
	 */
	public function handleRequest()
	{
		header('Content-Type: application/json');
		$method = $_SERVER['REQUEST_METHOD'];

		// Obtener el endpoint de la URL
		$uri = $_SERVER['REQUEST_URI'];
		$basePath = '/api'; // Cambia esto si tu base de URL es diferente
		$endpoint = str_replace($basePath, '', parse_url($uri, PHP_URL_PATH));

		// Asegurarse de que el endpoint comience con una barra
		if (substr($endpoint, 0, 1) !== '/') {
			$endpoint = '/' . $endpoint;
		}
		
		// Remover la barra inicial para mantener consistencia con los métodos de manejo
		$endpoint = ltrim($endpoint, '/');

		// Añadir documentación para el endpoint raíz
		if ($endpoint === '/' || $endpoint === '') {
			echo json_encode([
				'api_version' => 'v1',
				'available_endpoints' => [
					'Fungi' => [
						'GET /api/fungi' => 'Obtiene lista de todos los hongos',
						'GET /api/fungi/{id}' => 'Obtiene un hongo específico por ID',
						'GET /api/fungi/page/{page}/limit/{limit}' => 'Obtiene hongos paginados',
						'GET /api/fungi/random' => 'Obtiene un hongo aleatorio',
						'POST /api/fungi' => 'Crea un nuevo hongo',
						'PUT /api/fungi/{id}' => 'Actualiza un hongo existente',
						'DELETE /api/fungi/{id}' => 'Elimina un hongo',
					],
					'Users' => [
						'GET /api/users' => 'Obtiene lista de usuarios',
						'POST /api/users' => 'Crea un nuevo usuario',
					]
				],
				'documentation' => 'Para más información, visita /docs/api'
			]);
			return;
		}
		try {
			switch ($method) {
			case 'GET':
				$this->handleGet($endpoint);
				break;
			case 'POST':
				$this->handlePost($endpoint);
				break;
			case 'PUT':
				$this->handlePut($endpoint);
				break;
			case 'DELETE':
				$this->handleDelete($endpoint);
				break;
			default:
				http_response_code(405); // Método no permitido
				echo json_encode(['error' => ErrorMessages::HTTP_405]);
				break;
			}
		} catch (PDOException $e) {
			http_response_code(500); // Error interno del servidor
			echo json_encode(['error' => ErrorMessages::format(ErrorMessages::DB_QUERY_ERROR, $e->getMessage())]);
		}
	}

	/**
	 * Manejador de solicitudes GET.
	 */
	private function handleGet($endpoint)
	{
		if ($endpoint === 'fungi' || $endpoint === 'fungi/all') {
			$stmt = $this->pdo->query("SELECT * FROM fungi");
			echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
		} elseif (preg_match('/^fungi\/page\/(\d+)\/limit\/(\d+)$/', $endpoint, $matches)) {
			$pageNumber = (int)$matches[1];
			$limit = (int)$matches[2];
			$offset = ($pageNumber - 1) * $limit;

			$fungis = $this->db->getFungisPaginated($limit, $offset);
			echo json_encode(['success' => true, 'data' => $fungis]);
		} elseif (preg_match('/^fungi\/(\d+)$/', $endpoint, $matches)) {
			$id = $matches[1];
			$stmt = $this->pdo->prepare("
				SELECT f.*, c.*, t.*, 
					   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls 
				FROM fungi f 
				LEFT JOIN characteristics c ON f.id = c.fungi_id 
				LEFT JOIN taxonomy t ON f.id = t.fungi_id
				LEFT JOIN fungi_images fi ON f.id = fi.fungi_id 
				LEFT JOIN images i ON fi.image_id = i.id 
				LEFT JOIN image_config ic ON i.config_key = ic.config_key
				WHERE f.id = ? 
				GROUP BY f.id");
			$stmt->execute([$id]);
			$fungus = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if ($fungus) {
				echo json_encode(['success' => true, 'data' => $fungus]);
			} else {
				http_response_code(404);
				echo json_encode(['error' => ErrorMessages::DB_RECORD_NOT_FOUND]);
			}
		} elseif ($endpoint === 'users') {
			$stmt = $this->pdo->query("SELECT id, username, email, role, created_at FROM users");
			echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
		} elseif ($endpoint === 'fungi/random') {
			$randomFungus = $this->db->getRandomFungus();
			if ($randomFungus) {
				echo json_encode(['success' => true, 'data' => $randomFungus]);
			} else {
				http_response_code(404);
				echo json_encode(['error' => ErrorMessages::DB_RECORD_NOT_FOUND]);
			}
		} else {
			http_response_code(404);
			echo json_encode(['error' => ErrorMessages::HTTP_404]);
		}
	}

	/**
	 * Manejador de solicitudes POST.
	 */
	private function handlePost($endpoint)
	{
		$data = json_decode(file_get_contents('php://input'), true);

		if ($endpoint === 'fungi') {
			$requiredFields = ['name', 'edibility', 'habitat'];
			if (!$this->validateRequiredFields($data, $requiredFields)) {
				http_response_code(400);
				echo json_encode(['error' => ErrorMessages::format(ErrorMessages::VALIDATION_REQUIRED_FIELD, 'name, edibility, habitat')]);
				return;
			}

			$stmt = $this->pdo->prepare("INSERT INTO fungi (name, edibility, habitat, observations, common_name, synonym, title) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->execute([
				$data['name'],
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
				echo json_encode(['error' => ErrorMessages::format(ErrorMessages::VALIDATION_REQUIRED_FIELD, 'username, email, password')]);
				return;
			}

			$passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
			$stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
			$stmt->execute([$data['username'], $data['email'], $passwordHash]);

			echo json_encode(['id' => $this->pdo->lastInsertId()]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => ErrorMessages::HTTP_404]);
		}
	}

	/**
	 * Manejador de solicitudes PUT.
	 */
	private function handlePut($endpoint)
	{
		if (preg_match('/^fungi\/(\d+)$/', $endpoint, $matches)) {
			$id = $matches[1];
			$data = json_decode(file_get_contents('php://input'), true);

			$stmt = $this->pdo->prepare("UPDATE fungi SET name = ?, edibility = ?, habitat = ? WHERE id = ?");
			$stmt->execute([$data['name'], $data['edibility'], $data['habitat'], $id]);

			if ($stmt->rowCount() > 0) {
				echo json_encode(['success' => true]);
			} else {
				http_response_code(404);
				echo json_encode(['error' => ErrorMessages::DB_RECORD_NOT_FOUND]);
			}
		} else {
			http_response_code(404);
			echo json_encode(['error' => ErrorMessages::HTTP_404]);
		}
	}

	/**
	 * Manejador de solicitudes DELETE.
	 */
	private function handleDelete($endpoint)
	{
		if (preg_match('/^fungi\/(\d+)$/', $endpoint, $matches)) {
			$id = $matches[1];
			$stmt = $this->pdo->prepare("DELETE FROM fungi WHERE id = ?");
			$stmt->execute([$id]);

			if ($stmt->rowCount() > 0) {
				echo json_encode(['success' => true]);
			} else {
				http_response_code(404);
				echo json_encode(['error' => ErrorMessages::DB_RECORD_NOT_FOUND]);
			}
		} else {
			http_response_code(404);
			echo json_encode(['error' => ErrorMessages::HTTP_404]);
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
		$stmt = $this->pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ?");
		$stmt->execute([$username]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($user && password_verify($password, $user['password_hash'])) {
			unset($user['password_hash']); // No devolvemos el hash de la contraseña
			return $user;
		}

		return null;
	}
}
