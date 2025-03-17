<?php

namespace App\Controllers;

/**
 * @brief Controlador para la gestión de idiomas de la aplicación
 * 
 * @details Esta clase maneja todos los aspectos relacionados con la 
 * localización e internacionalización, incluyendo la detección de idioma,
 * configuración de locales y carga de dominios de traducción.
 */
class LangController
{
    /**
     * @brief Lista de idiomas soportados por la aplicación
     * @var array
     */
    private $supportedLanguages = ['es', 'en', 'ca'];
    
    /**
     * @brief Mapeo de códigos de idioma a locales de sistema
     * @var array
     */
    private $localeMap = [
        'es' => 'es_ES',
        'en' => 'en_US',
        'ca' => 'ca_ES'
    ];
    
    /**
     * @brief Directorio donde se almacenan las traducciones
     * @var string
     */
    private $localeDir;
    
    /**
     * @brief Constructor del controlador de idioma
     * 
     * @details Inicializa el directorio de traducciones
     */
    public function __construct()
    {
        $this->localeDir = dirname(__DIR__, 2) . '/locales';
    }
    
    /**
     * @brief Obtiene la lista de idiomas soportados
     * 
     * @return array Lista de idiomas soportados
     */
    public function getSupportedLanguages()
    {
        return $this->supportedLanguages;
    }
    
    /**
     * @brief Verifica si un idioma está soportado
     * 
     * @param string $lang Código del idioma a verificar
     * 
     * @return bool TRUE si el idioma está soportado, FALSE en caso contrario
     */
    public function isLanguageSupported($lang)
    {
        return in_array($lang, $this->supportedLanguages);
    }
    
    /**
     * @brief Inicializa el idioma de la aplicación
     * 
     * @details Detecta y configura el idioma actual según prioridad:
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
        if (isset($_GET['lang']) && $this->isLanguageSupported($_GET['lang'])) $_SESSION['idioma'] = $_GET['lang'];
        $idioma = $_SESSION['idioma'] ?? $_COOKIE['idioma'] ?? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es', 0, 2);
        
        if (!$this->isLanguageSupported($idioma)) $idioma = 'es';
        $_SESSION['idioma'] = $idioma;
        $this->configureLocale($idioma);
        return $idioma;
    }
    
    /**
     * @brief Configura el entorno local (gettext) para el idioma seleccionado
     * 
     * @param string $lang Código del idioma a configurar
     * 
     * @return void
     * 
     * @details
     * - Si el idioma no está soportado, usa español por defecto
     * - Obtiene el locale del sistema operativo correspondiente
     * - Configura las variables de entorno y locale para gettext
     * - Configura el dominio de mensajes principal
     * - Carga dominios adicionales predeterminados (navbar, about, home, profile)
     */
    public function configureLocale($lang)
    {
        if (!$this->isLanguageSupported($lang)) $lang = 'es';
        $locale = ($this->localeMap[$lang] ?? 'es_ES') . '.UTF-8';

        putenv("LANG=$locale");
        putenv("LANGUAGE=$locale");
        putenv("LC_ALL=$locale");
        setlocale(LC_ALL, $locale);
        
        bindtextdomain("messages", $this->localeDir);
        bind_textdomain_codeset("messages", "UTF-8");
        textdomain("messages");
        
        $default_domains = ['navbar', 'about', 'home', 'profile', 'stats', 'fungi'];
        foreach ($default_domains as $domain) $this->loadTextDomain($domain);
    }
    
    /**
     * @brief Carga un dominio de texto adicional para gettext
     * Verificar si el archivo de traducción existe
     * NO cambiar el dominio activo, solo registrarlo
     * Usar dgettext() para obtener traducciones de dominios específicos
     * 
     * @param string $domain Nombre del dominio de texto
     * @return bool Éxito de la operación
     */
    public function loadTextDomain($domain)
    {
        $moFile = $this->localeDir . '/' . $this->localeMap[$_SESSION['idioma'] ?? 'es'] . '/LC_MESSAGES/' . $domain . '.mo';
        
        if (!file_exists($moFile)) {
            error_log("No se encuentra el archivo de traducciones: $moFile");
            return false;
        }
        
        bindtextdomain($domain, $this->localeDir);
        bind_textdomain_codeset($domain, "UTF-8");
        
        return true;
    }
    
    /**
     * @brief Obtiene un texto traducido de un dominio específico
     * 
     * @param string $text Texto a traducir
     * @param string $domain Dominio de texto (predeterminado='messages')
     * 
     * @return string Texto traducido
     */
    public function gettext($text, $domain = 'messages')
    {
        $traduccion = dgettext($domain, $text);
        if ($traduccion === $text && $domain !== 'messages') {
            error_log("Error de traducción: '$text' no encontrado en dominio '$domain'");
            $traduccion = dgettext('messages', $text);
        }
        return $traduccion;
    }
    /**
     * @brief Cambia el idioma de la aplicación
     * 
     * @details Guarda la preferencia de idioma en sesión y cookie
     * y maneja tanto peticiones AJAX como redirecciones normales.
     * Obtiene el idioma seleccionado del POST, lo valida contra los soportados,
     * guarda en sesión y cookie por 30 días. Maneja respuestas diferentes
     * para peticiones AJAX (JSON) y normales (redirección).
     * 
     * @return array|void Respuesta JSON para AJAX o redirección para peticiones normales
     */
    public function changeLanguage()
    {
        $lang = $_POST['lang'] ?? 'es';
        if ($this->isLanguageSupported($lang)) {
            $_SESSION['idioma'] = $lang;
            setcookie('idioma', $lang, time() + (86400 * 30), "/");
        }
        
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'language' => $lang]);
            exit;
        } else {
            $redirect = $_POST['redirect'] ?? '/';
            header('Location: ' . $redirect);
            exit;
        }
    }
}
