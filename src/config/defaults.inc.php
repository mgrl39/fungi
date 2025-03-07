<?php
namespace App\Config;

use App\Config\ErrorMessages;

// Verificar si las constantes ya están definidas para evitar redefiniciones
if (!defined('DEFAULT_PAGE_SIZE')) {
    // Configuración general
    define('DEFAULT_PAGE_SIZE', 20);
    
    // Configuración de la base de datos
    define('DB_CHARSET', 'utf8');
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'fungidb');
    define('DB_USER', 'root');
    define('DB_PASS', 'Root@1234');
    
    // Configuración de seguridad
    define('JWT_SECRET_KEY', 'fungi_secret_key');
    
    // Configuración del entorno
    define('DEBUG_MODE', true);
    
    // Asegurarse de que las constantes están disponibles globalmente
    if (!getenv('DB_HOST')) {
        putenv("DB_HOST=" . DB_HOST);
        putenv("DB_NAME=" . DB_NAME);
        putenv("DB_USER=" . DB_USER);
        putenv("DB_PASS=" . DB_PASS);
    }
}

// Verificar que las constantes críticas estén definidas
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    throw new \RuntimeException(
        ErrorMessages::format(
            ErrorMessages::CONFIG_MISSING_KEY,
            'constantes de base de datos (DB_HOST, DB_NAME, DB_USER, DB_PASS)'
        )
    );
}

// Verificar que los valores de las constantes no estén vacíos
if (empty(DB_HOST) || empty(DB_NAME) || empty(DB_USER)) {
    throw new \RuntimeException(
        ErrorMessages::format(
            ErrorMessages::CONFIG_INVALID_VALUE,
            'configuración de base de datos'
        )
    );
}