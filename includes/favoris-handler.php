<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire des favoris SCI
 */
class SCI_Favoris_Handler {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sci_favoris';
        
        // Créer la table lors de l'activation du plugin
        add_action('init', array($this, 'create_favoris_table'));
    }
    
    /**
     * Crée la table des favoris si elle n'existe pas
     */
    public function create_favoris_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            siren varchar(20) NOT NULL,
            denomination text NOT NULL,
            dirigeant text,
            adresse text,
            ville varchar(100),
            code_postal varchar(10),
            date_added datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_siren (user_id, siren),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Ajoute un favori pour l'utilisateur courant
     * ✅ AUTOMATISATION : Crée automatiquement un lead unifié
     */
    public function add_favori($sci_data) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'Utilisateur non connecté');
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'siren' => sanitize_text_field($sci_data['siren']),
                'denomination' => sanitize_text_field($sci_data['denomination']),
                'dirigeant' => sanitize_text_field($sci_data['dirigeant']),
                'adresse' => sanitize_text_field($sci_data['adresse']),
                'ville' => sanitize_text_field($sci_data['ville']),
                'code_postal' => sanitize_text_field($sci_data['code_postal'])
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de l\'ajout en base de données');
        }
        
        // ✅ AUTOMATISATION : Créer automatiquement un lead unifié
        $this->create_unified_lead_from_sci($sci_data, $user_id);
        
        return true;
    }
    
    /**
     * ✅ AUTOMATISATION : Crée un lead unifié à partir d'un favori SCI
     */
    private function create_unified_lead_from_sci($sci_data, $user_id) {
        try {
            // Vérifier si le système unifié est disponible
            if (!class_exists('Unified_Leads_Manager')) {
                error_log("Système unifié non disponible pour la création du lead SCI");
                return;
            }
            
            $leads_manager = Unified_Leads_Manager::get_instance();
            
            // Préparer les données du lead
            $lead_data = array(
                'user_id' => $user_id,
                'lead_type' => 'sci',
                'original_id' => sanitize_text_field($sci_data['siren']),
                'status' => 'nouveau',
                'priorite' => 'normale',
                'notes' => sprintf(
                    "Favori SCI automatiquement créé\n" .
                    "Dénomination: %s\n" .
                    "Dirigeant: %s\n" .
                    "Adresse: %s, %s %s",
                    $sci_data['denomination'] ?? '',
                    $sci_data['dirigeant'] ?? '',
                    $sci_data['adresse'] ?? '',
                    $sci_data['code_postal'] ?? '',
                    $sci_data['ville'] ?? ''
                ),
                'data_originale' => $sci_data, // ✅ AJOUT : Inclure les données originales complètes
                'date_creation' => current_time('mysql'),
                'date_modification' => current_time('mysql')
            );
            
            // Créer le lead unifié
            $result = $leads_manager->add_lead($lead_data);
            
            if (is_wp_error($result)) {
                error_log("Erreur lors de la création automatique du lead unifié SCI: " . $result->get_error_message());
            } else {
                error_log("Lead unifié SCI créé automatiquement pour SIREN: " . $sci_data['siren']);
            }
        } catch (Exception $e) {
            error_log("Exception lors de la création automatique du lead unifié SCI: " . $e->getMessage());
        }
    }
    
    /**
     * Supprime un favori pour l'utilisateur courant
     * ✅ AUTOMATISATION : Supprime automatiquement le lead unifié correspondant
     */
    public function remove_favori($siren) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'Utilisateur non connecté');
        }
        
        $result = $wpdb->delete(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'siren' => sanitize_text_field($siren)
            ),
            array('%d', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la suppression');
        }
        
        // ✅ AUTOMATISATION : Supprimer automatiquement le lead unifié correspondant
        $this->remove_unified_lead_from_sci($siren, $user_id);
        
        return true;
    }
    
    /**
     * ✅ AUTOMATISATION : Supprime le lead unifié correspondant à un favori SCI
     */
    private function remove_unified_lead_from_sci($siren, $user_id) {
        try {
            // Vérifier si le système unifié est disponible
            if (!class_exists('Unified_Leads_Manager')) {
                error_log("Système unifié non disponible pour la suppression du lead SCI");
                return;
            }
            
            $leads_manager = Unified_Leads_Manager::get_instance();
            
            // Récupérer le lead unifié correspondant
            $leads = $leads_manager->get_leads($user_id, array(
                'lead_type' => 'sci',
                'original_id' => $siren
            ));
            
            if (!empty($leads)) {
                $lead = $leads[0];
                // Utiliser le flag skip_favori_removal pour éviter la boucle infinie
                $result = $leads_manager->delete_lead($lead->id, true);
                
                if (is_wp_error($result)) {
                    error_log("Erreur lors de la suppression automatique du lead unifié SCI: " . $result->get_error_message());
                } else {
                    error_log("Lead unifié SCI supprimé automatiquement pour SIREN: " . $siren);
                }
            }
        } catch (Exception $e) {
            error_log("Exception lors de la suppression automatique du lead unifié SCI: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère tous les favoris de l'utilisateur courant
     */
    public function get_favoris() {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return array();
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT siren, denomination, dirigeant, adresse, ville, code_postal 
                 FROM {$this->table_name} 
                 WHERE user_id = %d 
                 ORDER BY date_added DESC",
                $user_id
            ),
            ARRAY_A
        );
        
        return $results ? $results : array();
    }
    
    /**
     * Vérifie si un SIREN est en favori pour l'utilisateur courant
     */
    public function is_favori($siren) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} 
                 WHERE user_id = %d AND siren = %s",
                $user_id,
                sanitize_text_field($siren)
            )
        );
        
        return $count > 0;
    }
}

// Initialise le gestionnaire de favoris
$sci_favoris_handler = new SCI_Favoris_Handler();

// ✅ NOUVEAU : Fonction d'activation pour créer la table
function sci_activate_favoris_table() {
    global $sci_favoris_handler;
    $sci_favoris_handler->create_favoris_table();
    error_log("SCI Plugin - Table des favoris créée/activée");
}

// ✅ NOUVEAU : Hook d'activation
add_action('init', 'sci_activate_favoris_table');

/**
 * Gestionnaire AJAX pour les favoris
 */
function sci_manage_favoris_ajax() {
    // Désactiver l'affichage des erreurs WordPress pour éviter le HTML
    error_reporting(0);
    ini_set('display_errors', 0);
    
    try {
        // Vérification du nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sci_favoris_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        global $sci_favoris_handler;
        
        $operation = sanitize_text_field($_POST['operation']);
        
        // ✅ AMÉLIORÉ : Logs pour le debugging
        error_log("SCI Favoris AJAX - Opération: $operation");
        
        switch ($operation) {
            case 'add':
                if (!isset($_POST['sci_data'])) {
                    error_log("SCI Favoris AJAX - Erreur: Données manquantes pour l'ajout");
                    wp_send_json_error('Données manquantes');
                    return;
                }
                
                $sci_data = json_decode(stripslashes($_POST['sci_data']), true);
                if (!$sci_data) {
                    error_log("SCI Favoris AJAX - Erreur: Données JSON invalides");
                    wp_send_json_error('Données invalides');
                    return;
                }
                
                error_log("SCI Favoris AJAX - Ajout favori pour SIREN: " . ($sci_data['siren'] ?? 'N/A'));
                
                $result = $sci_favoris_handler->add_favori($sci_data);
                if (is_wp_error($result)) {
                    error_log("SCI Favoris AJAX - Erreur ajout: " . $result->get_error_message());
                    wp_send_json_error($result->get_error_message());
                } else {
                    error_log("SCI Favoris AJAX - Favori ajouté avec succès");
                    wp_send_json_success('Favori ajouté');
                }
                break;
                
            case 'remove':
                if (!isset($_POST['sci_data'])) {
                    error_log("SCI Favoris AJAX - Erreur: Données manquantes pour la suppression");
                    wp_send_json_error('Données manquantes');
                    return;
                }
                
                $sci_data = json_decode(stripslashes($_POST['sci_data']), true);
                if (!$sci_data || !isset($sci_data['siren'])) {
                    error_log("SCI Favoris AJAX - Erreur: SIREN manquant pour la suppression");
                    wp_send_json_error('SIREN manquant');
                    return;
                }
                
                error_log("SCI Favoris AJAX - Suppression favori pour SIREN: " . $sci_data['siren']);
                
                $result = $sci_favoris_handler->remove_favori($sci_data['siren']);
                if (is_wp_error($result)) {
                    error_log("SCI Favoris AJAX - Erreur suppression: " . $result->get_error_message());
                    wp_send_json_error($result->get_error_message());
                } else {
                    error_log("SCI Favoris AJAX - Favori supprimé avec succès");
                    wp_send_json_success('Favori supprimé');
                }
                break;
                
            case 'get':
                error_log("SCI Favoris AJAX - Récupération des favoris");
                $favoris = $sci_favoris_handler->get_favoris();
                error_log("SCI Favoris AJAX - " . count($favoris) . " favoris récupérés");
                wp_send_json_success($favoris);
                break;
                
            default:
                error_log("SCI Favoris AJAX - Erreur: Opération invalide: $operation");
                wp_send_json_error('Opération invalide');
        }
    } catch (Exception $e) {
        error_log("SCI Favoris AJAX - Exception: " . $e->getMessage());
        wp_send_json_error('Erreur interne du serveur');
    } catch (Error $e) {
        error_log("SCI Favoris AJAX - Error: " . $e->getMessage());
        wp_send_json_error('Erreur interne du serveur');
    }
}

add_action('wp_ajax_sci_manage_favoris', 'sci_manage_favoris_ajax');
add_action('wp_ajax_nopriv_sci_manage_favoris', 'sci_manage_favoris_ajax');