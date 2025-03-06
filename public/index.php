<?php
// public/index.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/controllers/DatabaseController.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/SessionController.php';
require_once __DIR__ . '/../src/config/i18n.php';
require_once __DIR__ . '/../src/config/AppInitializer.php';

use App\AppInitializer;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Inicializar la aplicación
list($db, $authController, $session, $twig) = AppInitializer::initialize();

// Cargar el archivo de rutas y pasar las dependencias
require_once __DIR__ . '/../src/routes.php';

// Agregar variable global para el tema
/*
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
$twig->addGlobal('theme', $theme);
*/