<?php

// public/api.php
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/controllers/DatabaseController.php';

try {
    // Crear una conexión a la base de datos
    $db = new DatabaseController();

    // Obtener el método HTTP y el endpoint solicitado
    $method = $_SERVER['REQUEST_METHOD'];
    $endpoint = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Limpiar el endpoint (eliminar prefijos innecesarios)
    $endpoint = trim($endpoint, '/');
    if (strpos($endpoint, 'api/') === 0) {
        $endpoint = substr($endpoint, 4);
    }

    // Manejar la solicitud según el método y el endpoint
    switch ($method) {
        case 'GET':
            handleGetRequest($db, $endpoint);
            break;
        case 'POST':
            handlePostRequest($db, $endpoint);
            break;
        case 'PUT':
            handlePutRequest($db, $endpoint);
            break;
        case 'DELETE':
            handleDeleteRequest($db, $endpoint);
            break;
        default:
            http_response_code(405); // Método no permitido
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
} catch (\Exception $e) {
    http_response_code(500); // Error interno del servidor
    echo json_encode(['error' => 'Error interno del servidor', 'message' => $e->getMessage()]);
}

/**
 * Manejador de solicitudes GET.
 */
function handleGetRequest($db, $endpoint)
{
    global $method;

    switch ($endpoint) {
        case 'fungi':
            $stmt = $db->query("SELECT * FROM fungi");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case preg_match('/^fungi\/(\d+)$/', $endpoint, $matches) ? true : false:
            $id = $matches[1];
            $stmt = $db->prepare("SELECT * FROM fungi WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Hongo no encontrado']);
            }
            break;

        case 'users':
            $stmt = $db->query("SELECT id, username, email, role, created_at FROM users");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case preg_match('/^users\/(\d+)$/', $endpoint, $matches) ? true : false:
            $id = $matches[1];
            $stmt = $db->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Usuario no encontrado']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
            break;
    }
}

/**
 * Manejador de solicitudes POST.
 */
function handlePostRequest($db, $endpoint)
{
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($endpoint) {
        case 'fungi':
            $requiredFields = ['name', 'author', 'edibility', 'habitat', 'observations', 'common_name', 'synonym', 'title'];
            if (!validateRequiredFields($data, $requiredFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan campos obligatorios']);
                return;
            }

            $stmt = $db->prepare("INSERT INTO fungi (name, author, edibility, habitat, observations, common_name, synonym, title) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['name'],
                $data['author'],
                $data['edibility'],
                $data['habitat'],
                $data['observations'],
                $data['common_name'],
                $data['synonym'],
                $data['title']
            ]);

            http_response_code(201); // Created
            echo json_encode(['id' => $db->lastInsertId()]);
            break;

        case 'users':
            $requiredFields = ['username', 'email', 'password'];
            if (!validateRequiredFields($data, $requiredFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan campos obligatorios']);
                return;
            }

            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$data['username'], $data['email'], $passwordHash]);

            http_response_code(201); // Created
            echo json_encode(['id' => $db->lastInsertId()]);
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
            break;
    }
}

/**
 * Manejador de solicitudes PUT.
 */
function handlePutRequest($db, $endpoint)
{
    $data = json_decode(file_get_contents('php://input'), true);

    switch ($endpoint) {
        case preg_match('/^fungi\/(\d+)$/', $endpoint, $matches) ? true : false:
            $id = $matches[1];
            $stmt = $db->prepare("UPDATE fungi SET name = ?, author = ?, edibility = ?, habitat = ?, observations = ?, common_name = ?, synonym = ?, title = ? WHERE id = ?");
            $stmt->execute([
                $data['name'] ?? null,
                $data['author'] ?? null,
                $data['edibility'] ?? null,
                $data['habitat'] ?? null,
                $data['observations'] ?? null,
                $data['common_name'] ?? null,
                $data['synonym'] ?? null,
                $data['title'] ?? null,
                $id
            ]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['message' => 'Hongo actualizado correctamente']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Hongo no encontrado']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
            break;
    }
}

/**
 * Manejador de solicitudes DELETE.
 */
function handleDeleteRequest($db, $endpoint)
{
    switch ($endpoint) {
        case preg_match('/^fungi\/(\d+)$/', $endpoint, $matches) ? true : false:
            $id = $matches[1];
            $stmt = $db->prepare("DELETE FROM fungi WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['message' => 'Hongo eliminado correctamente']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Hongo no encontrado']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
            break;
    }
}

/**
 * Validación de campos requeridos.
 */
function validateRequiredFields(array $data, array $requiredFields): bool
{
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return false;
        }
    }
    return true;
}
