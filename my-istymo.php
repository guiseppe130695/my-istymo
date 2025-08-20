<?php
/*
Plugin Name: My Istymo
Description: Plugin personnalisé SCI avec un panneau admin et un sélecteur de codes postaux.
Version: 1.6
Author: Brio Guiseppe
*/

if (!defined('ABSPATH')) exit; // Sécurité : Empêche l'accès direct au fichier

include plugin_dir_path(__FILE__) . 'popup-lettre.php';

// ✅ NOUVEAU : Fonction utilitaire pour récupérer les codes postaux de l'utilisateur
function sci_get_user_postal_codes($user_id = null) {
    if (!$user_id) {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
    }
    
    $codesPostauxArray = [];
    
    // Essayer d'abord avec ACF si disponible
    if (function_exists('get_field')) {
        $codePostal = get_field('code_postal_user', 'user_' . $user_id);
        if ($codePostal) {
            $codePostal = str_replace(' ', '', $codePostal);
            $codesPostauxArray = explode(';', $codePostal);
        }
    }
    
    // Si aucun code postal trouvé avec ACF, essayer avec les meta utilisateur WordPress
    if (empty($codesPostauxArray)) {
        $codePostal = get_user_meta($user_id, 'code_postal_user', true);
        if ($codePostal) {
            $codePostal = str_replace(' ', '', $codePostal);
            $codesPostauxArray = explode(';', $codePostal);
        }
    }
    
    return $codesPostauxArray;
}

// ✅ NOUVEAU : Fonction de log universelle pour tout le plugin
function my_istymo_log($message, $context = 'general') {
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/my-istymo-logs/';
    $log_file = $log_dir . $context . '-logs.txt';
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }
    
    // Formater le message avec timestamp
    $timestamp = current_time('Y-m-d H:i:s');
    $log_entry = "[$timestamp][$context] $message" . PHP_EOL;
    
    // Écrire dans le fichier de log
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// ✅ ALIAS pour compatibilité avec le code existant
if (!function_exists('lettre_laposte_log')) {
    function lettre_laposte_log($message) {
        my_istymo_log($message, 'laposte');
    }
}
require_once plugin_dir_path(__FILE__) . 'lib/tcpdf/tcpdf.php';
require_once plugin_dir_path(__FILE__) . 'includes/config-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/campaign-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/woocommerce-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/inpi-token-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/template-loader.php';

// ✅ NOUVEAU : Inclure les fichiers DPE
require_once plugin_dir_path(__FILE__) . 'includes/dpe-config-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpe-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpe-shortcodes.php';

// ✅ PHASE 1 : Système unifié de gestion des leads (AVANT les favoris)
require_once plugin_dir_path(__FILE__) . 'includes/unified-leads-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/lead-status-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/unified-leads-migration.php';
require_once plugin_dir_path(__FILE__) . 'includes/unified-leads-test.php';

// ✅ PHASE 3 : Système d'actions et workflow
require_once plugin_dir_path(__FILE__) . 'includes/lead-actions-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/lead-workflow.php';

// ✅ APRÈS le système unifié : Inclure les gestionnaires de favoris
require_once plugin_dir_path(__FILE__) . 'includes/favoris-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpe-favoris-handler.php';

// --- Ajout du menu SCI dans l'admin WordPress ---
add_action('admin_menu', 'sci_ajouter_menu');

function sci_ajouter_menu() {
    add_menu_page(
        'SCI',
        'SCI',
        'read',
        'sci-panel',
        'sci_afficher_panel',
        'dashicons-admin-home',
        6
    );

    add_submenu_page(
        'sci-panel',
        'Favoris',
        'Mes Favoris',
        'read',
        'sci-favoris',
        'sci_favoris_page'
    );

    // Ajouter une page pour les campagnes
    add_submenu_page(
        'sci-panel',
        'Campagnes',
        'Mes Campagnes',
        'read',
        'sci-campaigns',
        'sci_campaigns_page'
    );

    // Ajouter une page pour voir les logs d'API
    add_submenu_page(
        'sci-panel',
        'Logs API',
        'Logs API',
        'manage_options',
        'sci-logs',
        'sci_logs_page'
    );
    
    // ✅ NOUVEAU : Menu DPE
    add_menu_page(
        'DPE',
        'DPE',
        'read',
        'dpe-panel',
        'dpe_afficher_panel',
        'dashicons-admin-home',
        7
    );

    add_submenu_page(
        'dpe-panel',
        'Favoris DPE',
        'Mes Favoris DPE',
        'read',
        'dpe-favoris',
        'dpe_favoris_page'
    );
    
           // ✅ PHASE 2 : Menu principal pour le système unifié de gestion des leads
       add_menu_page(
           'Gestion des Leads',
           'Leads',
           'manage_options',
           'unified-leads',
           'unified_leads_admin_page',
           'dashicons-groups',
           8
       );
       
       // ✅ PHASE 2 : Sous-menu pour la configuration
       add_submenu_page(
           'unified-leads',
           'Configuration',
           'Configuration',
           'manage_options',
           'unified-leads-config',
           'unified_leads_config_page'
       );
}


       // ✅ PHASE 1 : Inclure la page d'administration des leads unifiés
       require_once plugin_dir_path(__FILE__) . 'templates/unified-leads-admin.php';
       
       // ✅ PHASE 2 : Inclure la page de configuration des leads unifiés
       require_once plugin_dir_path(__FILE__) . 'templates/unified-leads-config.php';

// --- Affichage du panneau d'administration SCI ---
function sci_afficher_panel() {
    // ✅ MODIFIÉ : Utiliser la fonction utilitaire pour récupérer les codes postaux
    $codesPostauxArray = sci_get_user_postal_codes();

    // Préparer le contexte pour les templates
    $context = [
        'codesPostauxArray' => $codesPostauxArray,
        'config_manager' => sci_config_manager(),
        'inpi_token_manager' => sci_inpi_token_manager(),
        'woocommerce_integration' => sci_woocommerce(),
        'campaign_manager' => sci_campaign_manager()
    ];

    // Charger les notifications d'administration
    sci_load_template('admin-notifications', $context);
    
    // Charger le template principal du panneau SCI
    sci_load_template('sci-panel', $context);
}

// ✅ NOUVEAU : AJAX Handler pour la recherche avec pagination
add_action('wp_ajax_sci_inpi_search_ajax', 'sci_inpi_search_ajax');
add_action('wp_ajax_nopriv_sci_inpi_search_ajax', 'sci_inpi_search_ajax');

function sci_inpi_search_ajax() {
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
    
    my_istymo_log("=== RECHERCHE AJAX INPI ===", 'inpi');
    my_istymo_log("Code postal: $code_postal", 'inpi');
    my_istymo_log("Page: $page", 'inpi');
    my_istymo_log("Taille page: $page_size", 'inpi');
    
    // Appeler la fonction de recherche avec pagination
    $resultats = sci_fetch_inpi_data_with_pagination($code_postal, $page, $page_size);
    
    if (is_wp_error($resultats)) {
        my_istymo_log("❌ Erreur recherche AJAX: " . $resultats->get_error_message(), 'inpi');
        wp_send_json_error($resultats->get_error_message());
        return;
    }
    
    if (empty($resultats['data'])) {
        my_istymo_log("⚠️ Aucun résultat trouvé", 'inpi');
        wp_send_json_error('Aucun résultat trouvé pour ce code postal');
        return;
    }
    
    // Formater les résultats
    $formatted_results = sci_format_inpi_results($resultats['data']);
    
    my_istymo_log("✅ Recherche AJAX réussie: " . count($formatted_results) . " résultats formatés", 'inpi');
    my_istymo_log("Pagination: " . json_encode($resultats['pagination']), 'inpi');
    
    wp_send_json_success([
        'results' => $formatted_results,
        'pagination' => $resultats['pagination']
    ]);
}

// ✅ MODIFIÉ : Appel API INPI avec pagination
function sci_fetch_inpi_data_with_pagination($code_postal, $page = 1, $page_size = 50) {
    // Utiliser le gestionnaire de tokens INPI
    $inpi_token_manager = sci_inpi_token_manager();
    $token = $inpi_token_manager->get_token();

    if (empty($token)) {
        return new WP_Error('token_manquant', 'Impossible de générer un token INPI. Veuillez vérifier vos identifiants dans la configuration.');
    }

    // Récupérer l'URL depuis la configuration
    $config_manager = sci_config_manager();
    $api_url = $config_manager->get_inpi_api_url();

    // ✅ URL avec paramètres de pagination
    $url = $api_url . '?' . http_build_query([
        'companyName' => 'SCI',
        'pageSize' => $page_size,
        'page' => $page,
        'zipCodes[]' => $code_postal
    ]);

    // Configuration des headers avec authorization et accept JSON
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json'
        ],
        'timeout' => 30
    ];

    my_istymo_log("=== REQUÊTE API INPI AVEC PAGINATION ===", 'inpi');
    my_istymo_log("URL: $url", 'inpi');
    my_istymo_log("Token: " . substr($token, 0, 20) . "...", 'inpi');

    // Effectue la requête HTTP GET via WordPress HTTP API
    $reponse = wp_remote_get($url, $args);

    // Vérifie s'il y a une erreur réseau
    if (is_wp_error($reponse)) {
        my_istymo_log("❌ Erreur réseau INPI: " . $reponse->get_error_message(), 'inpi');
        return new WP_Error('requete_invalide', 'Erreur lors de la requête : ' . $reponse->get_error_message());
    }

    // Récupère le code HTTP et le corps de la réponse
    $code_http = wp_remote_retrieve_response_code($reponse);
    $corps     = wp_remote_retrieve_body($reponse);
    $headers   = wp_remote_retrieve_headers($reponse);

    my_istymo_log("Code HTTP INPI: $code_http", 'inpi');
    my_istymo_log("Headers INPI: " . json_encode($headers->getAll()), 'inpi');

    // ✅ NOUVEAU : Gestion automatique des erreurs d'authentification
    if ($code_http === 401 || $code_http === 403) {
        my_istymo_log("🔄 Erreur d'authentification INPI détectée, tentative de régénération du token...", 'inpi');
        
        // Tenter de régénérer le token
        $new_token = $inpi_token_manager->handle_auth_error();
        
        if ($new_token) {
            my_istymo_log("✅ Nouveau token généré, nouvelle tentative de requête...", 'inpi');
            
            // Refaire la requête avec le nouveau token
            $args['headers']['Authorization'] = 'Bearer ' . $new_token;
            $reponse = wp_remote_get($url, $args);
            
            if (is_wp_error($reponse)) {
                return new WP_Error('requete_invalide', 'Erreur lors de la requête après régénération du token : ' . $reponse->get_error_message());
            }
            
            $code_http = wp_remote_retrieve_response_code($reponse);
            $corps = wp_remote_retrieve_body($reponse);
            $headers = wp_remote_retrieve_headers($reponse);
            
            my_istymo_log("Code HTTP après régénération: $code_http", 'inpi');
        } else {
            return new WP_Error('token_regeneration_failed', 'Impossible de régénérer le token INPI. Vérifiez vos identifiants.');
        }
    }

    // Si le code HTTP n'est toujours pas 200 OK, retourne une erreur
    if ($code_http !== 200) {
        my_istymo_log("❌ Erreur API INPI finale: Code $code_http - $corps", 'inpi');
        return new WP_Error('api_inpi', "Erreur de l'API INPI (code $code_http) : $corps");
    }

    // Décoder le JSON en tableau associatif PHP
    $donnees = json_decode($corps, true);

    // ✅ EXTRAIRE LES INFORMATIONS DE PAGINATION DES HEADERS
    $pagination_info = [
        'current_page' => intval($headers['pagination-page'] ?? $page),
        'page_size' => intval($headers['pagination-limit'] ?? $page_size),
        'total_count' => intval($headers['pagination-count'] ?? 0),
        'total_pages' => intval($headers['pagination-max-page'] ?? 1)
    ];

    my_istymo_log("✅ Requête INPI réussie", 'inpi');
    my_istymo_log("Données: " . (is_array($donnees) ? count($donnees) : 0) . " résultats", 'inpi');
    my_istymo_log("Pagination: " . json_encode($pagination_info), 'inpi');

    return [
        'data' => $donnees,
        'pagination' => $pagination_info
    ];
}

// ✅ FONCTION LEGACY POUR COMPATIBILITÉ (utilisée dans l'admin sans pagination)
function sci_fetch_inpi_data($code_postal) {
    $result = sci_fetch_inpi_data_with_pagination($code_postal, 1, 100);
    
    if (is_wp_error($result)) {
        return $result;
    }
    
    return $result['data'];
}

// --- Formatage des données reçues de l'API pour affichage dans le tableau ---
function sci_format_inpi_results(array $data): array {
    $results = [];

    // Parcourt chaque société retournée par l'API
    foreach ($data as $company) {
        // Récupère en toute sécurité les données imbriquées avec l'opérateur ?? (existe ou vide)
        $denomination = $company['formality']['content']['personneMorale']['identite']['entreprise']['denomination'] ?? '';
        $siren       = $company['formality']['content']['personneMorale']['identite']['entreprise']['siren'] ?? '';

        $adresseData = $company['formality']['content']['personneMorale']['adresseEntreprise']['adresse'] ?? [];

        // Compose l'adresse complète (numéro + type de voie + nom de voie)
        $adresse_complete = array_filter([
            $adresseData['numVoie'] ?? '',
            $adresseData['typeVoie'] ?? '',
            $adresseData['voie'] ?? '',
        ]);
        $adresse_texte = implode(' ', $adresse_complete);

        // Récupère le premier dirigeant s'il existe
        $pouvoirs = $company['formality']['content']['personneMorale']['composition']['pouvoirs'] ?? [];
        $dirigeant = '';

        if (isset($pouvoirs[0]['individu']['descriptionPersonne'])) {
            $pers = $pouvoirs[0]['individu']['descriptionPersonne'];
            // Concatène nom + prénoms
            $dirigeant = trim(($pers['nom'] ?? '') . ' ' . implode(' ', $pers['prenoms'] ?? []));
        }

        // Ajoute les données formatées au tableau final
        $results[] = [
            'denomination' => $denomination,
            'siren'        => $siren,
            'dirigeant'    => $dirigeant,
            'adresse'      => $adresse_texte,
            'ville'        => $adresseData['commune'] ?? '',
            'code_postal'  => $adresseData['codePostal'] ?? '',
        ];
    }

    return $results;
}

add_action('admin_enqueue_scripts', 'sci_enqueue_admin_scripts');

function sci_enqueue_admin_scripts() {
    // ✅ AMÉLIORÉ : Charger les scripts sur toutes les pages SCI
    $current_screen = get_current_screen();
    $is_sci_page = false;
    
    // Vérifier si on est sur une page SCI
    if ($current_screen) {
        $is_sci_page = strpos($current_screen->id, 'sci-') !== false || 
                      strpos($current_screen->id, 'toplevel_page_sci-panel') !== false;
    }
    
    // ✅ NOUVEAU : Vérifier si on est sur une page DPE
    $is_dpe_page = false;
    if ($current_screen) {
        $is_dpe_page = strpos($current_screen->id, 'dpe-') !== false || 
                      strpos($current_screen->id, 'toplevel_page_dpe-panel') !== false;
    }
    
    // Charger les scripts sur toutes les pages admin (ou seulement les pages SCI si nécessaire)
    if ($is_sci_page || $is_dpe_page || !$current_screen) {
        // Charge ton script JS personnalisé
        wp_enqueue_script(
            'sci-favoris',
            plugin_dir_url(__FILE__) . 'assets/js/favoris.js',
            array(), // dépendances, si tu utilises jQuery par exemple, mets ['jquery']
            '1.0',
            true // true = placer dans le footer
        );

        wp_enqueue_script(
            'sci-lettre-js',
            plugin_dir_url(__FILE__) . 'assets/js/lettre.js',
            array(), // ajouter 'jquery' si nécessaire
            '1.0',
            true
        );

        // Nouveau script pour le paiement
        wp_enqueue_script(
            'sci-payment-js',
            plugin_dir_url(__FILE__) . 'assets/js/payment.js',
            array(), 
            '1.0',
            true
        );

        // ✅ NOUVEAU : Script principal pour la page admin SCI
        wp_enqueue_script(
            'sci-admin-sci',
            plugin_dir_url(__FILE__) . 'assets/js/admin-sci.js',
            array(), 
            '1.0',
            true
        );

        // ✅ NOUVEAU : Script pour les favoris DPE
        wp_enqueue_script(
            'dpe-favoris',
            plugin_dir_url(__FILE__) . 'assets/js/dpe-favoris.js',
            array(), 
            '1.0',
            true
        );



        // ✅ NOUVEAU : Script pour les fonctionnalités avancées (TEMPORAIREMENT DÉSACTIVÉ)
        /*
        wp_enqueue_script(
            'sci-enhanced-features',
            plugin_dir_url(__FILE__) . 'assets/js/enhanced-features.js',
            array(), 
            '1.0',
            true
        );
        */

        // ✅ NOUVEAU : Récupérer les SIRENs contactés pour l'admin
        $campaign_manager = sci_campaign_manager();
        $contacted_sirens = $campaign_manager->get_user_contacted_sirens();

        // Localisation des variables AJAX pour le script favoris
        wp_localize_script('sci-favoris', 'sci_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sci_favoris_nonce'),
            'contacted_sirens' => $contacted_sirens // ✅ NOUVEAU : Liste des SIRENs contactés
        ));

        // Localisation pour le paiement - UTILISE L'URL STOCKÉE
        $woocommerce_integration = sci_woocommerce();
        $config_manager = sci_config_manager();
        wp_localize_script('sci-payment-js', 'sciPaymentData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sci_campaign_nonce'),
            'unit_price' => $woocommerce_integration->get_unit_price(),
            'woocommerce_ready' => $woocommerce_integration->is_woocommerce_ready(),
            'campaigns_url' => $config_manager->get_sci_campaigns_page_url() // ✅ MODIFIÉ : Utilise l'URL stockée
        ));

        // Localisation pour lettre.js (ajaxurl)
        wp_localize_script('sci-lettre-js', 'ajaxurl', admin_url('admin-ajax.php'));

        // ✅ NOUVEAU : Variables pour la recherche automatique
        // Récupérer les codes postaux de l'utilisateur connecté
        $codesPostauxArray = sci_get_user_postal_codes();
        
        // ✅ CORRIGÉ : S'assurer que $contacted_sirens est un tableau
        $contacted_sirens_array = is_array($contacted_sirens) ? $contacted_sirens : [];
        
        // ✅ MODIFIÉ : Passer les variables directement au script favoris
        wp_localize_script('sci-favoris', 'sciAutoSearch', array(
            'auto_search_enabled' => !empty($codesPostauxArray),
            'default_postal_code' => !empty($codesPostauxArray) ? $codesPostauxArray[0] : '',
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sci_search_nonce')
        ));

        // ✅ NOUVEAU : Localisation des variables pour le script admin-sci.js
        wp_localize_script('sci-admin-sci', 'sci_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sci_favoris_nonce'),
            'contacted_sirens' => $contacted_sirens_array
        ));

        // ✅ NOUVEAU : Variables pour la recherche automatique pour admin-sci.js
        wp_localize_script('sci-admin-sci', 'sciAutoSearch', array(
            'auto_search_enabled' => !empty($codesPostauxArray),
            'default_postal_code' => !empty($codesPostauxArray) ? $codesPostauxArray[0] : '',
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sci_search_nonce')
        ));

        // ✅ NOUVEAU : Variables pour les favoris DPE
        wp_localize_script('dpe-favoris', 'dpe_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dpe_favoris_nonce')
        ));



        // Facultatif : ajouter ton CSS si besoin
        wp_enqueue_style(
            'sci-style',
            plugin_dir_url(__FILE__) . 'assets/css/style.css'
        );

        // ✅ NOUVEAU : CSS spécifique pour la page admin SCI
        wp_enqueue_style(
            'sci-admin-sci',
            plugin_dir_url(__FILE__) . 'assets/css/admin-sci.css'
        );
    }
}

// --- NOUVELLE FONCTION AJAX POUR ENVOYER UNE LETTRE VIA L'API LA POSTE ---
add_action('wp_ajax_sci_envoyer_lettre_laposte', 'sci_envoyer_lettre_laposte_ajax');
add_action('wp_ajax_nopriv_sci_envoyer_lettre_laposte', 'sci_envoyer_lettre_laposte_ajax');

function sci_envoyer_lettre_laposte_ajax() {
    // Vérification des données reçues
    if (!isset($_POST['entry']) || !isset($_POST['pdf_base64'])) {
        wp_send_json_error('Données manquantes');
        return;
    }

    $entry = json_decode(stripslashes($_POST['entry']), true);
    $pdf_base64 = $_POST['pdf_base64'];
    $campaign_title = sanitize_text_field($_POST['campaign_title'] ?? '');
    $campaign_id = intval($_POST['campaign_id'] ?? 0);

    if (!$entry || !$pdf_base64) {
        wp_send_json_error('Données invalides');
        return;
    }

    // Récupérer les données de l'expéditeur depuis le gestionnaire de campagnes
    $campaign_manager = sci_campaign_manager();
    $expedition_data = $campaign_manager->get_user_expedition_data();
    
    // Vérifier que les données essentielles sont présentes
    $validation_errors = $campaign_manager->validate_expedition_data($expedition_data);
    if (!empty($validation_errors)) {
        wp_send_json_error('Données expéditeur incomplètes : ' . implode(', ', $validation_errors));
        return;
    }
    
    // Récupérer les paramètres configurés depuis le gestionnaire de configuration
    $config_manager = sci_config_manager();
    $laposte_params = $config_manager->get_laposte_payload_params();
    
    // Préparer le payload pour l'API La Poste avec les paramètres dynamiques
    $payload = array_merge($laposte_params, [
        // Adresse expéditeur (récupérée depuis le profil utilisateur)
        "adresse_expedition" => $expedition_data,

        // Adresse destinataire (SCI sélectionnée)
        "adresse_destination" => [
            "civilite" => "", // Pas de civilité pour les SCI
            "prenom" => "",   // Pas de prénom pour les SCI
            "nom" => $entry['dirigeant'] ?? '',
            "nom_societe" => $entry['denomination'] ?? '',
            "adresse_ligne1" => $entry['adresse'] ?? '',
            "adresse_ligne2" => "",
            "code_postal" => $entry['code_postal'] ?? '',
            "ville" => $entry['ville'] ?? '',
            "pays" => "FRANCE",
        ],

        // PDF encodé
        "fichier" => [
            "format" => "pdf",
            "contenu_base64" => $pdf_base64,
        ],
    ]);

    // Récupérer le token depuis la configuration sécurisée
    $token = $config_manager->get_laposte_token();

    if (empty($token)) {
        wp_send_json_error('Token La Poste non configuré');
        return;
    }

    // Logger le payload avant envoi (sans le PDF pour éviter les logs trop volumineux)
    $payload_for_log = $payload;
    $payload_for_log['fichier']['contenu_base64'] = '[PDF_BASE64_CONTENT_' . strlen($pdf_base64) . '_CHARS]';
    my_istymo_log("=== ENVOI LETTRE POUR {$entry['denomination']} ===", 'laposte');
    my_istymo_log("Payload envoyé: " . json_encode($payload_for_log, JSON_PRETTY_PRINT), 'laposte');

    // Envoyer via l'API La Poste
    $response = envoyer_lettre_via_api_la_poste_my_istymo($payload, $token);

    // Logger la réponse complète
    my_istymo_log("Réponse complète API: " . json_encode($response, JSON_PRETTY_PRINT), 'laposte');

    if ($response['success']) {
        my_istymo_log("✅ SUCCÈS pour {$entry['denomination']} - UID: " . ($response['uid'] ?? 'N/A'), 'laposte');
        
        // Mettre à jour le statut dans la base de données
        if ($campaign_id > 0) {
            $campaign_manager->update_letter_status(
                $campaign_id, 
                $entry['siren'], 
                'sent', 
                $response['uid'] ?? null
            );
        }
        
        wp_send_json_success([
            'message' => 'Lettre envoyée avec succès',
            'uid' => $response['uid'] ?? 'non disponible',
            'denomination' => $entry['denomination']
        ]);
    } else {
        $error_msg = 'Erreur API : ';
        if (isset($response['message']) && is_array($response['message'])) {
            $error_msg .= json_encode($response['message']);
        } elseif (isset($response['error'])) {
            $error_msg .= $response['error'];
        } else {
            $error_msg .= 'Erreur inconnue';
        }

        my_istymo_log("❌ ERREUR pour {$entry['denomination']}: $error_msg", 'laposte');
        my_istymo_log("Code HTTP: " . ($response['code'] ?? 'N/A'), 'laposte');
        my_istymo_log("Message détaillé: " . json_encode($response['message'] ?? [], JSON_PRETTY_PRINT), 'laposte');
        
        // Mettre à jour le statut d'erreur dans la base de données
        if ($campaign_id > 0) {
            $campaign_manager->update_letter_status(
                $campaign_id, 
                $entry['siren'], 
                'failed', 
                null, 
                $error_msg
            );
        }
        
        wp_send_json_error($error_msg);
    }
}

function envoyer_lettre_via_api_la_poste_my_istymo($payload, $token) {
    // Récupère l'URL depuis la configuration sécurisée
    $config_manager = sci_config_manager();
    $api_url = $config_manager->get_laposte_api_url();

    $headers = [
        'apiKey'       => $token, // ✅ Authentification via apiKey
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    ];

    $body = wp_json_encode($payload);

    $args = [
        'method'  => 'POST',
        'headers' => $headers,
        'body'    => $body,
        'timeout' => 30,
    ];

    // Logger la requête (sans le body pour éviter les logs trop volumineux)
    my_istymo_log("=== REQUÊTE API LA POSTE ===", 'laposte');
    my_istymo_log("URL: $api_url", 'laposte');
    my_istymo_log("Headers: " . json_encode($headers, JSON_PRETTY_PRINT), 'laposte');
    my_istymo_log("Body size: " . strlen($body) . " caractères", 'laposte');

    $response = wp_remote_post($api_url, $args);

    // Gestion des erreurs WordPress
    if (is_wp_error($response)) {
        my_istymo_log("❌ Erreur WordPress HTTP: " . $response->get_error_message(), 'laposte');
        return [
            'success' => false,
            'error'   => $response->get_error_message(),
        ];
    }

    $code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_headers = wp_remote_retrieve_headers($response);
    
    // Logger la réponse complète
    my_istymo_log("=== RÉPONSE API LA POSTE ===", 'laposte');
    my_istymo_log("Code HTTP: $code", 'laposte');
    my_istymo_log("Headers de réponse: " . json_encode($response_headers->getAll(), JSON_PRETTY_PRINT), 'laposte');
    my_istymo_log("Body de réponse: $response_body", 'laposte');

    $data = json_decode($response_body, true);
    
    // Logger les données décodées
    my_istymo_log("Données JSON décodées: " . json_encode($data, JSON_PRETTY_PRINT), 'laposte');

    if ($code >= 200 && $code < 300) {
        my_istymo_log("✅ Succès API (code $code)", 'laposte');
        return [
            'success' => true,
            'data'    => $data,
            'uid'     => $data['uid'] ?? null, // ✅ Extraction de l'UID
        ];
    } else {
        my_istymo_log("❌ Erreur API (code $code)", 'laposte');
        return [
            'success' => false,
            'code'    => $code,
            'message' => $data,
            'raw_response' => $response_body,
        ];
    }
}

// --- NOUVELLE FONCTION AJAX POUR RECUPERER LES FAVORIS ---

function sci_favoris_page() {
    global $sci_favoris_handler;
    $favoris = $sci_favoris_handler->get_favoris();
    
    // Préparer le contexte pour le template
    $context = [
        'favoris' => $favoris
    ];
    
    // Charger le template des favoris
    sci_load_template('sci-favoris', $context);
}

// --- PAGE POUR AFFICHER LES CAMPAGNES ---
function sci_campaigns_page() {
    $campaign_manager = sci_campaign_manager();
    $campaigns = $campaign_manager->get_user_campaigns();
    
    // Gestion de l'affichage des détails d'une campagne
    $view_mode = false;
    $campaign_details = null;
    
    if (isset($_GET['view']) && is_numeric($_GET['view'])) {
        $campaign_details = $campaign_manager->get_campaign_details(intval($_GET['view']));
        if ($campaign_details) {
            $view_mode = true;
        }
    }
    
    // Préparer le contexte pour le template
    $context = [
        'campaigns' => $campaigns,
        'campaign_details' => $campaign_details,
        'view_mode' => $view_mode
    ];
    
    // Charger le template des campagnes
    sci_load_template('sci-campaigns', $context);
}

// --- PAGE POUR AFFICHER LES LOGS D'API ---
function sci_logs_page() {
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/my-istymo-logs/';
    
    // ✅ NOUVEAU : Récupérer tous les fichiers de logs disponibles
    $log_files = [];
    if (file_exists($log_dir)) {
        $files = scandir($log_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
                $context = str_replace('-logs.txt', '', $file);
                $log_files[$context] = [
                    'path' => $log_dir . $file,
                    'name' => $context,
                    'size' => filesize($log_dir . $file),
                    'modified' => filemtime($log_dir . $file)
                ];
            }
        }
    }
    
    // ✅ NOUVEAU : Sélectionner le fichier de log à afficher
    $selected_log = $_GET['log'] ?? 'laposte';
    $log_file = $log_files[$selected_log]['path'] ?? $log_dir . 'laposte-logs.txt';
    
    // Préparer les données pour le template
    $log_content = '';
    $log_stats = [
        'size' => 0,
        'modified' => 0
    ];
    
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        $log_lines = explode("\n", $logs);
        $recent_logs = array_slice($log_lines, -100); // 100 dernières lignes
        $log_content = implode("\n", $recent_logs);
        $log_stats = [
            'size' => filesize($log_file),
            'modified' => filemtime($log_file)
        ];
    }
    
    // Préparer le contexte pour le template
    $context = [
        'log_file' => $log_file,
        'log_content' => $log_content,
        'log_stats' => $log_stats,
        'log_files' => $log_files,
        'selected_log' => $selected_log
    ];
    
    // Charger le template des logs
    sci_load_template('sci-logs', $context);
    
    // ✅ NOUVEAU : Gestion de l'effacement des logs avec sélection
    if (isset($_GET['clear']) && $_GET['clear'] == '1') {
        $log_to_clear = $_GET['log'] ?? 'laposte';
        $file_to_clear = $log_dir . $log_to_clear . '-logs.txt';
        
        if (file_exists($file_to_clear)) {
            unlink($file_to_clear);
            echo '<div class="notice notice-success"><p>Logs ' . esc_html($log_to_clear) . ' effacés avec succès.</p></div>';
            echo '<script>window.location.href = "' . admin_url('admin.php?page=sci-logs&log=' . $log_to_clear) . '";</script>';
        }
    }
}

// --- FONCTION AJAX POUR GÉNÉRER LES PDFS (CORRIGÉE) ---
add_action('wp_ajax_sci_generer_pdfs', 'sci_generer_pdfs');
add_action('wp_ajax_nopriv_sci_generer_pdfs', 'sci_generer_pdfs');

function sci_generer_pdfs() {
    // Vérification de sécurité
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'sci_campaign_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }

    if (!isset($_POST['data'])) {
        wp_send_json_error("Aucune donnée reçue.");
        return;
    }

    $data = json_decode(stripslashes($_POST['data']), true);
    if (!isset($data['entries']) || !is_array($data['entries'])) {
        wp_send_json_error("Entrées invalides.");
        return;
    }

    my_istymo_log("=== DÉBUT GÉNÉRATION PDFs ===", 'pdf');
    my_istymo_log("Titre campagne: " . ($data['title'] ?? 'N/A'), 'pdf');
    my_istymo_log("Nombre d'entrées: " . count($data['entries']), 'pdf');

    // Créer la campagne en base de données
    $campaign_manager = sci_campaign_manager();
    $campaign_id = $campaign_manager->create_campaign($data['title'], $data['content'], $data['entries']);
    
    if (is_wp_error($campaign_id)) {
        my_istymo_log("❌ Erreur création campagne: " . $campaign_id->get_error_message(), 'pdf');
        wp_send_json_error("Erreur lors de la création de la campagne : " . $campaign_id->get_error_message());
        return;
    }

    my_istymo_log("✅ Campagne créée avec ID: $campaign_id", 'pdf');

    // Inclure TCPDF
    if (!class_exists('TCPDF')) {
        require_once plugin_dir_path(__FILE__) . 'lib/tcpdf/tcpdf.php';
    }

    $upload_dir = wp_upload_dir();
    $pdf_dir = $upload_dir['basedir'] . '/campagnes/';
    $pdf_url_base = $upload_dir['baseurl'] . '/campagnes/';
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists($pdf_dir)) {
        wp_mkdir_p($pdf_dir);
        my_istymo_log("📁 Dossier créé: $pdf_dir", 'pdf');
    }

    $pdf_links = [];

    foreach ($data['entries'] as $index => $entry) {
        try {
            my_istymo_log("📄 Génération PDF " . ($index + 1) . "/" . count($data['entries']) . " pour: " . ($entry['denomination'] ?? 'N/A'), 'pdf');
            
            $nom = $entry['dirigeant'] ?? 'Dirigeant';
            $texte = str_replace('[NOM]', $nom, $data['content']);

            // Créer le PDF avec TCPDF
            $pdf = new TCPDF();
            $pdf->SetCreator('SCI Plugin');
            $pdf->SetAuthor('SCI Plugin');
            $pdf->SetTitle('Lettre pour ' . ($entry['denomination'] ?? 'SCI'));
            $pdf->SetSubject('Lettre SCI');
            
            // Paramètres de page
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(TRUE, 25);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            
            // Ajouter une page
            $pdf->AddPage();
            
            // Définir la police
            $pdf->SetFont('helvetica', '', 12);
            
            // Ajouter le contenu
            $pdf->writeHTML(nl2br(htmlspecialchars($texte)), true, false, true, false, '');

            // Générer le nom de fichier sécurisé
            $filename = sanitize_file_name($entry['denomination'] . '-' . $nom . '-' . time() . '-' . $index) . '.pdf';
            $filepath = $pdf_dir . $filename;
            $fileurl = $pdf_url_base . $filename;

            // Sauvegarder le PDF
            $pdf->Output($filepath, 'F');

            // Vérifier que le fichier a été créé
            if (file_exists($filepath)) {
                $pdf_links[] = [
                    'url' => $fileurl,
                    'name' => $filename,
                    'path' => $filepath
                ];
                
                my_istymo_log("✅ PDF généré avec succès : $filename pour {$entry['denomination']}", 'pdf');
            } else {
                my_istymo_log("❌ Erreur : PDF non créé pour {$entry['denomination']}", 'pdf');
            }

        } catch (Exception $e) {
            my_istymo_log("❌ Erreur lors de la génération PDF pour {$entry['denomination']}: " . $e->getMessage(), 'pdf');
        }
    }

    if (empty($pdf_links)) {
        my_istymo_log("❌ Aucun PDF généré", 'pdf');
        wp_send_json_error('Aucun PDF n\'a pu être généré');
        return;
    }

    my_istymo_log("✅ Génération terminée : " . count($pdf_links) . " PDFs créés sur " . count($data['entries']) . " demandés", 'pdf');

    wp_send_json_success([
        'files' => $pdf_links,
        'campaign_id' => $campaign_id,
        'message' => count($pdf_links) . ' PDFs générés avec succès'
    ]);
}

// ✅ NOUVEAU : Fonctions pour les pages DPE

// ✅ CRÉER LES TABLES DPE LORS DE L'ACTIVATION
register_activation_hook(__FILE__, 'sci_create_dpe_tables');

function sci_create_dpe_tables() {
    // Forcer la création de la table des favoris DPE
    dpe_favoris_handler()->create_favoris_table();
}

// ✅ FORCER LA CRÉATION DE LA TABLE DPE MAINTENANT
add_action('init', 'sci_force_create_dpe_tables');

function sci_force_create_dpe_tables() {
    // Forcer la création de la table des favoris DPE
    dpe_favoris_handler()->create_favoris_table();
}

// ✅ PHASE 3 : Handlers AJAX pour les actions et le workflow
add_action('wp_ajax_my_istymo_add_lead_action', 'my_istymo_ajax_add_lead_action');
add_action('wp_ajax_my_istymo_update_lead_action', 'my_istymo_ajax_update_lead_action');
add_action('wp_ajax_my_istymo_delete_lead_action', 'my_istymo_ajax_delete_lead_action');
add_action('wp_ajax_my_istymo_get_lead_action', 'my_istymo_ajax_get_lead_action');
add_action('wp_ajax_my_istymo_change_lead_status', 'my_istymo_ajax_change_lead_status');
add_action('wp_ajax_my_istymo_get_lead_details', 'my_istymo_ajax_get_lead_details');
add_action('wp_ajax_my_istymo_get_lead_detail_content', 'my_istymo_ajax_get_lead_detail_content');
add_action('wp_ajax_my_istymo_validate_workflow_transition', 'my_istymo_ajax_validate_workflow_transition');
add_action('wp_ajax_my_istymo_get_workflow_transitions', 'my_istymo_ajax_get_workflow_transitions');
add_action('wp_ajax_my_istymo_get_status_change_validation', 'my_istymo_ajax_get_status_change_validation');
add_action('wp_ajax_my_istymo_get_workflow_step_info', 'my_istymo_ajax_get_workflow_step_info');

// ✅ NOUVEAU : Handlers AJAX pour l'édition des leads
add_action('wp_ajax_my_istymo_update_lead', 'my_istymo_ajax_update_lead');

// ✅ Fonction de mise à jour de lead
function my_istymo_ajax_update_lead() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    $status = sanitize_text_field($_POST['status'] ?? '');
    $priorite = sanitize_text_field($_POST['priorite'] ?? '');
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');
    
    if (!$lead_id) {
        wp_send_json_error('ID du lead manquant');
        return;
    }
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    
    // Préparer les données à mettre à jour
    $update_data = [];
    if (!empty($status)) $update_data['status'] = $status;
    if (!empty($priorite)) $update_data['priorite'] = $priorite;
    if (isset($_POST['notes'])) $update_data['notes'] = $notes; // Permettre les notes vides
    
    $result = $leads_manager->update_lead($lead_id, $update_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success('Lead mis à jour avec succès');
    }
}

// ✅ Handler pour la suppression de leads
add_action('wp_ajax_delete_unified_lead', 'my_istymo_ajax_delete_unified_lead');

// ✅ Fonction de suppression de lead
function my_istymo_ajax_delete_unified_lead() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    
    if (!$lead_id) {
        wp_send_json_error('ID du lead manquant');
        return;
    }
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $result = $leads_manager->delete_lead($lead_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success('Lead supprimé avec succès');
    }
}

// ✅ Fonctions AJAX pour les actions
function my_istymo_ajax_add_lead_action() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    $action_type = sanitize_text_field($_POST['action_type']);
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $result = sanitize_text_field($_POST['result'] ?? 'en_attente');
    $scheduled_date = sanitize_text_field($_POST['scheduled_date'] ?? '');
    
    $user_id = get_current_user_id();
    
    $actions_manager = Lead_Actions_Manager::get_instance();
    $action_id = $actions_manager->add_action($lead_id, $user_id, $action_type, $description, $result, $scheduled_date);
    
    if (is_wp_error($action_id)) {
        wp_send_json_error($action_id->get_error_message());
    } else {
        wp_send_json_success(['action_id' => $action_id]);
    }
}

function my_istymo_ajax_update_lead_action() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $action_id = intval($_POST['action_id']);
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $result = sanitize_text_field($_POST['result'] ?? '');
    
    $data = [];
    if (!empty($description)) $data['description'] = $description;
    if (!empty($result)) $data['result'] = $result;
    
    $actions_manager = Lead_Actions_Manager::get_instance();
    $result = $actions_manager->update_action($action_id, $data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success();
    }
}

function my_istymo_ajax_delete_lead_action() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $action_id = intval($_POST['action_id']);
    
    $actions_manager = Lead_Actions_Manager::get_instance();
    $result = $actions_manager->delete_action($action_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success();
    }
}

function my_istymo_ajax_get_lead_action() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $action_id = intval($_POST['action_id']);
    
    global $wpdb;
    $action = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}my_istymo_lead_actions WHERE id = %d",
        $action_id
    ));
    
    if (!$action) {
        wp_send_json_error('Action introuvable');
    } else {
        wp_send_json_success($action);
    }
}

function my_istymo_ajax_change_lead_status() {
    error_log('🔄 my_istymo_ajax_change_lead_status appelée');
    
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    $new_status = sanitize_text_field($_POST['new_status']);
    
    error_log("📋 Données reçues - Lead ID: $lead_id, Nouveau statut: $new_status");
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $workflow_manager = Lead_Workflow::get_instance();
    
    // Valider la transition
    $lead = $leads_manager->get_lead($lead_id);
    if (!$lead) {
        error_log("❌ Lead introuvable: $lead_id");
        wp_send_json_error('Lead introuvable');
    }
    
    error_log("✅ Lead trouvé - Statut actuel: " . $lead->status);
    
    $validation = $workflow_manager->validate_transition($lead->status, $new_status, ['id' => $lead_id]);
    if (is_wp_error($validation)) {
        error_log("❌ Validation échouée: " . $validation->get_error_message());
        wp_send_json_error($validation->get_error_message());
    }
    
    error_log("✅ Validation réussie");
    
    // Effectuer le changement
    $result = $leads_manager->update_lead($lead_id, ['status' => $new_status]);
    
    if (is_wp_error($result)) {
        error_log("❌ Erreur lors de la mise à jour: " . $result->get_error_message());
        wp_send_json_error($result->get_error_message());
    } else {
        error_log("✅ Statut mis à jour avec succès");
        wp_send_json_success();
    }
}

function my_istymo_ajax_get_lead_details() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $lead = $leads_manager->get_lead($lead_id);
    
    if (!$lead) {
        wp_send_json_error('Lead introuvable');
    } else {
        wp_send_json_success($lead);
    }
}

function my_istymo_ajax_get_lead_detail_content() {
    try {
        check_ajax_referer('my_istymo_nonce', 'nonce');
        
        $lead_id = intval($_POST['lead_id']);
        
        if (!$lead_id) {
            wp_send_json_error('ID du lead manquant');
        }
        
        // Vérifier que le lead existe
        $leads_manager = Unified_Leads_Manager::get_instance();
        $lead = $leads_manager->get_lead($lead_id);
        
        if (!$lead) {
            wp_send_json_error('Lead introuvable');
        }
        
        // Générer le contenu directement
        $content = my_istymo_generate_lead_detail_content($lead_id, $lead);
        
        if (empty($content)) {
            wp_send_json_error('Erreur lors de la génération du contenu');
        }
        
        wp_send_json_success($content);
        
    } catch (Exception $e) {
        error_log('Erreur dans my_istymo_ajax_get_lead_detail_content: ' . $e->getMessage());
        wp_send_json_error('Erreur interne du serveur: ' . $e->getMessage());
    }
}

function my_istymo_ajax_validate_workflow_transition() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    $from_status = sanitize_text_field($_POST['from_status']);
    $to_status = sanitize_text_field($_POST['to_status']);
    
    $workflow_manager = Lead_Workflow::get_instance();
    $validation = $workflow_manager->validate_transition($from_status, $to_status, ['id' => $lead_id]);
    
    if (is_wp_error($validation)) {
        wp_send_json_error([
            'message' => $validation->get_error_message(),
            'required_actions' => []
        ]);
    } else {
        $suggested_actions = $workflow_manager->get_suggested_actions_for_transition($from_status, $to_status);
        wp_send_json_success([
            'suggested_actions' => $suggested_actions
        ]);
    }
}

function my_istymo_ajax_get_workflow_transitions() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $workflow_manager = Lead_Workflow::get_instance();
    
    $lead = $leads_manager->get_lead($lead_id);
    if (!$lead) {
        wp_send_json_error('Lead introuvable');
    }
    
    $transitions = $workflow_manager->get_allowed_transitions($lead->status);
    wp_send_json_success($transitions);
}

// Nouvelle fonction pour générer le contenu du modal de détail des leads
function my_istymo_generate_lead_detail_content($lead_id, $lead) {
    ob_start();
    ?>
    <div class="my-istymo-lead-detail-modal" data-lead-id="<?php echo esc_attr($lead_id); ?>">
        
        <!-- En-tête du modal -->
        <div class="my-istymo-modal-header">
            <h2>
                <span class="my-istymo-lead-type-badge my-istymo-lead-type-<?php echo esc_attr($lead->lead_type); ?>">
                    <?php echo esc_html(strtoupper($lead->lead_type)); ?>
                </span>
                Lead #<?php echo esc_html($lead_id); ?>
            </h2>
            <button type="button" class="my-istymo-modal-close" data-action="close-lead-detail">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        
        <!-- Contenu du modal -->
        <div class="my-istymo-modal-content-detail">
            
            <!-- Informations de base -->
            <div class="my-istymo-lead-info-section">
                <h3>ℹ️ Informations de base</h3>
                
                <form id="lead-edit-form" class="my-istymo-edit-form">
                    <input type="hidden" name="lead_id" value="<?php echo esc_attr($lead_id); ?>">
                    
                    <div class="my-istymo-info-container">
                        <div class="my-istymo-info-group">
                            <label>Créé le :</label>
                            <span class="my-istymo-info-value"><?php echo esc_html(date('d/m/Y H:i', strtotime($lead->date_creation))); ?></span>
                        </div>
                        
                        <div class="my-istymo-info-group">
                            <label>ID Original :</label>
                            <span class="my-istymo-info-value"><?php echo esc_html($lead->original_id); ?></span>
                        </div>
                        
                        <div class="my-istymo-info-group">
                            <label>Statut :</label>
                            <select name="status" class="my-istymo-select">
                                <option value="nouveau" <?php selected($lead->status, 'nouveau'); ?>>Nouveau</option>
                                <option value="en_cours" <?php selected($lead->status, 'en_cours'); ?>>En cours</option>
                                <option value="termine" <?php selected($lead->status, 'termine'); ?>>Terminé</option>
                                <option value="annule" <?php selected($lead->status, 'annule'); ?>>Annulé</option>
                            </select>
                        </div>
                        
                        <div class="my-istymo-info-group">
                            <label>Priorité :</label>
                            <select name="priorite" class="my-istymo-select">
                                <option value="basse" <?php selected($lead->priorite, 'basse'); ?>>Basse</option>
                                <option value="normale" <?php selected($lead->priorite, 'normale'); ?>>Normale</option>
                                <option value="haute" <?php selected($lead->priorite, 'haute'); ?>>Haute</option>
                                <option value="urgente" <?php selected($lead->priorite, 'urgente'); ?>>Urgente</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Informations spécifiques du lead intégrées -->
                    <?php if (!empty($lead->data_originale)): ?>
                    <div class="my-istymo-lead-specific-inline">
                        <h4>📋 Informations spécifiques</h4>
                        
                        <?php if ($lead->lead_type === 'dpe'): ?>
                            <?php echo my_istymo_render_dpe_info($lead->data_originale); ?>
                        <?php elseif ($lead->lead_type === 'sci'): ?>
                            <?php echo my_istymo_render_sci_info($lead->data_originale); ?>
                        <?php endif; ?>
                        

                    </div>
                    <?php endif; ?>
                    
                    <div class="my-istymo-info-row-notes">
                        <div class="my-istymo-info-group my-istymo-full-width">
                            <label>Notes :</label>
                            <textarea name="notes" class="my-istymo-textarea" rows="4" placeholder="Ajoutez des notes sur ce lead..."><?php echo esc_textarea($lead->notes); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="my-istymo-form-actions">
                        <button type="submit" class="my-istymo-btn my-istymo-btn-primary">
                            <span class="dashicons dashicons-saved"></span>
                            Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
            
            
            <!-- Historique des actions -->
            <div class="my-istymo-lead-history-section">
                <h3>📝 Historique des actions</h3>
                <div class="my-istymo-no-actions">
                    <p>Aucune action enregistrée pour ce lead.</p>
                </div>
            </div>
        </div>
        
        <!-- Pied du modal -->
        <div class="my-istymo-modal-footer">
            <button type="button" class="my-istymo-btn my-istymo-btn-secondary" data-action="close-lead-detail">
                Fermer
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function my_istymo_render_dpe_info($data) {
    ob_start();
    ?>
    <div class="my-istymo-dpe-info">
        <!-- Informations générales -->
        <div class="my-istymo-info-section">
            <h4 class="my-istymo-section-title">📋 Informations générales</h4>
            <div class="my-istymo-info-grid">
                <?php if (!empty($data['adresse_ban'])): ?>
                <div class="my-istymo-info-item">
                    <label>📍 Adresse</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($data['adresse_ban']); ?>
                        <?php if (!empty($data['nom_commune_ban'])): ?>
                            <br><small><?php echo esc_html($data['nom_commune_ban']); ?>
                            <?php if (!empty($data['code_postal_ban'])): ?>
                                (<?php echo esc_html($data['code_postal_ban']); ?>)
                            <?php endif; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['type_batiment'])): ?>
                <div class="my-istymo-info-item">
                    <label>🏢 Type de bâtiment</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html(ucfirst($data['type_batiment'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['surface_habitable_logement'])): ?>
                <div class="my-istymo-info-item">
                    <label>📐 Surface habitable</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html(number_format($data['surface_habitable_logement'], 0, ',', ' ')); ?> m²
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['annee_construction']) && $data['annee_construction'] != '0'): ?>
                <div class="my-istymo-info-item">
                    <label>🏗️ Année de construction</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($data['annee_construction']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance énergétique -->
        <?php if (!empty($data['etiquette_dpe']) || !empty($data['etiquette_ges']) || !empty($data['conso_5_usages_ef_energie_n1']) || !empty($data['emission_ges_5_usages_energie_n1'])): ?>
        <div class="my-istymo-info-section">
            <h4 class="my-istymo-section-title">⚡ Performance énergétique</h4>
            <div class="my-istymo-info-grid">
                <?php if (!empty($data['etiquette_dpe'])): ?>
                <div class="my-istymo-info-item">
                    <label>⚡ Classe DPE</label>
                    <div class="my-istymo-info-value">
                        <span class="my-istymo-dpe-class my-istymo-dpe-class-<?php echo esc_attr(strtolower($data['etiquette_dpe'])); ?>">
                            <?php echo esc_html($data['etiquette_dpe']); ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['etiquette_ges'])): ?>
                <div class="my-istymo-info-item">
                    <label>🌱 Classe GES</label>
                    <div class="my-istymo-info-value">
                        <span class="my-istymo-dpe-class my-istymo-dpe-class-<?php echo esc_attr(strtolower($data['etiquette_ges'])); ?>">
                            <?php echo esc_html($data['etiquette_ges']); ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['conso_5_usages_ef_energie_n1'])): ?>
                <div class="my-istymo-info-item">
                    <label>⚡ Consommation énergétique</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html(number_format($data['conso_5_usages_ef_energie_n1'], 0, ',', ' ')); ?> kWh/m²/an
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['emission_ges_5_usages_energie_n1'])): ?>
                <div class="my-istymo-info-item">
                    <label>💨 Émissions GES</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html(number_format($data['emission_ges_5_usages_energie_n1'], 0, ',', ' ')); ?> kgCO2/m²/an
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Informations DPE -->
        <?php if (!empty($data['date_etablissement_dpe']) || !empty($data['numero_dpe']) || !empty($data['dpe_id'])): ?>
        <div class="my-istymo-info-section">
            <h4 class="my-istymo-section-title">📄 Détails du DPE</h4>
            <div class="my-istymo-info-grid">
                <?php if (!empty($data['date_etablissement_dpe'])): ?>
                <div class="my-istymo-info-item">
                    <label>📅 Date d'établissement</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html(date('d/m/Y', strtotime($data['date_etablissement_dpe']))); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['numero_dpe'])): ?>
                <div class="my-istymo-info-item">
                    <label>🔢 Numéro DPE</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($data['numero_dpe']); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($data['dpe_id'])): ?>
                <div class="my-istymo-info-item">
                    <label>🆔 ID Système</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($data['dpe_id']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function my_istymo_render_sci_info($data) {
    ob_start();
    ?>
    <div class="my-istymo-sci-info">
        <?php
        // Fonction helper pour récupérer une valeur avec plusieurs clés possibles
        $getValue = function($data, $keys) {
            if (is_string($keys)) $keys = [$keys];
            foreach ($keys as $key) {
                if (!empty($data[$key])) return $data[$key];
            }
            return null;
        };
        ?>

        <!-- Informations générales -->
        <div class="my-istymo-info-section">
            <h4 class="my-istymo-section-title">🏢 Informations de l'entreprise</h4>
            <div class="my-istymo-info-grid">
                <?php
                // Dénomination
                $denomination = $getValue($data, ['denomination', 'raisonSociale', 'nom_societe', 'nom']);
                if ($denomination): ?>
                <div class="my-istymo-info-item">
                    <label>🏢 Dénomination sociale</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($denomination); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php
                // SIREN
                $siren = $getValue($data, ['siren', 'numeroSiren', 'identifiant']);
                if ($siren): ?>
                <div class="my-istymo-info-item">
                    <label>🔢 Numéro SIREN</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($siren); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php
                // Dirigeant
                $dirigeant = $getValue($data, ['dirigeant', 'representant', 'gerant']);
                if ($dirigeant): ?>
                <div class="my-istymo-info-item">
                    <label>👤 Dirigeant principal</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($dirigeant); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Adresse -->
        <?php 
        $adresse = $getValue($data, ['adresse', 'adresseComplete', 'adresse_complete']);
        $ville = $getValue($data, ['ville', 'commune', 'localite']);
        $code_postal = $getValue($data, ['code_postal', 'codePostal', 'cp']);
        if ($adresse || $ville): ?>
        <div class="my-istymo-info-section">
            <h4 class="my-istymo-section-title">📍 Localisation</h4>
            <div class="my-istymo-info-grid">
                <?php if ($adresse): ?>
                <div class="my-istymo-info-item">
                    <label>📍 Adresse</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($adresse); ?>
                        <?php if ($ville): ?>
                            <br><small><?php echo esc_html($ville); ?>
                            <?php if ($code_postal): ?>
                                (<?php echo esc_html($code_postal); ?>)
                            <?php endif; ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php elseif ($ville): ?>
                <div class="my-istymo-info-item">
                    <label>🏘️ Ville</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($ville); ?>
                        <?php if ($code_postal): ?>
                            (<?php echo esc_html($code_postal); ?>)
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Informations financières et juridiques -->
        <?php 
        $capital = $getValue($data, ['capital', 'capitalSocial', 'montant_capital']);
        $forme_juridique = $getValue($data, ['forme_juridique', 'formeJuridique', 'type_societe']);
        $date_creation = $getValue($data, ['date_creation', 'dateCreation', 'date_immatriculation']);
        if ($capital || $forme_juridique || $date_creation): ?>
        <div class="my-istymo-info-section">
            <h4 class="my-istymo-section-title">💼 Informations juridiques</h4>
            <div class="my-istymo-info-grid">
                <?php if ($forme_juridique): ?>
                <div class="my-istymo-info-item">
                    <label>⚖️ Forme juridique</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($forme_juridique); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($capital): ?>
                <div class="my-istymo-info-item">
                    <label>💰 Capital social</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html(number_format($capital, 0, ',', ' ')); ?> €
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($date_creation): ?>
                <div class="my-istymo-info-item">
                    <label>📅 Date de création</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html(date('d/m/Y', strtotime($date_creation))); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Activité -->
        <?php 
        $activite = $getValue($data, ['activite', 'activitePrincipale', 'objet_social']);
        if ($activite): ?>
        <div class="my-istymo-info-section">
            <h4 class="my-istymo-section-title">🎯 Activité</h4>
            <div class="my-istymo-info-grid">
                <div class="my-istymo-info-item">
                    <label>💼 Activité principale</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($activite); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function my_istymo_ajax_get_status_change_validation() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    $new_status = sanitize_text_field($_POST['new_status']);
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $workflow_manager = Lead_Workflow::get_instance();
    
    $lead = $leads_manager->get_lead($lead_id);
    if (!$lead) {
        wp_send_json_error('Lead introuvable');
    }
    
    $validation = $workflow_manager->validate_transition($lead->status, $new_status, ['id' => $lead_id]);
    
    if (is_wp_error($validation)) {
        wp_send_json_success([
            'valid' => false,
            'message' => $validation->get_error_message(),
            'required_actions' => []
        ]);
    } else {
        wp_send_json_success([
            'valid' => true,
            'message' => 'Transition autorisée'
        ]);
    }
}

function my_istymo_ajax_get_workflow_step_info() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    $step_status = sanitize_text_field($_POST['step_status']);
    
    $workflow_manager = Lead_Workflow::get_instance();
    $status_manager = Lead_Status_Manager::get_instance();
    
    $statuses = $status_manager->get_all_statuses();
    $status_info = $statuses[$step_status] ?? [];
    
    $step_info = [
        'title' => $status_info['label'] ?? ucfirst($step_status),
        'description' => $status_info['description'] ?? 'Description de l\'étape ' . $step_status,
        'recommended_actions' => $workflow_manager->get_suggested_actions($step_status),
        'criteria' => $status_info['criteria'] ?? ['Critères à définir']
    ];
    
    wp_send_json_success($step_info);
}



// --- AFFICHAGE DU PANNEAU DPE ---
function dpe_afficher_panel() {
    // ✅ NOUVEAU : Charger le CSS DPE pour l'admin
    wp_enqueue_style(
        'dpe-admin-style',
        plugin_dir_url(__FILE__) . 'assets/css/dpe-style.css',
        array(),
        '1.0.2'
    );
    
    // Récupérer les codes postaux de l'utilisateur
    $codesPostauxArray = sci_get_user_postal_codes();

    // Préparer le contexte pour les templates
    $context = [
        'codesPostauxArray' => $codesPostauxArray,
        'config_manager' => dpe_config_manager(),
        'favoris_handler' => dpe_favoris_handler(),
        'dpe_handler' => dpe_handler(),
        'atts' => [] // ✅ AJOUTÉ : Variable atts pour éviter l'erreur
    ];

    // ✅ CHANGÉ : Utiliser le template simplifié qui fonctionne
    sci_load_template('dpe-panel-simple', $context);
}

// --- PAGE POUR AFFICHER LES FAVORIS DPE ---
function dpe_favoris_page() {
    // ✅ NOUVEAU : Charger le CSS DPE pour l'admin
    wp_enqueue_style(
        'dpe-admin-style',
        plugin_dir_url(__FILE__) . 'assets/css/dpe-style.css',
        array(),
        '1.0.2'
    );
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        echo '<div class="wrap"><div class="notice notice-error"><p>Vous devez être connecté pour voir vos favoris DPE.</p></div></div>';
        return;
    }
    
    // Récupérer les favoris
    $favoris = dpe_favoris_handler()->get_user_favoris($user_id);
    
    // Préparer le contexte pour le template
    $context = [
        'favoris' => $favoris,
        'favoris_handler' => dpe_favoris_handler(),
        'dpe_handler' => dpe_handler(),
        'atts' => [
            'title' => 'Mes Favoris DPE' // ✅ AJOUTÉ : Titre par défaut
        ]
    ];
    
    // Charger le template des favoris DPE
    sci_load_template('dpe-favoris', $context);
}

?>