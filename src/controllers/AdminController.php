<?php

namespace App\Controllers;

/**
 * @class AdminController
 * @brief Controlador para funciones administrativas simplificado
 */
class AdminController
{
    private $db;
    private $session;
    private $userController;
    private $fungiController;

    /**
     * @brief Constructor del controlador de administración
     */
    public function __construct($db, $session)
    {
        $this->db = $db;
        $this->session = $session;
        $this->userController = new UserController($db, $session);
        $this->fungiController = new FungiController($db);
    }

    /**
     * @brief Manejador para el dashboard
     */
    public function dashboardHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        // Obtener estadísticas básicas
        $userQuery = $this->db->query("SELECT COUNT(*) as total FROM users");
        $userData = $userQuery->fetch(\PDO::FETCH_ASSOC);
        $totalUsers = $userData['total'] ?? 0;
        
        $fungiQuery = $this->db->query("SELECT COUNT(*) as total FROM fungi");
        $fungiData = $fungiQuery->fetch(\PDO::FETCH_ASSOC);
        $totalFungi = $fungiData['total'] ?? 0;
        
        return [
            'title' => _('Panel de Administración'),
            'totalUsers' => $totalUsers,
            'totalFungi' => $totalFungi,
            'dashboard' => true
        ];
    }

    /**
     * @brief Manejador para gestión de usuarios
     */
    public function usersHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        $query = $this->db->query("SELECT * FROM users ORDER BY id DESC");
        $users = $query->fetchAll(\PDO::FETCH_ASSOC);
        
        $message = '';
        if (isset($_GET['created']) && $_GET['created'] === 'true') {
            $message = _('Usuario creado correctamente');
        } else if (isset($_GET['deleted']) && $_GET['deleted'] === 'true') {
            $message = _('Usuario eliminado correctamente');
        }
        
        return [
            'title' => _('Gestión de Usuarios'),
            'users' => $users,
            'message' => $message
        ];
    }

    /**
     * @brief Manejador para crear un nuevo usuario
     */
    public function createUserHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        $message = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            
            if (empty($username) || empty($email) || empty($password)) {
                $error = _('Todos los campos son obligatorios');
            } else {
                try {
                    // Verificar si el nombre de usuario o email ya existe
                    $checkQuery = $this->db->query(
                        "SELECT COUNT(*) as total FROM users WHERE username = ? OR email = ?",
                        [$username, $email]
                    );
                    $result = $checkQuery->fetch(\PDO::FETCH_ASSOC);
                    
                    if ($result['total'] > 0) {
                        $error = _('El nombre de usuario o email ya está en uso');
                    } else {
                        // Generar hash de contraseña usando password_hash nativo
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insertar nuevo usuario
                        $this->db->query(
                            "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
                            [$username, $email, $hashedPassword, $role]
                        );
                        
                        header('Location: /admin/users?created=true');
                        exit;
                    }
                } catch (\Exception $e) {
                    $error = 'Error: ' . $e->getMessage();
                }
            }
        }
        
        return [
            'title' => _('Crear Usuario'),
            'message' => $message,
            'error' => $error
        ];
    }

    /**
     * @brief Manejador para eliminar un usuario
     */
    public function deleteUserHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        $userId = (int) $_GET['id'] ?? 0;
        if (!$userId && !empty($_SERVER['PATH_INFO'])) {
            $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            $userId = (int) end($parts);
        }
        
        // Evitar eliminar al propio administrador
        $currentUser = $this->session->getUserData();
        if ($currentUser['id'] == $userId) {
            header('Location: /admin/users?error=selfdelete');
            exit;
        }
        
        $result = $this->userController->deleteUser($userId);
        
        if ($result) {
            header('Location: /admin/users?deleted=true');
        } else {
            header('Location: /admin/users?error=deletefailed');
        }
        exit;
    }
    
    /**
     * @brief Manejador para la gestión de hongos
     */
    public function fungiHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        $query = $this->db->query("SELECT f.id, f.name, f.common_name, f.edibility FROM fungi f ORDER BY f.id DESC");
        $fungi = $query->fetchAll(\PDO::FETCH_ASSOC);
        
        $message = '';
        if (isset($_GET['created']) && $_GET['created'] === 'true') {
            $message = _('Hongo creado correctamente');
        } else if (isset($_GET['deleted']) && $_GET['deleted'] === 'true') {
            $message = _('Hongo eliminado correctamente');
        } else if (isset($_GET['updated']) && $_GET['updated'] === 'true') {
            $message = _('Hongo actualizado correctamente');
        }
        
        return [
            'title' => _('Gestión de Hongos'),
            'fungi' => $fungi,
            'message' => $message
        ];
    }
    
    /**
     * @brief Manejador para crear un nuevo hongo
     */
    public function createFungiHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        $message = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $edibility = $_POST['edibility'] ?? '';
            $habitat = $_POST['habitat'] ?? '';
            
            if (empty($name) || empty($edibility) || empty($habitat)) {
                $error = _('Los campos nombre científico, comestibilidad y hábitat son obligatorios');
            } else {
                try {
                    $this->db->beginTransaction();
                    
                    // Insertar en la tabla fungi
                    $this->db->query(
                        "INSERT INTO fungi (name, edibility, habitat, observations, common_name, synonym) 
                         VALUES (?, ?, ?, ?, ?, ?)",
                        [
                            $name,
                            $edibility,
                            $habitat,
                            $_POST['observations'] ?? '',
                            $_POST['common_name'] ?? '',
                            $_POST['synonym'] ?? ''
                        ]
                    );
                    
                    // Obtener el ID del hongo recién creado
                    $result = $this->db->query("SELECT LAST_INSERT_ID() as id");
                    $fungi = $result->fetch(\PDO::FETCH_ASSOC);
                    $fungiId = $fungi['id'] ?? 0;
                    
                    if (!$fungiId) {
                        throw new \Exception(_('Error al obtener el ID del hongo creado'));
                    }
                    
                    // Insertar características (si las hay)
                    $cap = $_POST['cap'] ?? '';
                    $hymenium = $_POST['hymenium'] ?? '';
                    $stipe = $_POST['stipe'] ?? '';
                    $flesh = $_POST['flesh'] ?? '';
                    
                    if (!empty($cap) || !empty($hymenium) || !empty($stipe) || !empty($flesh)) {
                        $this->db->query(
                            "INSERT INTO characteristics (fungi_id, cap, hymenium, stipe, flesh) 
                             VALUES (?, ?, ?, ?, ?)",
                            [$fungiId, $cap, $hymenium, $stipe, $flesh]
                        );
                    }
                    
                    // Insertar taxonomía (si la hay)
                    $kingdom = $_POST['kingdom'] ?? '';
                    $phylum = $_POST['phylum'] ?? '';
                    $class = $_POST['class'] ?? '';
                    $order = $_POST['order'] ?? '';
                    $family = $_POST['family'] ?? '';
                    $genus = $_POST['genus'] ?? '';
                    
                    if (!empty($kingdom) || !empty($phylum) || !empty($class) || 
                        !empty($order) || !empty($family) || !empty($genus)) {
                        $this->db->query(
                            "INSERT INTO taxonomy (fungi_id, kingdom, phylum, class, `order`, family, genus) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)",
                            [$fungiId, $kingdom, $phylum, $class, $order, $family, $genus]
                        );
                    }
                    
                    // Procesar imágenes (si las hay)
                    if (isset($_FILES['fungi_images']) && !empty($_FILES['fungi_images']['name'][0])) {
                        $uploadDir = 'uploads/fungi/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        // Obtener configuración de imagen
                        $configResult = $this->db->query("SELECT * FROM image_config WHERE config_key = 'upload_path' LIMIT 1");
                        $config = $configResult->fetch(\PDO::FETCH_ASSOC);
                        $configKey = $config ? $config['config_key'] : 'upload_path';
                        
                        // Procesar cada imagen
                        $images = $_FILES['fungi_images'];
                        $count = count($images['name']);
                        
                        for ($i = 0; $i < $count; $i++) {
                            if ($images['error'][$i] === 0) {
                                $filename = $fungiId . '_' . time() . '_' . $i . '_' . basename($images['name'][$i]);
                                $destination = $uploadDir . $filename;
                                
                                if (move_uploaded_file($images['tmp_name'][$i], $destination)) {
                                    // Guardar en la tabla images
                                    $this->db->query(
                                        "INSERT INTO images (filename, config_key, description) VALUES (?, ?, ?)",
                                        [$filename, $configKey, "Imagen de $name"]
                                    );
                                    
                                    // Obtener el ID de la imagen
                                    $imageResult = $this->db->query("SELECT LAST_INSERT_ID() as id");
                                    $image = $imageResult->fetch(\PDO::FETCH_ASSOC);
                                    $imageId = $image['id'] ?? 0;
                                    
                                    if ($imageId) {
                                        // Relacionar con el hongo
                                        $this->db->query(
                                            "INSERT INTO fungi_images (fungi_id, image_id) VALUES (?, ?)",
                                            [$fungiId, $imageId]
                                        );
                                    }
                                }
                            }
                        }
                    }
                    
                    $this->db->commit();
                    header('Location: /admin/fungi?created=true');
                    exit;
                } catch (\Exception $e) {
                    $this->db->rollBack();
                    $error = 'Error: ' . $e->getMessage();
                }
            }
        }
        
        return [
            'title' => _('Crear Hongo'),
            'message' => $message,
            'error' => $error
        ];
    }
    
    /**
     * @brief Manejador para editar un hongo
     */
    public function editFungiHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        $fungiId = (int) ($_GET['id'] ?? 0);
        if (!$fungiId && !empty($_SERVER['PATH_INFO'])) {
            $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            $fungiId = (int) end($parts);
        }
        
        // Obtener datos del hongo
        $fungusQuery = $this->db->query("SELECT * FROM fungi WHERE id = ?", [$fungiId]);
        $fungus = $fungusQuery->fetch(\PDO::FETCH_ASSOC);
        
        if (!$fungus) {
            header('Location: /admin/fungi?error=notfound');
            exit;
        }
        
        // Obtener taxonomía
        $taxonomyQuery = $this->db->query("SELECT * FROM taxonomy WHERE fungi_id = ?", [$fungiId]);
        $taxonomy = $taxonomyQuery->fetch(\PDO::FETCH_ASSOC) ?: [];
        
        // Obtener características
        $characteristicsQuery = $this->db->query("SELECT * FROM characteristics WHERE fungi_id = ?", [$fungiId]);
        $characteristics = $characteristicsQuery->fetch(\PDO::FETCH_ASSOC) ?: [];
        
        // Obtener imágenes asociadas
        $imagesQuery = $this->db->query(
            "SELECT i.* FROM images i 
             JOIN fungi_images fi ON i.id = fi.image_id 
             WHERE fi.fungi_id = ?", 
            [$fungiId]
        );
        $fungusImages = $imagesQuery->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        
        $message = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $edibility = $_POST['edibility'] ?? '';
            $habitat = $_POST['habitat'] ?? '';
            
            if (empty($name) || empty($edibility) || empty($habitat)) {
                $error = _('Los campos nombre científico, comestibilidad y hábitat son obligatorios');
            } else {
                try {
                    $this->db->beginTransaction();
                    
                    // Construir los campos a actualizar
                    $updates = [
                        'name' => $name,
                        'edibility' => $edibility,
                        'habitat' => $habitat,
                        'observations' => $_POST['observations'] ?? '',
                        'common_name' => $_POST['common_name'] ?? '',
                        'synonym' => $_POST['synonym'] ?? ''
                    ];
                    
                    // Construir la consulta dinámica para actualizar fungi
                    $setClause = [];
                    $params = [];
                    
                    foreach ($updates as $field => $value) {
                        $setClause[] = "$field = ?";
                        $params[] = $value;
                    }
                    
                    // Añadir el ID al final de los parámetros
                    $params[] = $fungiId;
                    
                    $sql = "UPDATE fungi SET " . implode(', ', $setClause) . " WHERE id = ?";
                    $result = $this->db->query($sql, $params);
                    
                    // Actualizar taxonomía
                    if (isset($_POST['taxonomy']) && is_array($_POST['taxonomy'])) {
                        $taxonomyData = $_POST['taxonomy'];
                        
                        // Verificar si existe taxonomía para este hongo
                        $taxonomyCheck = $this->db->query("SELECT COUNT(*) as count FROM taxonomy WHERE fungi_id = ?", [$fungiId]);
                        $taxonomyExists = $taxonomyCheck->fetch(\PDO::FETCH_ASSOC)['count'] > 0;
                        
                        if ($taxonomyExists) {
                            // Actualizar registro existente
                            $taxSetClause = [];
                            $taxParams = [];
                            
                            foreach ($taxonomyData as $field => $value) {
                                $taxSetClause[] = "$field = ?";
                                $taxParams[] = $value;
                            }
                            
                            // Añadir el fungi_id al final de los parámetros
                            $taxParams[] = $fungiId;
                            
                            $taxSql = "UPDATE taxonomy SET " . implode(', ', $taxSetClause) . " WHERE fungi_id = ?";
                            $this->db->query($taxSql, $taxParams);
                        } else {
                            // Insertar nuevo registro
                            $taxonomyData['fungi_id'] = $fungiId;
                            
                            $taxFields = implode(', ', array_keys($taxonomyData));
                            $taxPlaceholders = implode(', ', array_fill(0, count($taxonomyData), '?'));
                            
                            $this->db->query(
                                "INSERT INTO taxonomy ($taxFields) VALUES ($taxPlaceholders)",
                                array_values($taxonomyData)
                            );
                        }
                    }
                    
                    // Actualizar características
                    if (isset($_POST['characteristics']) && is_array($_POST['characteristics'])) {
                        $charData = $_POST['characteristics'];
                        
                        // Verificar si existen características para este hongo
                        $charCheck = $this->db->query("SELECT COUNT(*) as count FROM characteristics WHERE fungi_id = ?", [$fungiId]);
                        $charExists = $charCheck->fetch(\PDO::FETCH_ASSOC)['count'] > 0;
                        
                        if ($charExists) {
                            // Actualizar registro existente
                            $charSetClause = [];
                            $charParams = [];
                            
                            foreach ($charData as $field => $value) {
                                $charSetClause[] = "$field = ?";
                                $charParams[] = $value;
                            }
                            
                            // Añadir el fungi_id al final de los parámetros
                            $charParams[] = $fungiId;
                            
                            $charSql = "UPDATE characteristics SET " . implode(', ', $charSetClause) . " WHERE fungi_id = ?";
                            $this->db->query($charSql, $charParams);
                        } else {
                            // Insertar nuevo registro
                            $charData['fungi_id'] = $fungiId;
                            
                            $charFields = implode(', ', array_keys($charData));
                            $charPlaceholders = implode(', ', array_fill(0, count($charData), '?'));
                            
                            $this->db->query(
                                "INSERT INTO characteristics ($charFields) VALUES ($charPlaceholders)",
                                array_values($charData)
                            );
                        }
                    }
                    
                    // Procesar nuevas imágenes
                    if (isset($_FILES['fungi_images']) && !empty($_FILES['fungi_images']['name'][0])) {
                        $uploadDir = 'uploads/fungi/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        // Obtener configuración de imagen
                        $configResult = $this->db->query("SELECT * FROM image_config WHERE config_key = 'upload_path' LIMIT 1");
                        $config = $configResult->fetch(\PDO::FETCH_ASSOC);
                        $configKey = $config ? $config['config_key'] : 'upload_path';
                        
                        // Procesar cada imagen
                        $images = $_FILES['fungi_images'];
                        $count = count($images['name']);
                        
                        for ($i = 0; $i < $count; $i++) {
                            if ($images['error'][$i] === 0) {
                                $filename = $fungiId . '_' . time() . '_' . $i . '_' . basename($images['name'][$i]);
                                $destination = $uploadDir . $filename;
                                
                                if (move_uploaded_file($images['tmp_name'][$i], $destination)) {
                                    // Guardar en la tabla images
                                    $this->db->query(
                                        "INSERT INTO images (filename, config_key, description) VALUES (?, ?, ?)",
                                        [$filename, $configKey, "Imagen de $name"]
                                    );
                                    
                                    // Obtener el ID de la imagen
                                    $imageResult = $this->db->query("SELECT LAST_INSERT_ID() as id");
                                    $image = $imageResult->fetch(\PDO::FETCH_ASSOC);
                                    $imageId = $image['id'] ?? 0;
                                    
                                    if ($imageId) {
                                        // Relacionar con el hongo
                                        $this->db->query(
                                            "INSERT INTO fungi_images (fungi_id, image_id) VALUES (?, ?)",
                                            [$fungiId, $imageId]
                                        );
                                    }
                                }
                            }
                        }
                    }
                    
                    $this->db->commit();
                    header('Location: /admin/fungi?updated=true');
                    exit;
                } catch (\Exception $e) {
                    $this->db->rollBack();
                    $error = 'Error: ' . $e->getMessage();
                }
            }
        }
        
        return [
            'title' => _('Editar Hongo'),
            'fungus' => $fungus,
            'taxonomy' => $taxonomy,
            'characteristics' => $characteristics,
            'fungusImages' => $fungusImages,
            'message' => $message,
            'error' => $error
        ];
    }
    
    /**
     * @brief Manejador para eliminar un hongo
     */
    public function deleteFungiHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        $fungiId = (int) ($_GET['id'] ?? 0);
        
        if (!$fungiId && !empty($_SERVER['PATH_INFO'])) {
            $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            $fungiId = (int) end($parts);
        }
        
        if (!$fungiId) {
            header('Location: /admin/fungi?error=invalidid');
            exit;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Primero eliminamos las relaciones de imágenes
            $imageQuery = $this->db->query(
                "SELECT i.id, i.filename FROM images i 
                 JOIN fungi_images fi ON i.id = fi.image_id 
                 WHERE fi.fungi_id = ?", 
                [$fungiId]
            );
            $images = $imageQuery->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($images as $image) {
                // Eliminar el archivo físico
                $filePath = 'uploads/fungi/' . $image['filename'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Eliminar relación
                $this->db->query("DELETE FROM fungi_images WHERE image_id = ?", [$image['id']]);
                
                // Eliminar registro de imagen
                $this->db->query("DELETE FROM images WHERE id = ?", [$image['id']]);
            }
            
            // Eliminar taxonomía
            $this->db->query("DELETE FROM taxonomy WHERE fungi_id = ?", [$fungiId]);
            
            // Eliminar características
            $this->db->query("DELETE FROM characteristics WHERE fungi_id = ?", [$fungiId]);
            
            // Eliminar likes y favoritos
            $this->db->query("DELETE FROM user_likes WHERE fungi_id = ?", [$fungiId]);
            $this->db->query("DELETE FROM user_favorites WHERE fungi_id = ?", [$fungiId]);
            
            // Finalmente eliminar el hongo
            $this->db->query("DELETE FROM fungi WHERE id = ?", [$fungiId]);
            
            $this->db->commit();
            header('Location: /admin/fungi?deleted=true');
        } catch (\Exception $e) {
            $this->db->rollBack();
            header('Location: /admin/fungi?error=' . urlencode($e->getMessage()));
        }
        exit;
    }

    /**
     * @brief Manejador para editar un usuario
     */
    public function editUserHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        $userId = (int) $_GET['id'] ?? 0;
        if (!$userId && !empty($_SERVER['PATH_INFO'])) {
            $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            $userId = (int) end($parts);
        }
        
        // Obtener datos del usuario
        $userQuery = $this->db->query("SELECT * FROM users WHERE id = ?", [$userId]);
        $user = $userQuery->fetch(\PDO::FETCH_ASSOC);
        
        if (!$user) {
            header('Location: /admin/users?error=notfound');
            exit;
        }
        
        $message = '';
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($email)) {
                $error = _('Los campos nombre de usuario y email son obligatorios');
            } else {
                // Construir los campos a actualizar
                $updates = [
                    'username' => $username,
                    'email' => $email,
                    'role' => $role
                ];
                
                // Si se proporciona una nueva contraseña, actualizarla
                if (!empty($password)) {
                    // Usar password_hash nativo en lugar de depender de AuthController
                    $updates['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                
                // Construir la consulta dinámica
                $setClause = [];
                $params = [];
                
                foreach ($updates as $field => $value) {
                    $setClause[] = "$field = ?";
                    $params[] = $value;
                }
                
                // Añadir el ID al final de los parámetros
                $params[] = $userId;
                
                $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = ?";
                $result = $this->db->query($sql, $params);
                
                if ($result) {
                    header('Location: /admin/users?updated=true');
                    exit;
                } else {
                    $error = _('Error al actualizar el usuario');
                }
            }
        }
        
        return [
            'title' => _('Editar Usuario'),
            'user' => $user,
            'message' => $message,
            'error' => $error
        ];
    }

    /**
     * @brief Manejador para eliminar una imagen de un hongo
     */
    public function deleteImageHandler()
    {
        if (!$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        $imageId = (int) ($_GET['image_id'] ?? 0);
        $fungiId = (int) ($_GET['fungi_id'] ?? 0);
        
        if (!$imageId || !$fungiId) {
            header('Location: /admin/fungi?error=invalidparams');
            exit;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Obtener información de la imagen
            $imageQuery = $this->db->query("SELECT * FROM images WHERE id = ?", [$imageId]);
            $image = $imageQuery->fetch(\PDO::FETCH_ASSOC);
            
            // Eliminar relación
            $this->db->query("DELETE FROM fungi_images WHERE fungi_id = ? AND image_id = ?", [$fungiId, $imageId]);
            
            // Eliminar archivo físico si existe
            if ($image && !empty($image['filename'])) {
                $filePath = 'uploads/fungi/' . $image['filename'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Eliminar registro de la imagen
            $this->db->query("DELETE FROM images WHERE id = ?", [$imageId]);
            
            $this->db->commit();
            header('Location: /admin/edit-fungi?id=' . $fungiId . '&imageDeleted=true');
        } catch (\Exception $e) {
            $this->db->rollBack();
            header('Location: /admin/edit-fungi?id=' . $fungiId . '&error=' . urlencode($e->getMessage()));
        }
        exit;
    }
}
