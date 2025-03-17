<?php

namespace App\Controllers;

class RouteController {
    private $routes = [];
    private $db;
    private $session;
    private $twig;
    private $authController;
    private $fungiController;
    private $statsController;
    private $docsController;
    private $langController;
    
    public function __construct($twig, $db, $session, $controllers = []) {
        $this->twig = $twig;
        $this->db = $db;
        $this->session = $session;
        
        // Asignar controladores
        $this->authController = $controllers['auth'] ?? null;
        $this->fungiController = $controllers['fungi'] ?? null;
        $this->statsController = $controllers['stats'] ?? null;
        $this->docsController = $controllers['docs'] ?? null;
        $this->langController = $controllers['lang'] ?? null;
    }
    
    public function addRoute($path, $options) {
        $this->routes[$path] = $options;
    }
    
    public function addRoutes($routes) {
        $this->routes = array_merge($this->routes, $routes);
    }
    /**
     * Maneja la solicitud de ruta actual
     * 
     * 1. Intenta coincidencia exacta primero
     * 2. Si no hay coincidencia exacta, intenta coincidencia con patrones
     *
     * @param string $uri URI de la solicitud actual
     * @return bool True si se procesó correctamente la ruta
     */
    public function handleRequest($uri) {
        if (isset($this->routes[$uri])) return $this->processRoute($this->routes[$uri]);
        else return $this->processPatternRoutes($uri);
    }
    
    /**
     * Procesa una ruta específica y maneja las redirecciones, autenticación y renderizado
     *
     * @param array $route Configuración de la ruta a procesar
     * @return bool True si la ruta se procesó correctamente
     */
    private function processRoute($route) {
        if (isset($route['redirect'])) {
            header('Location: ' . $route['redirect']);
            exit;
        }
        if (isset($route['auth_required']) && $route['auth_required'] && !$this->session->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        if (isset($route['admin_required']) && $route['admin_required'] && !$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        $data = isset($route['handler']) ? $route['handler']($this->twig, $this->db, $this->session, $this->authController ?? null) : [];
        if (empty($data) && isset($route['title'])) $data = ['title' => $route['title']];
        if (isset($route['template']) && $route['template'] !== null) renderTemplate($route['template'], $data);
        return true;
    }
    
    /**
     * Procesa rutas que coinciden con patrones de expresiones regulares
     * 
     * Itera sobre las rutas buscando coincidencias con patrones que contengan
     * expresiones regulares. Si no encuentra coincidencia, muestra la página 404.
     *
     * @param string $uri URI de la solicitud actual
     * @return bool True si se encontró y procesó una ruta, False si se mostró 404
     */
    private function processPatternRoutes($uri) {
        foreach ($this->routes as $pattern => $route) {
            if (strpos($pattern, '(') !== false) {
                if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                    return $this->processRoute($route);
                }
            }
        }
        header('HTTP/1.1 404 Not Found');
        $data = isset($this->routes['/404']['handler']) ? $this->routes['/404']['handler']($this->twig, $this->db, $this->session, $this->authController ?? null) : ['title' => _('Página no encontrada')];
        renderTemplate($this->routes['/404']['template'], $data);
        return false;
    }
} 