<?php

namespace App\Controllers\Api;

use App\Config\ErrorMessages;

/**
 * @class ApiAuthController
 * @brief Controlador para manejar la autenticación y autorización en la API.
 *
 * Esta clase proporciona métodos para verificar tokens, gestionar sesiones,
 * y generar nuevos tokens de autenticación.
 *
 * @package App\Controllers\Api
 */
class ApiAuthController
{
    private $pdo;
    private $db;

    /**
     * Constructor del controlador
     * 
     * @param \PDO $pdo Conexión PDO a la base de datos
     * @param \App\Controllers\DatabaseController $db Modelo de base de datos
     */
    public function __construct($pdo, $db)
    {
        $this->pdo = $pdo;
        $this->db = $db;
    }

    /**
     * Verifica las credenciales de un usuario
     * 
     * @param string $username Nombre de usuario
     * @param string $password Contraseña
     * @return array|null Datos del usuario si es válido, null en caso contrario
     */
    public function login(string $username, string $password): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']); // No devolver el hash de la contraseña
            return $user;
        }

        return null;
    }

    /**
     * Genera un token JWT para un usuario
     * 
     * @param array $user Datos del usuario
     * @return string Token JWT generado
     */
    public function generateJwtToken(array $user): string
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
     * Verifica un token JWT
     * 
     * @param string $token Token JWT a verificar
     * @return array|bool Payload si el token es válido, false en caso contrario
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
        $validEncoded = $this->base64URLEncode($valid);
        
        if ($signature !== $validEncoded) {
            return false;
        }
        
        $payload = json_decode(base64_decode($this->base64URLDecode($payload)), true);
        
        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }

    /**
     * Codifica en Base64 URL seguro
     * 
     * @param string $data Datos a codificar
     * @return string Datos codificados
     */
    private function base64URLEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodifica Base64 URL seguro
     * 
     * @param string $data Datos a decodificar
     * @return string Datos decodificados
     */
    private function base64URLDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padding = 4 - $remainder;
            $data .= str_repeat('=', $padding);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Valida los campos requeridos en una solicitud
     * 
     * @param array $data Datos a validar
     * @param array $requiredFields Campos requeridos
     * @return bool True si todos los campos existen y no están vacíos
     */
    public function validateRequiredFields(array $data, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtiene el usuario autenticado de la solicitud actual
     * 
     * Verifica todas las fuentes posibles de autenticación:
     * - Encabezado Authorization con token Bearer
     * - Sesión PHP activa
     * - Cookies de autenticación
     *
     * @return array|null Datos del usuario si está autenticado, null en caso contrario
     */
    public function getUserFromRequest(): ?array
    {
        // Verificar token Bearer en el encabezado HTTP
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        $user = null;
        
        // Verificar token JWT en encabezado Authorization
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = $this->verifyJwtToken($token);
            if ($payload) {
                $user = [
                    'id' => $payload['sub'],
                    'username' => $payload['username'],
                    'role' => $payload['role']
                ];
                return $user;
            }
        }
        
        // Verificar sesión PHP activa
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $user = [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? 'Usuario',
                'role' => $_SESSION['role'] ?? 'user'
            ];
            return $user;
        }
        
        // Iniciar sesión si no está activa para verificar cookies
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar cookies (JWT o token de sesión)
        if (isset($_COOKIE['jwt'])) {
            $token = $_COOKIE['jwt'];
            $payload = $this->verifyJwtToken($token);
            if ($payload) {
                $user = [
                    'id' => $payload['sub'],
                    'username' => $payload['username'],
                    'role' => $payload['role'] ?? 'user'
                ];
                return $user;
            }
        }
        
        return null;
    }

    /**
     * Verifica si el usuario tiene acceso a un recurso y responde con error 401 si no
     *
     * @return array|null Datos del usuario si está autenticado, responde con error 401 y termina ejecución si no
     */
    public function requireAuth(): ?array
    {
        $user = $this->getUserFromRequest();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => ErrorMessages::AUTH_REQUIRED
            ]);
            exit;
        }
        
        return $user;
    }
} 