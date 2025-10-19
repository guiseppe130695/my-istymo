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
        add_action('wp_ajax_filter_unified_leads', array($this, 'ajax_filter_leads'));
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
            lead_type enum('sci', 'dpe', 'lead_vendeur') NOT NULL,
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
        
        // ✅ NOUVEAU : Mettre à jour la table pour supporter lead_vendeur
        $this->update_table_for_lead_vendeur();
        
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
    public function get_leads($user_id = null, $filters = array(), $per_page = null, $page = 1) {
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
        
        $sql = "SELECT * FROM {$this->leads_table} WHERE {$where_clause} ORDER BY date_creation DESC";
        
        // Ajouter la pagination si spécifiée
        if ($per_page && $per_page > 0) {
            $offset = ($page - 1) * $per_page;
            $sql .= " LIMIT %d OFFSET %d";
            $where_values[] = $per_page;
            $where_values[] = $offset;
        }
        
        $sql = $wpdb->prepare($sql, $where_values);
        
        $leads = $wpdb->get_results($sql);
        
        // Décoder les données originales
        foreach ($leads as $lead) {
            $lead->data_originale = json_decode($lead->data_originale, true);
        }
        
        return $leads;
    }
    
    /**
     * Compte le nombre de leads avec filtres
     */
    public function get_leads_count($filters = array()) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return 0;
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
            "SELECT COUNT(*) FROM {$this->leads_table} WHERE {$where_clause}",
            $where_values
        );
        
        return (int) $wpdb->get_var($sql);
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
        
        // Toujours mettre à jour la date de modification
        $update_fields['date_modification'] = current_time('mysql');
        $update_formats[] = '%s';
        
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
            try {
                $this->remove_original_favori($lead->lead_type, $lead->original_id, $user_id);
            } catch (Exception $e) {
                // Ignorer les erreurs de suppression du favori - le lead principal est déjà supprimé
                error_log("⚠️ Erreur lors de la suppression du favori original (ignorée): " . $e->getMessage());
            }
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
            } elseif ($lead_type === 'lead_vendeur') {
                // ✅ NOUVEAU : Supprimer le favori Lead Vendeur en base
                $lead_vendeur_table = $wpdb->prefix . 'lead_vendeur_favorites';
                $result = $wpdb->delete(
                    $lead_vendeur_table,
                    array(
                        'user_id' => $user_id,
                        'entry_id' => $original_id
                    ),
                    array('%d', '%d')
                );
                
                if ($result === false) {
                    error_log("Erreur lors de la suppression automatique du favori Lead Vendeur: " . $wpdb->last_error);
                } else {
                    error_log("Favori Lead Vendeur supprimé automatiquement pour Entry ID: " . $original_id);
                }
            }
        } catch (Exception $e) {
            error_log("Exception lors de la suppression automatique du favori original: " . $e->getMessage());
            // Ne pas relancer l'exception - juste logger l'erreur
        } catch (Error $e) {
            error_log("Error lors de la suppression automatique du favori original: " . $e->getMessage());
            // Ne pas relancer l'erreur - juste logger l'erreur
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
                'notes' => '',
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
                'notes' => '',
                'data_originale' => array(
                    'dpe_id' => $favorite->dpe_id,
                    'adresse_ban' => $favorite->adresse_ban,
                    'code_postal_ban' => $favorite->code_postal_ban,
                    'nom_commune_ban' => $favorite->nom_commune_ban,
                    'etiquette_dpe' => $favorite->etiquette_dpe,
                    'complement_adresse_logement' => $favorite->complement_adresse_logement,
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
     * Migre les favoris Lead Vendeur existants
     */
    public function migrate_lead_vendeur_favorites() {
        // Vérifier si le gestionnaire Lead Vendeur est disponible
        if (!function_exists('lead_vendeur_favoris_handler')) {
            my_istymo_log('Gestionnaire Lead Vendeur non disponible, migration ignorée', 'unified_leads');
            return 0;
        }
        
        $favoris_handler = lead_vendeur_favoris_handler();
        $migrated_count = $favoris_handler->migrate_existing_favorites();
        
        my_istymo_log("Migration Lead Vendeur terminée : {$migrated_count} leads migrés", 'unified_leads');
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
            
            // Vérifier que le lead existe avant de tenter de le supprimer
            $existing_lead = $this->get_lead($lead_id);
            if (!$existing_lead) {
                wp_send_json_error('Lead non trouvé');
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
        
        $leads = $this->get_leads(null, $filters, null, 1);
        wp_send_json_success($leads);
    }
    
    /**
     * AJAX: Filtrer les leads et retourner le HTML du tableau
     */
    public function ajax_filter_leads() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'my_istymo_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        // Récupérer les filtres
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
        if (!empty($_POST['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_POST['date_from']);
        }
        if (!empty($_POST['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_POST['date_to']);
        }
        
        // Récupérer la page
        $page = max(1, intval($_POST['paged'] ?? 1));
        $per_page = 20;
        
        // Récupérer les leads avec pagination
        $leads = $this->get_leads(null, $filters, $per_page, $page);
        $total_leads = $this->get_leads_count($filters);
        $total_pages = ceil($total_leads / $per_page);
        
        // Générer le HTML du tableau
        ob_start();
        if (!empty($leads)) {
            echo '<div class="my-istymo-modern-table">';
            echo '<table class="my-istymo-leads-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th class="my-istymo-th-checkbox"><input type="checkbox" class="my-istymo-select-all"></th>';
            echo '<th class="my-istymo-th-company"><i class="fas fa-building"></i> Entreprise</th>';
            echo '<th class="my-istymo-th-category"><i class="fas fa-tags"></i> Catégorie</th>';
            echo '<th class="my-istymo-th-priority"><i class="fas fa-flag"></i> Priorité</th>';
            echo '<th class="my-istymo-th-location"><i class="fas fa-map-marker-alt"></i> Localisation</th>';
            echo '<th class="my-istymo-th-status"><i class="fas fa-info-circle"></i> Statut</th>';
            echo '<th class="my-istymo-th-actions"></th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($leads as $lead) {
                $this->render_lead_row($lead);
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            
            // Pagination
            if ($total_pages > 1) {
                echo '<div class="my-istymo-pagination">';
                echo '<div class="my-istymo-pagination-info">';
                echo sprintf('Affichage de %d à %d sur %d résultats', 
                    (($page - 1) * $per_page) + 1, 
                    min($page * $per_page, $total_leads), 
                    $total_leads
                );
                echo '</div>';
                echo '<div class="my-istymo-pagination-links">';
                
                if ($page > 1) {
                    echo '<a href="#" class="my-istymo-pagination-link" data-page="' . ($page - 1) . '">« Précédent</a>';
                }
                
                for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
                    $active_class = ($i == $page) ? ' active' : '';
                    echo '<a href="#" class="my-istymo-pagination-link' . $active_class . '" data-page="' . $i . '">' . $i . '</a>';
                }
                
                if ($page < $total_pages) {
                    echo '<a href="#" class="my-istymo-pagination-link" data-page="' . ($page + 1) . '">Suivant »</a>';
                }
                
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="my-istymo-no-results">';
            echo '<i class="fas fa-search"></i>';
            echo '<h3>Aucun lead trouvé</h3>';
            echo '<p>Aucun lead ne correspond aux critères de recherche.</p>';
            echo '</div>';
        }
        
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'html' => $html,
            'total' => $total_leads,
            'page' => $page,
            'total_pages' => $total_pages
        ));
    }
    
    /**
     * Rendre une ligne de lead pour le tableau
     */
    private function render_lead_row($lead) {
        // Extraire les données selon le type de lead (même logique que la page principale)
        $company_name = '';
        $location = '';
        $category = '';
        
        if (!empty($lead->data_originale)) {
            if ($lead->lead_type === 'dpe') {
                $company_name = $lead->data_originale['adresse_ban'] ?? 'Bien immobilier';
                $ville = $lead->data_originale['nom_commune_ban'] ?? '';
                $code_postal = $lead->data_originale['code_postal_ban'] ?? '';
                $location = $ville . ($code_postal ? ' (' . $code_postal . ')' : '');
                $category = 'Lead DPE';
            } elseif ($lead->lead_type === 'sci') {
                $company_name = $lead->data_originale['denomination'] ?? $lead->data_originale['raisonSociale'] ?? 'SCI';
                $ville = $lead->data_originale['ville'] ?? '';
                $code_postal = $lead->data_originale['code_postal'] ?? '';
                $location = $ville . ($code_postal ? ' (' . $code_postal . ')' : '');
                $category = 'Lead SCI';
            }
        }
        
        // Copier exactement la même structure HTML que la page principale
        echo '<tr class="my-istymo-table-row">';
        echo '<td class="my-istymo-td-checkbox">';
        echo '<input type="checkbox" class="my-istymo-lead-checkbox" value="' . esc_attr($lead->id) . '">';
        echo '</td>';
        echo '<td class="my-istymo-td-company">';
        echo '<div class="my-istymo-company-cell">';
        echo '<div class="my-istymo-company-icon">';
        if ($lead->lead_type === 'dpe') {
            echo '<span class="my-istymo-icon my-istymo-icon-house">🏠</span>';
        } else {
            echo '<span class="my-istymo-icon my-istymo-icon-building">🏢</span>';
        }
        echo '</div>';
        echo '<div class="my-istymo-company-info">';
        echo '<div class="my-istymo-company-name">' . esc_html($company_name ?: 'Lead #' . $lead->id) . '</div>';
        echo '<div class="my-istymo-company-id">ID: ' . esc_html($lead->original_id) . '</div>';
        echo '</div>';
        echo '</div>';
        echo '</td>';
        echo '<td class="my-istymo-td-category">';
        echo '<div class="my-istymo-category">' . esc_html($category) . '</div>';
        echo '</td>';
        echo '<td class="my-istymo-td-priority">';
        
        // Convertir les priorités en badges modernes (même logique que la page principale)
        $priority_class = '';
        $priority_text = '';
        switch($lead->priorite) {
            case 'haute':
                $priority_class = 'high';
                $priority_text = 'Haute';
                break;
            case 'normale':
                $priority_class = 'normal';
                $priority_text = 'Normale';
                break;
            case 'basse':
                $priority_class = 'low';
                $priority_text = 'Basse';
                break;
            default:
                $priority_class = 'normal';
                $priority_text = 'Normale';
        }
        echo '<span class="my-istymo-priority-badge my-istymo-priority-' . $priority_class . '">';
        echo '<span class="my-istymo-priority-dot"></span>';
        echo $priority_text;
        echo '</span>';
        echo '</td>';
        echo '<td class="my-istymo-td-location">';
        echo '<div class="my-istymo-location">' . esc_html($location ?: '—') . '</div>';
        echo '</td>';
        echo '<td class="my-istymo-td-status">';
        
        // Convertir les statuts en badges modernes (même logique que la page principale)
        $status_class = '';
        $status_text = '';
        switch($lead->status) {
            case 'nouveau':
                $status_class = 'pending';
                $status_text = 'Nouveau';
                break;
            case 'en_cours':
                $status_class = 'progress';
                $status_text = 'En cours';
                break;
            case 'qualifie':
                $status_class = 'completed';
                $status_text = 'Qualifié';
                break;
            case 'proposition':
                $status_class = 'warning';
                $status_text = 'Proposition';
                break;
            case 'negociation':
                $status_class = 'info';
                $status_text = 'Négociation';
                break;
            case 'gagne':
                $status_class = 'success';
                $status_text = 'Gagné';
                break;
            case 'perdu':
                $status_class = 'danger';
                $status_text = 'Perdu';
                break;
            case 'termine':
                $status_class = 'completed';
                $status_text = 'Terminé';
                break;
            default:
                $status_class = 'pending';
                $status_text = ucfirst($lead->status);
        }
        echo '<span class="my-istymo-status-badge my-istymo-status-' . $status_class . '">';
        echo '<span class="my-istymo-status-dot"></span>';
        echo $status_text;
        echo '</span>';
        echo '</td>';
        echo '<td class="my-istymo-td-actions">';
        echo '<div class="my-istymo-actions-buttons">';
        echo '<button class="my-istymo-action-btn view-lead" data-lead-id="' . esc_attr($lead->id) . '" onclick="openLeadDetailModal(' . esc_attr($lead->id) . '); return false;" title="Voir les détails">';
        echo '<i class="fas fa-eye"></i> Voir';
        echo '</button>';
        echo '<button class="my-istymo-action-btn delete-lead" data-lead-id="' . esc_attr($lead->id) . '" onclick="deleteLead(' . esc_attr($lead->id) . '); return false;" title="Supprimer">';
        echo '<i class="fas fa-trash"></i> Supprimer';
        echo '</button>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
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
    
    /**
     * ✅ NOUVEAU : Mettre à jour la table pour supporter lead_vendeur
     */
    private function update_table_for_lead_vendeur() {
        global $wpdb;
        
        // Vérifier si la colonne lead_type supporte déjà lead_vendeur
        $column_info = $wpdb->get_results("SHOW COLUMNS FROM {$this->leads_table} LIKE 'lead_type'");
        
        if (!empty($column_info)) {
            $column_definition = $column_info[0]->Type;
            
            // Si lead_vendeur n'est pas dans l'enum, l'ajouter
            if (strpos($column_definition, 'lead_vendeur') === false) {
                $wpdb->query("ALTER TABLE {$this->leads_table} MODIFY COLUMN lead_type ENUM('sci', 'dpe', 'lead_vendeur') NOT NULL");
                error_log("Table unified_leads mise à jour pour supporter lead_vendeur");
            }
        }
    }
}

// Initialiser le gestionnaire
Unified_Leads_Manager::get_instance();
