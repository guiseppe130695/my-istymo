<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire des statuts et priorités des leads
 * Définit les statuts disponibles et leurs propriétés
 */
class Lead_Status_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Pas d'initialisation spéciale nécessaire
    }
    
    /**
     * Récupère tous les statuts disponibles avec leurs propriétés
     */
    public function get_available_statuses() {
        return array(
            'nouveau' => array(
                'label' => 'Nouveau',
                'color' => '#0073aa',
                'icon' => 'dashicons-plus',
                'description' => 'Lead nouvellement créé',
                'can_transition_to' => array('en_cours', 'qualifie', 'perdu')
            ),
            'en_cours' => array(
                'label' => 'En cours',
                'color' => '#ffba00',
                'icon' => 'dashicons-clock',
                'description' => 'Lead en cours de traitement',
                'can_transition_to' => array('qualifie', 'perdu', 'en_attente')
            ),
            'qualifie' => array(
                'label' => 'Qualifié',
                'color' => '#46b450',
                'icon' => 'dashicons-yes',
                'description' => 'Lead qualifié et prêt pour la conversion',
                'can_transition_to' => array('convertis', 'perdu')
            ),
            'convertis' => array(
                'label' => 'Converti',
                'color' => '#00a32a',
                'icon' => 'dashicons-star-filled',
                'description' => 'Lead converti en client',
                'can_transition_to' => array('perdu')
            ),
            'perdu' => array(
                'label' => 'Perdu',
                'color' => '#dc3232',
                'icon' => 'dashicons-dismiss',
                'description' => 'Lead perdu ou non intéressé',
                'can_transition_to' => array('nouveau', 'en_cours')
            ),
            'en_attente' => array(
                'label' => 'En attente',
                'color' => '#999999',
                'icon' => 'dashicons-pause',
                'description' => 'Lead en attente de réponse',
                'can_transition_to' => array('en_cours', 'qualifie', 'perdu')
            )
        );
    }
    
    /**
     * Récupère toutes les priorités disponibles avec leurs propriétés
     */
    public function get_available_priorities() {
        return array(
            'basse' => array(
                'label' => 'Basse',
                'color' => '#999999',
                'icon' => 'dashicons-arrow-down',
                'description' => 'Priorité basse',
                'weight' => 1
            ),
            'normale' => array(
                'label' => 'Normale',
                'color' => '#0073aa',
                'icon' => 'dashicons-minus',
                'description' => 'Priorité normale',
                'weight' => 2
            ),
            'haute' => array(
                'label' => 'Haute',
                'color' => '#ffba00',
                'icon' => 'dashicons-arrow-up',
                'description' => 'Priorité haute',
                'weight' => 3
            ),
            'urgente' => array(
                'label' => 'Urgente',
                'color' => '#dc3232',
                'icon' => 'dashicons-warning',
                'description' => 'Priorité urgente',
                'weight' => 4
            )
        );
    }
    
    /**
     * Vérifie si une transition de statut est autorisée
     */
    public function can_transition_status($from_status, $to_status) {
        $statuses = $this->get_available_statuses();
        
        if (!isset($statuses[$from_status]) || !isset($statuses[$to_status])) {
            return false;
        }
        
        return in_array($to_status, $statuses[$from_status]['can_transition_to']);
    }
    
    /**
     * Récupère les statuts vers lesquels on peut transitionner depuis un statut donné
     */
    public function get_next_possible_statuses($current_status) {
        $statuses = $this->get_available_statuses();
        
        if (!isset($statuses[$current_status])) {
            return array();
        }
        
        return $statuses[$current_status]['can_transition_to'];
    }
    
    /**
     * Récupère les propriétés d'un statut
     */
    public function get_status_properties($status) {
        $statuses = $this->get_available_statuses();
        
        return isset($statuses[$status]) ? $statuses[$status] : null;
    }
    
    /**
     * Récupère les propriétés d'une priorité
     */
    public function get_priority_properties($priority) {
        $priorities = $this->get_available_priorities();
        
        return isset($priorities[$priority]) ? $priorities[$priority] : null;
    }
    
    /**
     * Génère le HTML pour un badge de statut
     */
    public function get_status_badge($status, $show_icon = true) {
        $properties = $this->get_status_properties($status);
        
        if (!$properties) {
            return '<span class="lead-status-badge lead-status-unknown">Inconnu</span>';
        }
        
        $icon_class = $show_icon ? $properties['icon'] : '';
        $icon_html = $show_icon ? '<i class="dashicons ' . $icon_class . '"></i>' : '';
        
        return sprintf(
            '<span class="lead-status-badge lead-status-%s" style="background-color: %s; color: white;">
                %s %s
            </span>',
            esc_attr($status),
            esc_attr($properties['color']),
            $icon_html,
            esc_html($properties['label'])
        );
    }
    
    /**
     * Génère le HTML pour un badge de priorité
     */
    public function get_priority_badge($priority, $show_icon = true) {
        $properties = $this->get_priority_properties($priority);
        
        if (!$properties) {
            return '<span class="lead-priority-badge lead-priority-unknown">Inconnue</span>';
        }
        
        $icon_class = $show_icon ? $properties['icon'] : '';
        $icon_html = $show_icon ? '<i class="dashicons ' . $icon_class . '"></i>' : '';
        
        return sprintf(
            '<span class="lead-priority-badge lead-priority-%s" style="background-color: %s; color: white;">
                %s %s
            </span>',
            esc_attr($priority),
            esc_attr($properties['color']),
            $icon_html,
            esc_html($properties['label'])
        );
    }
    
    /**
     * Génère les options HTML pour un select de statuts
     */
    public function get_status_options($selected_status = '') {
        $statuses = $this->get_available_statuses();
        $options = '';
        
        foreach ($statuses as $status => $properties) {
            $selected = ($status === $selected_status) ? 'selected' : '';
            $options .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($status),
                $selected,
                esc_html($properties['label'])
            );
        }
        
        return $options;
    }
    
    /**
     * Génère les options HTML pour un select de priorités
     */
    public function get_priority_options($selected_priority = '') {
        $priorities = $this->get_available_priorities();
        $options = '';
        
        foreach ($priorities as $priority => $properties) {
            $selected = ($priority === $selected_priority) ? 'selected' : '';
            $options .= sprintf(
                '<option value="%s" %s>%s</option>',
                esc_attr($priority),
                $selected,
                esc_html($properties['label'])
            );
        }
        
        return $options;
    }
    
    /**
     * Récupère les statistiques des statuts pour un utilisateur
     */
    public function get_status_statistics($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array();
        }
        
        global $wpdb;
        $leads_table = $wpdb->prefix . 'my_istymo_unified_leads';
        
        $sql = $wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM {$leads_table} WHERE user_id = %d GROUP BY status",
            $user_id
        );
        
        $results = $wpdb->get_results($sql);
        $statistics = array();
        
        // Initialiser tous les statuts avec 0
        $statuses = $this->get_available_statuses();
        foreach ($statuses as $status => $properties) {
            $statistics[$status] = array(
                'count' => 0,
                'label' => $properties['label'],
                'color' => $properties['color']
            );
        }
        
        // Remplir avec les vraies données
        foreach ($results as $result) {
            if (isset($statistics[$result->status])) {
                $statistics[$result->status]['count'] = intval($result->count);
            }
        }
        
        return $statistics;
    }
    
    /**
     * Récupère les statistiques des priorités pour un utilisateur
     */
    public function get_priority_statistics($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return array();
        }
        
        global $wpdb;
        $leads_table = $wpdb->prefix . 'my_istymo_unified_leads';
        
        $sql = $wpdb->prepare(
            "SELECT priorite, COUNT(*) as count FROM {$leads_table} WHERE user_id = %d GROUP BY priorite",
            $user_id
        );
        
        $results = $wpdb->get_results($sql);
        $statistics = array();
        
        // Initialiser toutes les priorités avec 0
        $priorities = $this->get_available_priorities();
        foreach ($priorities as $priority => $properties) {
            $statistics[$priority] = array(
                'count' => 0,
                'label' => $properties['label'],
                'color' => $properties['color']
            );
        }
        
        // Remplir avec les vraies données
        foreach ($results as $result) {
            if (isset($statistics[$result->priorite])) {
                $statistics[$result->priorite]['count'] = intval($result->count);
            }
        }
        
        return $statistics;
    }
    
    /**
     * Valide un statut
     */
    public function is_valid_status($status) {
        $statuses = $this->get_available_statuses();
        return isset($statuses[$status]);
    }
    
    /**
     * Valide une priorité
     */
    public function is_valid_priority($priority) {
        $priorities = $this->get_available_priorities();
        return isset($priorities[$priority]);
    }
    
    /**
     * Récupère le statut par défaut
     */
    public function get_default_status() {
        return 'nouveau';
    }
    
    /**
     * Récupère la priorité par défaut
     */
    public function get_default_priority() {
        return 'normale';
    }
}

// Initialiser le gestionnaire
Lead_Status_Manager::get_instance();
