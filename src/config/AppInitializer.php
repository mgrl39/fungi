<?php

namespace App\Config;

use App\Controllers\DatabaseController;
use App\Controllers\AuthController;
use App\Controllers\SessionController;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Dotenv\Dotenv;
use App\Config\ErrorMessages;

class AppInitializer
{
    public static function initialize()
    {
        // Cargar variables de entorno si existe un archivo .env
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }

        // Cargar configuración desde defaults.inc.php
        require_once __DIR__ . '/defaults.inc.php';

        // Iniciar la sesión si aún no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Configuración de internacionalización
        $lang_supported = ['es', 'en', 'ca'];
        
        // Detectar idioma desde la URL (para cambios de idioma)
        if (isset($_GET['lang']) && in_array($_GET['lang'], $lang_supported)) {
            $_SESSION['idioma'] = $_GET['lang'];
        }
        
        // Detectar idioma desde la sesión o asignar uno predeterminado
        $idioma = $_SESSION['idioma'] ?? 'es';
        $localeMap = [
            'es' => 'es_ES',
            'en' => 'en_US',
            'ca' => 'ca_ES',
        ];
        $locale = $localeMap[$idioma] . ".UTF-8";
        
        // Configurar `gettext` para usar el idioma seleccionado
        putenv("LANG=$locale");
        putenv("LANGUAGE=$locale");
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);

        // Verificar que existe el directorio de traducciones
        $localeDir = __DIR__ . '/../../locales';
        if (!is_dir($localeDir)) {
            error_log("Error: No existe el directorio de traducciones: $localeDir");
            throw new \RuntimeException("El directorio de traducciones no existe: $localeDir");
        }

        // Verificar que existe el archivo .mo
        $moFile = "$localeDir/{$localeMap[$idioma]}/LC_MESSAGES/messages.mo";
        if (!file_exists($moFile)) {
            error_log("Error: No se encuentra el archivo de traducciones: $moFile");
        }

        bindtextdomain("messages", $localeDir);
        bind_textdomain_codeset("messages", "UTF-8");
        textdomain("messages");

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
        
        // Agregar variables globales para el sistema de plantillas
        $twig->addGlobal('idioma_actual', $idioma);
        $twig->addGlobal('idiomas_soportados', $lang_supported);

        return [$db, $authController, $session, $twig];
    }
} 