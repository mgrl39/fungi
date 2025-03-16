<?php
namespace App\Controllers;

class SessionController {
    private $db;

    public function __construct(DatabaseController $db) {
        $this->db = $db;
    }

    /**
     * Verifica si el usuario tiene sesión activa
     */
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            return true;
        }
        
        // Verificar por cookies
        return $this->verifyTokenCookie() || $this->verifyJWTCookie();
    }

    /**
     * Codifica en Base64 URL seguro
     */
    private function base64URLEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Obtiene la clave secreta para JWT
     */
    private function getSecretKey() {
        return $_ENV['JWT_SECRET'] ?? 'mi_clave_secreta_por_defecto';
    }

    public function verifyTokenCookie() {
        if (!isset($_COOKIE['token'])) {
            return false;
        }
        
        $token = $_COOKIE['token'];
        
        try {
            $stmt = $this->db->query(
                "SELECT id, username, role FROM users WHERE token = :token",
                [':token' => $token]
            );
            
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($user) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                return true;
            } else {
                // Token inválido, eliminar cookie
                setcookie("token", "", time() - 3600, "/");
                return false;
            }
        } catch(\PDOException $e) {
            error_log("Error verificando token: " . $e->getMessage());
            return false;
        }
    }

    public function verifyJWTCookie() {
        if (!isset($_COOKIE['jwt'])) {
            return false;
        }
        
        $jwt = $_COOKIE['jwt'];
        $secretKey = $this->getSecretKey();
        
        try {
            // Verificar firma y expiración
            if (!$this->verifyJWT($jwt, $secretKey)) {
                setcookie("jwt", "", time() - 3600, "/");
                return false;
            }
            
            // Verificar en base de datos
            $stmt = $this->db->query(
                "SELECT u.id, u.username, u.role FROM jwt_tokens j 
                 JOIN users u ON j.user_id = u.id 
                 WHERE j.token = :token AND j.expires_at > NOW()",
                [':token' => $jwt]
            );
            
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($user) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                return true;
            } else {
                // Token no válido en la base de datos
                setcookie("jwt", "", time() - 3600, "/");
                return false;
            }
        } catch(\PDOException $e) {
            error_log("Error verificando JWT: " . $e->getMessage());
            return false;
        }
    }

    public function verifyJWT($jwt, $secretKey) {
        $tokenParts = explode('.', $jwt);
        
        if (count($tokenParts) != 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $tokenParts;
        
        // Verificar firma
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secretKey, true);
        $signatureCheck = $this->base64URLEncode($signature);
        
        if ($signatureCheck !== $signatureEncoded) {
            return false;
        }
        
        // Verificar expiración
        $payload = json_decode($this->base64URLDecode($payloadEncoded), true);
        
        if (!$payload || !isset($payload['exp'])) {
            return false;
        }
        
        if ($payload['exp'] < time()) {
            return false; // Token expirado
        }
        
        return true;
    }

    private function base64URLDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Obtiene los datos del usuario actual de la sesión
     * @return array|null Datos del usuario o null si no hay sesión
     */
    public function getUserData() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $sql = "SELECT id, username, email, role, created_at 
                    FROM users 
                    WHERE id = :user_id";
            
            $stmt = $this->db->query($sql, [':user_id' => $_SESSION['user_id']]);
            
            // Verificar si la consulta fue exitosa
            if ($stmt === false) {
                throw new \PDOException("La consulta falló");
            }
            
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Si no se encontró el usuario, devolver información básica de la sesión
            if (!$user && isset($_SESSION['user_id'])) {
                return [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'] ?? 'Usuario',
                    'role' => $_SESSION['role'] ?? 'user'
                ];
            }
            
            return $user;
        } catch(\PDOException $e) {
            error_log("Error obteniendo datos del usuario: " . $e->getMessage());
            
            // Devolver información básica de la sesión en caso de error
            if (isset($_SESSION['user_id'])) {
                return [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'] ?? 'Usuario',
                    'role' => $_SESSION['role'] ?? 'user'
                ];
            }
            
            return null;
        }
    }

    /**
     * Verifica si el usuario actual tiene permisos de administrador
     * 
     * @return bool True si el usuario es administrador, false en caso contrario
     */
    public function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Verificar si el rol está en la sesión
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            return true;
        }
        
        // Verificar en la base de datos si no está en sesión
        try {
            $sql = "SELECT role FROM users WHERE id = :user_id";
            $result = $this->db->query($sql, [':user_id' => $_SESSION['user_id']]);
            $userData = $result->fetch(\PDO::FETCH_ASSOC);
            
            if ($userData && $userData['role'] === 'admin') {
                // Actualizar la sesión con el rol correcto
                $_SESSION['role'] = 'admin';
                return true;
            }
        } catch(\Exception $e) {
            error_log("Error verificando permisos de administrador: " . $e->getMessage());
        }
        
        return false;
    }
}