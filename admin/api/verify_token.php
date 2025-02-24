<?php
function verifyJWT($token) {
    $secret_key = "tu_clave_secreta_muy_segura";
    
    $token_parts = explode('.', $token);
    if (count($token_parts) !== 3) {
        return false;
    }
    
    list($header_encoded, $payload_encoded, $signature_encoded) = $token_parts;
    
    $signature = base64url_encode(
        hash_hmac('SHA256', "$header_encoded.$payload_encoded", $secret_key, true)
    );
    
    if ($signature_encoded !== $signature) {
        return false;
    }
    
    $payload = json_decode(base64_decode(strtr($payload_encoded, '-_', '+/')), true);
    
    if ($payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

function requireAuth() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token no proporcionado']);
        exit;
    }
    
    $token = $matches[1];
    $payload = verifyJWT($token);
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido o expirado']);
        exit;
    }
    
    return $payload;
}
?> 