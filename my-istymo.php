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

// ✅ NOUVEAU : Fonction de logging pour les lettres La Poste
function lettre_laposte_log($message) {
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/lettre-laposte/';
    $log_file = $log_dir . 'logs.txt';
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }
    
    // Formater le message avec timestamp
    $timestamp = current_time('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message" . PHP_EOL;
    
    // Écrire dans le fichier de log
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
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
    
    lettre_laposte_log("=== RECHERCHE AJAX INPI ===");
    lettre_laposte_log("Code postal: $code_postal");
    lettre_laposte_log("Page: $page");
    lettre_laposte_log("Taille page: $page_size");
    
    // Appeler la fonction de recherche avec pagination
    $resultats = sci_fetch_inpi_data_with_pagination($code_postal, $page, $page_size);
    
    if (is_wp_error($resultats)) {
        lettre_laposte_log("❌ Erreur recherche AJAX: " . $resultats->get_error_message());
        wp_send_json_error($resultats->get_error_message());
        return;
    }
    
    if (empty($resultats['data'])) {
        lettre_laposte_log("⚠️ Aucun résultat trouvé");
        wp_send_json_error('Aucun résultat trouvé pour ce code postal');
        return;
    }
    
    // Formater les résultats
    $formatted_results = sci_format_inpi_results($resultats['data']);
    
    lettre_laposte_log("✅ Recherche AJAX réussie: " . count($formatted_results) . " résultats formatés");
    lettre_laposte_log("Pagination: " . json_encode($resultats['pagination']));
    
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

    lettre_laposte_log("=== REQUÊTE API INPI AVEC PAGINATION ===");
    lettre_laposte_log("URL: $url");
    lettre_laposte_log("Token: " . substr($token, 0, 20) . "...");

    // Effectue la requête HTTP GET via WordPress HTTP API
    $reponse = wp_remote_get($url, $args);

    // Vérifie s'il y a une erreur réseau
    if (is_wp_error($reponse)) {
        lettre_laposte_log("❌ Erreur réseau INPI: " . $reponse->get_error_message());
        return new WP_Error('requete_invalide', 'Erreur lors de la requête : ' . $reponse->get_error_message());
    }

    // Récupère le code HTTP et le corps de la réponse
    $code_http = wp_remote_retrieve_response_code($reponse);
    $corps     = wp_remote_retrieve_body($reponse);
    $headers   = wp_remote_retrieve_headers($reponse);

    lettre_laposte_log("Code HTTP INPI: $code_http");
    lettre_laposte_log("Headers INPI: " . json_encode($headers->getAll()));

    // ✅ NOUVEAU : Gestion automatique des erreurs d'authentification
    if ($code_http === 401 || $code_http === 403) {
        lettre_laposte_log("🔄 Erreur d'authentification INPI détectée, tentative de régénération du token...");
        
        // Tenter de régénérer le token
        $new_token = $inpi_token_manager->handle_auth_error();
        
        if ($new_token) {
            lettre_laposte_log("✅ Nouveau token généré, nouvelle tentative de requête...");
            
            // Refaire la requête avec le nouveau token
            $args['headers']['Authorization'] = 'Bearer ' . $new_token;
            $reponse = wp_remote_get($url, $args);
            
            if (is_wp_error($reponse)) {
                return new WP_Error('requete_invalide', 'Erreur lors de la requête après régénération du token : ' . $reponse->get_error_message());
            }
            
            $code_http = wp_remote_retrieve_response_code($reponse);
            $corps = wp_remote_retrieve_body($reponse);
            $headers = wp_remote_retrieve_headers($reponse);
            
            lettre_laposte_log("Code HTTP après régénération: $code_http");
        } else {
            return new WP_Error('token_regeneration_failed', 'Impossible de régénérer le token INPI. Vérifiez vos identifiants.');
        }
    }

    // Si le code HTTP n'est toujours pas 200 OK, retourne une erreur
    if ($code_http !== 200) {
        lettre_laposte_log("❌ Erreur API INPI finale: Code $code_http - $corps");
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

    lettre_laposte_log("✅ Requête INPI réussie");
    lettre_laposte_log("Données: " . (is_array($donnees) ? count($donnees) : 0) . " résultats");
    lettre_laposte_log("Pagination: " . json_encode($pagination_info));

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
    lettre_laposte_log("=== ENVOI LETTRE POUR {$entry['denomination']} ===");
    lettre_laposte_log("Payload envoyé: " . json_encode($payload_for_log, JSON_PRETTY_PRINT));

    // Envoyer via l'API La Poste
    $response = envoyer_lettre_via_api_la_poste_my_istymo($payload, $token);

    // Logger la réponse complète
    lettre_laposte_log("Réponse complète API: " . json_encode($response, JSON_PRETTY_PRINT));

    if ($response['success']) {
        lettre_laposte_log("✅ SUCCÈS pour {$entry['denomination']} - UID: " . ($response['uid'] ?? 'N/A'));
        
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

        lettre_laposte_log("❌ ERREUR pour {$entry['denomination']}: $error_msg");
        lettre_laposte_log("Code HTTP: " . ($response['code'] ?? 'N/A'));
        lettre_laposte_log("Message détaillé: " . json_encode($response['message'] ?? [], JSON_PRETTY_PRINT));
        
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
    lettre_laposte_log("=== REQUÊTE API LA POSTE ===");
    lettre_laposte_log("URL: $api_url");
    lettre_laposte_log("Headers: " . json_encode($headers, JSON_PRETTY_PRINT));
    lettre_laposte_log("Body size: " . strlen($body) . " caractères");

    $response = wp_remote_post($api_url, $args);

    // Gestion des erreurs WordPress
    if (is_wp_error($response)) {
        lettre_laposte_log("❌ Erreur WordPress HTTP: " . $response->get_error_message());
        return [
            'success' => false,
            'error'   => $response->get_error_message(),
        ];
    }

    $code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_headers = wp_remote_retrieve_headers($response);
    
    // Logger la réponse complète
    lettre_laposte_log("=== RÉPONSE API LA POSTE ===");
    lettre_laposte_log("Code HTTP: $code");
    lettre_laposte_log("Headers de réponse: " . json_encode($response_headers->getAll(), JSON_PRETTY_PRINT));
    lettre_laposte_log("Body de réponse: $response_body");

    $data = json_decode($response_body, true);
    
    // Logger les données décodées
    lettre_laposte_log("Données JSON décodées: " . json_encode($data, JSON_PRETTY_PRINT));

    if ($code >= 200 && $code < 300) {
        lettre_laposte_log("✅ Succès API (code $code)");
        return [
            'success' => true,
            'data'    => $data,
            'uid'     => $data['uid'] ?? null, // ✅ Extraction de l'UID
        ];
    } else {
        lettre_laposte_log("❌ Erreur API (code $code)");
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
    $log_file = $upload_dir['basedir'] . '/lettre-laposte/logs.txt';
    
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
        'log_stats' => $log_stats
    ];
    
    // Charger le template des logs
    sci_load_template('sci-logs', $context);
    
    // Gestion de l'effacement des logs
    if (isset($_GET['clear']) && $_GET['clear'] == '1') {
        if (file_exists($log_file)) {
            unlink($log_file);
            echo '<div class="notice notice-success"><p>Logs effacés avec succès.</p></div>';
            echo '<script>window.location.href = "' . admin_url('admin.php?page=sci-logs') . '";</script>';
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

    lettre_laposte_log("=== DÉBUT GÉNÉRATION PDFs ===");
    lettre_laposte_log("Titre campagne: " . ($data['title'] ?? 'N/A'));
    lettre_laposte_log("Nombre d'entrées: " . count($data['entries']));

    // Créer la campagne en base de données
    $campaign_manager = sci_campaign_manager();
    $campaign_id = $campaign_manager->create_campaign($data['title'], $data['content'], $data['entries']);
    
    if (is_wp_error($campaign_id)) {
        lettre_laposte_log("❌ Erreur création campagne: " . $campaign_id->get_error_message());
        wp_send_json_error("Erreur lors de la création de la campagne : " . $campaign_id->get_error_message());
        return;
    }

    lettre_laposte_log("✅ Campagne créée avec ID: $campaign_id");

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
        lettre_laposte_log("📁 Dossier créé: $pdf_dir");
    }

    $pdf_links = [];

    foreach ($data['entries'] as $index => $entry) {
        try {
            lettre_laposte_log("📄 Génération PDF " . ($index + 1) . "/" . count($data['entries']) . " pour: " . ($entry['denomination'] ?? 'N/A'));
            
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
                
                lettre_laposte_log("✅ PDF généré avec succès : $filename pour {$entry['denomination']}");
            } else {
                lettre_laposte_log("❌ Erreur : PDF non créé pour {$entry['denomination']}");
            }

        } catch (Exception $e) {
            lettre_laposte_log("❌ Erreur lors de la génération PDF pour {$entry['denomination']}: " . $e->getMessage());
        }
    }

    if (empty($pdf_links)) {
        lettre_laposte_log("❌ Aucun PDF généré");
        wp_send_json_error('Aucun PDF n\'a pu être généré');
        return;
    }

    lettre_laposte_log("✅ Génération terminée : " . count($pdf_links) . " PDFs créés sur " . count($data['entries']) . " demandés");

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

    // ✅ CHANGÉ : Utiliser le template simplifié basé sur votre code
    sci_load_template('dpe-panel-simple', $context);
}

// --- PAGE POUR AFFICHER LES FAVORIS DPE ---
function dpe_favoris_page() {
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