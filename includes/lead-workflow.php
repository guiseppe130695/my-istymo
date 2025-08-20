<?php
/**
 * Gestionnaire de Workflow pour les Leads
 * 
 * Cette classe gère les transitions de statuts autorisées et les règles
 * de workflow pour les leads unifiés.
 * 
 * @package My_Istymo
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class Lead_Workflow {
    
    /**
     * Instance de la classe (Singleton)
     */
    private static $instance = null;
    
    /**
     * Transitions de statuts autorisées
     * Format : [statut_actuel => [statuts_autorisés]]
     */
    private $allowed_transitions = [
        'nouveau' => ['en_cours', 'qualifie', 'perdu'],
        'en_cours' => ['qualifie', 'perdu', 'en_attente'],
        'qualifie' => ['proposition', 'gagne', 'perdu'],
        'proposition' => ['gagne', 'perdu', 'negocie'],
        'negocie' => ['gagne', 'perdu'],
        'gagne' => [], // Statut final
        'perdu' => ['nouveau'], // Possibilité de relancer
        'en_attente' => ['en_cours', 'perdu']
    ];
    
    /**
     * Actions suggérées par statut
     */
    private $suggested_actions = [
        'nouveau' => [
            'appel' => 'Premier contact téléphonique',
            'email' => 'Email de présentation',
            'note' => 'Notes de qualification'
        ],
        'en_cours' => [
            'appel' => 'Suivi téléphonique',
            'email' => 'Envoi de documentation',
            'rdv' => 'Programmer un rendez-vous',
            'note' => 'Notes de suivi'
        ],
        'qualifie' => [
            'appel' => 'Appel de qualification',
            'email' => 'Envoi de proposition',
            'rdv' => 'Rendez-vous de présentation',
            'note' => 'Notes de qualification'
        ],
        'proposition' => [
            'appel' => 'Suivi de proposition',
            'email' => 'Relance de proposition',
            'rdv' => 'Rendez-vous de négociation',
            'note' => 'Notes de négociation'
        ],
        'negocie' => [
            'appel' => 'Négociation téléphonique',
            'email' => 'Contre-proposition',
            'rdv' => 'Rendez-vous de clôture',
            'note' => 'Notes de négociation'
        ],
        'gagne' => [
            'note' => 'Notes de clôture',
            'email' => 'Email de remerciement'
        ],
        'perdu' => [
            'note' => 'Raison de la perte',
            'email' => 'Email de relance future'
        ],
        'en_attente' => [
            'appel' => 'Relance téléphonique',
            'email' => 'Email de relance',
            'note' => 'Notes de relance'
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
     * Obtenir les transitions autorisées pour un statut
     */
    public function get_allowed_transitions($current_status) {
        return $this->allowed_transitions[$current_status] ?? [];
    }
    
    /**
     * Vérifier si une transition est autorisée
     */
    public function is_transition_allowed($current_status, $new_status) {
        $allowed = $this->get_allowed_transitions($current_status);
        return in_array($new_status, $allowed);
    }
    
    /**
     * Obtenir tous les statuts disponibles
     */
    public function get_all_statuses() {
        return array_keys($this->allowed_transitions);
    }
    
    /**
     * Obtenir les actions suggérées pour un statut
     */
    public function get_suggested_actions($status) {
        return $this->suggested_actions[$status] ?? [];
    }
    
    /**
     * Obtenir les actions suggérées pour une transition
     */
    public function get_suggested_actions_for_transition($from_status, $to_status) {
        // Actions suggérées pour le nouveau statut
        $actions = $this->get_suggested_actions($to_status);
        
        // Actions spécifiques selon la transition
        $transition_actions = $this->get_transition_specific_actions($from_status, $to_status);
        
        return array_merge($actions, $transition_actions);
    }
    
    /**
     * Obtenir les actions spécifiques à une transition
     */
    private function get_transition_specific_actions($from_status, $to_status) {
        $specific_actions = [
            'nouveau' => [
                'en_cours' => [
                    'appel' => 'Premier appel de qualification'
                ],
                'qualifie' => [
                    'appel' => 'Appel de qualification approfondie',
                    'email' => 'Email de qualification'
                ]
            ],
            'en_cours' => [
                'qualifie' => [
                    'appel' => 'Appel de validation',
                    'note' => 'Notes de qualification validée'
                ],
                'perdu' => [
                    'note' => 'Raison de la perte',
                    'email' => 'Email de suivi'
                ]
            ],
            'qualifie' => [
                'proposition' => [
                    'email' => 'Envoi de proposition commerciale',
                    'rdv' => 'Rendez-vous de présentation'
                ]
            ],
            'proposition' => [
                'gagne' => [
                    'appel' => 'Appel de clôture',
                    'note' => 'Notes de clôture'
                ],
                'negocie' => [
                    'appel' => 'Appel de négociation',
                    'email' => 'Contre-proposition'
                ]
            ],
            'negocie' => [
                'gagne' => [
                    'appel' => 'Appel de finalisation',
                    'note' => 'Notes de clôture'
                ]
            ]
        ];
        
        return $specific_actions[$from_status][$to_status] ?? [];
    }
    
    /**
     * Valider une transition de statut
     */
    public function validate_transition($current_status, $new_status, $lead_data = []) {
        // Vérifier si la transition est autorisée
        if (!$this->is_transition_allowed($current_status, $new_status)) {
            return new WP_Error(
                'invalid_transition',
                sprintf('Transition non autorisée de "%s" vers "%s"', $current_status, $new_status)
            );
        }
        
        // Règles métier spécifiques
        $validation_result = $this->validate_business_rules($current_status, $new_status, $lead_data);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        return true;
    }
    
    /**
     * Valider les règles métier pour une transition
     */
    private function validate_business_rules($current_status, $new_status, $lead_data) {
        // Règle : Pour passer en "qualifie", il faut au moins une action
        if ($new_status === 'qualifie' && $current_status === 'nouveau') {
            $actions_manager = Lead_Actions_Manager::get_instance();
            $actions = $actions_manager->get_lead_history($lead_data['id'] ?? 0, 10);
            
            if (empty($actions)) {
                return new WP_Error(
                    'no_actions',
                    'Au moins une action doit être effectuée avant de qualifier un lead'
                );
            }
        }
        
        // Règle : Pour passer en "proposition", il faut être qualifié
        if ($new_status === 'proposition' && $current_status !== 'qualifie') {
            return new WP_Error(
                'not_qualified',
                'Un lead doit être qualifié avant de recevoir une proposition'
            );
        }
        
        // Règle : Pour passer en "gagne", il faut être en proposition ou négociation
        if ($new_status === 'gagne' && !in_array($current_status, ['proposition', 'negocie'])) {
            return new WP_Error(
                'not_ready_to_win',
                'Un lead doit être en proposition ou négociation pour être gagné'
            );
        }
        
        return true;
    }
    
    /**
     * Obtenir les statistiques de workflow
     */
    public function get_workflow_stats() {
        global $wpdb;
        
        $query = "
            SELECT 
                status,
                COUNT(*) as count,
                AVG(DATEDIFF(NOW(), date_creation)) as avg_days
            FROM {$wpdb->prefix}my_istymo_unified_leads
            GROUP BY status
            ORDER BY count DESC
        ";
        
        $stats = $wpdb->get_results($query);
        
        if ($stats === null) {
            return [];
        }
        
        // Enrichir avec les informations de workflow
        foreach ($stats as $stat) {
            $stat->allowed_transitions = $this->get_allowed_transitions($stat->status);
            $stat->suggested_actions = $this->get_suggested_actions($stat->status);
        }
        
        return $stats;
    }
    
    /**
     * Obtenir les leads qui nécessitent une action
     */
    public function get_leads_needing_action($user_id = null) {
        global $wpdb;
        
        $where_conditions = ["l.status IN ('nouveau', 'en_cours', 'en_attente')"];
        $prepare_values = [];
        
        if ($user_id) {
            $where_conditions[] = "l.user_id = %d";
            $prepare_values[] = $user_id;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT l.*, 
                   DATEDIFF(NOW(), l.date_creation) as days_since_creation,
                   DATEDIFF(NOW(), l.date_modification) as days_since_update
            FROM {$wpdb->prefix}my_istymo_unified_leads l
            WHERE {$where_clause}
            ORDER BY l.date_modification ASC
        ";
        
        if (!empty($prepare_values)) {
            $query = $wpdb->prepare($query, ...$prepare_values);
        }
        
        $leads = $wpdb->get_results($query);
        
        if ($leads === null) {
            return [];
        }
        
        // Enrichir avec les suggestions d'actions
        foreach ($leads as $lead) {
            $lead->suggested_actions = $this->get_suggested_actions($lead->status);
            $lead->allowed_transitions = $this->get_allowed_transitions($lead->status);
        }
        
        return $leads;
    }
    
    /**
     * Obtenir les leads en risque de perte
     */
    public function get_leads_at_risk($days_threshold = 30) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT l.*, 
                    DATEDIFF(NOW(), l.date_modification) as days_since_update
             FROM {$wpdb->prefix}my_istymo_unified_leads l
             WHERE l.status IN ('en_cours', 'en_attente', 'proposition')
             AND DATEDIFF(NOW(), l.date_modification) > %d
             ORDER BY l.date_modification ASC",
            $days_threshold
        );
        
        $leads = $wpdb->get_results($query);
        
        if ($leads === null) {
            return [];
        }
        
        // Enrichir avec les suggestions de relance
        foreach ($leads as $lead) {
            $lead->suggested_actions = $this->get_suggested_actions($lead->status);
            $lead->risk_level = $this->calculate_risk_level($lead->days_since_update);
        }
        
        return $leads;
    }
    
    /**
     * Calculer le niveau de risque d'un lead
     */
    private function calculate_risk_level($days_since_update) {
        if ($days_since_update > 60) {
            return 'eleve';
        } elseif ($days_since_update > 30) {
            return 'moyen';
        } else {
            return 'faible';
        }
    }
    
    /**
     * Obtenir les prochaines actions recommandées pour un lead
     */
    public function get_next_recommended_actions($lead_id) {
        global $wpdb;
        
        // Obtenir le lead
        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}my_istymo_unified_leads WHERE id = %d",
            $lead_id
        ));
        
        if (!$lead) {
            return [];
        }
        
        // Obtenir les actions récentes
        $actions_manager = Lead_Actions_Manager::get_instance();
        $recent_actions = $actions_manager->get_lead_history($lead_id, 5);
        
        // Actions suggérées selon le statut
        $suggested_actions = $this->get_suggested_actions($lead->status);
        
        // Filtrer les actions déjà effectuées récemment
        $recent_action_types = array_column($recent_actions, 'action_type');
        $filtered_actions = array_diff_key($suggested_actions, array_flip($recent_action_types));
        
        // Ajouter des actions de transition si applicable
        $transition_actions = [];
        foreach ($this->get_allowed_transitions($lead->status) as $next_status) {
            $transition_actions = array_merge(
                $transition_actions,
                $this->get_suggested_actions_for_transition($lead->status, $next_status)
            );
        }
        
        return array_merge($filtered_actions, $transition_actions);
    }
    
    /**
     * Obtenir un résumé du workflow pour un lead
     */
    public function get_lead_workflow_summary($lead_id) {
        global $wpdb;
        
        // Obtenir le lead
        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}my_istymo_unified_leads WHERE id = %d",
            $lead_id
        ));
        
        if (!$lead) {
            return null;
        }
        
        // Obtenir les actions
        $actions_manager = Lead_Actions_Manager::get_instance();
        $actions = $actions_manager->get_lead_history($lead_id);
        $stats = $actions_manager->get_lead_action_stats($lead_id);
        
        // Calculer les métriques
        $total_actions = array_sum(array_column($stats, 'total'));
        $successful_actions = 0;
        foreach ($stats as $type_stats) {
            if (isset($type_stats['results']['reussi'])) {
                $successful_actions += $type_stats['results']['reussi']['count'];
            }
        }
        
        $success_rate = $total_actions > 0 ? ($successful_actions / $total_actions) * 100 : 0;
        
        return [
            'lead' => $lead,
            'current_status' => $lead->status,
            'allowed_transitions' => $this->get_allowed_transitions($lead->status),
            'suggested_actions' => $this->get_suggested_actions($lead->status),
            'total_actions' => $total_actions,
            'success_rate' => round($success_rate, 1),
            'days_in_status' => $lead->date_modification ? 
                floor((time() - strtotime($lead->date_modification)) / 86400) : 0,
            'next_recommended_actions' => $this->get_next_recommended_actions($lead_id)
        ];
    }
}
