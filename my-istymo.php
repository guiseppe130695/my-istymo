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
require_once plugin_dir_path(__FILE__) . 'includes/favoris-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/config-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/campaign-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/woocommerce-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/inpi-token-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/template-loader.php';

// ✅ NOUVEAU : Inclure les fichiers DPE
require_once plugin_dir_path(__FILE__) . 'includes/dpe-config-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpe-favoris-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpe-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpe-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpe-campaign-manager.php';


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

    // ✅ NOUVEAU : Ajouter une page pour les campagnes DPE
    add_submenu_page(
        'dpe-panel',
        'Campagnes DPE',
        'Mes Campagnes DPE',
        'read',
        'dpe-campaigns',
        'dpe_campaigns_page'
    );
}


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

// --- AFFICHAGE DU PANNEAU DPE ---
function dpe_afficher_panel() {
    // ✅ NOUVEAU : Charger le CSS DPE pour l'admin
    wp_enqueue_style(
        'dpe-admin-style',
        plugin_dir_url(__FILE__) . 'assets/css/dpe-style.css',
        array(),
        '1.0.2'
    );
    
    // ✅ NOUVEAU : Charger les scripts JavaScript nécessaires
    wp_enqueue_script(
        'dpe-selection-system',
        plugin_dir_url(__FILE__) . 'assets/js/dpe-selection-system.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    wp_enqueue_script(
        'dpe-lettre-script',
        plugin_dir_url(__FILE__) . 'assets/js/dpe-lettre.js',
        array('jquery', 'dpe-selection-system'),
        '1.0.0',
        true
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

// ✅ NOUVEAU : PAGE POUR AFFICHER LES CAMPAGNES DPE ---
function dpe_campaigns_page() {
    // ✅ NOUVEAU : Charger le CSS pour le panel des campagnes DPE
    wp_enqueue_style(
        'dpe-campaigns-admin',
        plugin_dir_url(__FILE__) . 'assets/css/dpe-campaigns-admin.css',
        array(),
        '1.0.0'
    );
    
    // Vérifier que l'utilisateur est connecté
    if (!is_user_logged_in()) {
        wp_die('Vous devez être connecté pour accéder à cette page.');
    }
    
    // Récupérer le gestionnaire de campagnes DPE
    $campaign_manager = dpe_campaign_manager();
    if (!$campaign_manager) {
        wp_die('Erreur : Gestionnaire de campagnes DPE non disponible.');
    }
    
    $current_user_id = get_current_user_id();
    $view_campaign_id = isset($_GET['view']) ? intval($_GET['view']) : null;
    
    // Mode vue détaillée d'une campagne
    if ($view_campaign_id) {
        $campaign_details = $campaign_manager->get_campaign_details($view_campaign_id, $current_user_id);
        
        if (!$campaign_details) {
            wp_die('Campagne non trouvée ou accès non autorisé.');
        }
        
        // Passer les données au template
        $context = array(
            'campaign_details' => $campaign_details,
            'view_mode' => true,
            'title' => '📬 Campagne DPE'
        );
        
        sci_load_template('dpe-campaigns', $context);
        return;
    }
    
    // Mode liste des campagnes
    $campaigns = $campaign_manager->get_user_campaigns($current_user_id);
    
    // ✅ DEBUG : Ajouter des informations de debug
    error_log("DPE Campaigns Debug - User ID: $current_user_id");
    error_log("DPE Campaigns Debug - Campaigns count: " . count($campaigns));
    if ($campaigns) {
        error_log("DPE Campaigns Debug - First campaign: " . json_encode($campaigns[0]));
    }
    
    $context = array(
        'campaigns' => $campaigns,
        'view_mode' => false,
        'title' => '📬 Mes Campagnes DPE',
        'show_empty_message' => true
    );
    
    sci_load_template('dpe-campaigns', $context);
}

// --- NOUVELLES FONCTIONS AJAX POUR DPE ---

// --- NOUVELLE FONCTION AJAX POUR ENVOYER UNE LETTRE DPE VIA L'API LA POSTE ---
add_action('wp_ajax_dpe_envoyer_lettre_laposte', 'dpe_envoyer_lettre_laposte_ajax');
add_action('wp_ajax_nopriv_dpe_envoyer_lettre_laposte', 'dpe_envoyer_lettre_laposte_ajax');

function dpe_envoyer_lettre_laposte_ajax() {
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
    $campaign_manager = dpe_campaign_manager();
    if (!$campaign_manager) {
        wp_send_json_error('Gestionnaire de campagnes DPE non disponible');
        return;
    }
    
    $expedition_data = $campaign_manager->get_user_expedition_data();
    
    // Vérifier que les données essentielles sont présentes
    $validation_errors = $campaign_manager->validate_expedition_data($expedition_data);
    if (!empty($validation_errors)) {
        wp_send_json_error('Données expéditeur incomplètes : ' . implode(', ', $validation_errors));
        return;
    }
    
    // Récupérer les paramètres configurés depuis le gestionnaire de configuration
    $config_manager = dpe_config_manager();
    $laposte_params = $config_manager->get_laposte_payload_params();
    
    // Préparer le payload pour l'API La Poste avec les paramètres dynamiques
    $payload = array_merge($laposte_params, [
        // Adresse expéditeur (récupérée depuis le profil utilisateur)
        "adresse_expedition" => $expedition_data,

        // Adresse destinataire (propriétaire de la maison)
        "adresse_destination" => [
            "civilite" => "M.",
            "prenom" => "",
            "nom" => "Propriétaire",
            "nom_societe" => "",
            "adresse_ligne1" => $entry['adresse'] ?? '',
            "adresse_ligne2" => "",
            "code_postal" => $entry['code_postal'] ?? '',
            "ville" => $entry['commune'] ?? '',
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
    my_istymo_log("=== ENVOI LETTRE DPE POUR {$entry['adresse']} ===", 'dpe_laposte');
    my_istymo_log("Payload envoyé: " . json_encode($payload_for_log, JSON_PRETTY_PRINT), 'dpe_laposte');

    // Envoyer via l'API La Poste
    $response = envoyer_lettre_via_api_la_poste_my_istymo($payload, $token);

    // Logger la réponse complète
    my_istymo_log("Réponse complète API: " . json_encode($response, JSON_PRETTY_PRINT), 'dpe_laposte');

    if ($response['success']) {
        my_istymo_log("✅ SUCCÈS pour {$entry['adresse']} - UID: " . ($response['uid'] ?? 'N/A'), 'dpe_laposte');
        
        // Mettre à jour le statut dans la base de données
        if ($campaign_id > 0) {
            $campaign_manager->update_letter_status(
                $campaign_id, 
                $entry['numero_dpe'], 
                'sent', 
                $response['uid'] ?? null
            );
        }
        
        wp_send_json_success([
            'message' => 'Lettre envoyée avec succès',
            'uid' => $response['uid'] ?? 'non disponible',
            'adresse' => $entry['adresse']
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

        my_istymo_log("❌ ERREUR pour {$entry['adresse']}: $error_msg", 'dpe_laposte');
        my_istymo_log("Code HTTP: " . ($response['code'] ?? 'N/A'), 'dpe_laposte');
        my_istymo_log("Message détaillé: " . json_encode($response['message'] ?? [], JSON_PRETTY_PRINT), 'dpe_laposte');
        
        // Mettre à jour le statut d'erreur dans la base de données
        if ($campaign_id > 0) {
            $campaign_manager->update_letter_status(
                $campaign_id, 
                $entry['numero_dpe'], 
                'failed', 
                null, 
                $error_msg
            );
        }
        
        wp_send_json_error($error_msg);
    }
}

// --- NOUVELLE FONCTION AJAX POUR GÉNÉRER LES PDFS DPE ---
add_action('wp_ajax_dpe_generer_pdfs', 'dpe_generer_pdfs_ajax');
add_action('wp_ajax_nopriv_dpe_generer_pdfs', 'dpe_generer_pdfs_ajax');

function dpe_generer_pdfs_ajax() {
    // Vérification des données reçues
    if (!isset($_POST['data'])) {
        wp_send_json_error('Données manquantes');
        return;
    }

    $campaign_data = json_decode(stripslashes($_POST['data']), true);
    
    if (!$campaign_data || !isset($campaign_data['entries']) || !isset($campaign_data['content'])) {
        wp_send_json_error('Données de campagne invalides');
        return;
    }

    // Vérifier que l'utilisateur est connecté
    if (!is_user_logged_in()) {
        wp_send_json_error('Utilisateur non connecté');
        return;
    }

    // Récupérer les managers nécessaires
    $campaign_manager = dpe_campaign_manager();
    if (!$campaign_manager) {
        wp_send_json_error('Gestionnaire de campagnes DPE non disponible');
        return;
    }

    try {
        // ✅ CORRIGÉ : Vérifier et forcer l'ID utilisateur correct
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            wp_send_json_error('ID utilisateur invalide - veuillez vous reconnecter');
            return;
        }
        
        // Log pour debug
        my_istymo_log("DPE PDF Generation - User ID: $current_user_id", 'dpe_campaigns');
        
        // Créer la campagne en base de données
        $campaign_id = $campaign_manager->create_campaign([
            'title' => $campaign_data['title'],
            'content' => $campaign_data['content'],
            'user_id' => $current_user_id, // ← Utiliser l'ID vérifié
            'type' => 'dpe_maison'
        ]);

        if (!$campaign_id) {
            wp_send_json_error('Erreur lors de la création de la campagne');
            return;
        }

        // Générer les PDFs
        if (!class_exists('TCPDF')) {
            require_once plugin_dir_path(__FILE__) . 'lib/tcpdf/tcpdf.php';
        }
        
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/campagnes-dpe/';
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }
        
        $files = [];
        
        foreach ($campaign_data['entries'] as $index => $entry) {
            // Générer le contenu personnalisé
            $texte = $campaign_data['content'];
            
            // Ajouter les informations spécifiques à la DPE
            $texte .= "\n\nInformations sur le bien :";
            $texte .= "\n- Adresse : " . ($entry['adresse'] ?? 'Non spécifiée');
            $texte .= "\n- Commune : " . ($entry['commune'] ?? 'Non spécifiée');
            $texte .= "\n- Étiquette DPE : " . ($entry['etiquette_dpe'] ?? 'Non spécifiée');
            $texte .= "\n- Étiquette GES : " . ($entry['etiquette_ges'] ?? 'Non spécifiée');
            $texte .= "\n- Surface : " . ($entry['surface'] ?? 'Non spécifiée') . " m²";
            
            $pdf = new TCPDF();
            $pdf->SetCreator('DPE Plugin');
            $pdf->SetAuthor('DPE Plugin');
            $pdf->SetTitle('Lettre pour ' . ($entry['adresse'] ?? 'DPE'));
            
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(TRUE, 25);
            
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            $pdf->writeHTML(nl2br(htmlspecialchars($texte)), true, false, true, false, '');
            
            $filename = sanitize_file_name($entry['adresse'] . '-' . time() . '-' . $index) . '.pdf';
            $pdf_path = $pdf_dir . $filename;
            
            $pdf->Output($pdf_path, 'F');
            
            if (file_exists($pdf_path)) {
                $files[] = [
                    'url' => $upload_dir['baseurl'] . '/campagnes-dpe/' . $filename,
                    'path' => $pdf_path,
                    'entry' => $entry
                ];
                
                // Ajouter l'entrée à la campagne
                $campaign_manager->add_campaign_entry($campaign_id, $entry);
            }
        }

        wp_send_json_success([
            'files' => $files,
            'campaign_id' => $campaign_id
        ]);

    } catch (Exception $e) {
        my_istymo_log("❌ Erreur génération PDFs DPE: " . $e->getMessage(), 'dpe_laposte');
        wp_send_json_error('Erreur lors de la génération des PDFs: ' . $e->getMessage());
    }
}

// --- NOUVELLE FONCTION AJAX POUR CRÉER UNE COMMANDE WOOCOMMERCE DPE ---
add_action('wp_ajax_dpe_create_order', 'dpe_create_order_ajax');
add_action('wp_ajax_nopriv_dpe_create_order', 'dpe_create_order_ajax');

function dpe_create_order_ajax() {
    // ✅ AJOUTÉ : Log de début pour debug
    my_istymo_log("🚀 dpe_create_order_ajax() appelée", 'dpe_laposte');
    my_istymo_log("POST data: " . print_r($_POST, true), 'dpe_laposte');
    
    // ✅ AJOUTÉ : Vérification du nonce (sécurité)
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dpe_campaign_nonce')) {
        my_istymo_log("❌ Nonce invalide pour DPE", 'dpe_laposte');
        wp_send_json_error('Nonce invalide');
        return;
    }
    
    // Vérification des données reçues (compatible avec le système SCI)
    if (!isset($_POST['campaign_data'])) {
        wp_send_json_error('Données de campagne manquantes');
        return;
    }

    $campaign_data = json_decode(stripslashes($_POST['campaign_data']), true);
    
    if (!$campaign_data || !isset($campaign_data['entries']) || !isset($campaign_data['title']) || !isset($campaign_data['content'])) {
        wp_send_json_error('Données de campagne invalides');
        return;
    }

    $entries = $campaign_data['entries'];
    $title = sanitize_text_field($campaign_data['title']);
    $content = sanitize_textarea_field($campaign_data['content']);

    if (!$entries || !is_array($entries)) {
        wp_send_json_error('Données d\'entrées invalides');
        return;
    }

    // Vérifier que l'utilisateur est connecté
    if (!is_user_logged_in()) {
        wp_send_json_error('Utilisateur non connecté');
        return;
    }

    // Vérifier que WooCommerce est disponible
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce n\'est pas disponible');
        return;
    }
    
    // ✅ AJOUTÉ : Log pour debug
    my_istymo_log("=== CRÉATION COMMANDE DPE WOOCOMMERCE ===", 'dpe_laposte');
    my_istymo_log("Utilisateur: " . get_current_user_id(), 'dpe_laposte');
    my_istymo_log("Nombre DPE: " . count($entries), 'dpe_laposte');
    my_istymo_log("Titre campagne: " . $title, 'dpe_laposte');

    try {
        // Créer la commande WooCommerce
        $order = wc_create_order();
        
        // Ajouter le produit DPE
        $product_id = get_option('dpe_product_id', 0);
        if (!$product_id || !wc_get_product($product_id)) {
            my_istymo_log("🔄 Produit DPE introuvable, création...", 'dpe_laposte');
            // Créer le produit s'il n'existe pas
            $product_id = create_dpe_product();
        }
        
        if (!$product_id) {
            throw new Exception('Impossible de créer ou récupérer le produit DPE');
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            throw new Exception('Produit DPE introuvable après création');
        }
        
        my_istymo_log("📦 Ajout du produit DPE (ID: $product_id) à la commande", 'dpe_laposte');
        $order->add_product($product, count($entries));
        
        // Définir l'adresse de facturation
        $user_id = get_current_user_id();
        $order->set_address(array(
            'first_name' => get_user_meta($user_id, 'first_name', true),
            'last_name'  => get_user_meta($user_id, 'last_name', true),
            'email'      => get_user_meta($user_id, 'user_email', true),
            'phone'      => get_user_meta($user_id, 'phone', true),
            'address_1'  => get_field('adresse_user', 'user_' . $user_id),
            'address_2'  => get_field('adresse2_user', 'user_' . $user_id),
            'city'       => get_field('ville_user', 'user_' . $user_id),
            'postcode'   => get_field('cp_user', 'user_' . $user_id),
            'country'    => 'FR'
        ), 'billing');
        
        // Définir l'adresse de livraison (même que facturation)
        $order->set_address(array(
            'first_name' => get_user_meta($user_id, 'first_name', true),
            'last_name'  => get_user_meta($user_id, 'last_name', true),
            'email'      => get_user_meta($user_id, 'user_email', true),
            'phone'      => get_user_meta($user_id, 'phone', true),
            'address_1'  => get_field('adresse_user', 'user_' . $user_id),
            'address_2'  => get_field('adresse2_user', 'user_' . $user_id),
            'city'       => get_field('ville_user', 'user_' . $user_id),
            'postcode'   => get_field('cp_user', 'user_' . $user_id),
            'country'    => 'FR'
        ), 'shipping');
        
        // Ajouter les métadonnées de la campagne
        $order->update_meta_data('_dpe_campaign_title', $title);
        $order->update_meta_data('_dpe_campaign_content', $content);
        $order->update_meta_data('_dpe_campaign_entries', $entries);
        $order->update_meta_data('_dpe_campaign_type', 'dpe_maison');
        $order->update_meta_data('_dpe_campaign_status', 'pending');
        
        // Calculer les totaux
        $order->calculate_totals();
        
        // Sauvegarder la commande
        $order->save();
        
        // ✅ MODIFIÉ : Générer l'URL de paiement pour checkout embarqué
        $checkout_url = $order->get_checkout_payment_url() . '&embedded=1&hide_admin_bar=1';
        
        my_istymo_log("✅ Commande DPE créée avec ID: " . $order->get_id(), 'dpe_laposte');
        
        wp_send_json_success(array(
            'order_id' => $order->get_id(),
            'checkout_url' => $checkout_url,
            'total' => $order->get_total(),
            'dpe_count' => count($entries)
        ));

    } catch (Exception $e) {
        my_istymo_log("❌ Erreur création commande DPE: " . $e->getMessage(), 'dpe_laposte');
        my_istymo_log("Stack trace: " . $e->getTraceAsString(), 'dpe_laposte');
        wp_send_json_error('Erreur lors de la création de la commande: ' . $e->getMessage());
    }
}

/**
 * Créer le produit DPE s'il n'existe pas
 */
function create_dpe_product() {
    try {
        my_istymo_log("🔄 Création du produit DPE...", 'dpe_laposte');
        
        // Vérifier que WooCommerce est disponible
        if (!class_exists('WC_Product_Simple')) {
            my_istymo_log("❌ WC_Product_Simple non disponible", 'dpe_laposte');
            return false;
        }
        
        $product = new WC_Product_Simple();
        $product->set_name('Campagne DPE - Envoi de courriers');
        $product->set_description('Service d\'envoi de courriers pour prospecter les propriétaires de maisons via les DPE');
        $product->set_short_description('Envoi de courriers personnalisés vers les propriétaires de maisons');
        $product->set_price(5.00);
        $product->set_regular_price(5.00);
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        
        $product_id = $product->save();
        
        if (is_wp_error($product_id)) {
            my_istymo_log("❌ Erreur création produit DPE: " . $product_id->get_error_message(), 'dpe_laposte');
            return false;
        }
        
        // Sauvegarder l'ID du produit
        update_option('dpe_product_id', $product_id);
        
        my_istymo_log("✅ Produit DPE créé avec ID: $product_id", 'dpe_laposte');
        
        return $product_id;
        
    } catch (Exception $e) {
        my_istymo_log("❌ Exception création produit DPE: " . $e->getMessage(), 'dpe_laposte');
        return false;
    }
}

// ✅ AJOUTÉ : Fonction AJAX pour vérifier le statut de paiement DPE
add_action('wp_ajax_dpe_check_payment_status', 'dpe_check_payment_status_ajax');
add_action('wp_ajax_nopriv_dpe_check_payment_status', 'dpe_check_payment_status_ajax');

function dpe_check_payment_status_ajax() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dpe_campaign_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }
    
    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_send_json_error('Commande introuvable');
        return;
    }
    
    $status = $order->get_status();
    
    // Considérer comme "completed" si le paiement est terminé
    $is_completed = in_array($status, ['completed', 'processing']);
    
    wp_send_json_success(array(
        'status' => $is_completed ? 'paid' : 'pending',
        'order_status' => $status
    ));
}

// ✅ AJOUTÉ : Hooks pour traiter les commandes DPE payées
add_action('woocommerce_order_status_completed', 'dpe_process_paid_campaign');
add_action('woocommerce_order_status_processing', 'dpe_process_paid_campaign');
add_action('woocommerce_payment_complete', 'dpe_process_paid_campaign');

function dpe_process_paid_campaign($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    // Vérifier si c'est une commande DPE
    $campaign_type = $order->get_meta('_dpe_campaign_type');
    if ($campaign_type !== 'dpe_maison') {
        return; // Pas une commande DPE
    }
    
    // Vérifier si déjà traitée
    $campaign_status = $order->get_meta('_dpe_campaign_status');
    if (in_array($campaign_status, ['processed', 'processing', 'completed'])) {
        my_istymo_log("ℹ️ Commande DPE #$order_id déjà traitée (statut: $campaign_status)", 'dpe_laposte');
        return;
    }
    
    my_istymo_log("🔄 Traitement de la commande DPE payée #$order_id", 'dpe_laposte');
    
    // Marquer comme en cours de traitement
    $order->update_meta_data('_dpe_campaign_status', 'processing');
    $order->save();
    
    try {
        // Récupérer les données de la campagne
        $campaign_title = $order->get_meta('_dpe_campaign_title');
        $campaign_content = $order->get_meta('_dpe_campaign_content');
        $campaign_entries = $order->get_meta('_dpe_campaign_entries');
        
        if (!$campaign_entries || !is_array($campaign_entries)) {
            throw new Exception('Données de campagne DPE invalides');
        }
        
        // Créer la campagne dans la base de données
        $campaign_manager = dpe_campaign_manager();
        if (!$campaign_manager) {
            throw new Exception('Gestionnaire de campagnes DPE non disponible');
        }
        
        // ✅ CORRIGÉ : Vérifier et forcer l'ID utilisateur correct
        $customer_id = $order->get_customer_id();
        if (!$customer_id) {
            throw new Exception('ID client de la commande invalide');
        }
        
        // Log pour debug
        my_istymo_log("DPE Order Processing - Customer ID: $customer_id, Order ID: $order_id", 'dpe_campaigns');
        
        $campaign_id = $campaign_manager->create_campaign([
            'title' => $campaign_title,
            'content' => $campaign_content,
            'entries' => $campaign_entries,
            'user_id' => $customer_id, // ← Utiliser l'ID client vérifié
            'type' => 'dpe_maison'
        ]);
        
        if (!$campaign_id) {
            throw new Exception('Impossible de créer la campagne DPE');
        }
        
        my_istymo_log("✅ Campagne DPE créée avec ID: $campaign_id", 'dpe_laposte');
        
        // Marquer comme terminée
        $order->update_meta_data('_dpe_campaign_status', 'completed');
        $order->update_meta_data('_dpe_campaign_id', $campaign_id);
        $order->save();
        
        my_istymo_log("✅ Commande DPE #$order_id traitée avec succès", 'dpe_laposte');
        
    } catch (Exception $e) {
        my_istymo_log("❌ Erreur traitement commande DPE #$order_id: " . $e->getMessage(), 'dpe_laposte');
        
        // Marquer comme en erreur
        $order->update_meta_data('_dpe_campaign_status', 'error');
        $order->update_meta_data('_dpe_campaign_error', $e->getMessage());
        $order->save();
    }
}

// ✅ AJOUTÉ : Gestion CORS pour les requêtes AJAX
add_action('init', 'my_istymo_handle_cors');

// ✅ ÉTENDU : CORS pour toutes les actions AJAX du plugin
$ajax_actions = [
    'dpe_create_order', 'dpe_check_payment_status',
    'sci_manage_favoris', 'dpe_manage_favoris',
    'dpe_search_ajax', 'test_dpe_api',
    'dpe_add_favori', 'dpe_remove_favori', 'dpe_get_favoris',
    'sci_add_favori', 'sci_remove_favori', 'sci_get_favoris'
];

foreach ($ajax_actions as $action) {
    add_action('wp_ajax_nopriv_' . $action, 'my_istymo_handle_cors_preflight', 1);
    add_action('wp_ajax_' . $action, 'my_istymo_handle_cors_preflight', 1);
}

function my_istymo_handle_cors() {
    // Vérifier si c'est une requête AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // Autoriser tous les domaines pour le développement
        $allowed_origins = array(
            'http://my-istymo.local',
            'http://my-istymo.local:10004',
            'http://my-istymo.local:10005',
            'http://my-istymo.local:10006',
            'http://localhost',
            'http://localhost:10004',
            'http://localhost:10005',
            'http://localhost:10006'
        );
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
        }
        
        // Gérer les requêtes OPTIONS (preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}

function my_istymo_handle_cors_preflight() {
    // Gestion CORS spécifique pour les actions AJAX DPE
    $allowed_origins = array(
        'http://my-istymo.local',
        'http://my-istymo.local:10004',
        'http://my-istymo.local:10005',
        'http://my-istymo.local:10006',
        'http://localhost',
        'http://localhost:10004',
        'http://localhost:10005',
        'http://localhost:10006'
    );
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
    }
    
    // Gérer les requêtes OPTIONS (preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Inclure les nouveaux fichiers DPE
require_once plugin_dir_path(__FILE__) . 'includes/dpe-campaign-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpe-config-manager.php';

?>