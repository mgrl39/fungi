<?php

namespace App\Config;

use App\Controllers\DatabaseController;
use App\Controllers\AuthController;
use App\Controllers\SessionController;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Dotenv\Dotenv;

class AppInitializer
{
    public static function initialize()
    {
        // Cargar variables de entorno
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        // Verifica que DatabaseController existe antes de instanciarlo
        if (!class_exists(DatabaseController::class)) {
            throw new \RuntimeException('La clase DatabaseController no se encuentra. Verifica que el archivo existe en src/Controllers/DatabaseController.php');
        }

        // Inicializar controladores
        $db = new DatabaseController();
        $authController = new AuthController($db);
        $session = new SessionController($db);

        // Configurar Twig
        $loader = new FilesystemLoader(__DIR__ . '/../../public/templates');
        $twig = new Environment($loader, [
            'cache' => false,
            'debug' => $_ENV['DEBUG_MODE'] === 'true',
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