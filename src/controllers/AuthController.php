<?php

namespace App\Controllers;

use App\Controllers\SessionController;

/**
 * @class AuthController
 * @brief Controlador para gestionar la autenticación de usuarios
 * 
 * Esta clase maneja el registro, inicio de sesión, cierre de sesión
 * y verificación de tokens para los usuarios del sistema.
 */
class AuthController {
    private $db;
    private $session;
    private $secretKey;
    
    /**
     * @brief Constructor del controlador de autenticación
     * 
     * @param DatabaseController $db Instancia del controlador de base de datos
     */
    public function __construct(DatabaseController $db) {
        $this->db = $db;
        $this->session = new SessionController($db);
        $this->secretKey = $this->getSecretKey();
    }
    
    /**
     * @brief Registra un nuevo usuario en el sistema
     * 
     * @param string $username Nombre de usuario
     * @param string $email Correo electrónico del usuario
     * @param string $password Contraseña del usuario
     * @param string $confirm_password Confirmación de la contraseña
     * 
     * @return array Arreglo con el resultado de la operación
     *         ['success' => bool, 'message' => string]
     */
    public function register($username, $email, $password, $confirm_password) {
        // Validaciones básicas
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Todos los campos son requeridos'];
        }
        
        // Validar longitud del username
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'message' => 'El nombre de usuario debe tener entre 3 y 50 caracteres'];
        }
        
        if ($password !== $confirm_password) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden'];
        }
        
        // Validar longitud del password
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        // Hash del password antes de guardarlo
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // Pasar el password ya hasheado
            if ($this->db->createUser($username, $email, $hashed_password)) {
                return ['success' => true, 'message' => 'Usuario registrado exitosamente'];
            } else {
                return ['success' => false, 'message' => 'El usuario o email ya existe'];
            }
        } catch (\Exception $e) {
            // Log del error para el administrador
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar el usuario'];
        }
    }
    
    /**
     * @brief Inicia sesión de un usuario
     * 
     * @param string $username Nombre de usuario o correo
     * @param string $password Contraseña
     * 
     * @return array Arreglo con el resultado de la operación
     *         ['success' => bool, 'message' => string, 'user' => object|null]
     */
    public function login($username, $password) {
        // Verificar si es un nombre de usuario o correo
        $field = filter_var($username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        try {
            // Buscar usuario
            $user = $this->db->query(
                "SELECT * FROM users WHERE $field = ?",
                [$username]
            )->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Credenciales inválidas', 'user' => null];
            }
            
            // Verificar contraseña
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Credenciales inválidas', 'user' => null];
            }
            
            // Iniciar sesión
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Generar token de sesión
            $token = $this->generateSessionToken($user);
            
            // Generar JWT y almacenarlo
            $jwt = $this->createJWT($user);
            
            // Actualizar último login
            $this->db->query(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            return [
                'success' => true, 
                'message' => 'Sesión iniciada correctamente',
                'user' => $user
            ];
        } catch (\Exception $e) {
            return [
                'success' => false, 
                'message' => 'Error al iniciar sesión: ' . $e->getMessage(),
                'user' => null
            ];
        }
    }
    
    /**
     * @brief Cierra la sesión del usuario
     * 
     * Elimina todas las cookies de sesión, tokens y destruye la sesión actual.
     * Utiliza los tokens disponibles para una identificación más precisa.
     * 
     * @return void
     */
    public function logout() {
        // Iniciar sesión si no está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Recopilar todos los identificadores disponibles
        $identifiers = [];
        
        // ID de usuario en sesión
        if (isset($_SESSION['user_id'])) {
            $identifiers['user_id'] = $_SESSION['user_id'];
        }
        
        // Token de sesión en cookie
        if (isset($_COOKIE['token'])) {
            $identifiers['token'] = $_COOKIE['token'];
        }
        
        // JWT en cookie
        if (isset($_COOKIE['jwt'])) {
            $identifiers['jwt'] = $_COOKIE['jwt'];
        }
        
        // Limpiar tokens en la base de datos usando todos los identificadores disponibles
        if (!empty($identifiers)) {
            try {
                if (isset($identifiers['user_id'])) {
                    $this->db->query(
                        "UPDATE users SET token = NULL, jwt = NULL WHERE id = ?",
                        [$identifiers['user_id']]
                    );
                }
                
                if (isset($identifiers['token'])) {
                    $this->db->query(
                        "UPDATE users SET token = NULL WHERE token = ?",
                        [$identifiers['token']]
                    );
                }
                
                if (isset($identifiers['jwt'])) {
                    $this->db->query(
                        "UPDATE users SET jwt = NULL WHERE jwt = ?",
                        [$identifiers['jwt']]
                    );
                }
            } catch (\Exception $e) {
                // Registrar error pero continuar con el proceso de logout
                error_log("Error al limpiar tokens: " . $e->getMessage());
            }
        }
        
        // Eliminar cookies relacionadas con la autenticación
        $this->removeAuthCookies();
        
        // Destruir la sesión
        session_destroy();
    }
    
    /**
     * @brief Elimina todas las cookies relacionadas con la autenticación
     * 
     * @return void
     */
    private function removeAuthCookies() {
        // Eliminar cookie de token
        if (isset($_COOKIE['token'])) {
            setcookie("token", "", time() - 3600, "/");
        }
        
        // Eliminar cookie de JWT
        if (isset($_COOKIE['jwt'])) {
            setcookie("jwt", "", time() - 3600, "/");
        }
        
        // Eliminar otras cookies de sesión que puedan existir
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), "", time() - 3600, "/");
        }
    }
    
    /**
     * @brief Cierra la sesión del usuario y redirige a la página principal
     * 
     * @return void
     */
    public function logoutAndRedirect() {
        // Llamar al método logout para limpiar la sesión
        $this->logout();
        
        // Redirigir a la página principal
        header('Location: /');
        exit;
    }
    
    /**
     * @brief Genera un token de sesión para el usuario
     * 
     * @param array $user Datos del usuario
     * @return string Token generado
     */
    private function generateSessionToken($user) {
        // Generar token aleatorio
        $token = bin2hex(random_bytes(16));
        
        // Guardar en cookie
        $this->createSecureCookie("token", $token, time() + (86400 * 30), "/");
        
        // Guardar en base de datos
        $this->db->query(
            "UPDATE users SET token = ? WHERE id = ?",
            [$token, $user['id']]
        );
        
        return $token;
    }
    
    /**
     * @brief Crea un token JWT para el usuario
     * 
     * @param array $user Datos del usuario
     * @return string Token JWT
     */
    private function createJWT($user) {
        // Datos para el JWT
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'exp' => time() + (86400 * 30) // 30 días
        ];
        
        // Generar JWT
        $jwt = $this->generateJWT($header, $payload);
        
        // Guardar en cookie
        $this->createSecureCookie("jwt", $jwt, time() + (86400 * 30), "/");
        
        // Guardar en base de datos
        $this->db->query(
            "UPDATE users SET jwt = ? WHERE id = ?",
            [$jwt, $user['id']]
        );
        
        return $jwt;
    }
    
    /**
     * @brief Genera un token JWT
     * 
     * @param array $header Cabecera del token
     * @param array $payload Datos del token
     * @return string Token JWT generado
     */
    private function generateJWT($header, $payload) {
        // Codificar header y payload
        $header_encoded = $this->base64URLEncode(json_encode($header));
        $payload_encoded = $this->base64URLEncode(json_encode($payload));
        
        // Crear firma
        $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $this->secretKey, true);
        $signature_encoded = $this->base64URLEncode($signature);
        
        // Crear JWT
        return "$header_encoded.$payload_encoded.$signature_encoded";
    }
    
    /**
     * @brief Verifica un token JWT
     * 
     * @param string $jwt Token JWT a verificar
     * @return mixed Datos decodificados o false
     */
    public function verifyToken($jwt) {
        try {
            // Verificar si el token existe en la base de datos
            $tokenData = $this->db->query(
                "SELECT * FROM users WHERE jwt = ?",
                [$jwt]
            )->fetch();
            
            if (!$tokenData) {
                return false;
            }
            
            // Verificar estructura del token
            list($header_encoded, $payload_encoded, $signature_encoded) = explode('.', $jwt);
            
            // Verificar firma
            $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $this->secretKey, true);
            $signature_check = $this->base64URLEncode($signature);
            
            if ($signature_encoded !== $signature_check) {
                return false;
            }
            
            // Verificar expiración
            $payload = json_decode(base64_decode($payload_encoded), true);
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false; // Expirado
            }
            
            return $payload;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Codifica en Base64 URL seguro
     * 
     * @param string $data Datos a codificar
     * @return string Datos codificados
     */
    private function base64URLEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
    
    /**
     * @brief Crea una cookie segura
     * 
     * @param string $name Nombre de la cookie
     * @param string $value Valor de la cookie
     * @param int $expires Tiempo de expiración
     * @param string $path Ruta de la cookie
     * @return bool Resultado de la operación
     */
    private function createSecureCookie($name, $value, $expires, $path) {
        $domain = '';
        $secure = false; // Cambiar a true en producción con HTTPS
        $httponly = true;
        
        return setcookie(
            $name,
            $value,
            $expires,
            $path,
            $domain,
            $secure,
            $httponly
        );
    }
    
    /**
     * @brief Obtiene la clave secreta para JWT
     * 
     * @return string Clave secreta
     */
    private function getSecretKey() {
        // Cargar desde .env o usar una clave predeterminada
        if (function_exists('getenv') && getenv('JWT_SECRET_KEY')) {
            return getenv('JWT_SECRET_KEY');
        }
        
        // Clave predeterminada (¡cambiar en producción!)
        return 'tu_clave_secreta_segura_para_jwt';
    }
    
    /**
     * @brief Verifica si el usuario está autenticado
     * 
     * @return bool Resultado de la verificación
     */
    public function isLoggedIn() {
        session_start();
        
        // Verificar sesión
        if (isset($_SESSION['user_id'])) {
            return true;
        }
        
        // Verificar token de cookie
        if (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
            $user = $this->db->query(
                "SELECT * FROM users WHERE token = ?",
                [$token]
            )->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                return true;
            }
        }
        
        // Verificar JWT
        if (isset($_COOKIE['jwt'])) {
            $jwt = $_COOKIE['jwt'];
            $payload = $this->verifyToken($jwt);
            
            if ($payload && isset($payload['user_id'])) {
                $_SESSION['user_id'] = $payload['user_id'];
                $_SESSION['username'] = $payload['username'];
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * @brief Procesa el formulario de registro
     * 
     * @param array $postData Datos enviados en el formulario
     * @param Twig $twig Instancia del motor de plantillas Twig
     * 
     * @return void Redirige al usuario o muestra el formulario con errores
     */
    public function handleRegistration($postData, $twig) {
        $result = $this->register(
            $postData['username'] ?? '',
            $postData['email'] ?? '',
            $postData['password'] ?? '',
            $postData['confirm_password'] ?? ''
        );
        
        if ($result['success']) {
            header('Location: /login?registered=1');
            exit;
        } else {
            echo $twig->render('register.twig', [
                'title' => _('Registro'),
                'error' => $result['message']
            ]);
        }
    }
}