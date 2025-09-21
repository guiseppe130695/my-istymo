<?php
/**
 * Test rapide des shortcodes DPE
 * À exécuter dans l'admin WordPress
 */

// Vérifier que WordPress est chargé
if (!defined('ABSPATH')) {
    die('Ce fichier doit être exécuté dans WordPress');
}

echo '<h1>🧪 Test des Shortcodes DPE</h1>';

// Test 1: Vérifier l'enregistrement
echo '<h2>1. Vérification de l\'enregistrement</h2>';
$shortcodes_to_test = ['dpe_test', 'dpe_panel', 'dpe_simple'];

foreach ($shortcodes_to_test as $shortcode) {
    if (shortcode_exists($shortcode)) {
        echo '<p style="color: green;">✅ Shortcode [' . $shortcode . '] enregistré</p>';
    } else {
        echo '<p style="color: red;">❌ Shortcode [' . $shortcode . '] NON enregistré</p>';
    }
}

// Test 2: Exécuter les shortcodes
echo '<h2>2. Test d\'exécution</h2>';

echo '<h3>Test [dpe_test]:</h3>';
echo do_shortcode('[dpe_test]');

echo '<h3>Test [dpe_panel]:</h3>';
echo do_shortcode('[dpe_panel]');

echo '<h3>Test [dpe_simple]:</h3>';
echo do_shortcode('[dpe_simple]');

echo '<h2>3. Instructions</h2>';
echo '<p>Si vous voyez des boîtes colorées ci-dessus, les shortcodes fonctionnent !</p>';
echo '<p>Si vous voyez du texte brut [dpe_xxx], il y a un problème.</p>';
?>
