<?php
/**
 * Diagnostic urgent des shortcodes DPE
 * À exécuter directement dans le navigateur
 */

// Vérifier que WordPress est chargé
if (!defined('ABSPATH')) {
    // Essayer de charger WordPress
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once(__DIR__ . '/' . $path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Impossible de charger WordPress. Exécutez ce fichier depuis l\'admin WordPress.');
    }
}

echo '<h1>🚨 DIAGNOSTIC URGENT - Shortcodes DPE</h1>';

// 1. Vérifier que WordPress fonctionne
echo '<h2>1. État de WordPress</h2>';
echo '<p style="color: green;">✅ WordPress chargé</p>';
echo '<p>Version WordPress: ' . get_bloginfo('version') . '</p>';

// 2. Vérifier que le plugin est activé
echo '<h2>2. État du plugin</h2>';
if (function_exists('test_shortcode_simple')) {
    echo '<p style="color: green;">✅ Plugin My Istymo chargé</p>';
} else {
    echo '<p style="color: red;">❌ Plugin My Istymo non chargé</p>';
}

// 3. Vérifier les shortcodes de test
echo '<h2>3. Test des shortcodes de base</h2>';
$test_shortcodes = ['test_simple', 'test_dpe'];

foreach ($test_shortcodes as $shortcode) {
    if (shortcode_exists($shortcode)) {
        echo '<p style="color: green;">✅ Shortcode [' . $shortcode . '] enregistré</p>';
    } else {
        echo '<p style="color: red;">❌ Shortcode [' . $shortcode . '] NON enregistré</p>';
    }
}

// 4. Vérifier les shortcodes DPE
echo '<h2>4. Test des shortcodes DPE</h2>';
$dpe_shortcodes = ['dpe_test', 'dpe_panel', 'dpe_simple'];

foreach ($dpe_shortcodes as $shortcode) {
    if (shortcode_exists($shortcode)) {
        echo '<p style="color: green;">✅ Shortcode [' . $shortcode . '] enregistré</p>';
    } else {
        echo '<p style="color: red;">❌ Shortcode [' . $shortcode . '] NON enregistré</p>';
    }
}

// 5. Vérifier la classe DPE_Shortcodes
echo '<h2>5. État de la classe DPE_Shortcodes</h2>';
if (class_exists('DPE_Shortcodes')) {
    echo '<p style="color: green;">✅ Classe DPE_Shortcodes chargée</p>';
} else {
    echo '<p style="color: red;">❌ Classe DPE_Shortcodes non chargée</p>';
}

// 6. Test d'exécution des shortcodes
echo '<h2>6. Test d\'exécution</h2>';

echo '<h3>Test [test_simple]:</h3>';
echo do_shortcode('[test_simple]');

echo '<h3>Test [test_dpe]:</h3>';
echo do_shortcode('[test_dpe]');

echo '<h3>Test [dpe_test]:</h3>';
echo do_shortcode('[dpe_test]');

echo '<h3>Test [dpe_panel]:</h3>';
echo do_shortcode('[dpe_panel]');

echo '<h3>Test [dpe_simple]:</h3>';
echo do_shortcode('[dpe_simple]');

// 7. Vérifier les erreurs
echo '<h2>7. Vérification des erreurs</h2>';
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $errors = file_get_contents($error_log);
    if (strpos($errors, 'DPE') !== false) {
        echo '<p style="color: orange;">⚠️ Erreurs DPE trouvées dans les logs</p>';
        echo '<pre style="background: #f0f0f0; padding: 10px; max-height: 200px; overflow-y: scroll;">';
        echo htmlspecialchars(substr($errors, -2000)); // Derniers 2000 caractères
        echo '</pre>';
    } else {
        echo '<p style="color: green;">✅ Aucune erreur DPE dans les logs</p>';
    }
} else {
    echo '<p style="color: orange;">⚠️ Impossible de lire les logs d\'erreur</p>';
}

echo '<h2>8. Instructions</h2>';
echo '<p><strong>Si vous voyez des boîtes colorées ci-dessus :</strong> Les shortcodes fonctionnent !</p>';
echo '<p><strong>Si vous voyez du texte brut [xxx] :</strong> Il y a un problème avec ce shortcode.</p>';
echo '<p><strong>Si rien ne s\'affiche :</strong> Il y a une erreur PHP fatale.</p>';

echo '<h2>9. Actions recommandées</h2>';
echo '<ol>';
echo '<li>Testez d\'abord [test_simple] sur une page</li>';
echo '<li>Si ça marche, testez [dpe_test]</li>';
echo '<li>Si ça marche, testez [dpe_panel] ou [dpe_simple]</li>';
echo '<li>Vérifiez les logs d\'erreur WordPress</li>';
echo '</ol>';
?>
