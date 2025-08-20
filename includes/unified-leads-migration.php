<?php
if (!defined('ABSPATH')) exit;

/**
 * Script de migration pour le système unifié de gestion des leads
 * Permet de migrer les données existantes et de tester l'infrastructure
 */
class Unified_Leads_Migration {
    
    private static $instance = null;
    private $leads_manager;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->leads_manager = Unified_Leads_Manager::get_instance();
        
        // Ajouter les actions AJAX pour la migration
        add_action('wp_ajax_migrate_sci_favorites', array($this, 'ajax_migrate_sci_favorites'));
        add_action('wp_ajax_migrate_dpe_favorites', array($this, 'ajax_migrate_dpe_favorites'));
        add_action('wp_ajax_migrate_all_favorites', array($this, 'ajax_migrate_all_favorites'));
        add_action('wp_ajax_test_unified_leads', array($this, 'ajax_test_unified_leads'));
    }
    
    /**
     * Teste l'infrastructure complète
     */
    public function test_infrastructure() {
        $results = array(
            'tables_created' => false,
            'sci_migration' => 0,
            'dpe_migration' => 0,
            'test_leads' => 0,
            'errors' => array()
        );
        
        try {
            // 1. Vérifier que les tables sont créées
            $this->leads_manager->create_tables();
            $results['tables_created'] = $this->verify_tables_exist();
            
            if (!$results['tables_created']) {
                $results['errors'][] = 'Les tables n\'ont pas pu être créées';
                return $results;
            }
            
            // 2. Migrer les favoris SCI
            $results['sci_migration'] = $this->leads_manager->migrate_sci_favorites();
            
            // 3. Migrer les favoris DPE
            $results['dpe_migration'] = $this->leads_manager->migrate_dpe_favorites();
            
            // 4. Créer des leads de test
            $results['test_leads'] = $this->create_test_leads();
            
        } catch (Exception $e) {
            $results['errors'][] = 'Erreur lors du test : ' . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Vérifie que les tables existent
     */
    public function verify_tables_exist() {
        global $wpdb;
        
        $leads_table = $wpdb->prefix . 'my_istymo_unified_leads';
        $actions_table = $wpdb->prefix . 'my_istymo_lead_actions';
        
        $leads_exists = $wpdb->get_var("SHOW TABLES LIKE '{$leads_table}'") === $leads_table;
        $actions_exists = $wpdb->get_var("SHOW TABLES LIKE '{$actions_table}'") === $actions_table;
        
        return $leads_exists && $actions_exists;
    }
    
    /**
     * Crée des leads de test pour valider le système
     */
    private function create_test_leads() {
        $test_leads = array(
            array(
                'lead_type' => 'sci',
                'original_id' => 'TEST_SCI_001',
                'status' => 'nouveau',
                'priorite' => 'haute',
                'notes' => 'Lead de test SCI - haute priorité',
                'data_originale' => array(
                    'siren' => 'TEST_SCI_001',
                    'denomination' => 'Entreprise Test SCI',
                    'dirigeant' => 'Jean Test',
                    'adresse' => '123 Rue Test',
                    'ville' => 'Paris',
                    'code_postal' => '75001'
                )
            ),
            array(
                'lead_type' => 'dpe',
                'original_id' => 'TEST_DPE_001',
                'status' => 'en_cours',
                'priorite' => 'normale',
                'notes' => 'Lead de test DPE - en cours',
                'data_originale' => array(
                    'dpe_id' => 'TEST_DPE_001',
                    'adresse_ban' => '456 Avenue Test',
                    'code_postal_ban' => '75002',
                    'nom_commune_ban' => 'Paris',
                    'etiquette_dpe' => 'C',
                    'etiquette_ges' => '3',
                    'surface_habitable_logement' => 80,
                    'annee_construction' => 1990,
                    'type_batiment' => 'Appartement'
                )
            ),
            array(
                'lead_type' => 'sci',
                'original_id' => 'TEST_SCI_002',
                'status' => 'qualifie',
                'priorite' => 'urgente',
                'notes' => 'Lead de test SCI - qualifié urgent',
                'data_originale' => array(
                    'siren' => 'TEST_SCI_002',
                    'denomination' => 'Autre Entreprise Test',
                    'dirigeant' => 'Marie Test',
                    'adresse' => '789 Boulevard Test',
                    'ville' => 'Lyon',
                    'code_postal' => '69001'
                )
            )
        );
        
        $created_count = 0;
        
        foreach ($test_leads as $test_lead) {
            $result = $this->leads_manager->add_lead($test_lead);
            if (!is_wp_error($result)) {
                $created_count++;
            }
        }
        
        return $created_count;
    }
    
    /**
     * Récupère les statistiques de migration
     */
    public function get_migration_statistics() {
        global $wpdb;
        
        $leads_table = $wpdb->prefix . 'my_istymo_unified_leads';
        $sci_table = $wpdb->prefix . 'sci_favoris';
        $dpe_table = $wpdb->prefix . 'dpe_favoris';
        
        $stats = array(
            'unified_leads_total' => 0,
            'unified_leads_sci' => 0,
            'unified_leads_dpe' => 0,
            'original_sci_favorites' => 0,
            'original_dpe_favorites' => 0,
            'migration_progress' => array()
        );
        
        // Compter les leads unifiés
        if ($this->verify_tables_exist()) {
            $stats['unified_leads_total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table}");
            $stats['unified_leads_sci'] = $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE lead_type = 'sci'");
            $stats['unified_leads_dpe'] = $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table} WHERE lead_type = 'dpe'");
        }
        
        // Compter les favoris originaux
        $sci_exists = $wpdb->get_var("SHOW TABLES LIKE '{$sci_table}'") === $sci_table;
        if ($sci_exists) {
            $stats['original_sci_favorites'] = $wpdb->get_var("SELECT COUNT(*) FROM {$sci_table}");
        }
        
        $dpe_exists = $wpdb->get_var("SHOW TABLES LIKE '{$dpe_table}'") === $dpe_table;
        if ($dpe_exists) {
            $stats['original_dpe_favorites'] = $wpdb->get_var("SELECT COUNT(*) FROM {$dpe_table}");
        }
        
        // Calculer le progrès de migration
        if ($stats['original_sci_favorites'] > 0) {
            $stats['migration_progress']['sci'] = round(($stats['unified_leads_sci'] / $stats['original_sci_favorites']) * 100, 2);
        } else {
            $stats['migration_progress']['sci'] = 100;
        }
        
        if ($stats['original_dpe_favorites'] > 0) {
            $stats['migration_progress']['dpe'] = round(($stats['unified_leads_dpe'] / $stats['original_dpe_favorites']) * 100, 2);
        } else {
            $stats['migration_progress']['dpe'] = 100;
        }
        
        return $stats;
    }
    
    /**
     * AJAX: Migrer les favoris SCI
     */
    public function ajax_migrate_sci_favorites() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'unified_leads_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $count = $this->leads_manager->migrate_sci_favorites();
        wp_send_json_success(array(
            'migrated_count' => $count,
            'message' => "Migration SCI terminée : {$count} leads migrés"
        ));
    }
    
    /**
     * AJAX: Migrer les favoris DPE
     */
    public function ajax_migrate_dpe_favorites() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'unified_leads_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $count = $this->leads_manager->migrate_dpe_favorites();
        wp_send_json_success(array(
            'migrated_count' => $count,
            'message' => "Migration DPE terminée : {$count} leads migrés"
        ));
    }
    
    /**
     * AJAX: Migrer tous les favoris
     */
    public function ajax_migrate_all_favorites() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'unified_leads_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $sci_count = $this->leads_manager->migrate_sci_favorites();
        $dpe_count = $this->leads_manager->migrate_dpe_favorites();
        
        wp_send_json_success(array(
            'sci_migrated' => $sci_count,
            'dpe_migrated' => $dpe_count,
            'total_migrated' => $sci_count + $dpe_count,
            'message' => "Migration complète terminée : {$sci_count} SCI + {$dpe_count} DPE = " . ($sci_count + $dpe_count) . " leads migrés"
        ));
    }
    
    /**
     * AJAX: Tester l'infrastructure
     */
    public function ajax_test_unified_leads() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'unified_leads_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $results = $this->test_infrastructure();
        wp_send_json_success($results);
    }
    
    /**
     * Génère un rapport de migration
     */
    public function generate_migration_report() {
        $stats = $this->get_migration_statistics();
        $report = array(
            'date' => current_time('Y-m-d H:i:s'),
            'statistics' => $stats,
            'infrastructure_status' => $this->verify_tables_exist(),
            'recommendations' => array()
        );
        
        // Ajouter des recommandations basées sur les statistiques
        if ($stats['migration_progress']['sci'] < 100) {
            $report['recommendations'][] = 'Migration SCI incomplète - vérifier les données source';
        }
        
        if ($stats['migration_progress']['dpe'] < 100) {
            $report['recommendations'][] = 'Migration DPE incomplète - vérifier les données source';
        }
        
        if ($stats['unified_leads_total'] === 0) {
            $report['recommendations'][] = 'Aucun lead unifié trouvé - exécuter la migration';
        }
        
        return $report;
    }
    
    /**
     * Nettoie les données de test
     */
    public function cleanup_test_data() {
        global $wpdb;
        
        $leads_table = $wpdb->prefix . 'my_istymo_unified_leads';
        
        $deleted_count = $wpdb->delete(
            $leads_table,
            array('notes' => array('LIKE', '%Lead de test%')),
            array('%s')
        );
        
        return $deleted_count;
    }
}

// Initialiser le gestionnaire de migration
Unified_Leads_Migration::get_instance();
