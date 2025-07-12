<?php
/**
 * Template Loader pour My Istymo
 * Gère le chargement des templates avec les variables de contexte
 */

if (!defined('ABSPATH')) exit;

/**
 * Charge un template avec les variables de contexte
 * 
 * @param string $template_name Nom du template (sans extension)
 * @param array $context Variables à passer au template
 * @return void
 */
function sci_load_template($template_name, $context = []) {
    $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $template_name . '.php';
    
    if (!file_exists($template_path)) {
        error_log("Template non trouvé : $template_path");
        return;
    }
    
    // Extraire les variables du contexte pour les rendre disponibles dans le template
    extract($context);
    
    // Inclure le template
    include $template_path;
}

/**
 * Récupère le contenu d'un template comme string
 * 
 * @param string $template_name Nom du template (sans extension)
 * @param array $context Variables à passer au template
 * @return string Contenu du template
 */
function sci_get_template_content($template_name, $context = []) {
    ob_start();
    sci_load_template($template_name, $context);
    return ob_get_clean();
} 