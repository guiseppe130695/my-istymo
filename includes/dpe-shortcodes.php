<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire des shortcodes DPE - Version simplifiée utilisant le template
 */
class DPE_Shortcodes {
    
    public function __construct() {
        // Enregistrer les shortcodes DPE
        add_shortcode('dpe_panel', array($this, 'dpe_panel_shortcode'));
        add_shortcode('dpe_simple', array($this, 'dpe_panel_shortcode')); // Alias pour dpe_panel
        
        // Ajouter un shortcode de test simple
        add_shortcode('dpe_test', array($this, 'dpe_test_shortcode'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'), 5);
        add_action('wp_head', array($this, 'force_enqueue_on_shortcode_pages'), 1);
        add_action('wp_footer', array($this, 'ensure_scripts_loaded'), 999);
        
        // Débogage : vérifier que les shortcodes sont bien enregistrés
        if (shortcode_exists('dpe_panel')) {
            error_log('DPE Shortcode: [dpe_panel] est bien enregistré');
        } else {
            error_log('DPE Shortcode: ERREUR - [dpe_panel] n\'est pas enregistré');
        }
        
        if (shortcode_exists('dpe_simple')) {
            error_log('DPE Shortcode: [dpe_simple] est bien enregistré');
        } else {
            error_log('DPE Shortcode: ERREUR - [dpe_simple] n\'est pas enregistré');
        }
    }
    
    /**
     * Shortcode de test simple
     */
    public function dpe_test_shortcode($atts) {
        return '<div style="background: yellow; padding: 20px; border: 2px solid red;"><h2>✅ TEST SIMPLE RÉUSSI</h2><p>Le shortcode [dpe_test] fonctionne !</p></div>';
    }
    
    /**
     * Force le chargement sur les pages avec shortcodes DPE
     */
    public function force_enqueue_on_shortcode_pages() {
        global $post;
        
        // Vérifier si on est sur une page avec les shortcodes DPE
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'dpe_panel') || has_shortcode($post->post_content, 'dpe_simple'))) {
            // Forcer le chargement immédiat
            $this->force_enqueue_assets([]);
        }
        
        // Fallback : vérifier dans le contenu global
        if (!wp_style_is('dpe-frontend-style', 'enqueued')) {
            global $wp_query;
            if (is_a($wp_query, 'WP_Query')) {
                foreach ($wp_query->posts as $post_item) {
                    if (has_shortcode($post_item->post_content, 'dpe_panel') || has_shortcode($post_item->post_content, 'dpe_simple')) {
                        $this->force_enqueue_assets([]);
                        break;
                    }
                }
            }
        }
    }
    
    /**
     * S'assurer que les scripts sont chargés en footer
     */
    public function ensure_scripts_loaded() {
        global $post;
        
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'dpe_panel') || has_shortcode($post->post_content, 'dpe_simple'))) {
            // Vérifier si les scripts sont chargés, sinon les charger
            if (!wp_style_is('dpe-frontend-style', 'done')) {
                $this->force_enqueue_assets([]);
            }
        }
        
        // Fallback : vérifier dans le contenu global
        if (!wp_style_is('dpe-frontend-style', 'enqueued')) {
            global $wp_query;
            if (is_a($wp_query, 'WP_Query')) {
                foreach ($wp_query->posts as $post_item) {
                    if (has_shortcode($post_item->post_content, 'dpe_panel') || has_shortcode($post_item->post_content, 'dpe_simple')) {
                        $this->force_enqueue_assets([]);
                        break;
                    }
                }
            }
        }
    }
    
    /**
     * Enqueue les scripts pour le frontend avec détection renforcée
     */
    public function enqueue_frontend_scripts() {
        global $post;
        
        $should_load = false;
        
        // Méthode 1 : Vérifier le post actuel
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'dpe_panel') || has_shortcode($post->post_content, 'dpe_simple'))) {
            $should_load = true;
        }
        
        // Méthode 2 : Vérifier via les paramètres GET (pour les pages dynamiques)
        if (!$should_load && (
            isset($_GET['dpe_view']) || 
            strpos($_SERVER['REQUEST_URI'] ?? '', 'dpe') !== false
        )) {
            $should_load = true;
        }
        
        // Méthode 3 : Forcer sur certaines pages spécifiques
        if (!$should_load && (
            is_page() || 
            is_single() || 
            is_front_page() ||
            is_home()
        )) {
            // Vérifier le contenu de la page actuelle
            $content = get_the_content();
            if (strpos($content, '[dpe_') !== false) {
                $should_load = true;
            }
        }
        
        // Méthode 4 : Vérifier dans le contenu global (pour les pages avec shortcodes)
        if (!$should_load) {
            global $wp_query;
            if (is_a($wp_query, 'WP_Query')) {
                foreach ($wp_query->posts as $post_item) {
                    if (has_shortcode($post_item->post_content, 'dpe_panel') || has_shortcode($post_item->post_content, 'dpe_simple')) {
                        $should_load = true;
                        break;
                    }
                }
            }
        }
        
        if ($should_load) {
            $this->force_enqueue_assets([]);
        }
    }
    
    /**
     * Force le chargement des assets
     */
    private function force_enqueue_assets($codesPostauxArray = []) {
        // Charger le CSS des composants (nécessaire pour le template)
        if (!wp_style_is('components-style', 'enqueued')) {
        wp_enqueue_style(
            'components-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/components.css',
            array(),
            '1.0.0'
        );
        }
        
        // Charger le CSS DPE
        if (!wp_style_is('dpe-frontend-style', 'enqueued')) {
            wp_enqueue_style(
                'dpe-frontend-style',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/dpe-style.css',
                array('components-style'),
                '1.0.5'
            );
        }
        
        // Charger le script favoris (le frontend JavaScript est intégré dans le template)
        if (!wp_script_is('dpe-favoris-script', 'enqueued')) {
        wp_enqueue_script(
            'dpe-favoris-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dpe-favoris.js',
            array('jquery'),
            '1.0.4',
            true
        );
        
        // Localiser le script avec les données nécessaires
        wp_localize_script('dpe-favoris-script', 'dpe_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dpe_favoris_nonce'),
            'codes_postaux' => $codesPostauxArray
        ));
        }
    }
    
    /**
     * Shortcode pour le panneau principal DPE - Version simplifiée utilisant le template
     */
    public function dpe_panel_shortcode($atts) {
        // Test simple pour vérifier que le shortcode fonctionne
        $output = '<div style="border: 2px solid red; padding: 20px; margin: 20px;">';
        $output .= '<h2>🔧 TEST SHORTCODE DPE</h2>';
        $output .= '<p>Le shortcode [dpe_panel] fonctionne !</p>';
        
        // Vérifier l'utilisateur
        if (!is_user_logged_in()) {
            $output .= '<p style="color: red;">❌ Utilisateur non connecté</p>';
            $output .= '</div>';
            return $output;
        } else {
            $output .= '<p style="color: green;">✅ Utilisateur connecté</p>';
        }
        
        // Récupérer les codes postaux de l'utilisateur
        $current_user = wp_get_current_user();
        $codePostal = get_field('code_postal_user', 'user_' . $current_user->ID);
        $codesPostauxArray = [];
        
        if ($codePostal) {
            $codePostal = str_replace(' ', '', $codePostal);
            $codesPostauxArray = explode(';', $codePostal);
            $output .= '<p style="color: green;">✅ Codes postaux trouvés: ' . implode(', ', $codesPostauxArray) . '</p>';
        } else {
            $output .= '<p style="color: orange;">⚠️ Aucun code postal configuré</p>';
        }
        
        // Forcer le chargement des assets avec les codes postaux
        $this->force_enqueue_assets($codesPostauxArray);
        
        $atts = shortcode_atts(array(
            'title' => '',
            'show_config_warnings' => 'true'
        ), $atts);
        
        // Vérifier les assets
        if (wp_style_is('dpe-frontend-style', 'enqueued')) {
            $output .= '<p style="color: green;">✅ CSS DPE chargé</p>';
        } else {
            $output .= '<p style="color: red;">❌ CSS DPE non chargé</p>';
        }
        
        if (wp_script_is('dpe-favoris-script', 'enqueued')) {
            $output .= '<p style="color: green;">✅ JS Favoris chargé</p>';
        } else {
            $output .= '<p style="color: red;">❌ JS Favoris non chargé</p>';
        }
        
        // Vérifier les fonctions nécessaires
        if (function_exists('dpe_config_manager')) {
            $output .= '<p style="color: green;">✅ dpe_config_manager disponible</p>';
                } else {
            $output .= '<p style="color: red;">❌ dpe_config_manager manquant</p>';
        }
        
        if (function_exists('sci_load_template')) {
            $output .= '<p style="color: green;">✅ sci_load_template disponible</p>';
                } else {
            $output .= '<p style="color: red;">❌ sci_load_template manquant</p>';
        }
        
        $output .= '<hr>';
        $output .= '<h3>Chargement du template DPE...</h3>';
        
        // Préparer le contexte pour le template (identique à la fonction qui fonctionne)
        $context = [
            'codesPostauxArray' => $codesPostauxArray,
            'config_manager' => function_exists('dpe_config_manager') ? dpe_config_manager() : null,
            'favoris_handler' => function_exists('dpe_favoris_handler') ? dpe_favoris_handler() : null,
            'dpe_handler' => function_exists('dpe_handler') ? dpe_handler() : null,
            'atts' => $atts
        ];
        
        // Utiliser directement le template qui fonctionne
        ob_start();
        
        if (function_exists('sci_load_template')) {
            sci_load_template('dpe-panel-simple', $context);
            $template_output = ob_get_clean();
            $output .= $template_output;
                    } else {
            $output .= '<p style="color: red;">❌ Impossible de charger le template</p>';
        }
        
        $output .= '</div>';
        return $output;
    }
}

// Initialiser la classe des shortcodes DPE
try {
new DPE_Shortcodes();
    error_log('DPE_Shortcodes: Classe initialisée avec succès');
} catch (Exception $e) {
    error_log('DPE_Shortcodes: ERREUR lors de l\'initialisation - ' . $e->getMessage());
}
?> 
