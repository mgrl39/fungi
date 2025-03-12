<?php

namespace App\Controllers;

class LangController
{
    /**
     * Constructor del controlador de idioma
     */
    public function __construct()
    {
        // Inicialización si es necesaria
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
        $idiomasSoportados = ['es', 'en', 'ca', 'fr', 'de'];
        if (in_array($lang, $idiomasSoportados)) {
            // Guardar el idioma en la sesión
            $_SESSION['idioma'] = $lang;
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
}
