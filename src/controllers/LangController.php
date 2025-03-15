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
        
        // Configurar dominio de mensajes principal
        bindtextdomain("messages", $this->localeDir);
        bind_textdomain_codeset("messages", "UTF-8");
        textdomain("messages");
        
        // NUEVO: Cargar dominios adicionales de forma predeterminada
        $default_domains = ['navbar', 'about', 'home'];
        foreach ($default_domains as $domain) {
            $this->loadTextDomain($domain);
        }
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
    
    /**
     * Carga un dominio de texto adicional para gettext
     * 
     * @param string $domain Nombre del dominio de texto
     * @return bool Éxito de la operación
     */
    public function loadTextDomain($domain)
    {
        // Verificar si el archivo de traducción existe
        $moFile = $this->localeDir . '/' . $this->localeMap[$_SESSION['idioma'] ?? 'es'] . '/LC_MESSAGES/' . $domain . '.mo';
        
        if (!file_exists($moFile)) {
            error_log("No se encuentra el archivo de traducciones: $moFile");
            return false;
        }
        
        bindtextdomain($domain, $this->localeDir);
        bind_textdomain_codeset($domain, "UTF-8");
        
        // AÑADIR LÍNEA CLAVE: De forma temporal, activar este dominio para verificar que funciona
        if ($domain === 'navbar' || $domain === 'about' || $domain === 'home') {
            textdomain($domain); // Esto cambia el dominio activo para gettext()
        }
        
        return true;
    }
    
    /**
     * Obtiene un texto traducido de un dominio específico
     * 
     * @param string $text Texto a traducir
     * @param string $domain Dominio de texto
     * @return string Texto traducido
     */
    public function gettext($text, $domain = 'messages')
    {
        $traduccion = dgettext($domain, $text);
        if ($traduccion === $text && $domain !== 'messages') {
            error_log("Error de traducción: '$text' no encontrado en dominio '$domain'");
            // Intenta buscar en el dominio messages como último recurso
            $traduccion = dgettext('messages', $text);
        }
        return $traduccion;
    }
}
