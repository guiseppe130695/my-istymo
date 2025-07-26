<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire de campagnes DPE
 */
class DPE_Campaign_Manager {
    
    private $table_campaigns;
    private $table_entries;
    
    public function __construct() {
        global $wpdb;
        $this->table_campaigns = $wpdb->prefix . 'dpe_campaigns';
        $this->table_entries = $wpdb->prefix . 'dpe_campaign_entries';
        
        // Créer les tables si elles n'existent pas
        add_action('init', array($this, 'create_tables'));
    }
    
    /**
     * Créer les tables nécessaires
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des campagnes
        $sql_campaigns = "CREATE TABLE IF NOT EXISTS {$this->table_campaigns} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            user_id bigint(20) NOT NULL,
            type varchar(50) DEFAULT 'dpe_maison',
            status varchar(20) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Table des entrées de campagne
        $sql_entries = "CREATE TABLE IF NOT EXISTS {$this->table_entries} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            campaign_id mediumint(9) NOT NULL,
            numero_dpe varchar(50) NOT NULL,
            type_batiment varchar(50),
            adresse text,
            commune varchar(100),
            code_postal varchar(10),
            surface varchar(20),
            etiquette_dpe varchar(10),
            etiquette_ges varchar(10),
            date_dpe varchar(20),
            letter_status varchar(20) DEFAULT 'pending',
            letter_uid varchar(100),
            letter_error text,
            sent_at datetime NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY numero_dpe (numero_dpe),
            KEY letter_status (letter_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_campaigns);
        dbDelta($sql_entries);
    }
    
    /**
     * Créer une nouvelle campagne
     */
    public function create_campaign($data) {
        global $wpdb;
        
        // ✅ CORRIGÉ : Validation de l'ID utilisateur
        if (!isset($data['user_id']) || !$data['user_id'] || !is_numeric($data['user_id'])) {
            error_log("DPE Campaign Manager - Invalid user_id: " . json_encode($data['user_id']));
            return false;
        }
        
        // Vérifier que l'utilisateur existe
        $user_exists = get_user_by('ID', $data['user_id']);
        if (!$user_exists) {
            error_log("DPE Campaign Manager - User ID does not exist: " . $data['user_id']);
            return false;
        }
        
        // Log pour debug
        error_log("DPE Campaign Manager - Creating campaign for user ID: " . $data['user_id']);
        
        $result = $wpdb->insert(
            $this->table_campaigns,
            array(
                'title' => $data['title'],
                'content' => $data['content'],
                'user_id' => $data['user_id'],
                'type' => $data['type'] ?? 'dpe_maison',
                'status' => 'draft'
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
        
        if (!$result) {
            return false;
        }
        
        $campaign_id = $wpdb->insert_id;
        
        // ✅ NOUVEAU : Ajouter les entrées si fournies
        if (isset($data['entries']) && is_array($data['entries'])) {
            foreach ($data['entries'] as $entry) {
                $this->add_campaign_entry($campaign_id, $entry);
            }
        }
        
        return $campaign_id;
    }
    
    /**
     * Ajouter une entrée à une campagne
     */
    public function add_campaign_entry($campaign_id, $entry_data) {
        global $wpdb;
        
        return $wpdb->insert(
            $this->table_entries,
            array(
                'campaign_id' => $campaign_id,
                'numero_dpe' => $entry_data['numero_dpe'],
                'type_batiment' => $entry_data['type_batiment'],
                'adresse' => $entry_data['adresse'],
                'commune' => $entry_data['commune'],
                'code_postal' => $entry_data['code_postal'],
                'surface' => $entry_data['surface'],
                'etiquette_dpe' => $entry_data['etiquette_dpe'],
                'etiquette_ges' => $entry_data['etiquette_ges'],
                'date_dpe' => $entry_data['date_dpe'],
                'letter_status' => 'pending'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Mettre à jour le statut d'une lettre
     */
    public function update_letter_status($campaign_id, $numero_dpe, $status, $uid = null, $error = null) {
        global $wpdb;
        
        $data = array(
            'letter_status' => $status,
            'updated_at' => current_time('mysql')
        );
        
        if ($uid) {
            $data['letter_uid'] = $uid;
        }
        
        if ($error) {
            $data['letter_error'] = $error;
        }
        
        // Si le statut est 'sent', ajouter la date d'envoi
        if ($status === 'sent') {
            $data['sent_at'] = current_time('mysql');
        }
        
        return $wpdb->update(
            $this->table_entries,
            $data,
            array(
                'campaign_id' => $campaign_id,
                'numero_dpe' => $numero_dpe
            ),
            array('%s', '%s'),
            array('%d', '%s')
        );
    }
    
    /**
     * Récupérer les données d'expédition de l'utilisateur depuis WooCommerce
     */
    public function get_user_expedition_data($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $current_user = get_user_by('ID', $user_id);
        
        // ✅ NOUVEAU : Utiliser le nom du profil utilisateur WordPress + adresse WooCommerce
        $data = array(
            "civilite" => 'M.', // Valeur par défaut
            "prenom" => $current_user->first_name ?? '',
            "nom" => $current_user->last_name ?? '',
            "nom_societe" => '', // Peut être ajouté plus tard si nécessaire
            "adresse_ligne1" => get_user_meta($user_id, 'billing_address_1', true) ?? '',
            "adresse_ligne2" => get_user_meta($user_id, 'billing_address_2', true) ?? '',
            "code_postal" => get_user_meta($user_id, 'billing_postcode', true) ?? '',
            "ville" => get_user_meta($user_id, 'billing_city', true) ?? '',
            "pays" => get_user_meta($user_id, 'billing_country', true) ?? 'FR',
        );
        
        // ✅ Si pas de données de facturation, essayer les données de livraison
        if (empty($data['adresse_ligne1'])) {
            $data['adresse_ligne1'] = get_user_meta($user_id, 'shipping_address_1', true) ?? '';
        }
        if (empty($data['adresse_ligne2'])) {
            $data['adresse_ligne2'] = get_user_meta($user_id, 'shipping_address_2', true) ?? '';
        }
        if (empty($data['code_postal'])) {
            $data['code_postal'] = get_user_meta($user_id, 'shipping_postcode', true) ?? '';
        }
        if (empty($data['ville'])) {
            $data['ville'] = get_user_meta($user_id, 'shipping_city', true) ?? '';
        }
        if (empty($data['pays'])) {
            $data['pays'] = get_user_meta($user_id, 'shipping_country', true) ?? 'FR';
        }
        
        // ✅ Si le nom est vide, utiliser le display_name comme fallback
        if (empty($data['nom'])) {
            $data['nom'] = $current_user->display_name ?? '';
        }
        
        // ✅ Si le prénom est vide, essayer de l'extraire du display_name
        if (empty($data['prenom']) && !empty($current_user->display_name)) {
            $name_parts = explode(' ', $current_user->display_name);
            $data['prenom'] = $name_parts[0] ?? '';
        }
        
        // ✅ DEBUG : Logger les données pour diagnostic
        error_log("DPE Expedition Data (WooCommerce + Profile) for user $user_id: " . json_encode($data));
        
        return $data;
    }
    
    /**
     * Valider les données d'expédition
     */
    public function validate_expedition_data($data) {
        $errors = array();
        
        // ✅ DEBUG : Logger les données reçues pour validation
        error_log("DPE Validation - Data received: " . json_encode($data));
        
        // Vérifier si les données sont vides ou null
        if (empty($data['nom']) || $data['nom'] === null || $data['nom'] === '') {
            $errors[] = 'Nom de l\'expéditeur manquant';
        }
        
        if (empty($data['adresse_ligne1']) || $data['adresse_ligne1'] === null || $data['adresse_ligne1'] === '') {
            $errors[] = 'Adresse de l\'expéditeur manquante';
        }
        
        if (empty($data['code_postal']) || $data['code_postal'] === null || $data['code_postal'] === '') {
            $errors[] = 'Code postal de l\'expéditeur manquant';
        }
        
        if (empty($data['ville']) || $data['ville'] === null || $data['ville'] === '') {
            $errors[] = 'Ville de l\'expéditeur manquante';
        }
        
        // ✅ DEBUG : Logger les erreurs trouvées
        if (!empty($errors)) {
            error_log("DPE Validation - Errors found: " . json_encode($errors));
        }
        
        return $errors;
    }
    
    /**
     * Créer des données WooCommerce par défaut pour l'utilisateur
     */
    public function create_default_woocommerce_data($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $current_user = get_user_by('ID', $user_id);
        
        // Créer des données par défaut basées sur les informations WordPress
        $default_data = array(
            'billing_address_1' => 'Adresse à compléter',
            'billing_postcode' => '75000',
            'billing_city' => 'Paris',
            'billing_country' => 'FR',
            'shipping_address_1' => 'Adresse à compléter',
            'shipping_postcode' => '75000',
            'shipping_city' => 'Paris',
            'shipping_country' => 'FR'
        );
        
        // Sauvegarder les données par défaut
        foreach ($default_data as $key => $value) {
            if (empty(get_user_meta($user_id, $key, true))) {
                update_user_meta($user_id, $key, $value);
            }
        }
        
        return $default_data;
    }
    
    /**
     * Récupérer les campagnes d'un utilisateur
     */
    public function get_user_campaigns($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, 
                    COUNT(e.id) as entries_count
             FROM {$this->table_campaigns} c
             LEFT JOIN {$this->table_entries} e ON c.id = e.campaign_id
             WHERE c.user_id = %d 
             GROUP BY c.id
             ORDER BY c.created_at DESC",
            $user_id
        ), ARRAY_A);
        
        // ✅ DEBUG : Logger les résultats
        error_log("DPE get_user_campaigns - User ID: $user_id, Results count: " . count($results));
        if ($results) {
            error_log("DPE get_user_campaigns - First result: " . json_encode($results[0]));
        }
        
        return $results;
    }
    
    /**
     * Récupérer les entrées d'une campagne
     */
    public function get_campaign_entries($campaign_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_entries} WHERE campaign_id = %d ORDER BY created_at ASC",
            $campaign_id
        ), ARRAY_A);
    }
    
    /**
     * Récupérer les statistiques d'une campagne
     */
    public function get_campaign_stats($campaign_id) {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN letter_status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN letter_status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN letter_status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM {$this->table_entries} 
            WHERE campaign_id = %d",
            $campaign_id
        ));
        
        return $stats;
    }
    
    /**
     * Récupérer les détails complets d'une campagne pour un utilisateur
     */
    public function get_campaign_details($campaign_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        global $wpdb;
        
        // Récupérer la campagne
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_campaigns} WHERE id = %d AND user_id = %d",
            $campaign_id,
            $user_id
        ), ARRAY_A);
        
        if (!$campaign) {
            return false;
        }
        
        // Récupérer les entrées (lettres)
        $entries = $this->get_campaign_entries($campaign_id);
        
        // Récupérer les statistiques
        $stats = $this->get_campaign_stats($campaign_id);
        
        // Assembler les détails
        $details = $campaign;
        $details['entries'] = $entries;
        $details['total_letters'] = $stats->total;
        $details['sent_letters'] = $stats->sent;
        $details['failed_letters'] = $stats->failed;
        $details['pending_letters'] = $stats->pending;
        
        return $details;
    }
}

// Initialiser le gestionnaire de campagnes DPE
function dpe_campaign_manager() {
    static $instance = null;
    if ($instance === null) {
        $instance = new DPE_Campaign_Manager();
    }
    return $instance;
}

// Hook d'initialisation
add_action('plugins_loaded', 'dpe_campaign_manager');
?> 