<?php
/**
 * Gestionnaire des favoris pour les leads vendeur
 */

if (!defined('ABSPATH')) exit;

class Lead_Vendeur_Favoris_Handler {
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'my_istymo_lead_vendeur_favoris';
        
        // Créer la table si elle n'existe pas
        add_action('init', array($this, 'create_favoris_table'));
    }
    
    /**
     * Créer la table des favoris
     */
    public function create_favoris_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            entry_id int(11) NOT NULL,
            form_id int(11) NOT NULL,
            data_originale longtext,
            date_ajout datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_entry (user_id, entry_id),
            KEY user_id (user_id),
            KEY entry_id (entry_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Ajouter un lead vendeur aux favoris
     * ✅ AUTOMATISATION : Crée automatiquement un lead unifié
     */
    public function add_favori($user_id, $entry_id, $form_id, $data = null) {
        global $wpdb;
        
        // Récupérer les données de l'entrée si non fournies
        if (!$data && class_exists('GFAPI')) {
            $entry = GFAPI::get_entry($entry_id);
            if (is_wp_error($entry)) {
                return false;
            }
            $data = json_encode($entry);
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'entry_id' => $entry_id,
                'form_id' => $form_id,
                'data_originale' => $data
            ),
            array('%d', '%d', '%d', '%s')
        );
        
        if ($result !== false) {
            // ✅ AUTOMATISATION : Créer automatiquement un lead unifié
            $entry_data = json_decode($data, true);
            error_log("Lead Vendeur - Tentative de création lead unifié pour entry_id: $entry_id, user_id: $user_id");
            $this->create_unified_lead_from_lead_vendeur($entry_data, $user_id, $entry_id);
        } else {
            error_log("Lead Vendeur - Erreur lors de l'insertion en base de données");
        }
        
        return $result !== false;
    }
    
    /**
     * ✅ AUTOMATISATION : Crée un lead unifié à partir d'un favori Lead Vendeur
     */
    private function create_unified_lead_from_lead_vendeur($entry_data, $user_id, $entry_id) {
        try {
            // Vérifier si le système unifié est disponible
            if (!class_exists('Unified_Leads_Manager')) {
                error_log("Système unifié non disponible pour la création du lead Lead Vendeur");
                return;
            }
            
            $leads_manager = Unified_Leads_Manager::get_instance();
            
            // Extraire les informations importantes des champs Gravity Forms
            $extracted_data = $this->extract_lead_vendeur_data($entry_data);
            error_log("Lead Vendeur - Données extraites: " . print_r($extracted_data, true));
            
            // Préparer les données du lead
            $lead_data = array(
                'user_id' => $user_id,
                'lead_type' => 'lead_vendeur',
                'original_id' => $entry_id,
                'status' => 'nouveau',
                'priorite' => 'normale',
                'notes' => $this->format_lead_vendeur_notes($extracted_data),
                'data_originale' => $entry_data, // ✅ AJOUT : Inclure les données originales complètes
                'date_creation' => current_time('mysql'),
                'date_modification' => current_time('mysql')
            );
            
            // Créer le lead unifié
            $result = $leads_manager->add_lead($lead_data);
            
            if (is_wp_error($result)) {
                error_log("Erreur lors de la création automatique du lead unifié Lead Vendeur: " . $result->get_error_message());
            } else {
                error_log("Lead unifié Lead Vendeur créé automatiquement pour Entry ID: " . $entry_id);
            }
        } catch (Exception $e) {
            error_log("Exception lors de la création automatique du lead unifié Lead Vendeur: " . $e->getMessage());
        }
    }
    
    /**
     * Extraire les données importantes des champs Gravity Forms Lead Vendeur
     */
    private function extract_lead_vendeur_data($entry_data) {
        $extracted = array(
            'notes' => '',
            'contact_info' => array(),
            'property_info' => array(),
            'summary' => ''
        );
        
        if (!is_array($entry_data)) {
            return $extracted;
        }
        
        // Informations de contact
        $contact_fields = array(
            '45' => 'telephone', // Téléphone
            '46' => 'email',     // E-mail
            '44' => 'nom',       // Civilité (nom)
        );
        
        foreach ($contact_fields as $field_id => $key) {
            if (isset($entry_data[$field_id])) {
                $extracted['contact_info'][$key] = $entry_data[$field_id];
            }
        }
        
        // Informations sur le bien
        $property_fields = array(
            '6' => 'type_bien',      // Type de bien
            '10' => 'surface_m2',    // Surface M2
            '50' => 'emplacement',   // Emplacement
            '4' => 'adresse',        // Adresse
            '9' => 'type_appartement', // Type d'appartement
            '15' => 'type_maison',   // Type de maison
            '57' => 'type_terrain',  // Type de terrain
            '61' => 'type_fond_commerce', // Type Fond de Commerce
        );
        
        foreach ($property_fields as $field_id => $key) {
            if (isset($entry_data[$field_id]) && !empty($entry_data[$field_id])) {
                $extracted['property_info'][$key] = $entry_data[$field_id];
            }
        }
        
        // Commentaire
        if (isset($entry_data['55']) && !empty($entry_data['55'])) {
            $extracted['notes'] = $entry_data['55'];
        }
        
        // Créer un résumé
        $summary_parts = array();
        
        // Type de bien
        if (!empty($extracted['property_info']['type_bien'])) {
            $summary_parts[] = "Type: " . $extracted['property_info']['type_bien'];
        }
        
        // Surface
        if (!empty($extracted['property_info']['surface_m2'])) {
            $summary_parts[] = "Surface: " . $extracted['property_info']['surface_m2'] . " m²";
        }
        
        // Emplacement
        if (!empty($extracted['property_info']['emplacement'])) {
            $summary_parts[] = "Emplacement: " . $extracted['property_info']['emplacement'];
        }
        
        // Adresse
        if (!empty($extracted['property_info']['adresse'])) {
            $summary_parts[] = "Adresse: " . $extracted['property_info']['adresse'];
        }
        
        $extracted['summary'] = implode(' | ', $summary_parts);
        
        return $extracted;
    }
    
    /**
     * Formater les notes du lead vendeur de manière lisible
     */
    private function format_lead_vendeur_notes($extracted_data) {
        $notes = array();
        
        // Informations de contact
        if (!empty($extracted_data['contact_info'])) {
            $notes[] = "=== CONTACT ===";
            foreach ($extracted_data['contact_info'] as $key => $value) {
                if (!empty($value)) {
                    $label = ucfirst(str_replace('_', ' ', $key));
                    $notes[] = "$label: $value";
                }
            }
        }
        
        // Informations sur le bien
        if (!empty($extracted_data['property_info'])) {
            $notes[] = "\n=== BIEN ===";
            foreach ($extracted_data['property_info'] as $key => $value) {
                if (!empty($value)) {
                    $label = ucfirst(str_replace('_', ' ', $key));
                    $notes[] = "$label: $value";
                }
            }
        }
        
        // Commentaire original
        if (!empty($extracted_data['notes'])) {
            $notes[] = "\n=== COMMENTAIRE ===";
            $notes[] = $extracted_data['notes'];
        }
        
        return implode("\n", $notes);
    }
    
    /**
     * Supprimer un lead vendeur des favoris
     * ✅ AUTOMATISATION : Supprime automatiquement le lead unifié correspondant
     */
    public function remove_favori($user_id, $entry_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'entry_id' => $entry_id
            ),
            array('%d', '%d')
        );
        
        if ($result !== false) {
            // ✅ AUTOMATISATION : Supprimer automatiquement le lead unifié correspondant
            $this->remove_unified_lead_from_lead_vendeur($user_id, $entry_id);
        }
        
        return $result !== false;
    }
    
    /**
     * ✅ AUTOMATISATION : Supprime le lead unifié correspondant
     */
    private function remove_unified_lead_from_lead_vendeur($user_id, $entry_id) {
        try {
            // Vérifier si le système unifié est disponible
            if (!class_exists('Unified_Leads_Manager')) {
                error_log("Système unifié non disponible pour la suppression du lead Lead Vendeur");
                return;
            }
            
            $leads_manager = Unified_Leads_Manager::get_instance();
            
            // Trouver et supprimer le lead unifié correspondant
            $leads = $leads_manager->get_user_leads($user_id);
            
            foreach ($leads as $lead) {
                if ($lead->lead_type === 'lead_vendeur' && $lead->original_id == $entry_id) {
                    // ✅ IMPORTANT : skip_favori_removal = true pour éviter la boucle infinie
                    $delete_result = $leads_manager->delete_lead($lead->id, true);
                    
                    if (is_wp_error($delete_result)) {
                        error_log("Erreur lors de la suppression automatique du lead unifié Lead Vendeur: " . $delete_result->get_error_message());
                    } else {
                        error_log("Lead unifié Lead Vendeur supprimé automatiquement pour Entry ID: " . $entry_id);
                    }
                    break;
                }
            }
        } catch (Exception $e) {
            error_log("Exception lors de la suppression automatique du lead unifié Lead Vendeur: " . $e->getMessage());
        }
    }
    
    /**
     * Vérifier si un lead vendeur est en favori
     */
    public function is_favori($user_id, $entry_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND entry_id = %d",
            $user_id, $entry_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Récupérer tous les favoris d'un utilisateur
     */
    public function get_user_favoris($user_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY date_ajout DESC",
            $user_id
        ));
        
        return $results;
    }
    
    /**
     * Récupérer les favoris avec les données complètes
     */
    public function get_favoris_with_data($user_id) {
        $favoris = $this->get_user_favoris($user_id);
        $favoris_with_data = array();
        
        foreach ($favoris as $favori) {
            $data = json_decode($favori->data_originale, true);
            if ($data) {
                $favoris_with_data[] = array(
                    'id' => $favori->id,
                    'entry_id' => $favori->entry_id,
                    'form_id' => $favori->form_id,
                    'date_ajout' => $favori->date_ajout,
                    'data' => $data
                );
            }
        }
        
        return $favoris_with_data;
    }
    
    /**
     * Récupérer les IDs des favoris d'un utilisateur
     */
    public function get_user_favori_ids($user_id) {
        global $wpdb;
        
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT entry_id FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
        
        return $results;
    }
    
    /**
     * ✅ MIGRATION : Migre les favoris Lead Vendeur existants vers le système unifié
     */
    public function migrate_existing_favorites() {
        global $wpdb;
        
        // Vérifier si le système unifié est disponible
        if (!class_exists('Unified_Leads_Manager')) {
            error_log("Système unifié non disponible pour la migration Lead Vendeur");
            return 0;
        }
        
        $leads_manager = Unified_Leads_Manager::get_instance();
        $migrated_count = 0;
        
        // Récupérer tous les favoris existants
        $favorites = $wpdb->get_results("SELECT * FROM {$this->table_name}");
        
        foreach ($favorites as $favorite) {
            $entry_data = json_decode($favorite->data_originale, true);
            
            if ($entry_data) {
                $lead_data = array(
                    'user_id' => $favorite->user_id,
                    'lead_type' => 'lead_vendeur',
                    'original_id' => $favorite->entry_id,
                    'status' => 'nouveau',
                    'priorite' => 'normale',
                    'notes' => '',
                    'data_originale' => $entry_data,
                    'date_creation' => $favorite->date_ajout,
                    'date_modification' => $favorite->date_ajout
                );
                
                $result = $leads_manager->add_lead($lead_data);
                if (!is_wp_error($result)) {
                    $migrated_count++;
                }
            }
        }
        
        error_log("Migration Lead Vendeur terminée : {$migrated_count} leads migrés");
        return $migrated_count;
    }
}

// Fonction utilitaire pour récupérer l'instance
function lead_vendeur_favoris_handler() {
    return Lead_Vendeur_Favoris_Handler::get_instance();
}
