<?php

namespace App\Controllers;

class DebugController
{
    /**
     * Muestra información de depuración sobre archivos de traducción
     * 
     * @param string $idioma_actual Idioma actual de la aplicación
     * @param array $dominios Dominios de traducción a verificar
     * @return void
     */
    public function mostrarDebugTraducciones($idioma_actual = null, $dominios = ['messages', 'navbar', 'about'])
    {
        if (!DEBUG_MODE) return;
        if ($idioma_actual === null) $idioma_actual = $_SESSION['idioma'] ?? 'ca';
    
        echo "<!-- DEBUG TRADUCCIONES: -->\n";
        echo "<!-- Idioma actual: $idioma_actual -->\n";
        foreach ($dominios as $dominio) {
            $ruta_po = __DIR__ . "/../../locales/{$idioma_actual}_ES/LC_MESSAGES/{$dominio}.po";
            $ruta_mo = __DIR__ . "/../../locales/{$idioma_actual}_ES/LC_MESSAGES/{$dominio}.mo";
            echo "<!-- Dominio $dominio: -->\n";
            echo "<!--   $dominio.po: " . (file_exists($ruta_po) ? "EXISTE" : "NO EXISTE") . " -->\n";
            echo "<!--   $dominio.mo: " . (file_exists($ruta_mo) ? "EXISTE" : "NO EXISTE") . " -->\n";
            if (file_exists($ruta_mo)) {
                echo "<!--   Prueba: " . dgettext($dominio, $dominio === 'navbar' ? 'Inicio' : 'Acerca de Nosotros') . " -->\n";
            }
        }
    }
}
