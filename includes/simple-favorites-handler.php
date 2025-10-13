<?php
/**
 * Gestionnaire simple des favoris Lead Vendeur
 * Système de base sans intégration aux leads unifiés
 */

class Simple_Favorites_Handler {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'lead_vendeur_favorites';
    }
    
    /**
     * Créer la table des favoris
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            entry_id int(11) NOT NULL,
            form_id int(11) NOT NULL,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_entry (user_id, entry_id),
            KEY user_id (user_id),
            KEY entry_id (entry_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        // Log pour débogage
        error_log("Simple Favorites - Table creation result: " . print_r($result, true));
        error_log("Simple Favorites - Table name: " . $this->table_name);
        
        return $result;
    }
    
    /**
     * Ajouter un favori
     */
    public function add_favorite($user_id, $entry_id, $form_id) {
        global $wpdb;
        
        error_log("Simple Favorites - Adding favorite: user_id=$user_id, entry_id=$entry_id, form_id=$form_id");
        error_log("Simple Favorites - Table name: " . $this->table_name);
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'entry_id' => $entry_id,
                'form_id' => $form_id
            ),
            array('%d', '%d', '%d')
        );
        
        if ($result === false) {
            error_log("Simple Favorites - Insert failed: " . $wpdb->last_error);
        } else {
            error_log("Simple Favorites - Insert successful, ID: " . $wpdb->insert_id);
            
            // ✅ NOUVEAU : Créer automatiquement un lead unifié pour Lead Vendeur
            $this->create_unified_lead_for_lead_vendeur($user_id, $entry_id, $form_id);
        }
        
        return $result !== false;
    }
    
    /**
     * Supprimer un favori
     */
    public function remove_favorite($user_id, $entry_id) {
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
            // ✅ NOUVEAU : Supprimer automatiquement le lead unifié correspondant
            $this->remove_unified_lead_for_lead_vendeur($user_id, $entry_id);
        }
        
        return $result !== false;
    }
    
    /**
     * Vérifier si un entry est en favori
     */
    public function is_favorite($user_id, $entry_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND entry_id = %d",
            $user_id,
            $entry_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Obtenir tous les favoris d'un utilisateur
     */
    public function get_user_favorites($user_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT entry_id, form_id, date_created FROM {$this->table_name} WHERE user_id = %d ORDER BY date_created DESC",
            $user_id
        ));
        
        return $results;
    }
    
    /**
     * Obtenir les favoris avec les données des entrées
     */
    public function get_user_favorites_with_data($user_id) {
        $favorites = $this->get_user_favorites($user_id);
        $favorites_with_data = array();
        
        foreach ($favorites as $favorite) {
            if (class_exists('GFAPI')) {
                $entry = GFAPI::get_entry($favorite->entry_id);
                if (!is_wp_error($entry)) {
                    $favorites_with_data[] = array(
                        'entry' => $entry,
                        'date_created' => $favorite->date_created
                    );
                }
            }
        }
        
        return $favorites_with_data;
    }
    
    /**
     * Compter les favoris d'un utilisateur
     */
    public function count_user_favorites($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
        
        return intval($count);
    }
    
    /**
     * ✅ NOUVEAU : Créer un lead unifié pour Lead Vendeur
     */
    private function create_unified_lead_for_lead_vendeur($user_id, $entry_id, $form_id) {
        try {
            // Vérifier si le système unifié est disponible
            if (!class_exists('Unified_Leads_Manager')) {
                error_log("Système unifié non disponible pour la création du lead Lead Vendeur");
                return;
            }
            
            // Récupérer les données de l'entrée Gravity Forms
            if (!class_exists('GFAPI')) {
                error_log("Gravity Forms API non disponible");
                return;
            }
            
            $entry = GFAPI::get_entry($entry_id);
            if (is_wp_error($entry)) {
                error_log("Erreur lors de la récupération de l'entrée Gravity Forms: " . $entry->get_error_message());
                return;
            }
            
            $leads_manager = Unified_Leads_Manager::get_instance();
            
            // Préparer les données du lead
            $lead_data = array(
                'user_id' => $user_id,
                'lead_type' => 'lead_vendeur',
                'original_id' => $entry_id,
                'status' => 'nouveau',
                'priorite' => 'normale',
                'notes' => $this->format_lead_vendeur_notes($entry),
                'data_originale' => $entry,
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
     * ✅ NOUVEAU : Supprimer le lead unifié correspondant
     */
    private function remove_unified_lead_for_lead_vendeur($user_id, $entry_id) {
        try {
            error_log("Simple Favorites - Tentative de suppression lead unifié pour user_id: $user_id, entry_id: $entry_id");
            
            // Vérifier si le système unifié est disponible
            if (!class_exists('Unified_Leads_Manager')) {
                error_log("Système unifié non disponible pour la suppression du lead Lead Vendeur");
                return;
            }
            
            $leads_manager = Unified_Leads_Manager::get_instance();
            
            // Trouver et supprimer le lead unifié correspondant
            $leads = $leads_manager->get_user_leads($user_id);
            error_log("Simple Favorites - Nombre de leads trouvés: " . count($leads));
            
            $found = false;
            foreach ($leads as $lead) {
                error_log("Simple Favorites - Lead trouvé: ID=" . $lead->id . ", Type=" . $lead->lead_type . ", Original ID=" . $lead->original_id);
                
                if ($lead->lead_type === 'lead_vendeur' && $lead->original_id == $entry_id) {
                    error_log("Simple Favorites - Lead Lead Vendeur trouvé, suppression en cours...");
                    // ✅ IMPORTANT : skip_favori_removal = true pour éviter la boucle infinie
                    $delete_result = $leads_manager->delete_lead($lead->id, true);
                    
                    if (is_wp_error($delete_result)) {
                        error_log("Erreur lors de la suppression automatique du lead unifié Lead Vendeur: " . $delete_result->get_error_message());
                    } else {
                        error_log("Lead unifié Lead Vendeur supprimé automatiquement pour Entry ID: " . $entry_id);
                        $found = true;
                    }
                    break;
                }
            }
            
            if (!$found) {
                error_log("Simple Favorites - Aucun lead Lead Vendeur trouvé pour Entry ID: " . $entry_id);
            }
        } catch (Exception $e) {
            error_log("Exception lors de la suppression automatique du lead unifié Lead Vendeur: " . $e->getMessage());
        }
    }
    
    /**
     * ✅ NOUVEAU : Formater les notes pour Lead Vendeur
     */
    private function format_lead_vendeur_notes($entry_data) {
        // ✅ NOUVEAU : Notes vides par défaut pour Lead Vendeur
        return '';
    }
}

/**
 * Fonction helper pour obtenir l'instance du gestionnaire
 */
function simple_favorites_handler() {
    static $instance = null;
    if ($instance === null) {
        $instance = new Simple_Favorites_Handler();
    }
    return $instance;
}
