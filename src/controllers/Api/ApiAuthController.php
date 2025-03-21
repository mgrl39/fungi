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
     * 
     * El token JWT generado contiene:
     * - Header: typ=JWT, alg=HS256
     * - Payload:
     *   - iat: Timestamp de emisión
     *   - exp: Timestamp de expiración (1 hora)
     *   - sub: ID del usuario
     *   - username: Nombre de usuario
     *   - role: Rol del usuario
     * - Firma: HMAC SHA256 del header y payload codificados
     */
    public function generateJwtToken(array $user): string
    {
        $secretKey = (defined('\JWT_SECRET') ? \JWT_SECRET : getenv('JWT_SECRET')) ?? 'default_jwt_secret_key';
        
        $header = [ 'typ' => 'JWT', 'alg' => 'HS256' ];
        $issuedAt = time();
        $expire = $issuedAt + 3600;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        
        $headerEncoded = $this->base64URLEncode(json_encode($header));
        $payloadEncoded = $this->base64URLEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secretKey, true);
        $signatureEncoded = $this->base64URLEncode($signature);
        
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
} 