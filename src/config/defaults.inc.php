<?php
namespace App\Config;

use App\Config\ErrorMessages;

/**
 * @file
 * Archivo de configuración por defecto para la aplicación.
 * 
 * Este archivo define las constantes de configuración por defecto
 * para la aplicación, incluyendo la configuración de la base de datos,
 * seguridad y entorno.
 */

// Verificar si las constantes ya están definidas para evitar redefiniciones
if (!defined('DEFAULT_PAGE_SIZE')) {
    /**
     * @defgroup ConfiguraciónGeneral Configuración General
     * @{
     */
    /** Tamaño de página por defecto para la paginación. */
    define('DEFAULT_PAGE_SIZE', 20);
    /** @} */

    /**
     * @defgroup ConfiguraciónBaseDatos Configuración de la Base de Datos
     * @{
     */
    /** Conjunto de caracteres por defecto para la base de datos. */
    define('DB_CHARSET', 'utf8');
    /** Host de la base de datos. */
    define('DB_HOST', 'localhost');
    /** Nombre de la base de datos. */
    define('DB_NAME', 'fungidb');
    /** Usuario de la base de datos. */
    define('DB_USER', 'root');
    /** Contraseña de la base de datos. */
    define('DB_PASS', 'Root@1234');
    /** @} */

    /**
     * @defgroup ConfiguraciónSeguridad Configuración de Seguridad
     * @{
     */
    /** Clave secreta para JWT. */
    define('JWT_SECRET_KEY', 'fungi_secret_key');
    /** @} */

    /**
     * @defgroup ConfiguraciónEntorno Configuración del Entorno
     * @{
     */
    /** Modo de depuración. */
    define('DEBUG_MODE', true);
    /** @} */

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