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
        
        return $result !== false;
    }
    
    /**
     * Supprimer un lead vendeur des favoris
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
        
        return $result !== false;
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
}

// Fonction utilitaire pour récupérer l'instance
function lead_vendeur_favoris_handler() {
    return Lead_Vendeur_Favoris_Handler::get_instance();
}
