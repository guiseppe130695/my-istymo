<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire des shortcodes pour le plugin SCI
 */
class SCI_Shortcodes {
    
    public function __construct() {
        // Enregistrer les shortcodes existants
        add_shortcode('sci_panel', array($this, 'sci_panel_shortcode'));
        add_shortcode('sci_favoris', array($this, 'sci_favoris_shortcode'));
        add_shortcode('sci_campaigns', array($this, 'sci_campaigns_shortcode'));
        
        // ✅ NOUVEAU : Shortcodes DPE
        // ✅ SUPPRIMÉ : Shortcodes DPE déplacés vers dpe-shortcodes.php

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'), 5);
        add_action('wp_head', array($this, 'force_enqueue_on_shortcode_pages'), 1);
        add_action('wp_footer', array($this, 'ensure_scripts_loaded'), 999);
        
        // AJAX handlers existants
        add_action('wp_ajax_sci_frontend_search', array($this, 'frontend_search_ajax'));
        add_action('wp_ajax_nopriv_sci_frontend_search', array($this, 'frontend_search_ajax'));
        add_action('wp_ajax_sci_inpi_search_ajax', array($this, 'frontend_inpi_search_ajax'));
        add_action('wp_ajax_nopriv_sci_inpi_search_ajax', array($this, 'frontend_inpi_search_ajax'));
        
        // ✅ SUPPRIMÉ : Les actions AJAX DPE sont gérées dans dpe-handler.php
        // add_action('wp_ajax_dpe_search_ajax', array($this, 'dpe_search_ajax'));
        // add_action('wp_ajax_nopriv_dpe_search_ajax', array($this, 'dpe_search_ajax'));
    }
    
    /**
     * AJAX handler pour la recherche INPI avec pagination (frontend)
     */
    public function frontend_inpi_search_ajax() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sci_favoris_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $code_postal = sanitize_text_field($_POST['code_postal'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $page_size = intval($_POST['page_size'] ?? 50);
        
        if (empty($code_postal)) {
            wp_send_json_error('Code postal manquant');
            return;
        }
        
        // Valider les paramètres de pagination
        $page = max(1, $page);
        $page_size = max(1, min(100, $page_size)); // Limiter à 100 max
        
        lettre_laposte_log("=== RECHERCHE AJAX INPI FRONTEND ===");
        lettre_laposte_log("Code postal: $code_postal");
        lettre_laposte_log("Page: $page");
        lettre_laposte_log("Taille page: $page_size");
        
        // Appeler la fonction de recherche avec pagination
        $resultats = sci_fetch_inpi_data_with_pagination($code_postal, $page, $page_size);
        
        if (is_wp_error($resultats)) {
            lettre_laposte_log("Erreur recherche AJAX frontend: " . $resultats->get_error_message());
            wp_send_json_error($resultats->get_error_message());
            return;
        }
        
        if (empty($resultats['data'])) {
            lettre_laposte_log("Aucun résultat trouvé (frontend)");
            wp_send_json_error('Aucun résultat trouvé pour ce code postal');
            return;
        }
        
        // Formater les résultats
        $formatted_results = sci_format_inpi_results($resultats['data']);
        
        lettre_laposte_log("Recherche AJAX frontend réussie: " . count($formatted_results) . " résultats formatés");
        lettre_laposte_log("Pagination: " . json_encode($resultats['pagination']));
        
        wp_send_json_success([
            'results' => $formatted_results,
            'pagination' => $resultats['pagination']
        ]);
    }
    
    /**
     * Force le chargement sur les pages avec shortcodes
     */
    public function force_enqueue_on_shortcode_pages() {
        global $post;
        
        // Vérifier si on est sur une page avec un shortcode SCI ou DPE
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'sci_panel') ||
            has_shortcode($post->post_content, 'sci_favoris') ||
            has_shortcode($post->post_content, 'sci_campaigns') ||
            has_shortcode($post->post_content, 'dpe_panel') ||
            has_shortcode($post->post_content, 'dpe_favoris')
        )) {
            // Forcer le chargement immédiat
            $this->force_enqueue_assets([]);
        }
    }
    
    /**
     * S'assurer que les scripts sont chargés en footer
     */
    public function ensure_scripts_loaded() {
        global $post;
        
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'sci_panel') ||
            has_shortcode($post->post_content, 'sci_favoris') ||
            has_shortcode($post->post_content, 'sci_campaigns') ||
            has_shortcode($post->post_content, 'dpe_panel') ||
            has_shortcode($post->post_content, 'dpe_favoris')
        )) {
            // Vérifier si les scripts sont chargés, sinon les charger
            if (!wp_script_is('sci-frontend-favoris', 'done')) {
                $this->force_enqueue_assets([]);
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
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'sci_panel') ||
            has_shortcode($post->post_content, 'sci_favoris') ||
            has_shortcode($post->post_content, 'sci_campaigns') ||
            has_shortcode($post->post_content, 'dpe_panel') ||
            has_shortcode($post->post_content, 'dpe_favoris')
        )) {
            $should_load = true;
        }
        
        // Méthode 2 : Vérifier via les paramètres GET (pour les pages dynamiques)
        if (!$should_load && (
            isset($_GET['sci_view']) || 
            strpos($_SERVER['REQUEST_URI'] ?? '', 'sci') !== false ||
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
            if (strpos($content, '[sci_') !== false || strpos($content, '[dpe_') !== false) {
                $should_load = true;
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

        if (!wp_style_is('sci-frontend-style', 'enqueued')) {
            wp_enqueue_style(
                'sci-frontend-style',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/style.css',
                array(),
                '1.0.3' // Version incrémentée pour forcer le rechargement
            );
        }
        

        if (!wp_script_is('sci-frontend-favoris', 'enqueued')) {
            wp_enqueue_script(
                'sci-frontend-favoris',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/favoris.js',
                array(),
                '1.0.3',
                true
            );
        }

        // ✅ SUPPRIMÉ : Chargement DPE déplacé vers dpe-shortcodes.php
        
        if (!wp_script_is('sci-frontend-lettre', 'enqueued')) {
            wp_enqueue_script(
                'sci-frontend-lettre',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/lettre.js',
                array(),
                '1.0.3',
                true
            );
        }
        
        if (!wp_script_is('sci-frontend-payment', 'enqueued')) {
            wp_enqueue_script(
                'sci-frontend-payment',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/payment.js',
                array(),
                '1.0.3',
                true
            );
        }
        

        static $localized = false;
        if (!$localized) {

            $campaign_manager = sci_campaign_manager();
            $contacted_sirens = $campaign_manager->get_user_contacted_sirens();
            
            wp_localize_script('sci-frontend-favoris', 'sci_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sci_favoris_nonce'),
                'contacted_sirens' => $contacted_sirens
            ));
            

            $woocommerce_integration = sci_woocommerce();
            $config_manager = sci_config_manager();
            wp_localize_script('sci-frontend-payment', 'sciPaymentData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sci_campaign_nonce'),
                'unit_price' => $woocommerce_integration->get_unit_price(),
                'woocommerce_ready' => $woocommerce_integration->is_woocommerce_ready(),
                'campaigns_url' => $config_manager->get_sci_campaigns_page_url() // ✅ MODIFIÉ : Utilise l'URL stockée
            ));
            
            // ✅ Localisation pour lettre.js
            wp_localize_script('sci-frontend-lettre', 'ajaxurl', admin_url('admin-ajax.php'));
            
            // ✅ NOUVEAU : Variables pour la recherche automatique (frontend)
            wp_localize_script('sci-frontend-favoris', 'sciAutoSearch', array(
                'auto_search_enabled' => !empty($codesPostauxArray),
                'default_postal_code' => !empty($codesPostauxArray) ? $codesPostauxArray[0] : '',
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sci_search_nonce')
            ));

            // ✅ SUPPRIMÉ : Variables DPE déplacées vers dpe-shortcodes.php
            
            $localized = true;
        }
    }
    
    /**
     * Shortcode [sci_panel] - Panneau principal de recherche SCI avec pagination AJAX
     */
    public function sci_panel_shortcode($atts) {
        // ✅ Récupérer les codes postaux de l'utilisateur
        $current_user = wp_get_current_user();
        $codePostal = get_field('code_postal_user', 'user_' . $current_user->ID);
        $codesPostauxArray = [];
        
        if ($codePostal) {
            $codePostal = str_replace(' ', '', $codePostal);
            $codesPostauxArray = explode(';', $codePostal);
        }
        
        // ✅ FORCER LE CHARGEMENT DES ASSETS avec les codes postaux
        $this->force_enqueue_assets($codesPostauxArray);
        
        $atts = shortcode_atts(array(
            'title' => '',
            'show_config_warnings' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="sci-error">Vous devez être connecté pour utiliser cette fonctionnalité.</div>';
        }
        
        ob_start();
        ?>
        <div class="sci-frontend-wrapper">
            <h1><?php echo esc_html($atts['title']); ?></h1>
            
            <!-- ✅ INFORMATION POUR LES UTILISATEURS -->
            <div class="sci-info" style="background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #004085;">
                <p style="margin: 0; font-size: 16px; line-height: 1.5;">
                    💡 Prospectez directement les SCI. Vous avez également la possibilité de proposer vos services en envoyant un courrier.
                </p>
            </div>
            
            <!-- ✅ AFFICHAGE DES AVERTISSEMENTS DE CONFIGURATION -->
            <?php if ($atts['show_config_warnings'] === 'true'): ?>
                <?php
                // Vérifier si la configuration API est complète
                $config_manager = sci_config_manager();
                if (!$config_manager->is_configured()) {
                    echo '<div class="sci-error"><strong>⚠️ Configuration manquante :</strong> Veuillez configurer vos tokens API dans l\'administration.</div>';
                }

                // Vérifier la configuration INPI
                $inpi_token_manager = sci_inpi_token_manager();
                $username = get_option('sci_inpi_username');
                $password = get_option('sci_inpi_password');
                
                if (!$username || !$password) {
                    echo '<div class="sci-warning"><strong>⚠️ Identifiants INPI manquants :</strong> Veuillez configurer vos identifiants INPI pour la génération automatique de tokens.</div>';
                }

                // Vérifier WooCommerce
                $woocommerce_integration = sci_woocommerce();
                if (!$woocommerce_integration->is_woocommerce_ready()) {
                    echo '<div class="sci-warning"><strong>⚠️ WooCommerce requis :</strong> Veuillez installer et configurer WooCommerce pour utiliser le système de paiement.</div>';
                }

                // Vérifier la configuration des données expéditeur
                $campaign_manager = sci_campaign_manager();
                $expedition_data = $campaign_manager->get_user_expedition_data();
                $validation_errors = $campaign_manager->validate_expedition_data($expedition_data);
                
                if (!empty($validation_errors)) {
                    echo '<div class="sci-warning">';
                    echo '<strong>⚠️ Configuration expéditeur incomplète :</strong>';
                    echo '<ul>';
                    foreach ($validation_errors as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                ?>
            <?php endif; ?>

            <!-- ✅ FORMULAIRE DE RECHERCHE AJAX -->
            <form id="sci-search-form" class="sci-form">
                <div class="form-group-left">
                    <div class="form-group">
                        <label style="font-size:12px!important;" for="codePostal">Sélectionnez votre code postal :</label>
                        <select style="font-size:12px!important;" name="codePostal" id="codePostal" required>
                            <option style="font-size:12px!important;" value="">— Choisir un code postal —</option>
                                                    <?php foreach ($codesPostauxArray as $index => $value): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php echo ($index === 0) ? 'selected' : ''; ?>>
                                <?php echo esc_html($value); ?>
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" id="search-btn" class="sci-button" style="background: #000064 !important;">
                        🔍 Rechercher les SCI
                    </button>
                </div>

                
                <button id="send-letters-btn" type="button" class="sci-button secondary" disabled
                        data-tooltip="Prospectez directement les SCI. Vous avez également la possibilité de proposer vos services en envoyant un courrier"
                        style="font-size:12px!important; background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; border: none !important;">
                    📬 Créez une campagne d'envoi de courriers (<span id="selected-count">0</span>)
                </button>
                

                

            </form>

            <!-- ✅ ZONE DE CHARGEMENT -->
            <div id="search-loading" style="display: none;">
                <div class="loading-spinner"></div>
                <span>Recherche en cours...</span>
            </div>

                        <!-- ✅ ZONE DES RÉSULTATS - STRUCTURE STABLE -->
            <div id="search-results" style="display: none;">
                <div id="results-header">
                    <h2 id="results-title">📋 Résultats de recherche</h2>
                    <div id="pagination-info" style="display: none;"></div>
                </div>

                <!-- ✅ TABLEAU DES RÉSULTATS - STRUCTURE STABLE -->
                <table class="sci-table" id="results-table">
                    <thead>
                        <tr>
                            <th style="text-align: center !important;">Favoris</th>
                            <th>Dénomination</th>
                            <th>Dirigeant</th>
                            <th style="display: none;">SIREN</th>
                            <th>Adresse</th>
                            <th>Ville</th>
                            <th>Géolocalisation</th>
                            <th style="text-align: center !important;">Envoi courrier</th>
                            <th style="text-align: center !important;">Déjà contacté ?</th>
                        </tr>
                    </thead>
                    <tbody id="results-tbody">
                        <!-- Les résultats seront insérés ici par JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- ✅ CONTRÔLES DE PAGINATION - HORS DE LA ZONE DES RÉSULTATS -->
            <div id="pagination-controls" style="display: none; margin-top: 20px; text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                <div class="pagination-main" style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                    <button id="prev-page" disabled style="padding: 10px 20px; font-size: 10px!important; font-weight: 500; border-radius: 5px; background: #fff!important; color: #000064!important; cursor: pointer; transition: all 0.2s ease;">⬅️ Page précédente</button>
                    <span id="page-info" style="background: #0073aa; color: white; padding: 8px 15px; border-radius: 4px; font-size: 14px; font-weight: 500;">1/1</span>
                    <button id="next-page" disabled style="padding: 10px 20px; font-size: 10px!important; font-weight: 500; border-radius: 5px; background: #fff!important; color: #000064!important; cursor: pointer; transition: all 0.2s ease;">Page suivante ➡️</button>
                </div>
            </div>
            
            <!-- ✅ CACHE DES DONNÉES - ÉVITE LES RECHARGEMENTS -->
            <div id="data-cache" style="display: none;">
                <span id="cached-title"></span>
                <span id="cached-page"></span>
                <span id="cached-total"></span>
            </div>

            <!-- ✅ ZONE D'ERREUR -->
            <div id="search-error" style="display: none;" class="sci-error">
                <p id="error-message"></p>
            </div>
        </div>
        
        <!-- ✅ POPUP LETTRE -->
        <div id="letters-popup" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:10000; justify-content:center; align-items:center;">
            <div style="background:#fff; padding:25px; width:700px; max-width:95vw; max-height:95vh; overflow-y:auto; border-radius:12px;">
                <!-- Étape 1 : Liste des SCI sélectionnées -->
                <div class="step" id="step-1">
                    <h2>📋 SCI sélectionnées</h2>
                    <p style="color: #666; margin-bottom: 20px;">Vérifiez votre sélection avant de continuer</p>
                    <ul id="selected-sci-list" style="max-height:350px; overflow-y:auto; border:1px solid #ddd; padding:15px; margin-bottom:25px; border-radius:6px; background-color: #f9f9f9; list-style: none;">
                        <!-- Les SCI sélectionnées seront ajoutées ici par JavaScript -->
                    </ul>
                    <div style="text-align: center;">
                        <button id="to-step-2" class="sci-button" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; border: none !important; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px;">
                            ✍️ Rédiger le courriel →
                        </button>
                    </div>
                </div>
                
                <!-- Étape 2 : Contenu dynamique -->
                <div class="step" id="step-2" style="display:none;">
                    <!-- Le contenu sera généré par JavaScript -->
                </div>
            </div>
        </div>
        
        <!-- ✅ STYLES CSS POUR LA PAGINATION ET LE FORMULAIRE -->
        <style>
        /* Styles pour la pagination */
        #pagination-controls button:hover:not(:disabled) {
            background: #0073aa !important;
            color: white !important;
            border-color: #0073aa !important;
        }
        
        #pagination-controls button:disabled {
            background: #f0f0f0 !important;
            color: #999 !important;
            cursor: not-allowed !important;
            border-color: #ddd !important;
        }
        
        #pagination-controls button:active:not(:disabled) {
            background: #005a87 !important;
            transform: translateY(1px);
        }
        
        .pagination-main {
            margin-bottom: 10px;
        }
        
        /* Styles pour aligner le formulaire et le bouton */
        .form-group-left {
            display: flex;
            align-items: flex-end;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
            max-width: 300px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            height: 40px;
            box-sizing: border-box;
            line-height: 1.2;
        }
        
        #search-btn {
            height: 40px;
            padding: 8px 20px;
            white-space: nowrap;
            box-sizing: border-box;
            line-height: 1.2;
            align-self: flex-end;
        }
        
        /* ✅ TAILLE DE POLICE 12PX POUR TOUS LES ÉLÉMENTS DU TABLEAU */
        .sci-table,
        .sci-table th,
        .sci-table td,
        .sci-table button,
        .sci-table input,
        .sci-table a,
        .sci-table span {
            font-size: 12px !important;
        }
        
        /* ✅ TAILLE DE POLICE 12PX POUR TOUS LES ÉLÉMENTS DU POPUP */
        #letters-popup,
        #letters-popup h2,
        #letters-popup h3,
        #letters-popup h4,
        #letters-popup p,
        #letters-popup label,
        #letters-popup input,
        #letters-popup textarea,
        #letters-popup button,
        #letters-popup select,
        #letters-popup li,
        #letters-popup span,
        #letters-popup div,
        #letters-popup code,
        #letters-popup ul,
        #letters-popup ol {
            font-size: 12px !important;
        }
        
        /* ✅ EXCEPTIONS POUR LES TITRES PRINCIPAUX DU POPUP */
        #letters-popup h2 {
            font-size: 16px !important;
            font-weight: 600 !important;
        }
        
        /* ✅ NOUVEAUX STYLES POUR LES BOUTONS */
        /* Boutons standards (fond blanc, hover vert) */
        .sci-button:not(.secondary):not([id*="send-letters"]):not([id*="to-step"]):not([id*="send-campaign"]):not([id*="back-to-step"]) {
            background: white !important;
            color: #333 !important;
            border: 1px solid #ddd !important;
            transition: all 0.3s ease !important;
        }
        
        .sci-button:not(.secondary):not([id*="send-letters"]):not([id*="to-step"]):not([id*="send-campaign"]):not([id*="back-to-step"]):hover {
            background: #28a745 !important;
            color: white !important;
            border-color: #28a745 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        /* Boutons d'action (gardent le style vert) */
        .sci-button.secondary,
        #send-letters-btn,
        #to-step-2,
        #send-campaign,
        #back-to-step-1 {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
            color: white !important;
            border: none !important;
        }
        
        .sci-button.secondary:hover,
        #send-letters-btn:hover,
        #to-step-2:hover,
        #send-campaign:hover,
        #back-to-step-1:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }
        
        /* Boutons de pagination (style spécial) */
        #pagination-controls button {
            background: white !important;
            color: #333 !important;
            border: 1px solid #ddd !important;
            transition: all 0.3s ease !important;
        }
        
        #pagination-controls button:hover:not(:disabled) {
            background: #28a745 !important;
            color: white !important;
            border-color: #28a745 !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        /* ✅ STYLE POUR LE BOUTON DE PAIEMENT WOOCOMMERCE */
        .woocommerce #payment #place_order,
        .woocommerce #payment input[type="submit"],
        .woocommerce #payment button[type="submit"],
        .woocommerce #payment .button,
        .woocommerce #payment .button.alt,
        .woocommerce #payment .checkout-button,
        .woocommerce #payment .place-order .button,
        .woocommerce #payment .place-order input[type="submit"],
        .woocommerce #payment .place-order button[type="submit"] {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
            color: white !important;
            border: none !important;
            transition: all 0.3s ease !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        
        .woocommerce #payment #place_order:hover,
        .woocommerce #payment input[type="submit"]:hover,
        .woocommerce #payment button[type="submit"]:hover,
        .woocommerce #payment .button:hover,
        .woocommerce #payment .button.alt:hover,
        .woocommerce #payment .checkout-button:hover,
        .woocommerce #payment .place-order .button:hover,
        .woocommerce #payment .place-order input[type="submit"]:hover,
        .woocommerce #payment .place-order button[type="submit"]:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4) !important;
        }
        </style>
        
        <!-- ✅ SCRIPT JAVASCRIPT POUR LA PAGINATION -->
        <script>
        (function() {
            if (window.sciFrontendInitialized && window.sciFrontendInitialized === true) {
                return;
            }
            
            window.sciFrontendInitialized = true;
            
            if (!window.sciCache) {
                window.sciCache = {
                    title: '',
                    currentPage: 1,
                    totalPages: 1,
                    totalResults: 0,
                    codePostal: '',
                    pageSize: 50,
                    isSearching: false,
                    searchTimeout: null,
                    lastUpdate: 0
                };
            }
            
            const cache = window.sciCache;
            
            // ✅ NOUVEAU : Fonction pour mettre à jour le cache
            function updateCache(key, value) {
                cache[key] = value;
                cache.lastUpdate = Date.now();

            }
            
            // ✅ NOUVEAU : Fonction pour vérifier si les données ont changé
            function hasDataChanged(key, newValue) {
                return cache[key] !== newValue;
            }
            
            // ✅ NOUVEAU : Fonction pour forcer la mise à jour de la pagination
            function forceUpdatePagination() {
                const elements = getElements();
                if (elements && elements.pageInfo) {
                    const newPageText = `${cache.currentPage}/${cache.totalPages}`;
                    elements.pageInfo.textContent = newPageText;
                }
            }
            

            
            // ✅ AMÉLIORÉ : Fonction pour obtenir les paramètres de pagination
            function getCurrentPaginationParams() {
                return { 
                    page: cache.currentPage, 
                    codePostal: cache.codePostal 
                };
            }
            
            // ✅ NOUVEAU : Fonction pour mettre à jour le contenu du tableau de manière optimisée
            function updateTableContent(results) {
                const elements = getElements();
                if (!elements) return;
                
                const currentRowCount = elements.resultsTbody.children.length;
                const newRowCount = results.length;
                
                if (currentRowCount > 0) {
                    elements.resultsTbody.innerHTML = '';
                }
                
                results.forEach((result, index) => {
                    const row = createResultRow(result, index);
                    elements.resultsTbody.appendChild(row);
                });
                

            }
            
            // ✅ SUPPRIMÉ : Cette fonction n'est plus nécessaire car le cache est mis à jour dans displayResults
            function getElements() {
                const elements = {
                    searchForm: document.getElementById('sci-search-form'),
                    codePostalSelect: document.getElementById('codePostal'),
                    searchBtn: document.getElementById('search-btn'),
                    searchLoading: document.getElementById('search-loading'),
                    searchResults: document.getElementById('search-results'),
                    searchError: document.getElementById('search-error'),
                    resultsTitle: document.getElementById('results-title'),
                    paginationInfo: document.getElementById('pagination-info'),
                    resultsTbody: document.getElementById('results-tbody'),
                    prevPageBtn: document.getElementById('prev-page'),
                    nextPageBtn: document.getElementById('next-page'),
                    pageInfo: document.getElementById('page-info')
                };
                
                const criticalElements = ['searchForm', 'codePostalSelect', 'searchBtn', 'searchLoading', 'searchResults', 'searchError', 'resultsTbody'];
                const missingCriticalElements = criticalElements.filter(name => !elements[name]);
                
                if (missingCriticalElements.length > 0) {
                    return null;
                }
                
                return elements;
            }
            function performSearch(codePostal, page = 1, pageSize = 50) {
                const elements = getElements();
                if (!elements) return;
                if (cache.isSearching) {
                    return;
                }
                if (cache.searchTimeout) {
                    clearTimeout(cache.searchTimeout);
                    cache.searchTimeout = null;
                }
                cache.isSearching = true;
                
                if (!codePostal) {
                    displayError('Code postal manquant pour la recherche');
                    cache.isSearching = false;
                    return;
                }
                
                // ✅ MODIFIÉ : Ne mettre à jour que le code postal et la taille de page
                // La page actuelle sera mise à jour dans displayResults après réception des données
                updateCache('codePostal', codePostal);
                updateCache('pageSize', pageSize);
                
                console.log('🚀 Lancement recherche - Page demandée:', page, 'Code postal:', codePostal);
                elements.searchLoading.style.display = 'block';
                elements.searchResults.style.display = 'none';
                elements.searchError.style.display = 'none';
                
                const paginationControls = document.getElementById('pagination-controls');
                if (paginationControls) {
                    paginationControls.style.display = 'block';
                }
                elements.searchBtn.disabled = true;
                elements.searchBtn.textContent = '🔄 Recherche...';
                const formData = new FormData();
                formData.append('action', 'sci_inpi_search_ajax');
                formData.append('code_postal', codePostal);
                formData.append('page', page);
                formData.append('page_size', pageSize);
                formData.append('nonce', sci_ajax.nonce);
                const timeoutPromise = new Promise((_, reject) => {
                    cache.searchTimeout = setTimeout(() => {
                        reject(new Error('Timeout de la requête (30s)'));
                    }, 30000);
                });
                Promise.race([
                    fetch(sci_ajax.ajax_url, {
                        method: 'POST',
                        body: formData
                    }),
                    timeoutPromise
                ])
                .then(response => {
                    if (cache.searchTimeout) clearTimeout(cache.searchTimeout);
                    return response.json();
                })
                .then(data => {
                    if (cache.searchTimeout) clearTimeout(cache.searchTimeout);
                    cache.isSearching = false;
                    elements.searchLoading.style.display = 'none';
                    elements.searchBtn.disabled = false;
                    elements.searchBtn.textContent = '🔍 Rechercher les SCI';
                    if (data.success) {
                        displayResults(data.data);
                    } else {
                        displayError(data.data || 'Erreur lors de la recherche');
                    }
                })
                .catch(error => {
                    if (cache.searchTimeout) clearTimeout(cache.searchTimeout);
                    cache.isSearching = false;
                    elements.searchLoading.style.display = 'none';
                    elements.searchBtn.disabled = false;
                    elements.searchBtn.textContent = '🔍 Rechercher les SCI';
                    displayError('Erreur réseau lors de la recherche: ' + error.message);
                });
            }
            function displayResults(data) {
                const elements = getElements();
                if (!elements) return;
                const { results, pagination } = data;
                
                console.log('📊 Données de pagination reçues:', pagination);
                console.log('📄 Page actuelle dans les données:', pagination.current_page);
                console.log('📄 Total pages dans les données:', pagination.total_pages);
                
                // ✅ VALIDATION : Vérifier que les données de pagination sont valides
                if (!pagination || typeof pagination.current_page === 'undefined' || typeof pagination.total_pages === 'undefined') {
                    console.error('❌ Données de pagination invalides:', pagination);
                    displayError('Erreur: données de pagination manquantes');
                    return;
                }
                
                // ✅ MODIFIÉ : Récupérer le code postal actuel depuis le select
                const currentCodePostal = elements.codePostalSelect ? elements.codePostalSelect.value : '';
                
                // ✅ NOUVEAU : Mettre à jour le cache avec les nouvelles données
                const newTitle = `📋 Résultats de recherche (${pagination.total_count} SCI trouvées)`;
                const newPage = parseInt(pagination.current_page) || 1;
                const newTotalPages = parseInt(pagination.total_pages) || 1;
                
                console.log('💾 Mise à jour cache - Ancienne page:', cache.currentPage, '→ Nouvelle page:', newPage);
                console.log('💾 Mise à jour cache - Ancien total:', cache.totalPages, '→ Nouveau total:', newTotalPages);
                
                const titleChanged = hasDataChanged('title', newTitle);
                const pageChanged = hasDataChanged('currentPage', newPage);
                const totalPagesChanged = hasDataChanged('totalPages', newTotalPages);
                
                // ✅ NOUVEAU : Mettre à jour le cache seulement si nécessaire
                if (titleChanged) updateCache('title', newTitle);
                if (pageChanged) updateCache('currentPage', newPage);
                if (totalPagesChanged) updateCache('totalPages', newTotalPages);
                updateCache('totalResults', pagination.total_count);
                updateCache('codePostal', currentCodePostal);
                
                console.log('✅ Cache mis à jour:', {
                    currentPage: cache.currentPage,
                    totalPages: cache.totalPages,
                    totalResults: cache.totalResults,
                    codePostal: cache.codePostal
                });
                
                // ✅ AMÉLIORÉ : Afficher la zone de résultats seulement si cachée
                if (elements.searchResults.style.display === 'none') {
                    elements.searchResults.style.display = 'block';
                }
                elements.searchError.style.display = 'none';
                
                if (titleChanged) {
                    elements.resultsTitle.textContent = newTitle;
                }
                
                // ✅ NOUVEAU : Afficher les contrôles de pagination seulement si nécessaire
                const paginationControls = document.getElementById('pagination-controls');
                if (paginationControls && paginationControls.style.display === 'none') {
                    paginationControls.style.display = 'block';
                }
                
                // ✅ AMÉLIORÉ : Mettre à jour le contenu du tableau seulement
                updateTableContent(results);
                
                if (pageChanged || totalPagesChanged || cache.lastUpdate === 0) {
                    updatePaginationControls();
                }
                
                setTimeout(() => {
                    forceUpdatePagination();
                }, 100);
                
                reinitializeJavaScriptFeatures();
            }
            function createResultRow(result, index) {
                const row = document.createElement('tr');
                row.className = 'result-row';
                const mapsQuery = encodeURIComponent(`${result.adresse} ${result.code_postal} ${result.ville}`);
                const mapsUrl = `https://www.google.com/maps/place/${mapsQuery}`;
                row.innerHTML = `
                    <td style="text-align: center !important;">
                        <button class="fav-btn" 
                                data-siren="${escapeHtml(result.siren)}"
                                data-denomination="${escapeHtml(result.denomination)}"
                                data-dirigeant="${escapeHtml(result.dirigeant)}"
                                data-adresse="${escapeHtml(result.adresse)}"
                                data-ville="${escapeHtml(result.ville)}"
                                data-code-postal="${escapeHtml(result.code_postal)}"
                                aria-label="Ajouter aux favoris"
                                style="font-size: 1.5rem; background: none; border: none; cursor: pointer; color: #ccc; transition: color 0.3s;">☆</button>
                    </td>
                    <td>${escapeHtml(result.denomination)}</td>
                    <td>${escapeHtml(result.dirigeant)}</td>
                    <td style="display: none;">${escapeHtml(result.siren)}</td>
                    <td>${escapeHtml(result.adresse)}</td>
                    <td>${escapeHtml(result.ville)}</td>
                    <td style="color: #0064A6 !important; text-align: center !important;">
                        <a href="${mapsUrl}" 
                           target="_blank" 
                           class="maps-link"
                           title="Localiser ${escapeHtml(result.denomination)} sur Google Maps" style="font-size: 14px !important;">
                            Localiser
                        </a>
                    </td>
                    <td style="text-align: center !important;">
                        <input type="checkbox" class="send-letter-checkbox"
                            data-denomination="${escapeHtml(result.denomination)}"
                            data-dirigeant="${escapeHtml(result.dirigeant)}"
                            data-siren="${escapeHtml(result.siren)}"
                            data-adresse="${escapeHtml(result.adresse)}"
                            data-ville="${escapeHtml(result.ville)}"
                            data-code-postal="${escapeHtml(result.code_postal)}"
                        />
                    </td>
                    <td style="text-align: center !important;">
                        <span class="contact-status" data-siren="${escapeHtml(result.siren)}" style="display: none;">
                            <span class="contact-status-icon"></span>
                            <span class="contact-status-text"></span>
                        </span>
                    </td>
                `;
                return row;
            }
            function updatePaginationControls() {
                console.log('🔧 Mise à jour pagination shortcode - Page:', cache.currentPage, 'Total:', cache.totalPages);
                
                const elements = getElements();
                if (!elements || !elements.pageInfo) {
                    console.log('⚠️ Éléments pagination non trouvés dans updatePaginationControls');
                    return;
                }
                
                const newPageText = `${cache.currentPage}/${cache.totalPages}`;
                const currentPageText = elements.pageInfo.textContent;
                
                if (currentPageText !== newPageText) {
                    elements.pageInfo.textContent = newPageText;
                    console.log('📄 Info page mise à jour:', newPageText);
                }
                
                const prevShouldBeDisabled = cache.currentPage <= 1;
                const nextShouldBeDisabled = cache.currentPage >= cache.totalPages;
                
                if (elements.prevPageBtn.disabled !== prevShouldBeDisabled) {
                    elements.prevPageBtn.disabled = prevShouldBeDisabled;
                    console.log('⬅️ Bouton précédent disabled:', prevShouldBeDisabled);
                }
                if (elements.nextPageBtn.disabled !== nextShouldBeDisabled) {
                    elements.nextPageBtn.disabled = nextShouldBeDisabled;
                    console.log('➡️ Bouton suivant disabled:', nextShouldBeDisabled);
                }
                
                const paginationControls = document.getElementById('pagination-controls');
                if (paginationControls && paginationControls.style.display === 'none') {
                    paginationControls.style.display = 'block';
                    console.log('👁️ Contrôles de pagination affichés');
                }
            }
            function reinitializeJavaScriptFeatures() {
                setTimeout(() => {
                    if (typeof window.attachFavorisListeners === 'function') {
                        window.attachFavorisListeners();
                    }
                    
                    // ✅ NOUVEAU : Mettre à jour l'affichage des boutons favoris
                    if (typeof window.forceUpdateFavoris === 'function') {
                        window.forceUpdateFavoris();
                    } else if (typeof window.updateFavButtons === 'function') {
                        window.updateFavButtons();
                    }
                    
                    if (typeof window.updateContactStatus === 'function') {
                        window.updateContactStatus();
                    }
                    
                    // Restaurer les sélections SCI sur la nouvelle page
                    if (typeof window.restoreSCISelections === 'function') {
                        window.restoreSCISelections();
                    }
                    
                    // Forcer la mise à jour de l'UI
                    if (typeof window.updateSCISelectionUI === 'function') {
                        setTimeout(() => {
                            window.updateSCISelectionUI();
                        }, 200);
                    }
                }, 100);
            }
            function displayError(message) {
                const elements = getElements();
                if (!elements) return;
                elements.searchResults.style.display = 'none';
                elements.searchError.style.display = 'block';
                elements.searchError.querySelector('#error-message').textContent = message;
            }
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }
            function initialize() {
                const elements = getElements();
                if (!elements) {
                    return;
                }
                
                function autoLoadFirstCodePostal() {
                    console.log('🚀 Début autoLoadFirstCodePostal (frontend)');
                    console.log('🔍 sciAutoSearch disponible:', typeof sciAutoSearch !== 'undefined');
                    
                    if (typeof sciAutoSearch !== 'undefined') {
                        console.log('🔍 sciAutoSearch.auto_search_enabled:', sciAutoSearch.auto_search_enabled);
                        console.log('🔍 sciAutoSearch.default_postal_code:', sciAutoSearch.default_postal_code);
                    }
                    
                    // ✅ AMÉLIORÉ : Vérifier si la recherche automatique est activée
                    if (typeof sciAutoSearch !== 'undefined' && sciAutoSearch.auto_search_enabled && sciAutoSearch.default_postal_code) {
                        // Utiliser le premier code postal de l'utilisateur
                        const defaultCodePostal = sciAutoSearch.default_postal_code;
                        
                        // S'assurer que le premier code postal est sélectionné
                        elements.codePostalSelect.value = defaultCodePostal;
                        
                        console.log('🔄 Chargement automatique du premier code postal (frontend):', defaultCodePostal);
                        console.log('🔍 elements.codePostalSelect.value après sélection:', elements.codePostalSelect.value);
                        
                        // Lancer automatiquement la recherche
                        console.log('🚀 Lancement de performSearch (frontend) avec:', defaultCodePostal, 1, cache.pageSize);
                        performSearch(defaultCodePostal, 1, cache.pageSize);
                    } else if (elements.codePostalSelect.options.length > 1) {
                        // Fallback : sélectionner automatiquement le premier code postal disponible
                        elements.codePostalSelect.selectedIndex = 1;
                        const firstCodePostal = elements.codePostalSelect.value;
                        
                        console.log('🔄 Chargement automatique du premier code postal disponible (frontend):', firstCodePostal);
                        
                        // Lancer automatiquement la recherche
                        performSearch(firstCodePostal, 1, cache.pageSize);
                    } else {
                        console.log('⚠️ Aucun code postal configuré pour le chargement automatique (frontend)');
                        console.log('🔍 elements.codePostalSelect.options.length:', elements.codePostalSelect.options.length);
                    }
                }
                
                autoLoadFirstCodePostal();
                
                // Mettre à jour l'UI au chargement initial
                setTimeout(() => {
                    if (typeof window.updateSCISelectionUI === 'function') {
                        window.updateSCISelectionUI();
                    }
                }, 500);
                
                elements.searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const codePostal = elements.codePostalSelect.value;
                    if (!codePostal) {
                        alert('Veuillez sélectionner un code postal');
                        return;
                    }
                    performSearch(codePostal, 1, cache.pageSize);
                });
                

                
                // ✅ AMÉLIORÉ : Vérifier que les boutons de pagination existent
                console.log('🔍 Éléments pagination shortcode trouvés:');
                console.log('- Bouton précédent:', elements.prevPageBtn ? '✅' : '❌');
                console.log('- Bouton suivant:', elements.nextPageBtn ? '✅' : '❌');
                console.log('- Info page:', elements.pageInfo ? '✅' : '❌');
                
                if (elements.prevPageBtn) {
                    elements.prevPageBtn.addEventListener('click', function() {
                        console.log('🔄 Clic bouton précédent (shortcode)');
                        console.log('📊 État du cache:', {
                            currentPage: cache.currentPage,
                            totalPages: cache.totalPages,
                            codePostal: cache.codePostal,
                            pageSize: cache.pageSize
                        });
                        console.log('🔍 Bouton désactivé?', elements.prevPageBtn.disabled);
                        
                        const codePostal = elements.codePostalSelect ? elements.codePostalSelect.value : cache.codePostal;
                        const prevPage = cache.currentPage - 1;
                        
                        console.log('🧮 Calcul: Page actuelle', cache.currentPage, '- 1 =', prevPage);
                        console.log('✅ Condition prevPage >= 1:', prevPage >= 1);
                        
                        if (prevPage >= 1) {
                            console.log('✅ Navigation vers page:', prevPage, 'avec code postal:', codePostal);
                            performSearch(codePostal, prevPage, cache.pageSize);
                        } else {
                            console.log('⚠️ Déjà sur la première page - navigation bloquée');
                        }
                    });
                }
                
                if (elements.nextPageBtn) {
                    elements.nextPageBtn.addEventListener('click', function() {
                        console.log('🔄 Clic bouton suivant (shortcode) - Page actuelle:', cache.currentPage, 'Total pages:', cache.totalPages);
                        const codePostal = elements.codePostalSelect ? elements.codePostalSelect.value : cache.codePostal;
                        const nextPage = cache.currentPage + 1;
                        
                        if (nextPage <= cache.totalPages) {
                            console.log('✅ Navigation vers page:', nextPage);
                            performSearch(codePostal, nextPage, cache.pageSize);
                        } else {
                            console.log('⚠️ Déjà sur la dernière page');
                        }
                    });
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialize);
            } else {
                setTimeout(initialize, 0);
            }
            
            // ✅ SUPPRIMÉ : Fonctions de sélections exposées (gérées par lettre.js)
            
            // ✅ NOUVEAU : Exposer les fonctions de débogage (optionnel)
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                window.forceUpdatePagination = forceUpdatePagination;
                window.sciCache = cache;
            }
            
            window.sciFrontendInitialized = true;
        })();
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode [sci_favoris] - Liste des favoris
     */
    public function sci_favoris_shortcode($atts) {
        // ✅ FORCER LE CHARGEMENT DES ASSETS
        $this->force_enqueue_assets([]);
        
        $atts = shortcode_atts(array(
            'title' => '',
            'show_empty_message' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="sci-error">Vous devez être connecté pour voir vos favoris.</div>';
        }
        
        global $sci_favoris_handler;
        $favoris = $sci_favoris_handler->get_favoris();
        
        ob_start();
        ?>
        <div class="sci-frontend-wrapper">
            <h1><?php echo esc_html($atts['title']); ?></h1>
            
            <?php if (empty($favoris) && $atts['show_empty_message'] === 'true'): ?>
                <div class="sci-info">
                    <p>Aucun favori pour le moment. Ajoutez des SCI à vos favoris depuis la recherche pour les retrouver ici facilement.</p>
                </div>
            <?php else: ?>
                <table class="sci-table" id="table-favoris">
                    <thead>
                        <tr>
                            <th>Dénomination</th>
                            <th>Dirigeant</th>
                            <th>SIREN</th>
                            <th>Adresse</th>
                            <th>Ville</th>
                            <th>Géolocalisation</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($favoris as $fav): ?>
                            <tr>
                                <td><?php echo esc_html($fav['denomination']); ?></td>
                                <td><?php echo esc_html($fav['dirigeant']); ?></td>
                                <td><?php echo esc_html($fav['siren']); ?></td>
                                <td><?php echo esc_html($fav['adresse']); ?></td>
                                <td><?php echo esc_html($fav['ville']); ?></td>
                                <td>
                                    <?php 
                                    $maps_query = urlencode($fav['adresse'] . ' ' . $fav['code_postal'] . ' ' . $fav['ville']);
                                    $maps_url = 'https://www.google.com/maps/place/' . $maps_query;
                                    ?>
                                    <a href="<?php echo esc_url($maps_url); ?>" 
                                       target="_blank" 
                                       class="maps-link"
                                       title="Localiser <?php echo esc_attr($fav['denomination']); ?> sur Google Maps">
                                        Localiser SCI
                                    </a>
                                </td>
                                <td>
                                    <button class="remove-fav-btn sci-button" 
                                            data-siren="<?php echo esc_attr($fav['siren']); ?>"
                                            style="    box-shadow: none !important; background: #fff!important; color:#000064!important; font-size: 12px; padding: 6px 12px;">
                                        ❌
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <script>
        // Vérifier que les variables AJAX sont disponibles
        if (typeof sci_ajax === 'undefined') {
            window.sci_ajax = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('sci_favoris_nonce'); ?>'
            };
        }
        
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.remove-fav-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer ce favori ?')) {
                        return;
                    }

                    const siren = btn.getAttribute('data-siren');
                    const formData = new FormData();
                    formData.append('action', 'sci_manage_favoris');
                    formData.append('operation', 'remove');
                    formData.append('nonce', sci_ajax.nonce);
                    formData.append('sci_data', JSON.stringify({siren: siren}));

                    fetch(sci_ajax.ajax_url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Erreur lors de la suppression : ' + data.data);
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Erreur réseau');
                    });
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode [sci_campaigns] - Liste des campagnes
     */
    public function sci_campaigns_shortcode($atts) {
        // ✅ FORCER LE CHARGEMENT DES ASSETS
        $this->force_enqueue_assets([]);
        
        $atts = shortcode_atts(array(
            'title' => '',
            'show_empty_message' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="sci-error">Vous devez être connecté pour voir vos campagnes.</div>';
        }
        
        $campaign_manager = sci_campaign_manager();
        $config_manager = sci_config_manager();
        
        // Gestion de l'affichage des détails d'une campagne
        if (isset($_GET['sci_view']) && $_GET['sci_view'] === 'campaign' && isset($_GET['id']) && is_numeric($_GET['id'])) {
            $campaign_details = $campaign_manager->get_campaign_details(intval($_GET['id']));
            if ($campaign_details) {
                return $this->display_campaign_details_frontend($campaign_details, $atts);
            }
        }
        
        $campaigns = $campaign_manager->get_user_campaigns();
        
        ob_start();
        ?>
        <div class="sci-frontend-wrapper">
            <h1><?php echo esc_html($atts['title']); ?></h1>
            
            <?php if (empty($campaigns) && $atts['show_empty_message'] === 'true'): ?>
                <div class="sci-info">
                    <p>Aucune campagne trouvée. Créez votre première campagne depuis la recherche SCI.</p>
                </div>
            <?php else: ?>
                <table class="sci-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Statut</th>
                            <th>Total</th>
                            <th>Envoyées</th>
                            <th>Erreurs</th>
                            <th>Date création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><strong><?php echo esc_html($campaign['title']); ?></strong></td>
                                <td>
                                    <?php
                                    $status_labels = [
                                        'draft' => '📝 Brouillon',
                                        'processing' => '⏳ En cours',
                                        'completed' => '✅ Terminée',
                                        'completed_with_errors' => '⚠️ Terminée avec erreurs'
                                    ];
                                    echo $status_labels[$campaign['status']] ?? $campaign['status'];
                                    ?>
                                </td>
                                <td><?php echo intval($campaign['total_letters']); ?></td>
                                <td><?php echo intval($campaign['sent_letters']); ?></td>
                                <td><?php echo intval($campaign['failed_letters']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($campaign['created_at'])); ?></td>
                                <td>
                                    <a href="<?php echo add_query_arg(array('sci_view' => 'campaign', 'id' => $campaign['id'])); ?>" 
                                       class="sci-button" style="font-size: 12px; padding: 6px 12px;">
                                        👁️ Voir détails
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Affichage des détails d'une campagne pour le frontend
     */
    private function display_campaign_details_frontend($campaign, $atts) {
        $config_manager = sci_config_manager();
        
        ob_start();
        ?>
        <div class="sci-frontend-wrapper">
           
            <a href="<?php echo $config_manager->get_sci_campaigns_page_url(); ?>" class="sci-button" style="background:#FFF!important; color:#000064!important; box-shadow:none!important; font-size:14px!important;">
                ← Retour aux campagnes
            </a>
            
            <h4>Détails : <?php echo esc_html($campaign['title']); ?></h4>
            <br><br>
            <div style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 6px; border: 1px solid #ddd;">
                <h3>📊 Résumé</h3>
                <p><strong>Statut :</strong> 
                    <?php
                    $status_labels = [
                        'draft' => '📝 Brouillon',
                        'processing' => '⏳ En cours',
                        'completed' => '✅ Terminée',
                        'completed_with_errors' => '⚠️ Terminée avec erreurs'
                    ];
                    echo $status_labels[$campaign['status']] ?? $campaign['status'];
                    ?>
                </p>
                <p><strong>Total lettres :</strong> <?php echo intval($campaign['total_letters']); ?></p>
                <p><strong>Envoyées :</strong> <?php echo intval($campaign['sent_letters']); ?></p>
                <p><strong>Erreurs :</strong> <?php echo intval($campaign['failed_letters']); ?></p>
                <p><strong>Date création :</strong> <?php echo date('d/m/Y H:i:s', strtotime($campaign['created_at'])); ?></p>
                
                <h4>📝 Contenu de la lettre :</h4>
                <div style="background: white; padding: 15px; border-left: 4px solid #0073aa; border-radius: 4px;">
                    <?php echo nl2br(esc_html($campaign['content'])); ?>
                </div>
            </div>
            <br><br>
            
            <h3>📋 Détail des envois</h3>
            <table class="sci-table">
                <thead>
                    <tr>
                        <th>SCI</th>
                        <th>Dirigeant</th>
                        <th>SIREN</th>
                        <th>Adresse</th>
                        <th>Statut</th>
                        <th>UID La Poste</th>
                        <th>Date envoi</th>
                        <th>Erreur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaign['letters'] as $letter): ?>
                        <tr>
                            <td><?php echo esc_html($letter['sci_denomination']); ?></td>
                            <td><?php echo esc_html($letter['sci_dirigeant']); ?></td>
                            <td><?php echo esc_html($letter['sci_siren']); ?></td>
                            <td><?php echo esc_html($letter['sci_adresse'] . ', ' . $letter['sci_code_postal'] . ' ' . $letter['sci_ville']); ?></td>
                            <td>
                                <?php
                                $status_icons = [
                                    'pending' => '⏳ En attente',
                                    'sent' => '✅ Envoyée',
                                    'failed' => '❌ Erreur'
                                ];
                                echo $status_icons[$letter['status']] ?? $letter['status'];
                                ?>
                            </td>
                            <td>
                                <?php if ($letter['laposte_uid']): ?>
                                    <code style="background: #f1f1f1; padding: 2px 4px; border-radius: 3px; font-size: 12px;">
                                        <?php echo esc_html($letter['laposte_uid']); ?>
                                    </code>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $letter['sent_at'] ? date('d/m/Y H:i', strtotime($letter['sent_at'])) : '-'; ?>
                            </td>
                            <td>
                                <?php if ($letter['error_message']): ?>
                                    <span style="color: #dc3545; font-size: 12px;">
                                        <?php echo esc_html($letter['error_message']); ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler pour la recherche frontend
     */
    public function frontend_search_ajax() {
        // Même logique que l'admin mais pour le frontend
        // Cette fonction peut être utilisée pour des recherches AJAX si nécessaire
        wp_send_json_error('Non implémenté');
    }
    
    /**
     * ✅ SUPPRIMÉ : Shortcodes DPE déplacés vers dpe-shortcodes.php
     * Les shortcodes DPE sont maintenant gérés dans un fichier séparé pour éviter les conflits
     */
    
    /**
     * ✅ SUPPRIMÉ : AJAX handler pour la recherche DPE (géré dans dpe-handler.php)
     */
    // public function dpe_search_ajax() {
    //     // Cette fonction a été supprimée car elle est dupliquée dans dpe-handler.php
    // }
}

// Initialiser les shortcodes
new SCI_Shortcodes();