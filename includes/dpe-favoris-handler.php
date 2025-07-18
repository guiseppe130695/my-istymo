<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire des favoris DPE
 */
class DPE_Favoris_Handler {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'dpe_favoris';
        
        // ✅ CRÉER LA TABLE IMMÉDIATEMENT
        $this->create_favoris_table();
        
        // AJAX handlers
        add_action('wp_ajax_dpe_add_favori', array($this, 'ajax_add_favori'));
        add_action('wp_ajax_dpe_remove_favori', array($this, 'ajax_remove_favori'));
        add_action('wp_ajax_dpe_get_favoris', array($this, 'ajax_get_favoris'));
        add_action('wp_ajax_dpe_manage_favoris', array($this, 'ajax_manage_favoris'));
    }
    
    /**
     * Crée la table des favoris DPE
     */
    public function create_favoris_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            dpe_id varchar(255) NOT NULL,
            adresse_ban varchar(500) NOT NULL,
            code_postal_ban varchar(10) NOT NULL,
            nom_commune_ban varchar(100) NOT NULL,
            etiquette_dpe varchar(10) NOT NULL,
            etiquette_ges varchar(10) NOT NULL,
            conso_5_usages_ef_energie_n1 decimal(10,2),
            emission_ges_5_usages_energie_n1 decimal(10,2),
            surface_habitable_logement int(11),
            annee_construction int(11),
            type_batiment varchar(100),
            date_etablissement_dpe date,
            numero_dpe varchar(50),
            complement_adresse_logement varchar(255),
            coordonnee_cartographique_x_ban decimal(15,6),
            coordonnee_cartographique_y_ban decimal(15,6),
            dpe_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_dpe (user_id, dpe_id),
            KEY user_id (user_id),
            KEY code_postal (code_postal_ban),
            KEY etiquette_dpe (etiquette_dpe),
            KEY etiquette_ges (etiquette_ges)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * AJAX: Ajouter un favori DPE
     */
    public function ajax_add_favori() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dpe_favoris_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Utilisateur non connecté');
            return;
        }
        
        $dpe_data = $_POST['dpe_data'] ?? null;
        if (!$dpe_data) {
            wp_send_json_error('Données DPE manquantes');
            return;
        }
        
        // Sanitiser les données
        $dpe_id = sanitize_text_field($dpe_data['_id'] ?? '');
        $adresse = sanitize_text_field($dpe_data['adresse_ban'] ?? '');
        $code_postal = sanitize_text_field($dpe_data['code_postal_ban'] ?? '');
        $commune = sanitize_text_field($dpe_data['nom_commune_ban'] ?? '');
        $etiquette_dpe = sanitize_text_field($dpe_data['etiquette_dpe'] ?? '');
        $etiquette_ges = sanitize_text_field($dpe_data['etiquette_ges'] ?? '');
        
        if (empty($dpe_id) || empty($adresse)) {
            wp_send_json_error('Données DPE incomplètes');
            return;
        }
        
        // Ajouter le favori
        $result = $this->add_favori($user_id, $dpe_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success('Favori DPE ajouté avec succès');
    }
    
    /**
     * AJAX: Supprimer un favori DPE
     */
    public function ajax_remove_favori() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dpe_favoris_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Utilisateur non connecté');
            return;
        }
        
        $dpe_id = sanitize_text_field($_POST['dpe_id'] ?? '');
        if (empty($dpe_id)) {
            wp_send_json_error('ID DPE manquant');
            return;
        }
        
        // Supprimer le favori
        $result = $this->remove_favori($user_id, $dpe_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success('Favori DPE supprimé avec succès');
    }
    
    /**
     * AJAX: Récupérer les favoris DPE
     */
    public function ajax_get_favoris() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dpe_favoris_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Utilisateur non connecté');
            return;
        }
        
        $favoris = $this->get_user_favoris($user_id);
        wp_send_json_success($favoris);
    }
    
    /**
     * Ajouter un favori DPE
     */
    public function add_favori($user_id, $dpe_data) {
        global $wpdb;
        
        // Vérifier si le favori existe déjà
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE user_id = %d AND dpe_id = %s",
            $user_id,
            $dpe_data['_id']
        ));
        
        if ($existing) {
            return new WP_Error('favori_exists', 'Ce DPE est déjà dans vos favoris');
        }
        
        // Préparer les données
        $data = array(
            'user_id' => $user_id,
            'dpe_id' => $dpe_data['_id'],
            'adresse_ban' => $dpe_data['adresse_ban'] ?? '',
            'code_postal_ban' => $dpe_data['code_postal_ban'] ?? '',
            'nom_commune_ban' => $dpe_data['nom_commune_ban'] ?? '',
            'etiquette_dpe' => $dpe_data['etiquette_dpe'] ?? '',
            'etiquette_ges' => $dpe_data['etiquette_ges'] ?? '',
            'conso_5_usages_ef_energie_n1' => floatval($dpe_data['conso_5_usages_ef_energie_n1'] ?? 0),
            'emission_ges_5_usages_energie_n1' => floatval($dpe_data['emission_ges_5_usages_energie_n1'] ?? 0),
            'surface_habitable_logement' => intval($dpe_data['surface_habitable_logement'] ?? 0),
            'annee_construction' => intval($dpe_data['annee_construction'] ?? 0),
            'type_batiment' => $dpe_data['type_batiment'] ?? '',
            'date_etablissement_dpe' => $dpe_data['date_etablissement_dpe'] ?? '',
            'numero_dpe' => $dpe_data['numero_dpe'] ?? '',
            'complement_adresse_logement' => $dpe_data['complement_adresse_logement'] ?? '',
            'coordonnee_cartographique_x_ban' => floatval($dpe_data['coordonnee_cartographique_x_ban'] ?? 0),
            'coordonnee_cartographique_y_ban' => floatval($dpe_data['coordonnee_cartographique_y_ban'] ?? 0),
            'dpe_data' => json_encode($dpe_data)
        );
        
        $result = $wpdb->insert($this->table_name, $data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de l\'ajout du favori');
        }
        
        return true;
    }
    
    /**
     * Supprimer un favori DPE
     */
    public function remove_favori($user_id, $dpe_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'dpe_id' => $dpe_id
            ),
            array('%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la suppression du favori');
        }
        
        return true;
    }
    
    /**
     * Récupérer les favoris d'un utilisateur
     */
    public function get_user_favoris($user_id) {
        global $wpdb;
        
        $favoris = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        return $favoris;
    }
    
    /**
     * Vérifier si un DPE est en favori
     */
    public function is_favori($user_id, $dpe_id) {
        global $wpdb;
        
        $favori = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE user_id = %d AND dpe_id = %s",
            $user_id,
            $dpe_id
        ));
        
        return !empty($favori);
    }
    
    /**
     * Compter les favoris d'un utilisateur
     */
    public function count_user_favoris($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * AJAX: Gestion unifiée des favoris DPE (add, remove, get)
     */
    public function ajax_manage_favoris() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dpe_favoris_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Utilisateur non connecté');
            return;
        }
        
        $operation = $_POST['operation'] ?? '';
        
        switch ($operation) {
            case 'add':
                $dpe_data = json_decode(stripslashes($_POST['dpe_data'] ?? '{}'), true);
                if (!$dpe_data || empty($dpe_data['numero_dpe'])) {
                    wp_send_json_error('Données DPE manquantes');
                    return;
                }
                
                // Convertir le format des données pour correspondre à la structure attendue
                $formatted_data = array(
                    '_id' => $dpe_data['numero_dpe'],
                    'numero_dpe' => $dpe_data['numero_dpe'],
                    'adresse_ban' => $dpe_data['adresse'] ?? '',
                    'code_postal_ban' => $dpe_data['code_postal'] ?? '',
                    'nom_commune_ban' => $dpe_data['commune'] ?? '',
                    'etiquette_dpe' => $dpe_data['etiquette_dpe'] ?? '',
                    'etiquette_ges' => $dpe_data['etiquette_ges'] ?? '',
                    'surface_habitable_logement' => $dpe_data['surface'] ?? '',
                    'type_batiment' => $dpe_data['type_batiment'] ?? '',
                    'date_etablissement_dpe' => $dpe_data['date_dpe'] ?? ''
                );
                
                $result = $this->add_favori($user_id, $formatted_data);
                if (is_wp_error($result)) {
                    wp_send_json_error($result->get_error_message());
                    return;
                }
                wp_send_json_success('Favori DPE ajouté avec succès');
                break;
                
            case 'remove':
                $dpe_data = json_decode(stripslashes($_POST['dpe_data'] ?? '{}'), true);
                if (!$dpe_data || empty($dpe_data['numero_dpe'])) {
                    wp_send_json_error('Données DPE manquantes');
                    return;
                }
                
                $result = $this->remove_favori($user_id, $dpe_data['numero_dpe']);
                if (is_wp_error($result)) {
                    wp_send_json_error($result->get_error_message());
                    return;
                }
                wp_send_json_success('Favori DPE supprimé avec succès');
                break;
                
            case 'get':
                $favoris = $this->get_user_favoris($user_id);
                wp_send_json_success($favoris);
                break;
                
            default:
                wp_send_json_error('Opération non reconnue');
                return;
        }
    }
}

// Fonction helper pour accéder au gestionnaire
function dpe_favoris_handler() {
    return DPE_Favoris_Handler::get_instance();
} 