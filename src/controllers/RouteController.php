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
    
    public function handleRequest($uri) {
        // 1. Intentar coincidencia exacta primero
        if (isset($this->routes[$uri])) {
            return $this->processRoute($this->routes[$uri]);
        } 
        // 2. Intentar coincidencia con patrones
        else {
            return $this->processPatternRoutes($uri);
        }
    }
    
    private function processRoute($route) {
        // Si la ruta tiene una redirección configurada
        if (isset($route['redirect'])) {
            header('Location: ' . $route['redirect']);
            exit;
        }
        
        // Verificar si se requiere autenticación para esta ruta
        if (isset($route['auth_required']) && $route['auth_required'] && !$this->session->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        
        // Verificar si se requiere rol de administrador
        if (isset($route['admin_required']) && $route['admin_required'] && !$this->session->isAdmin()) {
            header('Location: /');
            exit;
        }
        
        // Obtener datos para la vista usando el manejador personalizado
        $data = isset($route['handler']) ? $route['handler']($this->twig, $this->db, $this->session, $this->authController ?? null) : [];
        
        // Si no hay datos pero hay título, usar el título como datos
        if (empty($data) && isset($route['title'])) {
            $data = ['title' => $route['title']];
        }
        
        // Renderizar la plantilla
        if (isset($route['template']) && $route['template'] !== null) {
            renderTemplate($route['template'], $data);
        }
        
        return true;
    }
    
    private function processPatternRoutes($uri) {
        foreach ($this->routes as $pattern => $route) {
            // Si el patrón contiene caracteres especiales como paréntesis (indicando expresión regular)
            if (strpos($pattern, '(') !== false) {
                if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                    return $this->processRoute($route);
                }
            }
        }
        
        // Si no se encontró ninguna coincidencia, mostrar 404
        header('HTTP/1.1 404 Not Found');
        
        // Obtener datos para la página 404
        $data = isset($this->routes['/404']['handler']) 
            ? $this->routes['/404']['handler']($this->twig, $this->db, $this->session, $this->authController ?? null) 
            : ['title' => _('Página no encontrada')];
            
        // Renderizar la plantilla 404
        renderTemplate($this->routes['/404']['template'], $data);
        
        return false;
    }
} 