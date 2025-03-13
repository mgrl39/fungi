<?php

namespace App\Controllers;

use PDO;
use PDOException;
use App\Config\ErrorMessages;

/**
 * @class ApiController
 * @brief Controlador para manejar las solicitudes de la API RESTful.
 * 
 * Esta clase proporciona endpoints para acceder y manipular datos de hongos y usuarios
 * a través de una interfaz API RESTful, implementando métodos para las operaciones CRUD.
 *
 * @package App\Controllers
 */
class ApiController
{
	private $pdo;
	private $db;
	private $session;

	/**
	 * @brief Constructor del controlador de API.
	 * 
	 * Inicializa la conexión a la base de datos usando PDO.
	 *
	 * @param DatabaseController $db Instancia del controlador de base de datos
	 * @throws PDOException Si ocurre un error en la conexión a la base de datos
	 */
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
	 * @brief Manejador de solicitudes HTTP.
	 * 
	 * Procesa las solicitudes entrantes, identifica el método HTTP y el endpoint,
	 * y dirige la solicitud al manejador apropiado.
	 *
	 * @return void
	 * @throws PDOException Si ocurre un error en la consulta a la base de datos
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
	 * @brief Manejador de solicitudes GET.
	 * 
	 * Procesa solicitudes HTTP GET para varios endpoints, incluyendo
	 * listado de hongos, consulta por ID, paginación y obtención de usuarios.
	 *
	 * @param string $endpoint El endpoint solicitado sin la base de la URL
	 * @return void Salida JSON directamente impresa
	 * @throws PDOException Si ocurre un error en la consulta a la base de datos
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
	 * @brief Manejador de solicitudes POST.
	 * 
	 * Procesa solicitudes HTTP POST para crear nuevos recursos como
	 * hongos y usuarios en la base de datos.
	 *
	 * @param string $endpoint El endpoint solicitado sin la base de la URL
	 * @return void Salida JSON directamente impresa
	 * @throws PDOException Si ocurre un error en la inserción a la base de datos
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
				echo json_encode([
					'success' => false,
					'error' => ErrorMessages::format(ErrorMessages::VALIDATION_REQUIRED_FIELD, 'username, email, password')
				]);
				return;
			}
			
			// Validación de formato de email
			if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'error' => ErrorMessages::VALIDATION_INVALID_EMAIL
				]);
				return;
			}
			
			// Verificar que el nombre de usuario no exista
			$stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
			$stmt->execute([$data['username']]);
			if ($stmt->fetch()) {
				http_response_code(409); // Conflict
				echo json_encode([
					'success' => false,
					'error' => ErrorMessages::format(ErrorMessages::VALIDATION_VALUE_ALREADY_EXISTS, 'username')
				]);
				return;
			}
			
			// Verificar que el email no exista
			$stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
			$stmt->execute([$data['email']]);
			if ($stmt->fetch()) {
				http_response_code(409); // Conflict
				echo json_encode([
					'success' => false,
					'error' => ErrorMessages::format(ErrorMessages::VALIDATION_VALUE_ALREADY_EXISTS, 'email')
				]);
				return;
			}
			
			// Generar hash de la contraseña
			$passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
			
			// Insertar el nuevo usuario
			$stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
			$stmt->execute([$data['username'], $data['email'], $passwordHash]);
			
			$userId = $this->pdo->lastInsertId();
			
			// Devolver respuesta exitosa
			echo json_encode([
				'success' => true,
				'id' => $userId,
				'message' => 'Usuario registrado exitosamente'
			]);
		} elseif ($endpoint === 'auth/login') {
			// Manejo de autenticación y generación de JWT
			$requiredFields = ['username', 'password'];
			if (!$this->validateRequiredFields($data, $requiredFields)) {
				http_response_code(400);
				echo json_encode([
					'success' => false, 
					'error' => ErrorMessages::format(ErrorMessages::VALIDATION_REQUIRED_FIELD, 'username, password')
				]);
				return;
			}

			// Autenticar usuario
			$user = $this->login($data['username'], $data['password']);
			
			if ($user) {
				// Generar token JWT
				$token = $this->generateJwtToken($user);
				
				echo json_encode([
					'success' => true, 
					'token' => $token,
					'user' => [
						'id' => $user['id'],
						'username' => $user['username'],
						'email' => $user['email'],
						'role' => $user['role']
					]
				]);
			} else {
				http_response_code(401);
				echo json_encode([
					'success' => false, 
					'error' => ErrorMessages::AUTH_INVALID_CREDENTIALS
				]);
			}
		} else {
			http_response_code(404);
			echo json_encode(['error' => ErrorMessages::HTTP_404]);
		}
	}

	/**
	 * @brief Manejador de solicitudes PUT.
	 * 
	 * Procesa solicitudes HTTP PUT para actualizar recursos existentes,
	 * principalmente información de hongos.
	 *
	 * @param string $endpoint El endpoint solicitado sin la base de la URL
	 * @return void Salida JSON directamente impresa
	 * @throws PDOException Si ocurre un error en la actualización en la base de datos
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
	 * @brief Manejador de solicitudes DELETE.
	 * 
	 * Procesa solicitudes HTTP DELETE para eliminar recursos,
	 * principalmente registros de hongos.
	 *
	 * @param string $endpoint El endpoint solicitado sin la base de la URL
	 * @return void Salida JSON directamente impresa
	 * @throws PDOException Si ocurre un error en la eliminación en la base de datos
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
	 * @brief Validación de campos requeridos.
	 * 
	 * Verifica que todos los campos requeridos estén presentes y no vacíos
	 * en los datos proporcionados.
	 *
	 * @param array $data Los datos a validar
	 * @param array $requiredFields Lista de campos requeridos
	 * @return bool Verdadero si todos los campos requeridos están presentes y no vacíos
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
	 * @brief Autenticación de usuario.
	 * 
	 * Verifica las credenciales del usuario contra la base de datos.
	 *
	 * @param string $username Nombre de usuario
	 * @param string $password Contraseña en texto plano
	 * @return array|null Datos del usuario si la autenticación es exitosa, null en caso contrario
	 * @throws PDOException Si ocurre un error en la consulta a la base de datos
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

	/**
	 * @brief Genera un token JWT para el usuario autenticado.
	 * 
	 * Crea un token JWT con la información del usuario y un tiempo de expiración.
	 *
	 * @param array $user Datos del usuario autenticado
	 * @return string Token JWT generado
	 */
	private function generateJwtToken(array $user): string
	{
		$secretKey = defined('JWT_SECRET') ? JWT_SECRET : getenv('JWT_SECRET');
		if (!$secretKey) {
			$secretKey = 'default_jwt_secret_key'; // ¡Solo como respaldo! Configurar siempre una clave segura
		}
		
		$issuedAt = time();
		$expire = $issuedAt + 3600; // Token válido por 1 hora
		
		$payload = [
			'iat' => $issuedAt,      // Tiempo en que fue emitido el token
			'exp' => $expire,        // Tiempo de expiración
			'sub' => $user['id'],    // ID del usuario como subject
			'username' => $user['username'],
			'role' => $user['role']
		];
		
		// Codificación simple del token - en producción usar una biblioteca JWT adecuada
		$header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
		$payloadEncoded = base64_encode(json_encode($payload));
		$signature = hash_hmac('sha256', "$header.$payloadEncoded", $secretKey, true);
		$signatureEncoded = base64_encode($signature);
		
		return "$header.$payloadEncoded.$signatureEncoded";
	}

	/**
	 * @brief Verifica un token JWT recibido.
	 * 
	 * Valida que el token sea auténtico y no haya expirado.
	 *
	 * @param string $token El token JWT a verificar
	 * @return array|bool Datos del payload si el token es válido, false en caso contrario
	 */
	public function verifyJwtToken(string $token)
	{
		$secretKey = defined('JWT_SECRET') ? JWT_SECRET : getenv('JWT_SECRET');
		if (!$secretKey) {
			$secretKey = 'default_jwt_secret_key'; // ¡Solo como respaldo!
		}
		
		$parts = explode('.', $token);
		if (count($parts) != 3) {
			return false;
		}
		
		list($header, $payload, $signature) = $parts;
		
		$valid = hash_hmac('sha256', "$header.$payload", $secretKey, true);
		$validEncoded = base64_encode($valid);
		
		if ($signature !== $validEncoded) {
			return false;
		}
		
		$payload = json_decode(base64_decode($payload), true);
		
		// Verificar expiración
		if (isset($payload['exp']) && $payload['exp'] < time()) {
			return false;
		}
		
		return $payload;
	}
}
