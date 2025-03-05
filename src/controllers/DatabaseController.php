<?php
// src/controllers/DatabaseController.php
// hagamos el .env para la conexión a la base de datos

class DatabaseController {
    private $pdo;

    public function __construct() {
        $host = $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_NAME'];
        $user = $_ENV['DB_USER'];
        $pass = $_ENV['DB_PASS'];

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    // Método para obtener todos los registros (para pruebas o sin paginar)
    public function getAllData($table) {
        $stmt = $this->pdo->prepare("
            SELECT f.*, t.*, c.*,
                   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls
            FROM $table f
            LEFT JOIN taxonomy t ON f.id = t.fungi_id
            LEFT JOIN characteristics c ON f.id = c.fungi_id
            LEFT JOIN fungi_images fi ON f.id = fi.fungi_id
            LEFT JOIN images i ON fi.image_id = i.id
            LEFT JOIN image_config ic ON i.config_key = ic.config_key
            GROUP BY f.id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para obtener fungis paginados
    public function getFungisPaginated($limit, $offset) {
        $stmt = $this->pdo->prepare("
            SELECT f.*, t.*, c.*,
                   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls
            FROM fungi f
            LEFT JOIN taxonomy t ON f.id = t.fungi_id
            LEFT JOIN characteristics c ON f.id = c.fungi_id
            LEFT JOIN fungi_images fi ON f.id = fi.fungi_id
            LEFT JOIN images i ON fi.image_id = i.id
            LEFT JOIN image_config ic ON i.config_key = ic.config_key
            GROUP BY f.id
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para obtener los detalles de un fungus por id
    public function getFungusById($id) {
        $stmt = $this->pdo->prepare("
            SELECT f.*, t.*, c.*,
                   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls
            FROM fungi f
            LEFT JOIN taxonomy t ON f.id = t.fungi_id
            LEFT JOIN characteristics c ON f.id = c.fungi_id
            LEFT JOIN fungi_images fi ON f.id = fi.fungi_id
            LEFT JOIN images i ON fi.image_id = i.id
            LEFT JOIN image_config ic ON i.config_key = ic.config_key
            WHERE f.id = :id
            GROUP BY f.id
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRandomFungus() {
        $stmt = $this->pdo->prepare("
            SELECT f.*, t.*, c.*, 
                   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls
            FROM fungi f
            LEFT JOIN taxonomy t ON f.id = t.fungi_id
            LEFT JOIN characteristics c ON f.id = c.fungi_id
            LEFT JOIN fungi_images fi ON f.id = fi.fungi_id
            LEFT JOIN images i ON fi.image_id = i.id
            LEFT JOIN image_config ic ON i.config_key = ic.config_key
            GROUP BY f.id
            ORDER BY RAND() LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Búsqueda avanzada de hongos por múltiples criterios
    public function searchFungi($criteria = []) {
        $query = "
            SELECT f.*, t.*, c.*,
                   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls
            FROM fungi f
            LEFT JOIN taxonomy t ON f.id = t.fungi_id
            LEFT JOIN characteristics c ON f.id = c.fungi_id
            LEFT JOIN fungi_images fi ON f.id = fi.fungi_id
            LEFT JOIN images i ON fi.image_id = i.id
            LEFT JOIN image_config ic ON i.config_key = ic.config_key
            WHERE 1=1
        ";
        $params = [];
        
        if (!empty($criteria['edibility'])) {
            $query .= " AND f.edibility = :edibility";
            $params[':edibility'] = $criteria['edibility'];
        }
        if (!empty($criteria['family'])) {
            $query .= " AND t.family LIKE :family";
            $params[':family'] = "%{$criteria['family']}%";
        }
        if (!empty($criteria['name'])) {
            $query .= " AND (f.name LIKE :name OR f.common_name LIKE :name)";
            $params[':name'] = "%{$criteria['name']}%";
        }

        $query .= " GROUP BY f.id";

        $stmt = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener estadísticas de hongos
    public function getFungiStats() {
        $stats = [];
        
        // Contar hongos por comestibilidad
        $stmt = $this->pdo->query("
            SELECT edibility, COUNT(*) as count 
            FROM fungi 
            GROUP BY edibility
        ");
        $stats['edibility'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Contar hongos por familia
        $stmt = $this->pdo->query("
            SELECT family, COUNT(*) as count 
            FROM taxonomy 
            WHERE family IS NOT NULL 
            GROUP BY family 
            ORDER BY count DESC 
            LIMIT 10
        ");
        $stats['top_families'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return $stats;
    }

    // Obtener hongos similares basados en características
    public function getSimilarFungi($fungiId, $limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT f2.*, t2.*, c2.*,
                   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls,
            (
                CASE WHEN f1.edibility = f2.edibility THEN 20 ELSE 0 END +
                CASE WHEN t1.family = t2.family THEN 30 ELSE 0 END +
                CASE WHEN t1.ordo = t2.ordo THEN 20 ELSE 0 END
            ) as similarity_score
            FROM fungi f1
            JOIN taxonomy t1 ON f1.id = t1.fungi_id
            JOIN characteristics c1 ON f1.id = c1.fungi_id
            JOIN fungi f2
            JOIN taxonomy t2 ON f2.id = t2.fungi_id
            JOIN characteristics c2 ON f2.id = c2.fungi_id
            LEFT JOIN fungi_images fi ON f2.id = fi.fungi_id
            LEFT JOIN images i ON fi.image_id = i.id
            LEFT JOIN image_config ic ON i.config_key = ic.config_key
            WHERE f1.id = :id AND f2.id != :id
            GROUP BY f2.id
            ORDER BY similarity_score DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':id', $fungiId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener hongos por temporada (basado en observaciones)
    public function getFungiByHabitat($habitat) {
        $stmt = $this->pdo->prepare("
            SELECT f.*, t.*, c.*,
                   GROUP_CONCAT(DISTINCT CONCAT(ic.path, i.filename)) as image_urls
            FROM fungi f
            LEFT JOIN taxonomy t ON f.id = t.fungi_id
            LEFT JOIN characteristics c ON f.id = c.fungi_id
            LEFT JOIN fungi_images fi ON f.id = fi.fungi_id
            LEFT JOIN images i ON fi.image_id = i.id
            LEFT JOIN image_config ic ON i.config_key = ic.config_key
            WHERE f.habitat LIKE :habitat
            GROUP BY f.id
        ");
        $stmt->bindValue(':habitat', "%$habitat%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createUser($username, $email, $password_hash) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash)
                VALUES (:username, :email, :password_hash)
            ");
            
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Método para verificar las credenciales del usuario
    public function verifyUser($username, $password) {
        // Agregar más logs para depuración
        error_log("Intentando verificar usuario: " . $username);
        
        $stmt = $this->pdo->prepare("
            SELECT id, username, email, password_hash, role
            FROM users
            WHERE username = :username OR email = :username
        ");
        
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("Usuario no encontrado en la base de datos");
            return false;
        }
        
        // Imprimir todos los datos relevantes
        error_log("Datos del usuario encontrado:");
        error_log("Username: " . $user['username']);
        error_log("Email: " . $user['email']);
        error_log("Password hash en DB: " . $user['password_hash']);
        error_log("Password proporcionado: " . $password);
        error_log("Longitud del password: " . strlen($password));
        error_log("Longitud del hash: " . strlen($user['password_hash']));
        
        // Verificar si hay espacios en blanco
        if (trim($password) !== $password) {
            error_log("¡ADVERTENCIA! La contraseña contiene espacios en blanco al inicio o final");
        }
        
        $verify_result = password_verify($password, $user['password_hash']);
        error_log("Resultado de password_verify: " . ($verify_result ? "TRUE" : "FALSE"));
        
        if ($verify_result) {
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    }

    public function updateUserTokens($userId, $token, $jwt) {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET token = :token, 
                jwt = :jwt,
                last_login = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':token' => $token,
            ':jwt' => $jwt,
            ':id' => $userId
        ]);
    }

    public function verifyUserToken($userId, $token) {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM users 
            WHERE id = :id AND token = :token
        ");
        
        $stmt->execute([':id' => $userId, ':token' => $token]);
        return (bool)$stmt->fetch();
    }

    public function verifyUserJWT($userId, $jwt) {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM users 
            WHERE id = :id AND jwt = :jwt
        ");
        
        $stmt->execute([':id' => $userId, ':jwt' => $jwt]);
        return (bool)$stmt->fetch();
    }

    public function updateUserProfile($userId, $data) {
        $allowedFields = ['email', 'bio', 'avatar_url'];
        $updates = [];
        $params = [':id' => $userId];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }

        if (empty($updates)) return false;

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function addFavorite($userId, $fungiId) {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO user_favorites (user_id, fungi_id)
            VALUES (:user_id, :fungi_id)
        ");
        
        return $stmt->execute([
            ':user_id' => $userId,
            ':fungi_id' => $fungiId
        ]);
    }

    public function removeFavorite($userId, $fungiId) {
        $stmt = $this->pdo->prepare("
            DELETE FROM user_favorites 
            WHERE user_id = :user_id AND fungi_id = :fungi_id
        ");
        
        return $stmt->execute([
            ':user_id' => $userId,
            ':fungi_id' => $fungiId
        ]);
    }

    public function getUserFavorites($userId) {
        $stmt = $this->pdo->prepare("
            SELECT f.* 
            FROM fungi f
            JOIN user_favorites uf ON f.id = uf.fungi_id
            WHERE uf.user_id = :user_id
        ");
        
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

}