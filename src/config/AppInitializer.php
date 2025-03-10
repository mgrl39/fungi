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
        $templatesDir = __DIR__ . '/../../public/templatess';
        
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

        // Agregar la función de traducción
        $twig->addFunction(new \Twig\TwigFunction('_', function ($string) {
            return $string; // Por ahora solo devuelve el string original
            // Aquí podrías implementar gettext u otro sistema de traducción
        }));

        return [$db, $authController, $session, $twig];
    }
} 