<?php
/**
 * Diagnostic du plugin DPE
 * À exécuter dans l'admin WordPress ou via une page temporaire
 */

// Vérifier que WordPress est chargé
if (!defined('ABSPATH')) {
    die('Ce fichier doit être exécuté dans WordPress');
}

echo '<h1>🔧 Diagnostic du Plugin DPE</h1>';

// 1. Vérifier que le plugin est activé
if (class_exists('DPE_Shortcodes')) {
    echo '<p style="color: green;">✅ Classe DPE_Shortcodes chargée</p>';
} else {
    echo '<p style="color: red;">❌ Classe DPE_Shortcodes non chargée</p>';
}

// 2. Vérifier les shortcodes
if (shortcode_exists('dpe_panel')) {
    echo '<p style="color: green;">✅ Shortcode [dpe_panel] enregistré</p>';
} else {
    echo '<p style="color: red;">❌ Shortcode [dpe_panel] non enregistré</p>';
}

if (shortcode_exists('dpe_test')) {
    echo '<p style="color: green;">✅ Shortcode [dpe_test] enregistré</p>';
} else {
    echo '<p style="color: red;">❌ Shortcode [dpe_test] non enregistré</p>';
}

// 3. Vérifier les fonctions nécessaires
$functions_to_check = [
    'dpe_config_manager',
    'dpe_favoris_handler', 
    'dpe_handler',
    'sci_load_template'
];

foreach ($functions_to_check as $function) {
    if (function_exists($function)) {
        echo '<p style="color: green;">✅ Fonction ' . $function . ' disponible</p>';
    } else {
        echo '<p style="color: red;">❌ Fonction ' . $function . ' manquante</p>';
    }
}

// 4. Vérifier les fichiers
$files_to_check = [
    'includes/dpe-shortcodes.php',
    'includes/dpe-config-manager.php',
    'includes/template-loader.php',
    'templates/dpe-panel-simple.php',
    'assets/css/dpe-style.css',
    'assets/css/components.css',
    'assets/js/dpe-favoris.js'
];

foreach ($files_to_check as $file) {
    $full_path = plugin_dir_path(__FILE__) . $file;
    if (file_exists($full_path)) {
        echo '<p style="color: green;">✅ Fichier ' . $file . ' existe</p>';
    } else {
        echo '<p style="color: red;">❌ Fichier ' . $file . ' manquant</p>';
    }
}

// 5. Vérifier l'utilisateur actuel
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo '<p style="color: green;">✅ Utilisateur connecté: ' . $current_user->user_login . '</p>';
    
    // Vérifier les codes postaux
    $codePostal = get_field('code_postal_user', 'user_' . $current_user->ID);
    if ($codePostal) {
        echo '<p style="color: green;">✅ Codes postaux configurés: ' . $codePostal . '</p>';
    } else {
        echo '<p style="color: orange;">⚠️ Aucun code postal configuré pour cet utilisateur</p>';
    }
} else {
    echo '<p style="color: red;">❌ Aucun utilisateur connecté</p>';
}

// 6. Test des shortcodes
echo '<h2>Test des Shortcodes</h2>';
echo '<p>Shortcode [dpe_test]: ' . do_shortcode('[dpe_test]') . '</p>';

echo '<h2>Instructions</h2>';
echo '<p>1. Testez d\'abord [dpe_test] dans une page</p>';
echo '<p>2. Si ça marche, testez [dpe_panel]</p>';
echo '<p>3. Vérifiez les logs d\'erreur WordPress pour plus de détails</p>';
?>
