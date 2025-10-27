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
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'entry_id' => $entry_id,
                'form_id' => $form_id
            ),
            array('%d', '%d', '%d')
        );
        
        if ($result !== false) {
            // Créer automatiquement un lead unifié pour Lead Vendeur
            $this->create_unified_lead_for_lead_vendeur($user_id, $entry_id, $form_id);
        }
        
        return $result !== false;
    }
    
    /**
     * Supprimer un favori
     */
    public function remove_favorite($user_id, $entry_id) {
        global $wpdb;
        
        // 1. Supprimer le favori traditionnel
        $result = $wpdb->delete(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'entry_id' => $entry_id
            ),
            array('%d', '%d')
        );
        
        // 2. Supprimer le lead unifié correspondant
        $this->remove_unified_lead($user_id, $entry_id);
        
        return $result !== false;
    }
    
    /**
     * Supprimer le lead unifié correspondant
     */
    private function remove_unified_lead($user_id, $entry_id) {
        try {
            if (!class_exists('Unified_Leads_Manager')) {
                return;
            }
            
            $leads_manager = Unified_Leads_Manager::get_instance();
            $leads = $leads_manager->get_leads($user_id);
            
            foreach ($leads as $lead) {
                if ($lead->lead_type === 'carte_succession' && $lead->original_id == $entry_id) {
                    $leads_manager->delete_lead($lead->id, true);
                    break;
                }
            }
        } catch (Exception $e) {
            // Ignorer les erreurs
        }
    }
    
    
    /**
     * Vérifier si un entry est en favori
     */
    public function is_favorite($user_id, $entry_id) {
        global $wpdb;
        
        // Vérifier seulement dans les favoris traditionnels pour éviter les erreurs
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
        
        // Récupérer seulement les favoris traditionnels pour éviter les erreurs
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
     * Créer un lead unifié pour Lead Vendeur
     */
    private function create_unified_lead_for_lead_vendeur($user_id, $entry_id, $form_id) {
        try {
            if (!class_exists('Unified_Leads_Manager')) {
                return;
            }
            
            // Récupérer les vraies données de Gravity Forms
            $entry_data = array();
            if (class_exists('GFAPI')) {
                $entry = GFAPI::get_entry($entry_id);
                if (!is_wp_error($entry)) {
                    $entry_data = $entry;
                }
            }
            
            $leads_manager = Unified_Leads_Manager::get_instance();
            
            // ✅ CORRECTION : Détecter automatiquement le type de lead
            $detected_type = $this->detect_lead_type_from_context($entry_data, $entry_id);
            
            $lead_data = array(
                'user_id' => $user_id,
                'lead_type' => $detected_type,
                'original_id' => $entry_id,
                'form_id' => $form_id,
                'status' => 'nouveau',
                'priorite' => 'normale',
                'notes' => $this->format_lead_vendeur_notes($entry_data),
                'data_originale' => $entry_data,
                'date_creation' => current_time('mysql'),
                'date_modification' => current_time('mysql')
            );
            
            $leads_manager->add_lead($lead_data);
            
        } catch (Exception $e) {
            // Ignorer les erreurs
        }
    }
    
    
    
    /**
     * Formater les notes pour Lead Vendeur
     */
    private function format_lead_vendeur_notes($entry_data) {
        // Retourner une chaîne vide par défaut pour les notes
        return '';
    }
    
    /**
     * ✅ NOUVEAU : Détecter le type de lead depuis le contexte
     */
    private function detect_lead_type_from_context($entry_data, $entry_id) {
        // Analyser les données pour déterminer le type
        if (is_array($entry_data)) {
            $data_string = json_encode($entry_data);
            
            // Détecter Lead Vendeur par des mots-clés spécifiques
            if (strpos($data_string, 'vendeur') !== false || 
                strpos($data_string, 'bien') !== false ||
                strpos($data_string, 'propriété') !== false ||
                strpos($data_string, 'vente') !== false) {
                return 'lead_vendeur';
            }
            
            // Détecter Lead Parrainage
            if (strpos($data_string, 'parrainage') !== false ||
                strpos($data_string, 'parrain') !== false) {
                return 'lead_parrainage';
            }
            
            // Détecter Carte de Succession
            if (strpos($data_string, 'succession') !== false ||
                strpos($data_string, 'décès') !== false ||
                strpos($data_string, 'héritage') !== false) {
                return 'carte_succession';
            }
        }
        
        // Essayer de déterminer par l'ID ou le contexte
        if (!empty($entry_id)) {
            // Vérifier dans quelle table/formulaire l'ID existe
            if (class_exists('GFAPI')) {
                $entry = GFAPI::get_entry($entry_id);
                if (!is_wp_error($entry)) {
                    $form_id = $entry['form_id'];
                    
                    // Déterminer le type selon le formulaire
                    if ($this->is_lead_vendeur_form($form_id)) {
                        return 'lead_vendeur';
                    } elseif ($this->is_lead_parrainage_form($form_id)) {
                        return 'lead_parrainage';
                    } elseif ($this->is_carte_succession_form($form_id)) {
                        return 'carte_succession';
                    }
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * ✅ NOUVEAU : Vérifier si un formulaire est un formulaire Lead Vendeur
     */
    private function is_lead_vendeur_form($form_id) {
        // Logique pour identifier les formulaires Lead Vendeur
        // À adapter selon votre configuration
        return false; // À implémenter selon vos besoins
    }
    
    /**
     * ✅ NOUVEAU : Vérifier si un formulaire est un formulaire Lead Parrainage
     */
    private function is_lead_parrainage_form($form_id) {
        // Logique pour identifier les formulaires Lead Parrainage
        // À adapter selon votre configuration
        return false; // À implémenter selon vos besoins
    }
    
    /**
     * ✅ NOUVEAU : Vérifier si un formulaire est un formulaire Carte de Succession
     */
    private function is_carte_succession_form($form_id) {
        // Logique pour identifier les formulaires Carte de Succession
        // À adapter selon votre configuration
        return false; // À implémenter selon vos besoins
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
