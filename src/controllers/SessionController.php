<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class SessionController {
    private $db;
    private static $secret_key;

    public function __construct(DatabaseController $db) {
        $this->db = $db;
        self::$secret_key = $_ENV['JWT_SECRET_KEY'] ?? 'temporal_key';
    }

    public function createSession($user) {
        try {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
        
            // Generar tokens
            $token = bin2hex(random_bytes(16));
            $jwt = $this->createJWT($user);
        
            // Guardar token JWT
            $expiresAt = date('Y-m-d H:i:s', time() + (86400 * 30));
            $success = $this->db->execute(
                "INSERT INTO jwt_tokens (user_id, token, expires_at) 
                 VALUES (?, ?, ?)",
                [$user['id'], $jwt, $expiresAt]
            );
        
            if (!$success) {
                error_log("Error al guardar JWT token");
                return false;
            }
        
            // Actualizar tokens en users
            $success = $this->db->updateUserTokens($user['id'], $token, $jwt);
            
            if (!$success) {
                error_log("Error al actualizar tokens de usuario");
                return false;
            }
        
            // Crear cookies seguras
            $this->createSecureCookie("token", $token);
            $this->createSecureCookie("jwt", $jwt);
        
            return true;
        } catch (Exception $e) {
            error_log("Error en createSession: " . $e->getMessage());
            return false;
        }
    }

    private function createJWT($user) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'exp' => time() + (86400 * 30)
        ];

        return $this->generateJWT($header, $payload);
    }

    private function generateJWT($header, $payload) {
        $header_encoded = $this->base64URLEncode(json_encode($header));
        $payload_encoded = $this->base64URLEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', 
            "$header_encoded.$payload_encoded", 
            self::$secret_key, 
            true
        );
        $signature_encoded = $this->base64URLEncode($signature);
        
        return "$header_encoded.$payload_encoded.$signature_encoded";
    }

    private function base64URLEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private function createSecureCookie($name, $value) {
        setcookie(
            $name,
            $value,
            [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && 
               $this->verifyTokenCookie() && 
               $this->verifyJWTCookie();
    }

    private function verifyTokenCookie() {
        if (!isset($_COOKIE['token'])) return false;
        return $this->db->verifyUserToken($_SESSION['user_id'], $_COOKIE['token']);
    }


    private function verifyJWTCookie() {
        if (!isset($_COOKIE['jwt'])) return false;
        
        // Verificar en la tabla jwt_tokens
        $token = $_COOKIE['jwt'];
        $userId = $_SESSION['user_id'];
        
        $tokenValid = $this->db->query(
            "SELECT 1 FROM jwt_tokens 
             WHERE user_id = ? AND token = ? 
             AND is_revoked = FALSE 
             AND expires_at > NOW()",
            [$userId, $token]
        )->fetch();
        
        return $tokenValid && $this->db->verifyUserJWT($userId, $token);
    }
} 