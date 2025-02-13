<?php

// public/api.php
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/controllers/DatabaseController.php';

try {
    // Crear una conexión a la base de datos mediante DatabaseController
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
            $fungis = $db->getAllData('fungi');
            echo json_encode($fungis);
            break;

        case preg_match('/^fungi\/(\d+)$/', $endpoint, $matches) ? true : false:
            $id = $matches[1];
            $fungus = $db->getFungusById($id);
            if ($fungus) {
                echo json_encode($fungus);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Hongo no encontrado']);
            }
            break;

        case 'taxonomy':
            $stmt = $db->pdo->query("SELECT * FROM taxonomy");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case preg_match('/^taxonomy\/(\d+)$/', $endpoint, $matches) ? true : false:
            $id = $matches[1];
            $stmt = $db->pdo->prepare("SELECT * FROM taxonomy WHERE fungi_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Taxonomía no encontrada']);
            }
            break;

        case 'characteristics':
            $stmt = $db->pdo->query("SELECT * FROM characteristics");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case preg_match('/^characteristics\/(\d+)$/', $endpoint, $matches) ? true : false:
            $id = $matches[1];
            $stmt = $db->pdo->prepare("SELECT * FROM characteristics WHERE fungi_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode($result);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Características no encontradas']);
            }
            break;

        case 'users':
            $stmt = $db->pdo->query("SELECT id, username, email, role, created_at FROM users");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
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

            $stmt = $db->pdo->prepare("INSERT INTO fungi (name, author, edibility, habitat, observations, common_name, synonym, title) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
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

            $fungusId = $db->pdo->lastInsertId();
            http_response_code(201); // Created
            echo json_encode(['id' => $fungusId]);

            // Insertar taxonomía si se proporciona
            if (isset($data['division']) || isset($data['subdivision']) || isset($data['class'])) {
                $stmt = $db->pdo->prepare("INSERT INTO taxonomy (fungi_id, division, subdivision, class, subclass, ordo, family) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $fungusId,
                    $data['division'] ?? null,
                    $data['subdivision'] ?? null,
                    $data['class'] ?? null,
                    $data['subclass'] ?? null,
                    $data['ordo'] ?? null,
                    $data['family'] ?? null
                ]);
            }

            // Insertar características si se proporciona
            if (isset($data['cap']) || isset($data['hymenium']) || isset($data['stipe'])) {
                $stmt = $db->pdo->prepare("INSERT INTO characteristics (fungi_id, cap, hymenium, stipe, flesh) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $fungusId,
                    $data['cap'] ?? null,
                    $data['hymenium'] ?? null,
                    $data['stipe'] ?? null,
                    $data['flesh'] ?? null
                ]);
            }

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
            $stmt = $db->pdo->prepare("UPDATE fungi SET name = ?, author = ?, edibility = ?, habitat = ?, observations = ?, common_name = ?, synonym = ?, title = ? WHERE id = ?");
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

            // Eliminar taxonomía asociada
            $stmt = $db->pdo->prepare("DELETE FROM taxonomy WHERE fungi_id = ?");
            $stmt->execute([$id]);

            // Eliminar características asociadas
            $stmt = $db->pdo->prepare("DELETE FROM characteristics WHERE fungi_id = ?");
            $stmt->execute([$id]);

            // Eliminar hongo
            $stmt = $db->pdo->prepare("DELETE FROM fungi WHERE id = ?");
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
