<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire unifié des leads SCI/DPE
 * Transforme le système de favoris en système professionnel de gestion des leads
 */
class Unified_Leads_Manager {
    
    private static $instance = null;
    private $leads_table;
    private $actions_table;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->leads_table = $wpdb->prefix . 'my_istymo_unified_leads';
        $this->actions_table = $wpdb->prefix . 'my_istymo_lead_actions';
        
        // Créer les tables lors de l'initialisation
        add_action('init', array($this, 'create_tables'));
        
        // AJAX handlers pour la gestion des leads
        add_action('wp_ajax_add_unified_lead', array($this, 'ajax_add_lead'));
        add_action('wp_ajax_remove_unified_lead', array($this, 'ajax_remove_lead'));
        add_action('wp_ajax_delete_unified_lead', array($this, 'ajax_remove_lead')); // Alias pour compatibilité
        add_action('wp_ajax_get_unified_leads', array($this, 'ajax_get_leads'));
        add_action('wp_ajax_update_lead_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_update_lead_priority', array($this, 'ajax_update_priority'));
        add_action('wp_ajax_add_lead_note', array($this, 'ajax_add_note'));
    }
    
    /**
     * Crée les tables nécessaires pour le système unifié
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table principale des leads
        $leads_sql = "CREATE TABLE IF NOT EXISTS {$this->leads_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            lead_type enum('sci', 'dpe') NOT NULL,
            original_id varchar(255) NOT NULL,
            status varchar(50) DEFAULT 'nouveau',
            priorite varchar(20) DEFAULT 'normale',
            notes longtext,
            data_originale longtext NOT NULL,
            date_creation datetime DEFAULT CURRENT_TIMESTAMP,
            date_modification datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            date_prochaine_action datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_lead (user_id, lead_type, original_id),
            KEY user_id (user_id),
            KEY lead_type (lead_type),
            KEY status (status),
            KEY priorite (priorite),
            KEY date_creation (date_creation),
            KEY date_prochaine_action (date_prochaine_action)
        ) $charset_collate;";
        
        // Table des actions sur les leads
        $actions_sql = "CREATE TABLE IF NOT EXISTS {$this->actions_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lead_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            description text NOT NULL,
            date_action datetime DEFAULT CURRENT_TIMESTAMP,
            resultat text,
            prochaine_action datetime NULL,
            PRIMARY KEY (id),
            KEY lead_id (lead_id),
            KEY user_id (user_id),
            KEY action_type (action_type),
            KEY date_action (date_action),
            FOREIGN KEY (lead_id) REFERENCES {$this->leads_table}(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($leads_sql);
        dbDelta($actions_sql);
        
        // Vérifier et ajouter les colonnes manquantes
        $this->ensure_date_prochaine_action_column();
        $this->ensure_prochaine_action_column();
        
        my_istymo_log('Tables unifiées créées avec succès', 'unified_leads');
    }
    
    /**
     * S'assure que la colonne date_prochaine_action existe
     */
    private function ensure_date_prochaine_action_column() {
        global $wpdb;
        
        // Vérifier si la colonne existe
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->leads_table} LIKE 'date_prochaine_action'");
        
        if (empty($column_exists)) {
            // Ajouter la colonne
            $wpdb->query("ALTER TABLE {$this->leads_table} ADD COLUMN date_prochaine_action datetime NULL AFTER date_modification");
            $wpdb->query("ALTER TABLE {$this->leads_table} ADD INDEX date_prochaine_action (date_prochaine_action)");
            my_istymo_log('Colonne date_prochaine_action ajoutée à la table leads', 'unified_leads');
        }
    }
    
    /**
     * S'assure que la colonne prochaine_action existe dans la table actions
     */
    private function ensure_prochaine_action_column() {
        global $wpdb;
        
        // Vérifier si la colonne existe
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->actions_table} LIKE 'prochaine_action'");
        
        if (empty($column_exists)) {
            // Ajouter la colonne
            $wpdb->query("ALTER TABLE {$this->actions_table} ADD COLUMN prochaine_action datetime NULL AFTER resultat");
            my_istymo_log('Colonne prochaine_action ajoutée à la table actions', 'unified_leads');
        }
    }
    
    /**
     * Met à jour la structure de la table si nécessaire
     */
    public function update_table_structure() {
        $this->ensure_date_prochaine_action_column();
        $this->ensure_prochaine_action_column();
        return true;
    }
    
    /**
     * Ajoute un nouveau lead
     */
    public function add_lead($lead_data) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'Utilisateur non connecté');
        }
        
        // Validation des données
        if (empty($lead_data['lead_type']) || empty($lead_data['original_id'])) {
            return new WP_Error('invalid_data', 'Données manquantes');
        }
        
        $result = $wpdb->insert(
            $this->leads_table,
            array(
                'user_id' => $user_id,
                'lead_type' => sanitize_text_field($lead_data['lead_type']),
                'original_id' => sanitize_text_field($lead_data['original_id']),
                'status' => sanitize_text_field($lead_data['status'] ?? 'nouveau'),
                'priorite' => sanitize_text_field($lead_data['priorite'] ?? 'normale'),
                'notes' => sanitize_textarea_field($lead_data['notes'] ?? ''),
                'data_originale' => wp_json_encode($lead_data['data_originale']),
                'date_prochaine_action' => $lead_data['date_prochaine_action'] ?? null
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de l\'ajout du lead');
        }
        
        $lead_id = $wpdb->insert_id;
        
        // Ajouter une action de création
        $this->add_action($lead_id, 'creation', 'Lead créé depuis ' . $lead_data['lead_type']);
        
        return $lead_id;
    }
    
    /**
     * Récupère les leads d'un utilisateur
     */
    public function get_leads($user_id = null, $filters = array()) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array();
        }
        
        $where_conditions = array('user_id = %d');
        $where_values = array($user_id);
        
        // Appliquer les filtres
        if (!empty($filters['lead_type'])) {
            $where_conditions[] = 'lead_type = %s';
            $where_values[] = $filters['lead_type'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['priorite'])) {
            $where_conditions[] = 'priorite = %s';
            $where_values[] = $filters['priorite'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'date_creation >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'date_creation <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->leads_table} WHERE {$where_clause} ORDER BY date_creation DESC",
            $where_values
        );
        
        $leads = $wpdb->get_results($sql);
        
        // Décoder les données originales
        foreach ($leads as $lead) {
            $lead->data_originale = json_decode($lead->data_originale, true);
        }
        
        return $leads;
    }
    
    /**
     * Récupère un lead spécifique par son ID
     */
    public function get_lead($lead_id) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return null;
        }
        
        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->leads_table} WHERE id = %d AND user_id = %d",
            $lead_id, $user_id
        ));
        
        if ($lead) {
            // Décoder les données originales
            $lead->data_originale = json_decode($lead->data_originale, true);
        }
        
        return $lead;
    }
    
    /**
     * Met à jour un lead
     */
    public function update_lead($lead_id, $update_data) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'Utilisateur non connecté');
        }
        
        // Vérifier que le lead appartient à l'utilisateur
        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->leads_table} WHERE id = %d AND user_id = %d",
            $lead_id, $user_id
        ));
        
        if (!$lead) {
            return new WP_Error('not_found', 'Lead non trouvé');
        }
        
        $update_fields = array();
        $update_formats = array();
        
        if (isset($update_data['status'])) {
            $update_fields['status'] = sanitize_text_field($update_data['status']);
            $update_formats[] = '%s';
        }
        
        if (isset($update_data['priorite'])) {
            $update_fields['priorite'] = sanitize_text_field($update_data['priorite']);
            $update_formats[] = '%s';
        }
        
        if (isset($update_data['notes'])) {
            $update_fields['notes'] = sanitize_textarea_field($update_data['notes']);
            $update_formats[] = '%s';
        }
        
        if (isset($update_data['date_prochaine_action'])) {
            $update_fields['date_prochaine_action'] = $update_data['date_prochaine_action'];
            $update_formats[] = '%s';
        }
        
        if (empty($update_fields)) {
            return new WP_Error('no_updates', 'Aucune donnée à mettre à jour');
        }
        
        $result = $wpdb->update(
            $this->leads_table,
            $update_fields,
            array('id' => $lead_id),
            $update_formats,
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la mise à jour');
        }
        
        return true;
    }
    
    /**
     * Supprime un lead
     * ✅ AUTOMATISATION : Supprime aussi automatiquement le favori correspondant dans SCI/DPE
     */
    public function delete_lead($lead_id, $skip_favori_removal = false) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'Utilisateur non connecté');
        }
        
        error_log("🗑️ Tentative de suppression du lead ID: {$lead_id}, skip_favori_removal: " . ($skip_favori_removal ? 'true' : 'false'));
        
        // Récupérer les informations du lead avant suppression
        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->leads_table} WHERE id = %d AND user_id = %d",
            $lead_id, $user_id
        ));
        
        if (!$lead) {
            error_log("❌ Lead non trouvé: ID {$lead_id}, User {$user_id}");
            return new WP_Error('not_found', 'Lead non trouvé');
        }
        
        error_log("📋 Lead trouvé: Type {$lead->lead_type}, Original ID {$lead->original_id}");
        
        // Supprimer le lead unifié
        $result = $wpdb->delete(
            $this->leads_table,
            array(
                'id' => $lead_id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        if ($result === false) {
            error_log("❌ Erreur lors de la suppression du lead: " . $wpdb->last_error);
            return new WP_Error('db_error', 'Erreur lors de la suppression');
        }
        
        error_log("✅ Lead supprimé avec succès");
        
        // ✅ AUTOMATISATION : Supprimer automatiquement le favori correspondant (sauf si skip_favori_removal = true)
        if (!$skip_favori_removal) {
            error_log("🔄 Suppression automatique du favori original...");
            $this->remove_original_favori($lead->lead_type, $lead->original_id, $user_id);
        } else {
            error_log("⏭️ Suppression du favori original ignorée (skip_favori_removal = true)");
        }
        
        return true;
    }
    
    /**
     * ✅ AUTOMATISATION : Supprime le favori original correspondant (SCI ou DPE)
     */
    private function remove_original_favori($lead_type, $original_id, $user_id) {
        try {
            global $wpdb;
            
            if ($lead_type === 'sci') {
                // Supprimer directement le favori SCI en base
                $sci_table = $wpdb->prefix . 'sci_favoris';
                $result = $wpdb->delete(
                    $sci_table,
                    array(
                        'user_id' => $user_id,
                        'siren' => $original_id
                    ),
                    array('%d', '%s')
                );
                
                if ($result === false) {
                    error_log("Erreur lors de la suppression automatique du favori SCI: " . $wpdb->last_error);
                } else {
                    error_log("Favori SCI supprimé automatiquement pour SIREN: " . $original_id);
                }
            } elseif ($lead_type === 'dpe') {
                // Supprimer directement le favori DPE en base
                $dpe_table = $wpdb->prefix . 'dpe_favoris';
                $result = $wpdb->delete(
                    $dpe_table,
                    array(
                        'user_id' => $user_id,
                        'dpe_id' => $original_id
                    ),
                    array('%d', '%s')
                );
                
                if ($result === false) {
                    error_log("Erreur lors de la suppression automatique du favori DPE: " . $wpdb->last_error);
                } else {
                    error_log("Favori DPE supprimé automatiquement pour DPE ID: " . $original_id);
                }
            }
        } catch (Exception $e) {
            error_log("Exception lors de la suppression automatique du favori original: " . $e->getMessage());
        }
    }
    
    /**
     * Ajoute une action sur un lead
     */
    public function add_action($lead_id, $action_type, $description, $resultat = '', $prochaine_action = null) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'Utilisateur non connecté');
        }
        
        $result = $wpdb->insert(
            $this->actions_table,
            array(
                'lead_id' => $lead_id,
                'user_id' => $user_id,
                'action_type' => sanitize_text_field($action_type),
                'description' => sanitize_textarea_field($description),
                'resultat' => sanitize_textarea_field($resultat),
                'prochaine_action' => $prochaine_action
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de l\'ajout de l\'action');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Récupère l'historique des actions d'un lead
     */
    public function get_lead_history($lead_id) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return array();
        }
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->actions_table} WHERE lead_id = %d AND user_id = %d ORDER BY date_action DESC",
            $lead_id, $user_id
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Migre les favoris SCI existants
     */
    public function migrate_sci_favorites() {
        global $wpdb;
        
        $sci_table = $wpdb->prefix . 'sci_favoris';
        
        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$sci_table}'");
        if (!$table_exists) {
            my_istymo_log('Table SCI favoris non trouvée, migration ignorée', 'unified_leads');
            return 0;
        }
        
        $favorites = $wpdb->get_results("SELECT * FROM {$sci_table}");
        $migrated_count = 0;
        
        foreach ($favorites as $favorite) {
            $lead_data = array(
                'lead_type' => 'sci',
                'original_id' => $favorite->siren,
                'status' => 'nouveau',
                'priorite' => 'normale',
                'notes' => 'Migré depuis les favoris SCI',
                'data_originale' => array(
                    'siren' => $favorite->siren,
                    'denomination' => $favorite->denomination,
                    'dirigeant' => $favorite->dirigeant,
                    'adresse' => $favorite->adresse,
                    'ville' => $favorite->ville,
                    'code_postal' => $favorite->code_postal
                )
            );
            
            $result = $this->add_lead($lead_data);
            if (!is_wp_error($result)) {
                $migrated_count++;
            }
        }
        
        my_istymo_log("Migration SCI terminée : {$migrated_count} leads migrés", 'unified_leads');
        return $migrated_count;
    }
    
    /**
     * Migre les favoris DPE existants
     */
    public function migrate_dpe_favorites() {
        global $wpdb;
        
        $dpe_table = $wpdb->prefix . 'dpe_favoris';
        
        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$dpe_table}'");
        if (!$table_exists) {
            my_istymo_log('Table DPE favoris non trouvée, migration ignorée', 'unified_leads');
            return 0;
        }
        
        $favorites = $wpdb->get_results("SELECT * FROM {$dpe_table}");
        $migrated_count = 0;
        
        foreach ($favorites as $favorite) {
            $lead_data = array(
                'lead_type' => 'dpe',
                'original_id' => $favorite->dpe_id,
                'status' => 'nouveau',
                'priorite' => 'normale',
                'notes' => 'Migré depuis les favoris DPE',
                'data_originale' => array(
                    'dpe_id' => $favorite->dpe_id,
                    'adresse_ban' => $favorite->adresse_ban,
                    'code_postal_ban' => $favorite->code_postal_ban,
                    'nom_commune_ban' => $favorite->nom_commune_ban,
                    'etiquette_dpe' => $favorite->etiquette_dpe,
                    'etiquette_ges' => $favorite->etiquette_ges,
                    'surface_habitable_logement' => $favorite->surface_habitable_logement,
                    'annee_construction' => $favorite->annee_construction,
                    'type_batiment' => $favorite->type_batiment
                )
            );
            
            $result = $this->add_lead($lead_data);
            if (!is_wp_error($result)) {
                $migrated_count++;
            }
        }
        
        my_istymo_log("Migration DPE terminée : {$migrated_count} leads migrés", 'unified_leads');
        return $migrated_count;
    }
    
    /**
     * AJAX: Ajouter un lead
     */
    public function ajax_add_lead() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'my_istymo_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $lead_data = array(
            'lead_type' => sanitize_text_field($_POST['lead_type'] ?? ''),
            'original_id' => sanitize_text_field($_POST['original_id'] ?? ''),
            'data_originale' => $_POST['data_originale'] ?? array()
        );
        
        $result = $this->add_lead($lead_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array('lead_id' => $result));
        }
    }
    
    /**
     * AJAX: Supprimer un lead
     */
    public function ajax_remove_lead() {
        // Désactiver l'affichage des erreurs WordPress pour éviter le HTML
        error_reporting(0);
        ini_set('display_errors', 0);
        
        try {
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'my_istymo_nonce')) {
                wp_send_json_error('Nonce invalide');
                return;
            }
            
            $lead_id = intval($_POST['lead_id'] ?? 0);
            
            if (!$lead_id) {
                wp_send_json_error('ID du lead manquant');
                return;
            }
            
            $result = $this->delete_lead($lead_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success('Lead supprimé avec succès');
            }
        } catch (Exception $e) {
            error_log("Unified Leads AJAX - Exception lors de la suppression: " . $e->getMessage());
            wp_send_json_error('Erreur interne du serveur');
        } catch (Error $e) {
            error_log("Unified Leads AJAX - Error lors de la suppression: " . $e->getMessage());
            wp_send_json_error('Erreur interne du serveur');
        }
    }
    
    /**
     * AJAX: Récupérer les leads
     */
    public function ajax_get_leads() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'my_istymo_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $filters = array();
        if (!empty($_POST['lead_type'])) {
            $filters['lead_type'] = sanitize_text_field($_POST['lead_type']);
        }
        if (!empty($_POST['status'])) {
            $filters['status'] = sanitize_text_field($_POST['status']);
        }
        if (!empty($_POST['priorite'])) {
            $filters['priorite'] = sanitize_text_field($_POST['priorite']);
        }
        
        $leads = $this->get_leads(null, $filters);
        wp_send_json_success($leads);
    }
    
    /**
     * AJAX: Mettre à jour le statut
     */
    public function ajax_update_status() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'my_istymo_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $lead_id = intval($_POST['lead_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        
        $result = $this->update_lead($lead_id, array('status' => $status));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            // Ajouter une action de changement de statut
            $this->add_action($lead_id, 'changement_statut', "Statut changé vers : {$status}");
            wp_send_json_success();
        }
    }
    
    /**
     * AJAX: Mettre à jour la priorité
     */
    public function ajax_update_priority() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'my_istymo_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $lead_id = intval($_POST['lead_id'] ?? 0);
        $priorite = sanitize_text_field($_POST['priorite'] ?? '');
        
        $result = $this->update_lead($lead_id, array('priorite' => $priorite));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success();
        }
    }
    
    /**
     * AJAX: Ajouter une note
     */
    public function ajax_add_note() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'my_istymo_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $lead_id = intval($_POST['lead_id'] ?? 0);
        $note = sanitize_textarea_field($_POST['note'] ?? '');
        
        $result = $this->update_lead($lead_id, array('notes' => $note));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            // Ajouter une action d'ajout de note
            $this->add_action($lead_id, 'ajout_note', "Note ajoutée : {$note}");
            wp_send_json_success();
        }
    }
}

// Initialiser le gestionnaire
Unified_Leads_Manager::get_instance();
