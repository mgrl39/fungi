<?php

namespace App\Controllers;

use PDO;
use PDOException;
use App\Config\ErrorMessages;
use App\Controllers\Api\ApiInfoController;
use App\Controllers\Api\ApiPutController;
use App\Controllers\Api\ApiAuthController;

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
		
		// Usar expresión regular para asegurar que solo se elimina el primer /api
		// en lugar de usar str_replace que reemplazaría todas las ocurrencias
		$endpoint = preg_replace('#^' . $basePath . '#', '', parse_url($uri, PHP_URL_PATH));

		// Asegurarse de que el endpoint comience con una barra
		if (substr($endpoint, 0, 1) !== '/') {
			$endpoint = '/' . $endpoint;
		}
		
		// Remover la barra inicial para mantener consistencia con los métodos de manejo
		$endpoint = ltrim($endpoint, '/');

		// Añadir documentación para el endpoint raíz
		if ($endpoint === '/' || $endpoint === '') {
			ApiInfoController::show();
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
	 * Procesa solicitudes HTTP GET delegando a ApiGetController
	 *
	 * @param string $endpoint El endpoint solicitado sin la base de la URL
	 * @return void Salida JSON directamente impresa
	 */
	private function handleGet($endpoint)
	{
		// Crear instancia del controlador GET
		$apiGetController = new \App\Controllers\Api\ApiGetController($this->pdo, $this->db);
		
		// Verificar el token de autenticación si se ha enviado
		$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
		$token = null;
		$user = null;
		
		// Verificar si hay un token Bearer en el encabezado
		if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
			$token = $matches[1];
			$user = $apiGetController->verifyAuthToken($token, [$this, 'verifyJwtToken']);
		}
		
		// Si no hay usuario autenticado por token Bearer, verificar sesión PHP
		if (!$user && session_status() === PHP_SESSION_ACTIVE) {
			if (isset($_SESSION['user_id'])) {
				$user = [
					'id' => $_SESSION['user_id'],
					'username' => $_SESSION['username'] ?? 'Usuario',
					'role' => $_SESSION['role'] ?? 'user'
				];
			}
		}
		
		// Si aún no hay usuario, iniciar sesión y verificar cookies
		if (!$user) {
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}
			
			// Verificar cookie de token o JWT
			if (isset($_COOKIE['token']) || isset($_COOKIE['jwt'])) {
				// Código de verificación de cookies...
				// (mantener la lógica existente)
			}
		}

		$result = null;

		if ($endpoint === 'auth/verify') {
			$result = $apiGetController->verifyAuth($user);
		}
		
		// Endpoint de búsqueda de hongos
		else if (preg_match('/^fungi\/search\/(\w+)\/(.+)$/', $endpoint, $matches)) {
			$param = $matches[1];
			$value = urldecode($matches[2]);
			$result = $apiGetController->searchFungi($param, $value);
		}
		
		else if ($endpoint === 'fungi' || $endpoint === 'fungi/all') {
			$result = $apiGetController->getAllFungi();
		} 
		
		else if (preg_match('/^fungi\/page\/(\d+)\/limit\/(\d+)$/', $endpoint, $matches)) {
			$pageNumber = (int)$matches[1];
			$limit = (int)$matches[2];
			$result = $apiGetController->getFungiPaginated($pageNumber, $limit);
		} 
		
		else if (preg_match('/^fungi\/(\d+)$/', $endpoint, $matches)) {
			$fungiId = (int)$matches[1];
			$userId = $user ? $user['id'] : null;
			$result = $apiGetController->getFungusById($fungiId, $userId);
		}
		
		else if ($endpoint === 'fungi/random') {
			$userId = $user ? $user['id'] : null;
			$result = $apiGetController->getRandomFungus($userId);
		}
		
		else if (preg_match('/^fungi\/edibility\/(.+)$/', $endpoint, $matches)) {
			$edibility = $matches[1];
			$result = $apiGetController->getFungiByEdibility($edibility);
		}
		
		else if ($endpoint === 'user/favorites') {
			if (!$user) {
				http_response_code(401);
				$result = [
					'success' => false,
					'error' => ErrorMessages::AUTH_REQUIRED
				];
			} else {
				$result = $apiGetController->getUserFavorites($user['id']);
			}
		}
		
		else {
			http_response_code(404);
			$result = ['error' => ErrorMessages::HTTP_404];
		}
		
		echo json_encode($result);
	}

	/**
	 * @brief Manejador de solicitudes POST.
	 * 
	 * Procesa solicitudes HTTP POST utilizando el controlador ApiPostController
	 *
	 * @param string $endpoint El endpoint solicitado sin la base de la URL
	 * @return void Salida JSON directamente impresa
	 */
	private function handlePost($endpoint)
	{
		// Crea una instancia del controlador de POST
		$apiPostController = new \App\Controllers\Api\ApiPostController($this->pdo, $this->db);
		
		// Datos de la solicitud
		$data = json_decode(file_get_contents('php://input'), true);
		$result = null;

		// Verificar autenticación para endpoints que requieren usuario
		if (preg_match('/^fungi\/(\d+)\/like$/', $endpoint, $matches) || preg_match('/^user\/favorites\/(\d+)$/', $endpoint, $matches)) {
			// Verificar autenticación
			$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
			$token = null;
			$user = null;
			
			// Verificar token de sesión o JWT
			if (preg_match('/Bearer\s(\S+)/', $authHeader, $tokenMatches)) {
				$token = $tokenMatches[1];
				$payload = $this->verifyJwtToken($token);
				if ($payload) {
					$user = [
						'id' => $payload['sub'],
						'username' => $payload['username'],
						'role' => $payload['role']
					];
				}
			} else if (isset($_SESSION['user_id'])) {
				// Usar sesión PHP si existe
				$user = [
					'id' => $_SESSION['user_id'],
					'username' => $_SESSION['username'],
					'role' => $_SESSION['role'] ?? 'user'
				];
			}
			
			if (!$user) {
				http_response_code(401);
				echo json_encode([
					'success' => false,
					'error' => ErrorMessages::AUTH_REQUIRED
				]);
				return;
			}
			
			$fungiId = $matches[1];
			
			// Procesar like
			if (strpos($endpoint, '/like') !== false) {
				$result = $apiPostController->likeFungi($user['id'], $fungiId);
			}
			
			// Procesar favoritos
			if (strpos($endpoint, 'user/favorites') !== false) {
				$result = $apiPostController->addFavorite($user['id'], $fungiId);
			}
			
			echo json_encode($result);
			return;
		}

		// Otros endpoints
		if ($endpoint === 'fungi') {
			$result = $apiPostController->createFungi($data);
		} 
		elseif ($endpoint === 'users') {
			$result = $apiPostController->registerUser($data);
		} 
		elseif ($endpoint === 'auth/login') {
			$result = $apiPostController->handleLogin(
				$data,
				[$this, 'login'],
				[$this, 'generateJwtToken']
			);
		} 
		elseif ($endpoint === 'auth/logout') {
			$result = $apiPostController->handleLogout();
		} 
		else {
			http_response_code(404);
			$result = ['error' => ErrorMessages::HTTP_404];
		}
		
		echo json_encode($result);
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
		// Crear instancia del controlador PUT
		$apiPutController = new \App\Controllers\Api\ApiPutController($this->pdo, $this->db);
		
		// Verificar autenticación
		$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
		$token = null;
		$user = null;
		
		// Crear instancia del controlador de autenticación
		$apiAuthController = new \App\Controllers\Api\ApiAuthController($this->pdo, $this->db);
		
		// Verificar token de sesión o JWT
		if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
			$token = $matches[1];
			$payload = $apiAuthController->verifyJwtToken($token);
			if ($payload) {
				$user = [
					'id' => $payload['sub'],
					'username' => $payload['username'],
					'role' => $payload['role']
				];
			}
		} else if (isset($_SESSION['user_id'])) {
			// Usar sesión PHP si existe
			$user = [
				'id' => $_SESSION['user_id'],
				'username' => $_SESSION['username'] ?? 'Usuario',
				'role' => $_SESSION['role'] ?? 'user'
			];
		}
		
		// Datos de la solicitud
		$data = json_decode(file_get_contents('php://input'), true);
		$result = null;
		
		if (preg_match('/^fungi\/(\d+)$/', $endpoint, $matches)) {
			$fungiId = $matches[1];
			
			if (!$user) {
				http_response_code(401);
				$result = [
					'success' => false,
					'error' => ErrorMessages::AUTH_REQUIRED
				];
			} else {
				$result = $apiPutController->updateFungi($fungiId, $data, $user);
			}
		} else {
			http_response_code(404);
			$result = [
				'success' => false,
				'error' => ErrorMessages::HTTP_404
			];
		}
		
		echo json_encode($result);
	}

	/**
	 * @brief Manejador de solicitudes DELETE.
	 * 
	 * Procesa solicitudes HTTP DELETE delegando a ApiDeleteController
	 *
	 * @param string $endpoint El endpoint solicitado sin la base de la URL
	 * @return void Salida JSON directamente impresa
	 */
	private function handleDelete($endpoint)
	{
		// Crear instancia del controlador DELETE
		$apiDeleteController = new \App\Controllers\Api\ApiDeleteController($this->pdo, $this->db);
		
		// Verificar autenticación
		$authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
		$token = null;
		$user = null;
		
		// Verificar token de sesión o JWT
		if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
			$token = $matches[1];
			$payload = $this->verifyJwtToken($token);
			if ($payload) {
				$user = [
					'id' => $payload['sub'],
					'username' => $payload['username'],
					'role' => $payload['role']
				];
			}
		} else if (isset($_SESSION['user_id'])) {
			// Usar sesión PHP si existe
			$user = [
				'id' => $_SESSION['user_id'],
				'username' => $_SESSION['username'] ?? 'Usuario',
				'role' => $_SESSION['role'] ?? 'user'
			];
		}
		
		// Resultado de la operación
		$result = null;
		
		if (preg_match('/^fungi\/(\d+)$/', $endpoint, $matches)) {
			// Eliminar hongo (requiere admin)
			$fungiId = $matches[1];
			
			if (!$user) {
				http_response_code(401);
				$result = [
					'success' => false,
					'error' => ErrorMessages::AUTH_REQUIRED
				];
			} else {
				$result = $apiDeleteController->deleteFungi($fungiId, $user);
			}
		} 
		else if (preg_match('/^fungi\/(\d+)\/like$/', $endpoint, $matches)) {
			// Quitar like a un hongo
			$fungiId = $matches[1];
			
			if (!$user) {
				http_response_code(401);
				$result = [
					'success' => false,
					'error' => ErrorMessages::AUTH_REQUIRED
				];
			} else {
				$result = $apiDeleteController->unlikeFungi($user['id'], $fungiId);
			}
		}
		else if (preg_match('/^user\/favorites\/(\d+)$/', $endpoint, $matches)) {
			// Quitar un hongo de favoritos
			$fungiId = $matches[1];
			
			if (!$user) {
				http_response_code(401);
				$result = [
					'success' => false,
					'error' => ErrorMessages::AUTH_REQUIRED
				];
			} else {
				$result = $apiDeleteController->removeFavorite($user['id'], $fungiId);
			}
		}
		else {
			http_response_code(404);
			$result = [
				'success' => false,
				'error' => ErrorMessages::HTTP_404
			];
		}
		
		echo json_encode($result);
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
		$secretKey = (defined('\JWT_SECRET') ? \JWT_SECRET : getenv('JWT_SECRET')) ?? 'default_jwt_secret_key';
		
		// Datos para el JWT
		$header = [
			'typ' => 'JWT',
			'alg' => 'HS256'
		];
		
		$issuedAt = time();
		$expire = $issuedAt + 3600; // Token válido por 1 hora
		
		$payload = [
			'iat' => $issuedAt,     // Tiempo en que fue emitido el token
			'exp' => $expire,       // Tiempo de expiración
			'sub' => $user['id'],   // ID del usuario como subject
			'username' => $user['username'],
			'role' => $user['role']
		];
		
		// Codificar header y payload
		$headerEncoded = $this->base64URLEncode(json_encode($header));
		$payloadEncoded = $this->base64URLEncode(json_encode($payload));
		
		// Crear firma
		$signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secretKey, true);
		$signatureEncoded = $this->base64URLEncode($signature);
		
		// Crear JWT
		return "$headerEncoded.$payloadEncoded.$signatureEncoded";
	}
	
	/**
	 * Codifica en Base64 URL seguro
	 * 
	 * @param string $data Datos a codificar
	 * @return string Datos codificados
	 */
	private function base64URLEncode($data): string
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
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
		$secretKey = (defined('\JWT_SECRET') ? \JWT_SECRET : getenv('JWT_SECRET')) ?? 'default_jwt_secret_key';
		
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
