<?php
/*
Plugin Name: My Istymo
Description: Plugin personnalisé SCI avec un panneau admin et un sélecteur de codes postaux.
Version: 1.6
Author: Brio Guiseppe
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
License: GPL v2 or later
Text Domain: my-istymo
Network: false
*/

if (!defined('ABSPATH')) exit; // Sécurité : Empêche l'accès direct au fichier

// ✅ VÉRIFICATIONS DE DÉPENDANCES POUR LA PRODUCTION
add_action('admin_init', 'my_istymo_check_dependencies');

function my_istymo_check_dependencies() {
    $missing_deps = [];
    
    // Vérifier WooCommerce
    if (!class_exists('WooCommerce')) {
        $missing_deps[] = 'WooCommerce';
    }
    
    // Vérifier ACF (Advanced Custom Fields)
    if (!function_exists('get_field')) {
        $missing_deps[] = 'Advanced Custom Fields (ACF)';
    }
    
    // Afficher les avertissements si des dépendances manquent
    if (!empty($missing_deps)) {
        add_action('admin_notices', function() use ($missing_deps) {
            $deps_list = implode(', ', $missing_deps);
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>My Istymo :</strong> Les plugins suivants sont requis pour un fonctionnement optimal : ' . esc_html($deps_list) . '</p>';
            echo '</div>';
        });
    }
}

// popup-lettre.php supprimé - fonctions intégrées dans le système principal

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

// ✅ NOUVEAU : Fonction de log universelle pour tout le plugin (PRODUCTION READY)
function my_istymo_log($message, $context = 'general') {
    // Logs seulement en mode debug ou si explicitement activés
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        // En production, logs seulement pour les erreurs critiques
        if ($context !== 'error' && $context !== 'critical') {
            return;
        }
    }
    
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
// unified-leads-test.php supprimé - fichier de test pour le développement uniquement

// ✅ PHASE 3 : Système d'actions et workflow
require_once plugin_dir_path(__FILE__) . 'includes/lead-actions-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/lead-workflow.php';

// ✅ APRÈS le système unifié : Inclure les gestionnaires de favoris
require_once plugin_dir_path(__FILE__) . 'includes/favoris-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/dpe-favoris-handler.php';

// ✅ NOUVEAU : Inclure les gestionnaires Lead Vendeur
require_once plugin_dir_path(__FILE__) . 'includes/lead-vendeur-config-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/lead-vendeur-favoris-handler.php';

// --- Ajout du menu SCI dans l'admin WordPress ---
add_action('admin_menu', 'sci_ajouter_menu');

function sci_ajouter_menu() {
    add_menu_page(
        'SCI',
        'SCI',
        'read',
        'sci-panel',
        'sci_afficher_panel',
        'dashicons-building',
        -1
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
        'dashicons-lightbulb',
        -2
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
           -3
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
       
       // ✅ NOUVEAU : Menu Lead Vendeur
       add_menu_page(
           'Lead Vendeur',
           'Lead Vendeur',
           'read',
           'lead-vendeur',
           'lead_vendeur_page',
           'dashicons-businessman',
           -4
       );
       
       // ✅ NOUVEAU : Sous-menu Configuration Lead Vendeur
       add_submenu_page(
           'lead-vendeur',
           'Configuration Lead Vendeur',
           'Configuration',
           'manage_options',
           'lead-vendeur-config',
           'lead_vendeur_config_page'
       );
       
       // ✅ NOUVEAU : Menu Carte de Succession
       add_menu_page(
           'Carte de Succession',
           'Carte de Succession',
           'read',
           'carte-succession',
           'carte_succession_page',
           'dashicons-chart-area',
           -5
       );
       
       // ✅ NOUVEAU : Menu Lead Parrainage
       add_menu_page(
           'Lead Parrainage',
           'Lead Parrainage',
           'read',
           'lead-parrainage',
           'lead_parrainage_page',
           'dashicons-groups',
           -6
       );
}


       // ✅ PHASE 1 : Inclure la page d'administration des leads unifiés
       require_once plugin_dir_path(__FILE__) . 'templates/unified-leads-admin.php';
       
       // ✅ NOUVEAU : Fonction pour détecter si un champ est un téléphone
       function is_phone_field($field_label, $value) {
           if (empty($value)) return false;
           
           // Vérifier le label du champ
           $phone_keywords = ['téléphone', 'telephone', 'phone', 'tel', 'mobile', 'portable', 'fixe'];
           $label_lower = strtolower($field_label);
           
           foreach ($phone_keywords as $keyword) {
               if (strpos($label_lower, $keyword) !== false) {
                   return true;
               }
           }
           
           // Vérifier si la valeur ressemble à un numéro de téléphone français
           $clean_value = preg_replace('/[^0-9+]/', '', $value);
           if (preg_match('/^(0[1-9]|\\+33[1-9]|33[1-9])[0-9]{8}$/', $clean_value)) {
               return true;
           }
           
           return false;
       }
       
       // ✅ NOUVEAU : Fonction pour formater un numéro de téléphone pour l'appel direct
       function format_phone_for_dialing($phone) {
           if (empty($phone)) return '';
           
           // Nettoyer le numéro (garder seulement les chiffres et +)
           $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
           
           // Si le numéro commence par 0, le remplacer par +33
           if (preg_match('/^0([1-9][0-9]{8})$/', $clean_phone, $matches)) {
               return '+33' . $matches[1];
           }
           
           // Si le numéro commence déjà par +33, le garder tel quel
           if (preg_match('/^\\+33([1-9][0-9]{8})$/', $clean_phone)) {
               return $clean_phone;
           }
           
           // Si le numéro commence par 33 (sans +), ajouter le +
           if (preg_match('/^33([1-9][0-9]{8})$/', $clean_phone, $matches)) {
               return '+33' . $matches[1];
           }
           
           // Si le numéro commence par +, le garder tel quel
           if (strpos($clean_phone, '+') === 0) {
               return $clean_phone;
           }
           
           // Par défaut, retourner le numéro tel quel
           return $clean_phone;
       }
       
       // ✅ NOUVEAU : Fonction pour détecter si un champ est une adresse
       function is_address_field($field_label, $value) {
           if (empty($value)) return false;
           
           // Vérifier le label du champ - mots-clés étendus
           $address_keywords = [
               'adresse', 'address', 'rue', 'street', 'voie', 'avenue', 'boulevard', 'place', 'lieu',
               'adr', 'addr', 'location', 'localisation', 'adresse complète', 'adresse complète',
               'adresse du bien', 'adresse du logement', 'adresse de la propriété',
               'numéro', 'numero', 'n°', 'n ', 'street number', 'numéro de rue',
               'adresse postale', 'adresse de contact', 'adresse principale'
           ];
           $label_lower = strtolower($field_label);
           
           foreach ($address_keywords as $keyword) {
               if (strpos($label_lower, $keyword) !== false) {
                   return true;
               }
           }
           
           // Vérifier aussi si la valeur ressemble à une adresse (contient des chiffres et des lettres)
           $clean_value = trim($value);
           if (preg_match('/\d+/', $clean_value) && preg_match('/[a-zA-ZÀ-ÿ]/', $clean_value) && strlen($clean_value) > 5) {
               return true;
           }
           
           // ✅ NOUVEAU : Détection spéciale pour les champs avec des IDs comme "4.1", "4.3", etc.
           if (preg_match('/^\d+\.\d+$/', $field_label)) {
               // Si c'est un champ avec un ID numérique, vérifier le contenu
               if (preg_match('/\d+/', $clean_value) && preg_match('/[a-zA-ZÀ-ÿ]/', $clean_value) && strlen($clean_value) > 5) {
                   return true;
               }
           }
           
           return false;
       }
       
       // ✅ NOUVEAU : Fonction pour détecter si un champ est une ville
       function is_city_field($field_label, $value) {
           if (empty($value)) return false;
           
           // Vérifier le label du champ
           $city_keywords = ['ville', 'city', 'commune', 'municipalité', 'localité'];
           $label_lower = strtolower($field_label);
           
           foreach ($city_keywords as $keyword) {
               if (strpos($label_lower, $keyword) !== false) {
                   return true;
               }
           }
           
           // ✅ NOUVEAU : Détection spéciale pour les champs avec des IDs comme "4.3"
           if (preg_match('/^\d+\.\d+$/', $field_label)) {
               // Si c'est un champ avec un ID numérique, vérifier le contenu
               $clean_value = trim($value);
               // Une ville est généralement composée uniquement de lettres (pas de chiffres)
               if (preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $clean_value) && strlen($clean_value) > 2) {
                   return true;
               }
           }
           
           return false;
       }
       
       // ✅ NOUVEAU : Fonction pour formater une adresse complète avec ville
       function format_address_with_city($address_value, $city_value, $field_label) {
           $address = trim($address_value);
           $city = trim($city_value);
           
           if (empty($address) && empty($city)) {
               return '';
           }
           
           if (empty($address)) {
               return $city;
           }
           
           if (empty($city)) {
               return $address;
           }
           
           return $address . '<br><small style="color: #666;">' . $city . '</small>';
       }

       // ✅ NOUVEAU : Fonction pour la page Lead Vendeur
       function lead_vendeur_page() {
           // Vérifier si l'utilisateur est connecté
           if (!is_user_logged_in()) {
               echo '<div class="wrap"><h1>Lead Vendeur</h1><p>Vous devez être connecté pour accéder à cette page.</p></div>';
               return;
           }
           
           // Charger les gestionnaires
           $config_manager = lead_vendeur_config_manager();
           $favoris_handler = lead_vendeur_favoris_handler();
           
           // Vérifier si Gravity Forms est actif
           if (!$config_manager->is_gravity_forms_active()) {
               echo '<div class="wrap">';
               echo '<h1>🏢 Lead Vendeur</h1>';
               echo '<div class="notice notice-error"><p><strong>Gravity Forms n\'est pas actif !</strong> Veuillez installer et activer Gravity Forms pour utiliser cette fonctionnalité.</p></div>';
               echo '</div>';
               return;
           }
           
           $config = $config_manager->get_config();
           
           // Si aucun formulaire configuré, afficher un message
           if (empty($config['gravity_form_id']) || !isset($config['gravity_form_id'])) {
               echo '<div class="wrap">';
               echo '<h1>🏢 Lead Vendeur</h1>';
               echo '<div class="notice notice-warning"><p><strong>Configuration requise !</strong> Veuillez d\'abord configurer le formulaire Gravity Forms dans la <a href="' . admin_url('admin.php?page=lead-vendeur-config') . '">page de configuration</a>.</p></div>';
               echo '</div>';
               return;
           }
           
           // ✅ NOUVEAU : Gestion de la pagination AJAX
           $current_page = 1; // Page par défaut pour le chargement initial
           $per_page = 20; // Nombre d'entrées par page
           
           // Récupérer les entrées du formulaire avec pagination
           $entries = $config_manager->get_form_entries_paginated(
               isset($config['gravity_form_id']) ? $config['gravity_form_id'] : 0, 
               $current_page, 
               $per_page
           );
           $favori_ids = $favoris_handler->get_user_favori_ids(get_current_user_id());
           
           // Calculer les informations de pagination
           $total_entries = $config_manager->get_form_entries_count(isset($config['gravity_form_id']) ? $config['gravity_form_id'] : 0);
           $total_pages = ceil($total_entries / $per_page);
           
           // Charger les styles et scripts
           wp_enqueue_style('lead-vendeur-style', plugin_dir_url(__FILE__) . 'assets/css/lead-vendeur.css', array(), '1.0.0');
           wp_enqueue_script('lead-vendeur-js', plugin_dir_url(__FILE__) . 'assets/js/lead-vendeur.js', array('jquery'), '1.0.0', true);
           
           // ✅ NOUVEAU : Données AJAX pour la pagination
           wp_localize_script('lead-vendeur-js', 'leadVendeurAjax', array(
               'ajax_url' => admin_url('admin-ajax.php'),
               'nonce' => wp_create_nonce('lead_vendeur_nonce'),
               'current_page' => $current_page,
               'per_page' => $per_page,
               'total_entries' => $total_entries,
               'total_pages' => $total_pages
           ));
           
           echo '<div class="wrap">';
           echo '<h1>🏢 Lead Vendeur</h1>';
           echo '<div class="my-istymo-container">';
           
           // ✅ NOUVEAU : Tableau principal avec style DPE/SCI et système de favoris
           if (!empty($entries)) {
               echo '<div class="my-istymo-card">';
               echo '<h2>📋 Leads Vendeur (' . $total_entries . ' au total)</h2>';
               
               // Informations de pagination (style DPE/SCI)
               if ($total_pages > 1) {
                   $start_entry = (($current_page - 1) * $per_page) + 1;
                   $end_entry = min($current_page * $per_page, $total_entries);
                   echo '<div class="pagination-info">';
                   echo '<span id="page-info">Page ' . $current_page . ' sur ' . $total_pages . '</span>';
                   echo '<span style="margin-left: 15px; color: #666;">Affichage des entrées ' . $start_entry . ' à ' . $end_entry . ' sur ' . $total_entries . '</span>';
                   echo '</div>';
               }
               
               // Tableau principal avec style DPE/SCI
               echo '<div class="lead-vendeur-table-container">';
               echo '<table class="wp-list-table widefat fixed striped lead-vendeur-table">';
               echo '<thead>';
               echo '<tr>';
               echo '<th class="favori-column">⭐</th>';
               
               // Colonne Ville
               if (!empty($entries)) {
                   $first_entry = reset($entries);
                   foreach ($first_entry as $field_id => $value) {
                       if ($field_id === '4.3' && !empty($value)) {
                           echo '<th>Ville</th>';
                           break;
                       }
                   }
               }
               
               // En-têtes fixes pour les colonnes principales
               echo '<th>Type</th>';
               echo '<th>Téléphone</th>';
               echo '<th>Date</th>';
               
               // En-têtes des colonnes configurées (sauf Site Web qui sera en dernier)
               if (!empty($config['display_fields'])) {
                   foreach ($config['display_fields'] as $field_id) {
                       if (isset($form_fields[$field_id])) {
                           $field_label = $form_fields[$field_id]['label'];
                           
                           // ✅ NOUVEAU : Filtrer seulement les champs de lien d'analyse spécifiques
                           if (strpos(strtolower($field_label), 'lien analyse') !== false || 
                               strpos(strtolower($field_label), 'analyse du bien') !== false) {
                               continue;
                           }
                           
                           // ✅ NOUVEAU : Ne pas afficher Site Web ici, il sera en dernier
                           if (strpos(strtolower($field_label), 'site') !== false ||
                               strpos(strtolower($field_label), 'web') !== false ||
                               strpos(strtolower($field_label), 'url') !== false) {
                               continue;
                           }
                           
                           // ✅ NOUVEAU : Titres plus explicites
                           $display_label = $field_label;
                           if (strpos(strtolower($field_label), 'nom') !== false || 
                               strpos(strtolower($field_label), 'name') !== false) {
                               $display_label = 'Nom';
                           } elseif (strpos(strtolower($field_label), 'telephone') !== false || 
                                     strpos(strtolower($field_label), 'phone') !== false) {
                               $display_label = 'Téléphone';
                           } elseif (strpos(strtolower($field_label), 'email') !== false || 
                                     strpos(strtolower($field_label), 'mail') !== false) {
                               $display_label = 'Email';
                           } elseif (strpos(strtolower($field_label), 'adresse') !== false || 
                                     strpos(strtolower($field_label), 'address') !== false) {
                               $display_label = 'Adresse';
                           }
                           
                           echo '<th>' . esc_html($display_label) . '</th>';
                       }
                   }
               }
               
               echo '<th>Actions</th>';
               echo '</tr>';
               echo '</thead>';
               echo '<tbody id="lead-vendeur-table-body">';
               
               // ✅ NOUVEAU : Laisser la pagination AJAX gérer l'affichage des données
               echo '<tr><td colspan="100%" style="text-align: center; padding: 20px;"><div class="loading-spinner"></div><p>Chargement des données...</p></td></tr>';
               
               echo '</tbody>';
               echo '</table>';
               echo '</div>';
               
               // Pagination (style DPE/SCI)
               if ($total_pages > 1) {
                   echo '<div class="lead-vendeur-pagination">';
                   echo '<div class="pagination-controls">';
                   
                   // Bouton précédent
                   if ($current_page > 1) {
                       echo '<button class="pagination-btn" data-page="' . ($current_page - 1) . '">← Précédent</button>';
                   }
                   
                   // Numéros de page
                   echo '<div class="pagination-numbers">';
                   $start_page = max(1, $current_page - 2);
                   $end_page = min($total_pages, $current_page + 2);
                   
                   if ($start_page > 1) {
                       echo '<span class="pagination-number" data-page="1">1</span>';
                       if ($start_page > 2) {
                           echo '<span class="pagination-ellipsis">...</span>';
                       }
                   }
                   
                   for ($i = $start_page; $i <= $end_page; $i++) {
                       $active_class = ($i == $current_page) ? 'current' : '';
                       echo '<span class="pagination-number ' . $active_class . '" data-page="' . $i . '">' . $i . '</span>';
                   }
                   
                   if ($end_page < $total_pages) {
                       if ($end_page < $total_pages - 1) {
                           echo '<span class="pagination-ellipsis">...</span>';
                       }
                       echo '<span class="pagination-number" data-page="' . $total_pages . '">' . $total_pages . '</span>';
                   }
                   
                   echo '</div>';
                   
                   // Bouton suivant
                   if ($current_page < $total_pages) {
                       echo '<button class="pagination-btn" data-page="' . ($current_page + 1) . '">Suivant →</button>';
                   }
                   
                   echo '</div>';
                   echo '</div>';
               }
               
               echo '</div>';
           } else {
               echo '<div class="my-istymo-card">';
               echo '<h2>📋 Aucun lead trouvé</h2>';
               echo '<p>Aucune entrée trouvée pour le formulaire configuré.</p>';
               echo '</div>';
           }
           
           // ✅ NOUVEAU : Section de débogage des données brutes (toujours visible)
           if (current_user_can('manage_options')) {
               echo '<div class="my-istymo-card" style="margin-top: 30px;">';
               echo '<h2>🔧 Données de débogage (Admin uniquement)</h2>';
               echo '<button type="button" id="toggle-debug-data" class="button button-secondary" style="margin-bottom: 15px;">';
               echo '<i class="fas fa-eye"></i> Afficher/Masquer les données brutes';
               echo '</button>';
               
               echo '<div id="debug-data-section" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6;">';
               echo '<h3>📊 Données brutes récupérées depuis la base de données</h3>';
               
               // Afficher les informations de configuration
               echo '<div style="margin-bottom: 20px;">';
               echo '<h4>⚙️ Configuration du formulaire</h4>';
               echo '<pre style="background: #fff; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px;">';
               echo esc_html(print_r($config, true));
               echo '</pre>';
               echo '</div>';
               
               // Afficher les champs du formulaire
               if (!empty($form_fields)) {
                   echo '<div style="margin-bottom: 20px;">';
                   echo '<h4>📝 Champs du formulaire Gravity Forms</h4>';
                   echo '<pre style="background: #fff; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px;">';
                   echo esc_html(print_r($form_fields, true));
                   echo '</pre>';
                   
                   // ✅ NOUVEAU : Détection des champs d'adresse pour débogage
                   echo '<h5>🔍 Détection des champs d\'adresse :</h5>';
                   echo '<ul style="background: #fff; padding: 10px; border-radius: 3px; font-size: 12px;">';
                   foreach ($form_fields as $field_id => $field) {
                       $label = $field['label'] ?? '';
                       $is_address = is_address_field($label, 'test_value');
                       $is_city = is_city_field($label, 'test_value');
                       $is_phone = is_phone_field($label, '0123456789');
                       
                       echo '<li><strong>' . esc_html($label) . '</strong> (ID: ' . $field_id . ') - ';
                       echo 'Adresse: ' . ($is_address ? '✅' : '❌') . ' | ';
                       echo 'Ville: ' . ($is_city ? '✅' : '❌') . ' | ';
                       echo 'Téléphone: ' . ($is_phone ? '✅' : '❌');
                       echo '</li>';
                   }
                   echo '</ul>';
                   
                   // ✅ NOUVEAU : Test avec des valeurs réelles
                   if (!empty($entries)) {
                       echo '<h5>🧪 Test avec des valeurs réelles (première entrée) :</h5>';
                       echo '<ul style="background: #fff; padding: 10px; border-radius: 3px; font-size: 12px;">';
                       $first_entry = reset($entries);
                       foreach ($config['display_fields'] as $field_id) {
                           $value = isset($first_entry[$field_id]) ? $first_entry[$field_id] : '';
                           $field_label = isset($form_fields[$field_id]) ? $form_fields[$field_id]['label'] : '';
                           $is_address = is_address_field($field_label, $value);
                           $is_city = is_city_field($field_label, $value);
                           $is_phone = is_phone_field($field_label, $value);
                           
                           echo '<li><strong>' . esc_html($field_label) . '</strong> (ID: ' . $field_id . ') - ';
                           echo 'Valeur: "' . esc_html($value) . '" - ';
                           echo 'Adresse: ' . ($is_address ? '✅' : '❌') . ' | ';
                           echo 'Ville: ' . ($is_city ? '✅' : '❌') . ' | ';
                           echo 'Téléphone: ' . ($is_phone ? '✅' : '❌');
                           echo '</li>';
                       }
                       echo '</ul>';
                   }
               echo '</div>';
               }
               
               // ✅ NOUVEAU : Afficher les entrées brutes dans un tableau formaté (champs sélectionnés)
               if (!empty($entries)) {
                   echo '<div style="margin-bottom: 20px;">';
                   echo '<h4>📋 Données brutes - Champs sélectionnés (' . count($entries) . ' entrées)</h4>';
                   
                   // Tableau des données brutes
                   echo '<div style="overflow-x: auto; border: 1px solid #dee2e6; border-radius: 5px;">';
                   echo '<table style="width: 100%; border-collapse: collapse; font-size: 12px;">';
                   echo '<thead style="background: #f8f9fa;">';
                   echo '<tr>';
                   echo '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">ID</th>';
                   echo '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">Date</th>';
                   
                   // En-têtes des champs sélectionnés uniquement
                   if (!empty($config['display_fields']) && !empty($form_fields)) {
                       foreach ($config['display_fields'] as $field_id) {
                           if (isset($form_fields[$field_id])) {
                               echo '<th style="padding: 8px; border: 1px solid #dee2e6; text-align: left;">';
                               echo esc_html($form_fields[$field_id]['label']) . ' (ID: ' . $field_id . ')';
                               echo '</th>';
                           }
                       }
                   }
                   echo '</tr>';
                   echo '</thead>';
                   echo '<tbody>';
                   
                   // Données des entrées
                   foreach ($entries as $entry) {
                       echo '<tr>';
                       echo '<td style="padding: 8px; border: 1px solid #dee2e6;">' . esc_html($entry['id']) . '</td>';
                       echo '<td style="padding: 8px; border: 1px solid #dee2e6;">' . esc_html($entry['date_created']) . '</td>';
                       
                       // Valeurs des champs sélectionnés uniquement
                       if (!empty($config['display_fields'])) {
                           foreach ($config['display_fields'] as $field_id) {
                               $value = isset($entry[$field_id]) ? $entry[$field_id] : '';
                               echo '<td style="padding: 8px; border: 1px solid #dee2e6; max-width: 200px; word-wrap: break-word;">';
                               echo esc_html($value);
                               echo '</td>';
                           }
                       }
                       echo '</tr>';
                   }
                   echo '</tbody>';
                   echo '</table>';
                   echo '</div>';
               echo '</div>';
               }
               
               // ✅ NOUVEAU : Afficher TOUTES les données brutes (tous les champs disponibles)
               if (!empty($entries)) {
                   echo '<div style="margin-bottom: 20px;">';
                   echo '<h4>🗂️ Données brutes complètes - Tous les champs disponibles (' . count($entries) . ' entrées)</h4>';
                   
                   // Tableau de toutes les données brutes
                   echo '<div style="overflow-x: auto; border: 1px solid #dee2e6; border-radius: 5px; max-height: 500px; overflow-y: auto;">';
                   echo '<table style="width: 100%; border-collapse: collapse; font-size: 11px;">';
                   echo '<thead style="background: #f8f9fa; position: sticky; top: 0;">';
                   echo '<tr>';
                   echo '<th style="padding: 6px; border: 1px solid #dee2e6; text-align: left; min-width: 50px;">ID</th>';
                   echo '<th style="padding: 6px; border: 1px solid #dee2e6; text-align: left; min-width: 120px;">Date</th>';
                   
                   // En-têtes de TOUS les champs disponibles
                   if (!empty($entries)) {
                       $first_entry = reset($entries);
                       foreach ($first_entry as $field_id => $value) {
                           if ($field_id !== 'id' && $field_id !== 'date_created') {
                               $field_label = isset($form_fields[$field_id]) ? $form_fields[$field_id]['label'] : 'Champ ' . $field_id;
                               echo '<th style="padding: 6px; border: 1px solid #dee2e6; text-align: left; min-width: 100px;">';
                               echo esc_html($field_label) . ' (ID: ' . $field_id . ')';
                               echo '</th>';
                           }
                       }
                   }
                   echo '</tr>';
                   echo '</thead>';
                   echo '<tbody>';
                   
                   // Données de toutes les entrées
                   foreach ($entries as $entry) {
                       echo '<tr>';
                       echo '<td style="padding: 6px; border: 1px solid #dee2e6;">' . esc_html($entry['id']) . '</td>';
                       echo '<td style="padding: 6px; border: 1px solid #dee2e6;">' . esc_html($entry['date_created']) . '</td>';
                       
                       // Valeurs de TOUS les champs
                       foreach ($entry as $field_id => $value) {
                           if ($field_id !== 'id' && $field_id !== 'date_created') {
                               echo '<td style="padding: 6px; border: 1px solid #dee2e6; max-width: 150px; word-wrap: break-word; font-size: 10px;">';
                               echo esc_html($value);
                               echo '</td>';
                           }
                       }
                       echo '</tr>';
                   }
                   echo '</tbody>';
                   echo '</table>';
                   echo '</div>';
               echo '</div>';
               }
               
               // Afficher les favoris
               if (!empty($favori_ids)) {
                   echo '<div style="margin-bottom: 20px;">';
                   echo '<h4>⭐ IDs des favoris de l\'utilisateur</h4>';
                   echo '<pre style="background: #fff; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px;">';
                   echo esc_html(print_r($favori_ids, true));
                   echo '</pre>';
               echo '</div>';
               }
               
               // Informations système
               echo '<div style="margin-bottom: 20px;">';
               echo '<h4>🖥️ Informations système</h4>';
               echo '<ul style="list-style: none; padding: 0;">';
               echo '<li><strong>User ID:</strong> ' . get_current_user_id() . '</li>';
               echo '<li><strong>Gravity Forms actif:</strong> ' . ($config_manager->is_gravity_forms_active() ? 'Oui' : 'Non') . '</li>';
               echo '<li><strong>Form ID configuré:</strong> ' . (isset($config['gravity_form_id']) ? $config['gravity_form_id'] : 'Non configuré') . '</li>';
               echo '<li><strong>Champs d\'affichage configurés:</strong> ' . (isset($config['display_fields']) ? count($config['display_fields']) : 0) . '</li>';
               echo '<li><strong>Timestamp:</strong> ' . current_time('Y-m-d H:i:s') . '</li>';
               echo '</ul>';
               echo '</div>';
               
               echo '</div>'; // Fin debug-data-section
               echo '</div>'; // Fin my-istymo-card
           }
           
           // ✅ NOUVEAU : Script pour l'intégration avec le système unifié
           echo '<script>
           jQuery(document).ready(function($) {
               // Gestion des favoris Lead Vendeur
               $(document).on("click", ".favori-toggle", function(e) {
                   e.preventDefault();
                   var $this = $(this);
                   var entryId = $this.data("entry-id");
                   var isActive = $this.hasClass("favori-active");
                   
                   $.ajax({
                       url: leadVendeurAjax.ajax_url,
                       type: "POST",
                       data: {
                           action: "toggle_lead_vendeur_favori",
                           nonce: leadVendeurAjax.nonce,
                           entry_id: entryId,
                           is_favori: isActive ? 0 : 1
                       },
                       success: function(response) {
                           if (response.success) {
                               if (isActive) {
                                   $this.removeClass("favori-active");
                                   $this.closest("tr").removeClass("favori-row");
                               } else {
                                   $this.addClass("favori-active");
                                   $this.closest("tr").addClass("favori-row");
                               }
                           }
                       }
                   });
               });
               
               // ✅ NOUVEAU : Détails désactivés - focus sur les données brutes
               console.log("Mode données brutes activé - détails désactivés");
           });
           </script>';
            
           echo '</div>';
           echo '</div>';
       }
       
       // ✅ NOUVEAU : Fonction pour la page Carte de Succession
       function carte_succession_page() {
           // Vérifier si l'utilisateur est connecté
           if (!is_user_logged_in()) {
               echo '<div class="wrap"><h1>Carte de Succession</h1><p>Vous devez être connecté pour accéder à cette page.</p></div>';
               return;
           }
           
           echo '<div class="wrap">';
           echo '<h1>🗺️ Carte de Succession</h1>';
           echo '<div class="my-istymo-container">';
           echo '<div class="my-istymo-card">';
           echo '<h2>📊 Cartographie des Successions</h2>';
           echo '<p>Cette section sera dédiée à la cartographie et à l\'analyse des successions immobilières.</p>';
           echo '<p><em>Contenu à développer...</em></p>';
           echo '</div>';
           echo '</div>';
           echo '</div>';
       }
       
       // ✅ NOUVEAU : Fonction pour la page Lead Parrainage
       function lead_parrainage_page() {
           // Vérifier si l'utilisateur est connecté
           if (!is_user_logged_in()) {
               echo '<div class="wrap"><h1>Lead Parrainage</h1><p>Vous devez être connecté pour accéder à cette page.</p></div>';
               return;
           }
           
           echo '<div class="wrap">';
           echo '<h1>🤝 Lead Parrainage</h1>';
           echo '<div class="my-istymo-container">';
           echo '<div class="my-istymo-card">';
           echo '<h2>👥 Gestion des Leads Parrainage</h2>';
           echo '<p>Cette section sera dédiée à la gestion des leads générés par le système de parrainage.</p>';
           echo '<p><em>Contenu à développer...</em></p>';
           echo '</div>';
           echo '</div>';
           echo '</div>';
       }
       
       // ✅ PHASE 2 : Inclure la page de configuration des leads unifiés
       require_once plugin_dir_path(__FILE__) . 'templates/unified-leads-config.php';
       
       // ✅ NOUVEAU : Inclure la page de configuration Lead Vendeur
       require_once plugin_dir_path(__FILE__) . 'templates/lead-vendeur-config.php';

// --- Affichage du panneau d'administration SCI ---
function sci_afficher_panel() {
    // Charger Font Awesome
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
    
    // Charger le CSS de protection contre les thèmes en premier
    wp_enqueue_style(
        'theme-protection-style',
        plugin_dir_url(__FILE__) . 'assets/css/theme-protection.css',
        array('font-awesome'),
        '1.0.4'
    );
    
    // Charger le CSS des composants génériques
    wp_enqueue_style(
        'components-style',
        plugin_dir_url(__FILE__) . 'assets/css/components.css',
        array('theme-protection-style'),
        '1.0.4'
    );
    
    // Charger le CSS SCI pour l'admin
    wp_enqueue_style(
        'sci-admin-style',
        plugin_dir_url(__FILE__) . 'assets/css/sci-style.css',
        array('components-style'),
        '1.0.4'
    );

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
    
    // Logs conditionnés pour la production
    if (defined('WP_DEBUG') && WP_DEBUG) {
        my_istymo_log("=== RECHERCHE AJAX INPI ===", 'inpi');
        my_istymo_log("Code postal: $code_postal", 'inpi');
        my_istymo_log("Page: $page", 'inpi');
        my_istymo_log("Taille page: $page_size", 'inpi');
    }
    
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

    // Logs conditionnés pour la production
    if (defined('WP_DEBUG') && WP_DEBUG) {
        my_istymo_log("=== REQUÊTE API INPI AVEC PAGINATION ===", 'inpi');
        my_istymo_log("URL: $url", 'inpi');
        my_istymo_log("Token: " . substr($token, 0, 20) . "...", 'inpi');
    }

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

    // Logs conditionnés pour la production
    if (defined('WP_DEBUG') && WP_DEBUG) {
        my_istymo_log("Code HTTP INPI: $code_http", 'inpi');
        my_istymo_log("Headers INPI: " . json_encode($headers->getAll()), 'inpi');
    }

    // ✅ NOUVEAU : Gestion automatique des erreurs d'authentification
    if ($code_http === 401 || $code_http === 403) {
        my_istymo_log("🔄 Erreur d'authentification INPI détectée, tentative de régénération du token...", 'error');
        
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

// ✅ Charger les Dashicons et Font Awesome partout (frontend + admin)
function sci_enqueue_global_styles() {
    // Toujours charger Dashicons
    wp_enqueue_style('dashicons');
    
    // Toujours charger Font Awesome
    wp_enqueue_style(
        'my-istymo-font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
}
add_action('wp_enqueue_scripts', 'sci_enqueue_global_styles');
add_action('admin_enqueue_scripts', 'sci_enqueue_global_styles');

// ✅ Fallback : Ajouter Font Awesome directement dans le head 
function sci_add_fontawesome_fallback() {
    // Essayer plusieurs CDN pour garantir le chargement
    echo '<!-- Font Awesome pour My-Istymo -->' . "\n";
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />' . "\n";
    echo '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.4.0/css/all.css" crossorigin="anonymous" />' . "\n";
    echo '<!-- Fin Font Awesome -->' . "\n";
}
add_action('wp_head', 'sci_add_fontawesome_fallback', 1);
add_action('admin_head', 'sci_add_fontawesome_fallback', 1);

// ✅ Debug : Ajouter un script de test Font Awesome
function sci_add_fontawesome_test() {
    if (current_user_can('manage_options')) {
        echo '<script>
        window.addEventListener("load", function() {
            var testElement = document.createElement("i");
            testElement.className = "fas fa-eye";
            testElement.style.display = "none";
            document.body.appendChild(testElement);
            
            var style = window.getComputedStyle(testElement, "::before");
            var content = style.getPropertyValue("content");
            
            if (content && content !== "none" && content !== "") {
                console.log("✅ My-Istymo: Font Awesome chargé avec succès!");
            } else {
                console.warn("❌ My-Istymo: Font Awesome ne s\'est pas chargé correctement");
            }
            
            document.body.removeChild(testElement);
        });
        </script>' . "\n";
    }
}
add_action('wp_footer', 'sci_add_fontawesome_test');
add_action('admin_footer', 'sci_add_fontawesome_test');

function sci_enqueue_admin_scripts() {
    // ✅ S'assurer que les Dashicons sont toujours chargés (admin et frontend)
    wp_enqueue_style('dashicons');
    
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
            'contacted_sirens' => $contacted_sirens, // ✅ NOUVEAU : Liste des SIRENs contactés
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG // ✅ NOUVEAU : Mode debug pour JavaScript
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
add_action('wp_ajax_nopriv_my_istymo_get_lead_details', 'my_istymo_ajax_get_lead_details');

// Action de test pour vérifier la connectivité AJAX
add_action('wp_ajax_my_istymo_test_ajax', 'my_istymo_ajax_test');
add_action('wp_ajax_nopriv_my_istymo_test_ajax', 'my_istymo_ajax_test');

function my_istymo_ajax_test() {
    error_log('=== TEST AJAX APPELÉ ===');
    wp_send_json_success(['message' => 'Test AJAX réussi!', 'timestamp' => current_time('mysql')]);
}
// Hooks AJAX supprimés - fonctionnalité simplifiée
add_action('wp_ajax_my_istymo_validate_workflow_transition', 'my_istymo_ajax_validate_workflow_transition');
add_action('wp_ajax_my_istymo_get_workflow_transitions', 'my_istymo_ajax_get_workflow_transitions');
add_action('wp_ajax_my_istymo_get_status_change_validation', 'my_istymo_ajax_get_status_change_validation');
add_action('wp_ajax_my_istymo_get_workflow_step_info', 'my_istymo_ajax_get_workflow_step_info');

// ✅ NOUVEAU : Handlers AJAX pour l'édition des leads
add_action('wp_ajax_my_istymo_update_lead', 'my_istymo_ajax_update_lead');
add_action('wp_ajax_my_istymo_update_lead_from_modal', 'my_istymo_ajax_update_lead_from_modal');
add_action('wp_ajax_nopriv_my_istymo_update_lead_from_modal', 'my_istymo_ajax_update_lead_from_modal');

// ✅ NOUVEAU : Handlers AJAX pour les favoris Lead Vendeur
add_action('wp_ajax_lead_vendeur_toggle_favori', 'lead_vendeur_ajax_toggle_favori');
add_action('wp_ajax_lead_vendeur_get_entry_details', 'lead_vendeur_ajax_get_entry_details');
add_action('wp_ajax_lead_vendeur_pagination', 'lead_vendeur_ajax_pagination');

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

// ✅ Fonction de mise à jour de lead depuis le modal de détail
function my_istymo_ajax_update_lead_from_modal() {
    check_ajax_referer('my_istymo_nonce', 'nonce');
    
    $lead_id = intval($_POST['lead_id']);
    $status = sanitize_text_field($_POST['status'] ?? '');
    $priorite = sanitize_text_field($_POST['priorite'] ?? '');
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');
    
    if (!$lead_id) {
        wp_send_json_error('ID du lead manquant');
        return;
    }
    
    if (empty($status) || empty($priorite)) {
        wp_send_json_error('Statut et priorité sont obligatoires');
        return;
    }
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    
    // Préparer les données à mettre à jour
    $update_data = [
        'status' => $status,
        'priorite' => $priorite,
        'notes' => $notes,
        'date_modification' => current_time('mysql')
    ];
    
    $result = $leads_manager->update_lead($lead_id, $update_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        // Invalider les caches WordPress pour forcer le rechargement des données
        wp_cache_flush();
        
        // Invalider les caches d'objets spécifiques
        wp_cache_delete('leads_user_' . get_current_user_id(), 'unified_leads');
        wp_cache_delete('lead_' . $lead_id, 'unified_leads');
        
        wp_send_json_success('Modifications sauvegardées avec succès');
    }
}

// ✅ Handler pour la suppression de leads
add_action('wp_ajax_delete_unified_lead', 'my_istymo_ajax_delete_unified_lead');
add_action('wp_ajax_nopriv_delete_unified_lead', 'my_istymo_ajax_delete_unified_lead');

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
    
    if (!$lead_id) {
        wp_send_json_error('ID du lead invalide');
        return;
    }
    
    try {
        $leads_manager = Unified_Leads_Manager::get_instance();
        $lead = $leads_manager->get_lead($lead_id);
        
        if (!$lead) {
            wp_send_json_error('Lead introuvable');
            return;
        }
        
        // Préparer les données pour l'affichage
        $data = array(
            'id' => $lead->id,
            'lead_type' => $lead->lead_type,
            'original_id' => $lead->original_id,
            'status' => $lead->status,
            'priorite' => $lead->priorite,
            'notes' => $lead->notes,
            'date_creation' => $lead->date_creation,
            'date_modification' => $lead->date_modification,
            'data_originale' => $lead->data_originale
        );
        
        wp_send_json_success($data);
        
    } catch (Exception $e) {
        wp_send_json_error('Erreur serveur: ' . $e->getMessage());
    }
}

// Fonction AJAX supprimée - fonctionnalité simplifiée

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

// Fonction AJAX simplifiée pour afficher l'ID du lead

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
                        <?php 
                        // Fonction pour nettoyer l'adresse
                        function cleanAddressDisplay($address) {
                            if (empty($address)) return 'Non spécifié';
                            $cleaned = preg_replace('/\s+\d{5}\s+[A-Za-zÀ-ÿ\s-]+$/', '', $address);
                            return trim($cleaned) ?: trim($address);
                        }
                        echo esc_html(cleanAddressDisplay($data['adresse_ban'])); 
                        ?>
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
        <?php if (!empty($data['etiquette_dpe']) || !empty($data['complement_adresse_logement']) || !empty($data['conso_5_usages_ef_energie_n1']) || !empty($data['emission_ges_5_usages_energie_n1'])): ?>
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
                
                <?php if (!empty($data['complement_adresse_logement'])): ?>
                <div class="my-istymo-info-item">
                    <label>📍 Complément adresse</label>
                    <div class="my-istymo-info-value">
                        <?php echo esc_html($data['complement_adresse_logement']); ?>
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
    // Charger Font Awesome
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
    
    // Charger le CSS de protection contre les thèmes en premier
    wp_enqueue_style(
        'theme-protection-style',
        plugin_dir_url(__FILE__) . 'assets/css/theme-protection.css',
        array('font-awesome'),
        '1.0.4'
    );
    
    // Charger le CSS des composants génériques
    wp_enqueue_style(
        'components-style',
        plugin_dir_url(__FILE__) . 'assets/css/components.css',
        array('theme-protection-style'),
        '1.0.4'
    );
    
    // Charger le CSS DPE pour l'admin
    wp_enqueue_style(
        'dpe-admin-style',
        plugin_dir_url(__FILE__) . 'assets/css/dpe-style.css',
        array('components-style'),
        '1.0.4'
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
    sci_load_template('dpe-panel', $context);
}

// --- PAGE POUR AFFICHER LES FAVORIS DPE ---
function dpe_favoris_page() {
    // Charger Font Awesome
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
    
    // Charger le CSS de protection contre les thèmes en premier
    wp_enqueue_style(
        'theme-protection-style',
        plugin_dir_url(__FILE__) . 'assets/css/theme-protection.css',
        array('font-awesome'),
        '1.0.4'
    );
    
    // Charger le CSS des composants génériques
    wp_enqueue_style(
        'components-style',
        plugin_dir_url(__FILE__) . 'assets/css/components.css',
        array('theme-protection-style'),
        '1.0.4'
    );
    
    // Charger le CSS DPE pour l'admin
    wp_enqueue_style(
        'dpe-admin-style',
        plugin_dir_url(__FILE__) . 'assets/css/dpe-style.css',
        array('components-style'),
        '1.0.4'
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

// --- SHORTCODE POUR EMBARQUER LE SYSTÈME DE LEADS ---
function my_istymo_leads_shortcode($atts) {
    // Vérifier si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        return '<div class="my-istymo-error">Vous devez être connecté pour accéder à la gestion des leads.</div>';
    }
    
    $atts = shortcode_atts(array(
        'title' => '',
        'show_filters' => 'true',
        'show_actions' => 'true',
        'per_page' => 20
    ), $atts);
    
    // Charger Font Awesome pour les icônes
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
    
    // Charger les styles et scripts de manière persistante
    wp_enqueue_style('unified-leads-css', plugin_dir_url(__FILE__) . 'assets/css/unified-leads.css', array('font-awesome'), '1.0.0');
    wp_enqueue_style('lead-edit-modal-css', plugin_dir_url(__FILE__) . 'assets/css/lead-edit-modal.css', array('font-awesome'), '1.0.0');
    wp_enqueue_script('unified-leads-admin', plugin_dir_url(__FILE__) . 'assets/js/unified-leads-admin.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('lead-actions', plugin_dir_url(__FILE__) . 'assets/js/lead-actions.js', array('jquery', 'jquery-ui-tooltip'), '1.0.0', true);
    wp_enqueue_script('lead-workflow', plugin_dir_url(__FILE__) . 'assets/js/lead-workflow.js', array('jquery'), '1.0.0', true);
    
    // Localiser les scripts
    wp_localize_script('unified-leads-admin', 'unifiedLeadsAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_nonce')
    ));
    
    wp_localize_script('lead-actions', 'leadActionsAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_nonce')
    ));
    
    wp_localize_script('lead-workflow', 'leadWorkflowAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_nonce')
    ));
    
    // Capturer la sortie de la fonction existante
    ob_start();
    
    // Inclure le template existant en passant les paramètres
    $context = array(
        'title' => $atts['title'],
        'show_filters' => $atts['show_filters'] === 'true',
        'show_actions' => $atts['show_actions'] === 'true',
        'per_page' => intval($atts['per_page']),
        'is_shortcode' => true,
        'shortcode_id' => 'my-istymo-leads-' . uniqid() // ID unique pour le shortcode
    );
    
    // Inclure le template existant
    unified_leads_admin_page($context);
    
    $content = ob_get_clean();
    
    return $content;
}

// Shortcode pour l'interface d'administration des leads unifiés
function unified_leads_admin_shortcode($atts) {
    // Démarrer la capture de sortie
    ob_start();
    
    // Attributs par défaut
    $atts = shortcode_atts(array(
        'title' => 'Gestion des Leads',
        'show_filters' => 'true',
        'show_actions' => 'true',
        'per_page' => '20',
        'lead_type' => '', // 'sci' ou 'dpe' pour filtrer par type
        'status' => '', // statut spécifique
        'priorite' => '' // priorité spécifique
    ), $atts);
    
    // Convertir les attributs string en booléens
    $show_filters = $atts['show_filters'] === 'true';
    $show_actions = $atts['show_actions'] === 'true';
    
    // Créer le contexte pour le template
    $context = array(
        'title' => $atts['title'],
        'show_filters' => $show_filters,
        'show_actions' => $show_actions,
        'per_page' => intval($atts['per_page']),
        'is_shortcode' => true,
        'shortcode_id' => uniqid('unified_leads_'),
        'default_filters' => array(
            'lead_type' => $atts['lead_type'],
            'status' => $atts['status'],
            'priorite' => $atts['priorite']
        )
    );
    
    // Inclure le template existant
    unified_leads_admin_page($context);
    
    $content = ob_get_clean();
    
    return $content;
}

// Enregistrer les shortcodes
add_shortcode('my_istymo_leads', 'my_istymo_leads_shortcode');
add_shortcode('unified_leads_admin', 'unified_leads_admin_shortcode');

// Hook pour charger les styles sur toutes les pages où le shortcode est utilisé
function my_istymo_enqueue_shortcode_styles() {
    global $post;
    
    if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'my_istymo_leads') || has_shortcode($post->post_content, 'unified_leads_admin'))) {
        // Charger Font Awesome pour les icônes
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // Charger les styles de manière globale
        wp_enqueue_style('unified-leads-css', plugin_dir_url(__FILE__) . 'assets/css/unified-leads.css', array('font-awesome'), '1.0.0');
        wp_enqueue_style('lead-edit-modal-css', plugin_dir_url(__FILE__) . 'assets/css/lead-edit-modal.css', array('font-awesome'), '1.0.0');
        
        // Charger les scripts
        wp_enqueue_script('unified-leads-admin', plugin_dir_url(__FILE__) . 'assets/js/unified-leads-admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('lead-actions', plugin_dir_url(__FILE__) . 'assets/js/lead-actions.js', array('jquery', 'jquery-ui-tooltip'), '1.0.0', true);
        wp_enqueue_script('lead-workflow', plugin_dir_url(__FILE__) . 'assets/js/lead-workflow.js', array('jquery'), '1.0.0', true);
        
        // Localiser les scripts
        wp_localize_script('unified-leads-admin', 'unifiedLeadsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_istymo_nonce')
        ));
        
        wp_localize_script('lead-actions', 'leadActionsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_istymo_nonce')
        ));
        
        wp_localize_script('lead-workflow', 'leadWorkflowAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_istymo_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'my_istymo_enqueue_shortcode_styles');

// Charger le système de migration
require_once plugin_dir_path(__FILE__) . 'migrations/migration-remove-etiquette-ges-v1.0.php';

// Charger l'interface d'administration des migrations
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/migration-admin.php';
}

// ✅ NOUVEAU : Fonctions AJAX pour Lead Vendeur
function lead_vendeur_ajax_toggle_favori() {
    check_ajax_referer('lead_vendeur_nonce', 'nonce');
    
    $entry_id = intval($_POST['entry_id']);
    $user_id = get_current_user_id();
    
    if (!$entry_id || !$user_id) {
        wp_send_json_error('Paramètres manquants');
        return;
    }
    
    $favoris_handler = lead_vendeur_favoris_handler();
    $config_manager = lead_vendeur_config_manager();
    $config = $config_manager->get_config();
    
    // Vérifier si c'est déjà un favori
    $is_favori = $favoris_handler->is_favori($user_id, $entry_id);
    
    if ($is_favori) {
        // Supprimer des favoris
        $result = $favoris_handler->remove_favori($user_id, $entry_id);
        $action = 'removed';
    } else {
        // Ajouter aux favoris
        $result = $favoris_handler->add_favori($user_id, $entry_id, isset($config['gravity_form_id']) ? $config['gravity_form_id'] : 0);
        $action = 'added';
    }
    
    if ($result) {
        wp_send_json_success(array(
            'action' => $action,
            'is_favori' => !$is_favori
        ));
    } else {
        wp_send_json_error('Erreur lors de la mise à jour des favoris');
    }
}

function lead_vendeur_ajax_get_entry_details() {
    check_ajax_referer('lead_vendeur_nonce', 'nonce');
    
    $entry_id = intval($_POST['entry_id']);
    
    if (!$entry_id) {
        wp_send_json_error('ID d\'entrée manquant');
        return;
    }
    
    if (!class_exists('GFAPI')) {
        wp_send_json_error('Gravity Forms non disponible');
        return;
    }
    
    $entry = GFAPI::get_entry($entry_id);
    
    if (is_wp_error($entry)) {
        wp_send_json_error('Entrée introuvable');
        return;
    }
    
    $config_manager = lead_vendeur_config_manager();
    $config = $config_manager->get_config();
    $form_fields = $config_manager->get_form_fields(isset($config['gravity_form_id']) ? $config['gravity_form_id'] : 0);
    
    // Préparer les données formatées
    $formatted_data = array();
    foreach ($form_fields as $field_id => $field) {
        $value = isset($entry[$field_id]) ? $entry[$field_id] : '';
        $formatted_data[] = array(
            'label' => $field['label'],
            'value' => $value,
            'type' => $field['type']
        );
    }
    
    wp_send_json_success(array(
        'entry' => $entry,
        'formatted_data' => $formatted_data,
        'date_created' => $entry['date_created']
    ));
}

// ✅ NOUVEAU : Handler AJAX pour la pagination Lead Vendeur
function lead_vendeur_ajax_pagination() {
    check_ajax_referer('lead_vendeur_nonce', 'nonce');
    
    $page = intval($_POST['page'] ?? 1);
    $per_page = intval($_POST['per_page'] ?? 20);
    
    if ($page < 1) $page = 1;
    if ($per_page < 1) $per_page = 20;
    
    // Récupérer les gestionnaires
    $config_manager = lead_vendeur_config_manager();
    $favoris_handler = lead_vendeur_favoris_handler();
    
    // Vérifier si Gravity Forms est actif
    if (!$config_manager->is_gravity_forms_active()) {
        wp_send_json_error('Gravity Forms n\'est pas actif');
        return;
    }
    
    $config = $config_manager->get_config();
    
    // Si aucun formulaire configuré
    if (empty($config['gravity_form_id'])) {
        wp_send_json_error('Aucun formulaire configuré');
        return;
    }
    
    // Récupérer les entrées avec pagination
    $entries = $config_manager->get_form_entries_paginated(
        $config['gravity_form_id'], 
        $page, 
        $per_page
    );
    $favori_ids = $favoris_handler->get_user_favori_ids(get_current_user_id());
    
    // Calculer les informations de pagination
    $total_entries = $config_manager->get_form_entries_count($config['gravity_form_id']);
    $total_pages = ceil($total_entries / $per_page);
    
    // Récupérer les champs du formulaire
    $form_fields = $config_manager->get_form_fields($config['gravity_form_id']);
    
    // Générer le HTML du tableau
    ob_start();
    
    if (!empty($entries)) {
        foreach ($entries as $entry) {
            $is_favori = in_array($entry['id'], $favori_ids);
            $favori_class = $is_favori ? 'favori-active' : '';
            
            // ✅ NOUVEAU : Initialiser la variable analyse_link pour chaque entrée
            $analyse_link = '';
            
            echo '<tr class="lead-vendeur-row" data-entry-id="' . esc_attr($entry['id']) . '">';
            
            // Colonne favori
            echo '<td class="favori-column">';
            echo '<span class="favori-toggle ' . $favori_class . '" data-entry-id="' . esc_attr($entry['id']) . '">';
            echo '<i class="fas fa-star"></i>';
            echo '</span>';
            echo '</td>';
            
            // ✅ NOUVEAU : Colonne Ville juste après Favoris
            if (isset($entry['4.3']) && !empty($entry['4.3'])) {
                echo '<td>' . esc_html($entry['4.3']) . '</td>';
            }
            // ✅ NOUVEAU : Ne pas générer de cellule vide si la ville n'existe pas
            
            // ✅ NOUVEAU : Colonnes configurées (sauf Site Web qui sera en dernier)
            if (!empty($config['display_fields'])) {
                foreach ($config['display_fields'] as $field_id) {
                    if (isset($form_fields[$field_id])) {
                        $field_label = $form_fields[$field_id]['label'];
                        
                        // Filtrer seulement les champs de lien d'analyse spécifiques
                        if (strpos(strtolower($field_label), 'lien analyse') !== false || 
                            strpos(strtolower($field_label), 'analyse du bien') !== false) {
                            continue;
                        }
                        
                        // ✅ NOUVEAU : Ne pas afficher Site Web ici, il sera en dernier
                        if (strpos(strtolower($field_label), 'site') !== false ||
                            strpos(strtolower($field_label), 'web') !== false ||
                            strpos(strtolower($field_label), 'url') !== false) {
                            continue;
                        }
                        
                        $value = isset($entry[$field_id]) ? $entry[$field_id] : '';
                        
                        // Vérifier si c'est un champ téléphone
                        if (is_phone_field($field_label, $value)) {
                            echo '<td>';
                            $formatted_phone = format_phone_for_dialing($value);
                            echo '<a href="tel:' . esc_attr($formatted_phone) . '" class="phone-link" title="Appeler directement">';
                            echo '<i class="fas fa-phone" style="margin-right: 5px; color: #007cba;"></i>';
                            echo esc_html($value);
                            echo '</a>';
                            echo '</td>';
                        } else {
                            echo '<td>' . esc_html($value) . '</td>';
                        }
                    }
                }
            }
            
            // Date
            echo '<td>' . esc_html(date('d/m/Y H:i', strtotime($entry['date_created']))) . '</td>';
            
            // ✅ SUPPRIMÉ : Colonne Site Web séparée (maintenant dans Actions)
            
            // ✅ NOUVEAU : Colonne Actions (Localiser + Analyser le bien + Voir détails)
            echo '<td class="actions-column">';
            
            // 1. Bouton Localiser le bien (en premier, couleur Google Maps)
            $address_parts = array();
            
            // Récupérer l'adresse (champ 4.1)
            if (isset($entry['4.1']) && !empty($entry['4.1'])) {
                $address_parts[] = $entry['4.1'];
            }
            
            // Récupérer la ville (champ 4.3)
            if (isset($entry['4.3']) && !empty($entry['4.3'])) {
                $address_parts[] = $entry['4.3'];
            }
            
            // Récupérer le code postal (champ 4.5)
            if (isset($entry['4.5']) && !empty($entry['4.5'])) {
                $address_parts[] = $entry['4.5'];
            }
            
            $full_address = implode(' ', $address_parts);
            
            if (!empty($full_address)) {
                $google_maps_url = 'https://www.google.com/maps?q=' . urlencode($full_address);
                echo '<button class="localiser-bien-btn button" onclick="window.open(\'' . esc_url($google_maps_url) . '\', \'_blank\')" title="Localiser sur Google Maps: ' . esc_attr($full_address) . '">';
                echo '<i class="fas fa-map-marker-alt" style="margin-right: 5px;"></i>';
                echo 'Localiser';
                echo '</button>';
            } else {
                $default_address = 'Nice, France';
                $google_maps_url = 'https://www.google.com/maps?q=' . urlencode($default_address);
                echo '<button class="localiser-bien-btn button" onclick="window.open(\'' . esc_url($google_maps_url) . '\', \'_blank\')" title="Localiser sur Google Maps (adresse par défaut)">';
                echo '<i class="fas fa-map-marker-alt" style="margin-right: 5px;"></i>';
                echo 'Localiser';
                echo '</button>';
            }
            
            // 2. Bouton Analyser le bien (au lieu de Site Web)
            $site_web_url = '';
            if (!empty($config['display_fields'])) {
                foreach ($config['display_fields'] as $field_id) {
                    if (isset($form_fields[$field_id])) {
                        $field_label = $form_fields[$field_id]['label'];
                        $value = isset($entry[$field_id]) ? $entry[$field_id] : '';
                        
                        if (strpos(strtolower($field_label), 'site') !== false ||
                            strpos(strtolower($field_label), 'web') !== false ||
                            strpos(strtolower($field_label), 'url') !== false) {
                            if (filter_var($value, FILTER_VALIDATE_URL)) {
                                $site_web_url = $value;
                                break;
                            }
                        }
                    }
                }
            }
            
            if (!empty($site_web_url)) {
                echo '<button class="analyser-bien-btn button" onclick="window.open(\'' . esc_url($site_web_url) . '\', \'_blank\')" title="Analyser le bien sur le site web">';
                echo '<i class="fas fa-search" style="margin-right: 5px;"></i>';
                echo 'Analyser le bien';
                echo '</button>';
            } else {
                echo '<button class="analyser-bien-btn button" disabled title="Pas d\'analyse disponible">';
                echo '<i class="fas fa-search" style="margin-right: 5px;"></i>';
                echo 'Analyser le bien';
                echo '</button>';
            }
            
            // 3. Bouton Voir détails (en dernier, couleur bleue)
            echo '<button class="view-lead-details button button-primary" data-entry-id="' . esc_attr($entry['id']) . '">';
            echo '<i class="fas fa-eye" style="margin-right: 5px;"></i>';
            echo 'Voir détails';
            echo '</button>';
            
            echo '</td>';
            
            echo '</tr>';
        }
    }
    
    $table_html = ob_get_clean();
    
    // Générer le HTML de pagination
    ob_start();
    
    if ($total_pages > 1) {
        echo '<div class="lead-vendeur-pagination">';
        echo '<div class="pagination-controls">';
        
        // Bouton Précédent
        if ($page > 1) {
            echo '<button type="button" class="button button-secondary pagination-btn" data-page="' . ($page - 1) . '">';
            echo '<i class="fas fa-chevron-left"></i> Précédent';
            echo '</button>';
        } else {
            echo '<span class="button button-secondary pagination-btn disabled">';
            echo '<i class="fas fa-chevron-left"></i> Précédent';
            echo '</span>';
        }
        
        // Numéros de pages
        echo '<div class="pagination-numbers">';
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        // Première page si nécessaire
        if ($start_page > 1) {
            echo '<button type="button" class="pagination-number" data-page="1">1</button>';
            if ($start_page > 2) {
                echo '<span class="pagination-ellipsis">...</span>';
            }
        }
        
        // Pages autour de la page actuelle
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $page) {
                echo '<span class="pagination-number current">' . $i . '</span>';
            } else {
                echo '<button type="button" class="pagination-number" data-page="' . $i . '">' . $i . '</button>';
            }
        }
        
        // Dernière page si nécessaire
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<span class="pagination-ellipsis">...</span>';
            }
            echo '<button type="button" class="pagination-number" data-page="' . $total_pages . '">' . $total_pages . '</button>';
        }
        
        echo '</div>';
        
        // Bouton Suivant
        if ($page < $total_pages) {
            echo '<button type="button" class="button button-secondary pagination-btn" data-page="' . ($page + 1) . '">';
            echo 'Suivant <i class="fas fa-chevron-right"></i>';
            echo '</button>';
        } else {
            echo '<span class="button button-secondary pagination-btn disabled">';
            echo 'Suivant <i class="fas fa-chevron-right"></i>';
            echo '</span>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    $pagination_html = ob_get_clean();
    
    // Informations de pagination
    $start_entry = (($page - 1) * $per_page) + 1;
    $end_entry = min($page * $per_page, $total_entries);
    
    wp_send_json_success(array(
        'table_html' => $table_html,
        'pagination_html' => $pagination_html,
        'pagination_info' => array(
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_entries' => $total_entries,
            'start_entry' => $start_entry,
            'end_entry' => $end_entry
        )
    ));
}

?>