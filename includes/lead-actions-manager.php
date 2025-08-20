<?php
/**
 * Gestionnaire des Actions sur les Leads
 * 
 * Cette classe gère l'historique des actions effectuées sur les leads,
 * incluant les appels, emails, SMS, rendez-vous et notes.
 * 
 * @package My_Istymo
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class Lead_Actions_Manager {
    
    /**
     * Instance de la classe (Singleton)
     */
    private static $instance = null;
    
    /**
     * Types d'actions disponibles
     */
    private $action_types = [
        'appel' => [
            'label' => 'Appel téléphonique',
            'icon' => '📞',
            'color' => '#28a745'
        ],
        'email' => [
            'label' => 'Email',
            'icon' => '📧',
            'color' => '#007bff'
        ],
        'sms' => [
            'label' => 'SMS',
            'icon' => '💬',
            'color' => '#6f42c1'
        ],
        'rdv' => [
            'label' => 'Rendez-vous',
            'icon' => '📅',
            'color' => '#fd7e14'
        ],
        'note' => [
            'label' => 'Note',
            'icon' => '📝',
            'color' => '#6c757d'
        ]
    ];
    
    /**
     * Résultats possibles des actions
     */
    private $action_results = [
        'reussi' => [
            'label' => 'Réussi',
            'color' => '#28a745'
        ],
        'echec' => [
            'label' => 'Échec',
            'color' => '#dc3545'
        ],
        'en_attente' => [
            'label' => 'En attente',
            'color' => '#ffc107'
        ],
        'reporte' => [
            'label' => 'Reporté',
            'color' => '#6c757d'
        ]
    ];
    
    /**
     * Constructeur privé (Singleton)
     */
    private function __construct() {
        // Initialisation si nécessaire
    }
    
    /**
     * Obtenir l'instance unique (Singleton)
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtenir tous les types d'actions
     */
    public function get_action_types() {
        return $this->action_types;
    }
    
    /**
     * Obtenir tous les résultats possibles
     */
    public function get_action_results() {
        return $this->action_results;
    }
    
    /**
     * Vérifier si un type d'action est valide
     */
    public function is_valid_action_type($type) {
        return array_key_exists($type, $this->action_types);
    }
    
    /**
     * Vérifier si un résultat est valide
     */
    public function is_valid_result($result) {
        return array_key_exists($result, $this->action_results);
    }
    
    /**
     * Ajouter une action à un lead
     */
    public function add_action($lead_id, $user_id, $action_type, $description = '', $result = 'en_attente', $scheduled_date = null) {
        global $wpdb;
        
        // Validation des données
        if (!$this->is_valid_action_type($action_type)) {
            return new WP_Error('invalid_action_type', 'Type d\'action invalide');
        }
        
        if (!$this->is_valid_result($result)) {
            return new WP_Error('invalid_result', 'Résultat invalide');
        }
        
        // Vérifier que le lead existe
        $lead_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}my_istymo_unified_leads WHERE id = %d",
            $lead_id
        ));
        
        if (!$lead_exists) {
            return new WP_Error('lead_not_found', 'Lead introuvable');
        }
        
        // Préparer les données
        $data = [
            'lead_id' => $lead_id,
            'user_id' => $user_id,
            'action_type' => $action_type,
            'description' => sanitize_textarea_field($description),
            'resultat' => $result,
            'date_action' => current_time('mysql'),
            'date_planification' => $scheduled_date ? $scheduled_date : null
        ];
        
        // Insérer l'action
        $result = $wpdb->insert(
            $wpdb->prefix . 'my_istymo_lead_actions',
            $data,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            return new WP_Error('insert_failed', 'Erreur lors de l\'insertion de l\'action');
        }
        
        $action_id = $wpdb->insert_id;
        
        // Log de l'action
        error_log("Action ajoutée - ID: {$action_id}, Lead: {$lead_id}, Type: {$action_type}, Utilisateur: {$user_id}");
        
        return $action_id;
    }
    
    /**
     * Obtenir l'historique des actions d'un lead
     */
    public function get_lead_history($lead_id, $limit = 50) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT a.*, u.display_name as user_name
             FROM {$wpdb->prefix}my_istymo_lead_actions a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.lead_id = %d
             ORDER BY a.date_action DESC
             LIMIT %d",
            $lead_id,
            $limit
        );
        
        $actions = $wpdb->get_results($query);
        
        if ($actions === null) {
            return [];
        }
        
        // Enrichir les données avec les labels
        foreach ($actions as $action) {
            $action->action_type_label = $this->action_types[$action->action_type]['label'] ?? $action->action_type;
            $action->action_type_icon = $this->action_types[$action->action_type]['icon'] ?? '📋';
            $action->result_label = $this->action_results[$action->resultat]['label'] ?? $action->resultat;
        }
        
        return $actions;
    }
    
    /**
     * Obtenir les actions programmées
     */
    public function get_scheduled_actions($user_id = null, $date_from = null, $date_to = null) {
        global $wpdb;
        
        $where_conditions = ["a.date_planification IS NOT NULL"];
        $prepare_values = [];
        
        if ($user_id) {
            $where_conditions[] = "a.user_id = %d";
            $prepare_values[] = $user_id;
        }
        
        if ($date_from) {
            $where_conditions[] = "a.date_planification >= %s";
            $prepare_values[] = $date_from;
        }
        
        if ($date_to) {
            $where_conditions[] = "a.date_planification <= %s";
            $prepare_values[] = $date_to;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "SELECT a.*, u.display_name as user_name, l.lead_type, l.original_id
                 FROM {$wpdb->prefix}my_istymo_lead_actions a
                 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                 LEFT JOIN {$wpdb->prefix}my_istymo_unified_leads l ON a.lead_id = l.id
                 WHERE {$where_clause}
                 ORDER BY a.date_planification ASC";
        
        if (!empty($prepare_values)) {
            $query = $wpdb->prepare($query, ...$prepare_values);
        }
        
        $actions = $wpdb->get_results($query);
        
        if ($actions === null) {
            return [];
        }
        
        // Enrichir les données
        foreach ($actions as $action) {
            $action->action_type_label = $this->action_types[$action->action_type]['label'] ?? $action->action_type;
            $action->action_type_icon = $this->action_types[$action->action_type]['icon'] ?? '📋';
        }
        
        return $actions;
    }
    
    /**
     * Mettre à jour une action
     */
    public function update_action($action_id, $data) {
        global $wpdb;
        
        // Validation des données
        if (isset($data['action_type']) && !$this->is_valid_action_type($data['action_type'])) {
            return new WP_Error('invalid_action_type', 'Type d\'action invalide');
        }
        
        if (isset($data['resultat']) && !$this->is_valid_result($data['resultat'])) {
            return new WP_Error('invalid_result', 'Résultat invalide');
        }
        
        // Préparer les données à mettre à jour
        $update_data = [];
        $format = [];
        
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }
        
        if (isset($data['resultat'])) {
            $update_data['resultat'] = $data['resultat'];
            $format[] = '%s';
        }
        
        if (isset($data['date_planification'])) {
            $update_data['date_planification'] = $data['date_planification'];
            $format[] = '%s';
        }
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'Aucune donnée à mettre à jour');
        }
        
        // Mettre à jour l'action
        $result = $wpdb->update(
            $wpdb->prefix . 'my_istymo_lead_actions',
            $update_data,
            ['id' => $action_id],
            $format,
            ['%d']
        );
        
        if ($result === false) {
            return new WP_Error('update_failed', 'Erreur lors de la mise à jour de l\'action');
        }
        
        // Log de la mise à jour
        error_log("Action mise à jour - ID: {$action_id}");
        
        return true;
    }
    
    /**
     * Supprimer une action
     */
    public function delete_action($action_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'my_istymo_lead_actions',
            ['id' => $action_id],
            ['%d']
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', 'Erreur lors de la suppression de l\'action');
        }
        
        // Log de la suppression
        error_log("Action supprimée - ID: {$action_id}");
        
        return true;
    }
    
    /**
     * Obtenir les statistiques des actions pour un lead
     */
    public function get_lead_action_stats($lead_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT 
                action_type,
                resultat,
                COUNT(*) as count
             FROM {$wpdb->prefix}my_istymo_lead_actions
             WHERE lead_id = %d
             GROUP BY action_type, resultat",
            $lead_id
        );
        
        $stats = $wpdb->get_results($query);
        
        if ($stats === null) {
            return [];
        }
        
        // Organiser les statistiques
        $organized_stats = [];
        foreach ($stats as $stat) {
            if (!isset($organized_stats[$stat->action_type])) {
                $organized_stats[$stat->action_type] = [
                    'label' => $this->action_types[$stat->action_type]['label'] ?? $stat->action_type,
                    'icon' => $this->action_types[$stat->action_type]['icon'] ?? '📋',
                    'total' => 0,
                    'results' => []
                ];
            }
            
            $organized_stats[$stat->action_type]['total'] += $stat->count;
            $organized_stats[$stat->action_type]['results'][$stat->resultat] = [
                'label' => $this->action_results[$stat->resultat]['label'] ?? $stat->resultat,
                'count' => $stat->count
            ];
        }
        
        return $organized_stats;
    }
    
    /**
     * Programmer une action future
     */
    public function schedule_action($lead_id, $user_id, $action_type, $scheduled_date, $description = '') {
        return $this->add_action($lead_id, $user_id, $action_type, $description, 'en_attente', $scheduled_date);
    }
    
    /**
     * Marquer une action comme terminée
     */
    public function complete_action($action_id, $resultat = 'reussi', $description = '') {
        $data = [
            'resultat' => $resultat
        ];
        
        if (!empty($description)) {
            $data['description'] = $description;
        }
        
        return $this->update_action($action_id, $data);
    }
    
    /**
     * Obtenir les actions en attente pour un lead
     */
    public function get_pending_actions($lead_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT a.*, u.display_name as user_name
             FROM {$wpdb->prefix}my_istymo_lead_actions a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.lead_id = %d AND a.resultat = 'en_attente'
             ORDER BY a.date_planification ASC, a.date_action ASC",
            $lead_id
        );
        
        $actions = $wpdb->get_results($query);
        
        if ($actions === null) {
            return [];
        }
        
        // Enrichir les données
        foreach ($actions as $action) {
            $action->action_type_label = $this->action_types[$action->action_type]['label'] ?? $action->action_type;
            $action->action_type_icon = $this->action_types[$action->action_type]['icon'] ?? '📋';
        }
        
        return $actions;
    }
}
