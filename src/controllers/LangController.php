<?php

namespace App\Controllers;

class LangController
{
    /**
     * Lista de idiomas soportados por la aplicación
     */
    private $supportedLanguages = ['es', 'en', 'ca', 'fr', 'de'];
    
    /**
     * Mapeo de códigos de idioma a locales de sistema
     */
    private $localeMap = [
        'es' => 'es_ES',
        'en' => 'en_US',
        'ca' => 'ca_ES',
        'fr' => 'fr_FR',
        'de' => 'de_DE',
    ];
    
    /**
     * Directorio donde se almacenan las traducciones
     */
    private $localeDir;
    
    /**
     * Constructor del controlador de idioma
     */
    public function __construct()
    {
        $this->localeDir = dirname(__DIR__, 2) . '/locales';
    }
    
    /**
     * Obtiene la lista de idiomas soportados
     * 
     * @return array Lista de idiomas soportados
     */
    public function getSupportedLanguages()
    {
        return $this->supportedLanguages;
    }
    
    /**
     * Verifica si un idioma está soportado
     * 
     * @param string $lang Código del idioma a verificar
     * @return bool True si el idioma está soportado, false en caso contrario
     */
    public function isLanguageSupported($lang)
    {
        return in_array($lang, $this->supportedLanguages);
    }
    
    /**
     * Inicializa el idioma de la aplicación
     * Detecta y configura el idioma actual según prioridad:
     * 1. Parámetro GET 'lang'
     * 2. Sesión
     * 3. Cookie
     * 4. Navegador
     * 5. Idioma por defecto (es)
     * 
     * @return string Código del idioma seleccionado
     */
    public function initializeLanguage()
    {
        // Detectar idioma desde la URL (para cambios de idioma)
        if (isset($_GET['lang']) && $this->isLanguageSupported($_GET['lang'])) {
            $_SESSION['idioma'] = $_GET['lang'];
        }
        
        // Detectar idioma desde la sesión, cookie, navegador o asignar uno predeterminado
        $idioma = $_SESSION['idioma'] 
            ?? $_COOKIE['idioma'] 
            ?? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es', 0, 2);
        
        if (!$this->isLanguageSupported($idioma)) {
            $idioma = 'es'; // Idioma por defecto
        }
        
        // Almacenar el idioma en sesión para futuras peticiones
        $_SESSION['idioma'] = $idioma;
        
        // Configurar gettext con el idioma seleccionado
        $this->configureLocale($idioma);
        
        return $idioma;
    }
    
    /**
     * Configura el entorno local (gettext) para el idioma seleccionado
     * 
     * @param string $lang Código del idioma a configurar
     * @return void
     */
    public function configureLocale($lang)
    {
        if (!$this->isLanguageSupported($lang)) {
            $lang = 'es'; // Idioma por defecto si el solicitado no está soportado
        }
        
        // Obtener el locale del sistema operativo correspondiente
        $locale = ($this->localeMap[$lang] ?? 'es_ES') . '.UTF-8';
        
        // Configurar gettext
        putenv("LANG=$locale");
        putenv("LANGUAGE=$locale");
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);
        
        // Asegurarse de que el directorio de traducciones existe y tiene los permisos correctos
        bindtextdomain("messages", $this->localeDir);
        bind_textdomain_codeset("messages", "UTF-8");
        textdomain("messages");
    }
    
    /**
     * Cambia el idioma de la aplicación
     * 
     * @return array Respuesta con el resultado del cambio de idioma
     */
    public function changeLanguage()
    {
        // Obtener el idioma seleccionado
        $lang = $_POST['lang'] ?? 'es';
        
        // Validar que el idioma esté entre los soportados
        if ($this->isLanguageSupported($lang)) {
            // Guardar el idioma en la sesión
            $_SESSION['idioma'] = $lang;
            
            // Guardar en cookie para recordar preferencia
            setcookie('idioma', $lang, time() + (86400 * 30), "/"); // Cookie válida por 30 días
        }
        
        // Verificar si es una solicitud AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            // Si es AJAX, devolvemos un JSON con estado de éxito
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'language' => $lang]);
            exit;
        } else {
            // Si no es AJAX, redirigir a la página anterior o principal
            $redirect = $_POST['redirect'] ?? '/';
            header('Location: ' . $redirect);
            exit;
        }
    }
    
    /**
     * Obtiene el idioma actual de la aplicación
     * 
     * @return string Código del idioma actual
     */
    public function getCurrentLanguage()
    {
        return $_SESSION['idioma'] ?? 'es';
    }
}
