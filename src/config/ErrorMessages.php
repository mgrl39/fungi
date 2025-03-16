<?php
namespace App\Config;

/**
 * @brief Clase que contiene mensajes de error estandarizados para la aplicación
 * @details Proporciona constantes organizadas por categoría para diferentes tipos de errores
 *          y un método para formatear mensajes con parámetros.
 * @namespace App\Config
 */
class ErrorMessages
{
    // Errores de Base de Datos
    const DB_CONNECTION_ERROR = '🛑 Error al conectar con la base de datos: %s 🛑';
    const DB_QUERY_ERROR = '❌ Error en la consulta: %s ❌';
    const DB_RECORD_NOT_FOUND = '🔍 No se encontró el registro solicitado 🔍';
    const DB_INSERT_ERROR = '❗ Error al insertar el registro: %s ❗';
    const DB_UPDATE_ERROR = '⚠️ Error al actualizar el registro: %s ⚠️';
    const DB_DELETE_ERROR = '🗑️ Error al eliminar el registro: %s 🗑️';
    
    // Errores de Autenticación
    const AUTH_INVALID_CREDENTIALS = '🔐 Credenciales inválidas 🔐';
    const AUTH_TOKEN_EXPIRED = '⏰ El token ha expirado ⏰';
    const AUTH_TOKEN_INVALID = '🚫 Token inválido 🚫';
    const AUTH_UNAUTHORIZED = '🚷 No autorizado para realizar esta acción 🚷';
    const AUTH_SESSION_EXPIRED = '⌛ La sesión ha expirado ⌛';

    const AUTH_REQUIRED = '🔐 Debes estar autenticado para realizar esta acción 🔐';
    
    // Errores de Validación
    const VALIDATION_REQUIRED_FIELD = '📝 El campo %s es obligatorio 📝';
    const VALIDATION_INVALID_EMAIL = '📧 El correo electrónico no es válido 📧';
    const VALIDATION_INVALID_FORMAT = '📋 Formato inválido para el campo %s 📋';
    const VALIDATION_PASSWORD_WEAK = '🔒 La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas y números 🔒';
    const VALIDATION_PASSWORDS_NOT_MATCH = '🔄 Las contraseñas no coinciden 🔄';
    
    // Errores de Archivo
    const FILE_UPLOAD_ERROR = '📤 Error al subir el archivo: %s 📤';
    const FILE_NOT_FOUND = '🔎 Archivo no encontrado: %s 🔎';
    const FILE_INVALID_TYPE = '📁 Tipo de archivo no permitido 📁';
    const FILE_SIZE_EXCEEDED = '📏 El tamaño del archivo excede el límite permitido 📏';
    
    // Errores de Configuración
    const CONFIG_MISSING_KEY = '🔑 Falta la clave de configuración: %s 🔑';
    const CONFIG_INVALID_VALUE = '⚙️ Valor de configuración inválido para: %s ⚙️';
    const CONFIG_FILE_NOT_FOUND = '📄 Archivo de configuración no encontrado: %s 📄';
    
    // Errores HTTP
    const HTTP_400 = '🍄 Solicitud incorrecta 🍄';
    const HTTP_401 = '🍄 No autorizado 🍄';
    const HTTP_403 = '🍄 Acceso prohibido 🍄';
    const HTTP_404 = '🍄 Recurso no encontrado 🍄';
    const HTTP_405 = '🍄 Método no permitido 🍄';
    const HTTP_429 = '🍄 Demasiadas solicitudes 🍄';
    const HTTP_500 = '🍄 Error interno del servidor 🍄';
    
    // Errores de Sistema
    const SYSTEM_INITIALIZATION_ERROR = '💻 Error al inicializar el sistema: %s 💻';
    const SYSTEM_DEPENDENCY_ERROR = '⛓️ Error de dependencia: %s ⛓️';
    const SYSTEM_MAINTENANCE_MODE = '🛠️ El sistema se encuentra en mantenimiento 🛠️';
    
    const VALIDATION_VALUE_ALREADY_EXISTS = 'Ya existe un registro con el valor %s';
    /**
     * Formatea un mensaje de error con los parámetros proporcionados
     *
     * @param string $message El mensaje de error con marcadores de posición
     * @param mixed ...$params Los parámetros para reemplazar en el mensaje
     * @return string El mensaje formateado con los parámetros
     */
    public static function format(string $message, ...$params): string
    {
        return vsprintf($message, $params);
    }
} 