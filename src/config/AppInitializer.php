<?php

namespace App;

use DatabaseController;
use AuthController;
use SessionController;
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

        return [$db, $authController, $session, $twig];
    }
} 