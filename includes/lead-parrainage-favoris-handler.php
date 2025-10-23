<?php
/**
 * Gestionnaire de favoris pour les leads parrainage
 * Système de favoris basé sur les entrées Gravity Forms
 */

if (!defined('ABSPATH')) exit;

class Lead_Parrainage_Favoris_Handler {
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
        $this->table_name = $wpdb->prefix . 'lead_parrainage_favoris';
        
        // Créer la table si elle n'existe pas
        $this->create_table();
        
        // Ajouter les actions AJAX
        add_action('wp_ajax_lead_parrainage_toggle_favorite', array($this, 'ajax_toggle_favorite'));
        add_action('wp_ajax_nopriv_lead_parrainage_toggle_favorite', array($this, 'ajax_toggle_favorite'));
    }
    
    /**
     * Créer la table des favoris
     */
    private function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            entry_id int(11) NOT NULL,
            form_id int(11) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_entry (user_id, entry_id),
            KEY user_id (user_id),
            KEY entry_id (entry_id),
            KEY form_id (form_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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
     * Basculer l'état d'un favori
     */
    public function toggle_favorite($user_id, $entry_id, $form_id) {
        if ($this->is_favorite($user_id, $entry_id)) {
            return $this->remove_favorite($user_id, $entry_id) ? 'removed' : false;
        } else {
            return $this->add_favorite($user_id, $entry_id, $form_id) ? 'added' : false;
        }
    }
    
    /**
     * Vérifier si un lead est en favori
     */
    public function is_favorite($user_id, $entry_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND entry_id = %d",
            $user_id,
            $entry_id
        ));
        
        return $result > 0;
    }
    
    /**
     * Récupérer tous les favoris d'un utilisateur
     */
    public function get_user_favorites($user_id, $form_id = null) {
        global $wpdb;
        
        $sql = "SELECT entry_id FROM {$this->table_name} WHERE user_id = %d";
        $params = array($user_id);
        
        if ($form_id !== null) {
            $sql .= " AND form_id = %d";
            $params[] = $form_id;
        }
        
        $results = $wpdb->get_col($wpdb->prepare($sql, $params));
        
        return array_map('intval', $results);
    }
    
    /**
     * Récupérer les favoris avec détails
     */
    public function get_user_favorites_with_details($user_id, $form_id = null) {
        global $wpdb;
        
        $sql = "SELECT entry_id, form_id, created_at FROM {$this->table_name} WHERE user_id = %d";
        $params = array($user_id);
        
        if ($form_id !== null) {
            $sql .= " AND form_id = %d";
            $params[] = $form_id;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Compter les favoris d'un utilisateur
     */
    public function count_user_favorites($user_id, $form_id = null) {
        global $wpdb;
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d";
        $params = array($user_id);
        
        if ($form_id !== null) {
            $sql .= " AND form_id = %d";
            $params[] = $form_id;
        }
        
        return intval($wpdb->get_var($wpdb->prepare($sql, $params)));
    }
    
    /**
     * Supprimer tous les favoris d'un utilisateur
     */
    public function clear_user_favorites($user_id, $form_id = null) {
        global $wpdb;
        
        $where = array('user_id' => $user_id);
        $where_format = array('%d');
        
        if ($form_id !== null) {
            $where['form_id'] = $form_id;
            $where_format[] = '%d';
        }
        
        return $wpdb->delete($this->table_name, $where, $where_format);
    }
    
    /**
     * Nettoyer les favoris orphelins (entrées supprimées)
     */
    public function cleanup_orphaned_favorites() {
        global $wpdb;
        
        // Supprimer les favoris dont les entrées n'existent plus
        $sql = "DELETE f FROM {$this->table_name} f 
                LEFT JOIN {$wpdb->prefix}gf_entry e ON f.entry_id = e.id 
                WHERE e.id IS NULL";
        
        return $wpdb->query($sql);
    }
    
    /**
     * Action AJAX pour basculer un favori
     */
    public function ajax_toggle_favorite() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'lead_parrainage_nonce')) {
            wp_die('Nonce invalide');
        }
        
        // Vérifier que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            wp_send_json_error('Utilisateur non connecté');
        }
        
        $user_id = get_current_user_id();
        $entry_id = intval($_POST['entry_id']);
        $form_id = intval($_POST['form_id']);
        
        if (!$entry_id || !$form_id) {
            wp_send_json_error('Paramètres invalides');
        }
        
        $result = $this->toggle_favorite($user_id, $entry_id, $form_id);
        
        if ($result) {
            wp_send_json_success(array(
                'action' => $result,
                'is_favorite' => $result === 'added'
            ));
        } else {
            wp_send_json_error('Erreur lors de la mise à jour du favori');
        }
    }
}

// Fonction utilitaire pour récupérer l'instance
function lead_parrainage_favoris_handler() {
    return Lead_Parrainage_Favoris_Handler::get_instance();
}

