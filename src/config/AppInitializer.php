<?php

namespace App\Config;

use App\Controllers\DatabaseController;
use App\Controllers\AuthController;
use App\Controllers\SessionController;
use App\Controllers\LangController;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Dotenv\Dotenv;
use App\Config\ErrorMessages;

/**
 * @class AppInitializer
 * @brief Clase responsable de la inicialización de la aplicación
 * 
 * Esta clase maneja la configuración inicial de la aplicación, incluyendo:
 * - Carga de variables de entorno
 * - Inicialización de sesiones
 * - Configuración de internacionalización (i18n)
 * - Inicialización de controladores
 * - Configuración del motor de plantillas Twig
 */
class AppInitializer
{
    /**
     * @brief Inicializa todos los componentes necesarios de la aplicación
     * 
     * @return array Array conteniendo las instancias inicializadas en el siguiente orden:
     *               [DatabaseController, AuthController, SessionController, Twig\Environment, LangController]
     * 
     * @throws \RuntimeException Si no se encuentra el directorio de traducciones
     * @throws \RuntimeException Si no se encuentra el directorio de templates
     * @throws \RuntimeException Si no se encuentra la clase DatabaseController
     */
    public static function initialize()
    {
        // Cargar variables de entorno si existe un archivo .env
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }

        // Cargar configuración desde defaults.inc.php
        // Iniciar la sesión si aún no está activa
        // Usar LangController en lugar de configuración directa
        require_once __DIR__ . '/defaults.inc.php';
        if (session_status() === PHP_SESSION_NONE) session_start();

        $langController = new LangController();
        $currentLanguage = $langController->initializeLanguage();
        
        // Cargar dominios adicionales
        $langController->loadTextDomain('navbar');
        $langController->loadTextDomain('about');
        $langController->loadTextDomain('fungi');
        $langController->loadTextDomain('admin');
        $langController->loadTextDomain('user');
        $langController->loadTextDomain('api');

        // Verifica que DatabaseController existe antes de instanciarlo
        if (!class_exists(DatabaseController::class)) {
            throw new \RuntimeException(ErrorMessages::format(
                ErrorMessages::SYSTEM_DEPENDENCY_ERROR, 
                'La clase DatabaseController no se encuentra. Verifica que el archivo existe en src/Controllers/DatabaseController.php'
            ));
        }

        // Inicializar controladores
        $db = new DatabaseController();
        $authController = new AuthController($db);
        $session = new SessionController($db);

        // Configurar Twig
        $templatesDir = __DIR__ . '/../../public/templates';
        
        // Verificar que el directorio de templates exista
        if (!is_dir($templatesDir)) {
            throw new \RuntimeException(ErrorMessages::format(
                ErrorMessages::FILE_NOT_FOUND, 
                'El directorio de templates no existe: ' . $templatesDir
            ));
        }
        
        $loader = new FilesystemLoader($templatesDir);
        $twig = new Environment($loader, [
            'cache' => false,
            'debug' => DEBUG_MODE,
        ]);
        $twig->addExtension(new DebugExtension());

        // Agregar la función de traducción usando gettext
        $twig->addFunction(new \Twig\TwigFunction('_', function ($string) {
            return gettext($string);
        }));
        
        // Agregar la función para traducciones con dominio específico
        $twig->addFunction(new \Twig\TwigFunction('__d', function ($text, $domain = 'messages') use ($langController) {
            $traduccion = $langController->gettext($text, $domain);
            if ($traduccion === $text && $domain !== 'messages') error_log("Falla traducción [$domain]: $text");
            return $traduccion;
        }));
        $twig->addGlobal('idioma_actual', $currentLanguage);
        $twig->addGlobal('idiomas_soportados', $langController->getSupportedLanguages());
        return [$db, $authController, $session, $twig, $langController];
    }
} 