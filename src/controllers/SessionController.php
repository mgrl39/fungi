<?php
namespace App\Controllers;

class SessionController {
    private $db;

    public function __construct(DatabaseController $db) {
        $this->db = $db;
    }

    public function userSignUp($username, $email, $password) {
        // Verificar si el usuario o email ya existe
        if ($this->userExists($username, $email)) {
            return [
                'success' => false,
                'message' => 'El nombre de usuario o correo electrónico ya existe'
            ];
        }
        
        try {
            $sql = "INSERT INTO users 
                    (username, email, password_hash, token, jwt) 
                    VALUES (:username, :email, :password_hash, :token, :jwt)";
        
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $params = [
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $hashed_password,
                ':token' => '',
                ':jwt' => ''
            ];
            
            $stmt = $this->db->query($sql, $params);
            
            if ($stmt) {
                return [
                    'success' => true,
                    'message' => 'Usuario registrado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al registrar usuario'
                ];
            }
        } catch(\PDOException $e) {
            error_log("Error en registro: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    public function userLogin($username, $password) {
        if (!$this->userExists($username)) {
            return [
                'success' => false,
                'message' => 'Nombre de usuario o contraseña incorrectos'
            ];
        } 
        
        try {
            $sql = "SELECT id, username, password_hash, role FROM users WHERE username = :username";
            $params = [':username' => $username];
            $stmt = $this->db->query($sql, $params);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Iniciar sesión
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Generar token de sesión
                $this->generateSessionToken($user['id']);
                
                // Crear JWT y guardar en cookie
                $jwt = $this->createJWT($user['id'], $user['username'], $user['role']);
                
                // Actualizar last_login
                $this->db->query(
                    "UPDATE users SET last_login = NOW() WHERE id = :id",
                    [':id' => $user['id']]
                );
                
                return [
                    'success' => true,
                    'message' => 'Login exitoso'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Nombre de usuario o contraseña incorrectos'
                ];
            }
        } catch(\PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }

    public function userLogout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Eliminar token de base de datos si existe
        if (isset($_SESSION['user_id'])) {
            $this->db->query(
                "UPDATE users SET token = NULL WHERE id = :id",
                [':id' => $_SESSION['user_id']]
            );
            
            // Invalidar JWT en la tabla jwt_tokens
            $this->db->query(
                "UPDATE jwt_tokens SET expires_at = NOW() WHERE user_id = :user_id AND expires_at > NOW()",
                [':user_id' => $_SESSION['user_id']]
            );
        }
        
        // Eliminar cookies
        setcookie("token", "", time() - 3600, "/");
        setcookie("jwt", "", time() - 3600, "/");
        
        // Destruir sesión
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ];
    }

    private function generateSessionToken($userId) {
        // Generar token aleatorio
        $token = bin2hex(random_bytes(16));
        
        // Guardar en la base de datos
        $this->db->query(
            "UPDATE users SET token = :token WHERE id = :id",
            [':token' => $token, ':id' => $userId]
        );
        
        // Establecer cookie
        $this->createSecureCookie("token", $token, time() + (86400 * 30), "/");
        
        return $token;
    }

    private function createJWT($userId, $username, $role) {
        // Crear header y payload
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $expirationTime = time() + (86400 * 30); // 30 días
        
        $payload = [
            'user_id' => $userId,
            'username' => $username,
            'role' => $role,
            'exp' => $expirationTime
        ];
        
        // Generar JWT
        $secretKey = $this->getSecretKey();
        $jwt = $this->generateJWT($header, $payload, $secretKey);
        
        // Guardar en la tabla jwt_tokens
        $this->db->query(
            "INSERT INTO jwt_tokens (user_id, token, expires_at) VALUES (:user_id, :token, FROM_UNIXTIME(:expires_at))",
            [
                ':user_id' => $userId,
                ':token' => $jwt,
                ':expires_at' => $expirationTime
            ]
        );
        
        // Establecer cookie JWT
        $this->createSecureCookie("jwt", $jwt, $expirationTime, "/");
        
        return $jwt;
    }

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
    
    public function isAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
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

    public function userExists($username, $email = null) {
        try {
            if ($email === null) {
                $sql = "SELECT id FROM users WHERE username = :username";
                $params = [':username' => $username];
            } else {
                $sql = "SELECT id FROM users WHERE username = :username OR email = :email";
                $params = [':username' => $username, ':email' => $email];
            }
            
            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch(\PDOException $e) {
            error_log("Error verificando existencia de usuario: " . $e->getMessage());
            return false;
        }
    }

    private function getSecretKey() {
        return $_ENV['JWT_SECRET_KEY'] ?? 'your_default_secret_key';
    }

    private function generateJWT($header, $payload, $secretKey) {
        $headerEncoded = $this->base64URLEncode(json_encode($header));
        $payloadEncoded = $this->base64URLEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secretKey, true);
        $signatureEncoded = $this->base64URLEncode($signature);
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
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

    private function base64URLEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function base64URLDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function createSecureCookie($cookieName, $cookieValue, $expirationTime, $path) {
        $domain = '';
        $secure = isset($_SERVER['HTTPS']); // true en HTTPS
        $httpOnly = true;
        
        setcookie(
            $cookieName,
            $cookieValue,
            [
                'expires' => $expirationTime,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => 'Strict'
            ]
        );
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
}