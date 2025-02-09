<?php
// public/index.php

// 1. Cargar el autoload de Composer para disponer de las dependencias (Twig, JWT, etc.)
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Configurar Twig para que lea las plantillas desde la carpeta "public/templates"
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
	// Desactiva la cache durante el desarrollo; en producción puedes usar una carpeta de cache.
	'cache' => false,
	'debug' => true,
]);

// (Opcional) Habilitar extensión de depuración para Twig.
$twig->addExtension(new \Twig\Extension\DebugExtension());

// 3. Obtener la ruta solicitada
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 4. Sistema de routing básico
switch ($uri) {
case '/':
case '/index':
	// Renderizar la plantilla de la página de inicio
	echo $twig->render('index.twig', [
		'title' => 'Página de Inicio'
	]);
	break;
case '/login':
	echo $twig->render('login.twig', [
		'title' => 'Iniciar Sesión'
	]);
	break;
default:
	echo $twig->render('404.twig', [
		'title' => 'Página no encontrada'
	]);
	break;
}

