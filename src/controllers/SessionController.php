<?php
namespace App\Controllers;

/**
 * @brief Controlador para la gestión de sesiones de usuario
 * 
 * @details Esta clase maneja la autenticación y estado de sesión de los usuarios,
 * incluyendo la verificación mediante sesiones PHP, cookies regulares y tokens JWT.
 */
class SessionController {
    /**
     * @var DatabaseController $db Instancia del controlador de base de datos
     */
    private $db;

    /**
     * @brief Constructor del controlador de sesiones
     * 
     * @param DatabaseController $db Instancia del controlador de base de datos
     */
    public function __construct(DatabaseController $db) {
        $this->db = $db;
    }

    /**
     * @brief Verifica si el usuario tiene sesión activa
     * 
     * @details Comprueba el estado de la sesión del usuario utilizando diferentes métodos:
     * - Sesión PHP activa
     * - Token en cookie
     * - JWT en cookie
     * 
     * @return bool TRUE si el usuario está autenticado, FALSE en caso contrario
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
     * @brief Codifica en Base64 URL seguro
     * 
     * @details Transforma un string codificado en Base64 para que sea seguro en URLs
     * reemplazando los caracteres '+' por '-', '/' por '_' y eliminando '='
     * 
     * @param string $data Datos a codificar
     * 
     * @return string Datos codificados en Base64 URL seguro
     */
    private function base64URLEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @brief Obtiene la clave secreta para JWT
     * 
     * @details Recupera la clave secreta de las variables de entorno o devuelve
     * una clave por defecto si no está configurada
     * 
     * @return string Clave secreta para firmar/verificar tokens JWT
     */
    private function getSecretKey() {
        return $_ENV['JWT_SECRET'] ?? 'mi_clave_secreta_por_defecto';
    }

    /**
     * @brief Verifica la autenticación mediante cookie de token
     * 
     * @details Comprueba si existe una cookie de token válida y autentica al usuario
     * utilizando los datos almacenados en la base de datos
     * 
     * @return bool TRUE si la autenticación fue exitosa, FALSE en caso contrario
     */
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

    /**
     * @brief Verifica la autenticación mediante cookie JWT
     * 
     * @details Comprueba si existe una cookie JWT válida, verifica su firma, expiración
     * y autentica al usuario utilizando los datos almacenados en la base de datos
     * 
     * @return bool TRUE si la autenticación fue exitosa, FALSE en caso contrario
     */
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

    /**
     * @brief Verifica la validez de un token JWT
     * 
     * @details Comprueba la estructura, firma y expiración de un token JWT
     * 
     * @param string $jwt Token JWT a verificar
     * @param string $secretKey Clave secreta para verificar la firma
     * 
     * @return bool TRUE si el token es válido, FALSE en caso contrario
     */
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

    /**
     * @brief Decodifica un string en Base64 URL seguro
     * 
     * @details Transforma un string en Base64 URL seguro a Base64 estándar
     * y lo decodifica
     * 
     * @param string $data Datos codificados en Base64 URL seguro
     * 
     * @return string Datos decodificados
     */
    private function base64URLDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * @brief Obtiene los datos del usuario actual de la sesión
     * 
     * @details Recupera la información completa del usuario desde la base de datos
     * utilizando el ID almacenado en la sesión
     * 
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
     * @brief Verifica si el usuario actual tiene permisos de administrador
     * 
     * @details Comprueba si el rol del usuario en sesión o en la base de datos
     * corresponde a un administrador
     * Comprueba si el usuario está autenticado y si el rol es administrador
     * Si no está autenticado, devuelve false
     * Si está autenticado, pero no es administrador, lo actualiza en la sesión
     * Si esta autenticado y es administrador, devuelve true
     * 
     * @return bool TRUE si el usuario es administrador, FALSE en caso contrario
     */
    public function isAdmin() {
        if (!$this->isLoggedIn()) return false;
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') return true;        
        try {
            $sql = "SELECT role FROM users WHERE id = :user_id";
            $result = $this->db->query($sql, [':user_id' => $_SESSION['user_id']]);
            $userData = $result->fetch(\PDO::FETCH_ASSOC);
            
            if ($userData && $userData['role'] === 'admin') {
                $_SESSION['role'] = 'admin';
                return true;
            }
        } catch(\Exception $e) {
            error_log("Error verificando permisos de administrador: " . $e->getMessage());
        }
        return false;
    }
}