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
