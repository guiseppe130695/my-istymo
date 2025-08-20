<?php
/**
 * Script de Test Automatisé - Phase 3: Fonctionnalités Avancées
 * 
 * Ce script teste automatiquement les fonctionnalités clés de la Phase 3
 * pour valider que tout fonctionne correctement.
 * 
 * @package My_Istymo
 * @since 1.0.0
 */

// Sécurité : Vérifier que nous sommes dans WordPress
if (!defined('ABSPATH')) {
    // Si appelé directement, charger WordPress
    require_once('../../../wp-load.php');
}

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    wp_die('Permissions insuffisantes pour exécuter les tests.');
}

/**
 * Classe de test pour la Phase 3
 */
class Phase3_Test_Suite {
    
    private $test_results = [];
    private $leads_manager;
    private $actions_manager;
    private $workflow_manager;
    private $status_manager;
    
    public function __construct() {
        // Initialiser les managers
        $this->leads_manager = Unified_Leads_Manager::get_instance();
        $this->actions_manager = Lead_Actions_Manager::get_instance();
        $this->workflow_manager = Lead_Workflow::get_instance();
        $this->status_manager = Lead_Status_Manager::get_instance();
    }
    
    /**
     * Exécuter tous les tests
     */
    public function run_all_tests() {
        echo "<h1>🧪 Tests Automatisés - Phase 3: Fonctionnalités Avancées</h1>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 5px;'>\n";
        
        $this->test_1_classes_loaded();
        $this->test_2_database_tables();
        $this->test_3_lead_actions_manager();
        $this->test_4_lead_workflow();
        $this->test_5_lead_status_manager();
        $this->test_6_ajax_handlers();
        $this->test_7_templates();
        $this->test_8_assets();
        
        $this->display_results();
        
        echo "</div>\n";
    }
    
    /**
     * Test 1: Vérifier que toutes les classes sont chargées
     */
    private function test_1_classes_loaded() {
        echo "<h3>📋 Test 1: Classes Chargées</h3>\n";
        
        $classes_to_test = [
            'Unified_Leads_Manager',
            'Lead_Actions_Manager', 
            'Lead_Workflow',
            'Lead_Status_Manager',
            'Unified_Leads_Migration',
            'Unified_Leads_Test'
        ];
        
        $all_loaded = true;
        
        foreach ($classes_to_test as $class) {
            if (class_exists($class)) {
                echo "✅ {$class} - Chargée\n";
                $this->test_results['classes'][$class] = true;
            } else {
                echo "❌ {$class} - Non chargée\n";
                $this->test_results['classes'][$class] = false;
                $all_loaded = false;
            }
        }
        
        $this->test_results['classes_loaded'] = $all_loaded;
        echo "<strong>Résultat: " . ($all_loaded ? "✅ Toutes les classes sont chargées" : "❌ Certaines classes manquent") . "</strong>\n\n";
    }
    
    /**
     * Test 2: Vérifier les tables de base de données
     */
    private function test_2_database_tables() {
        echo "<h3>🗄️ Test 2: Tables de Base de Données</h3>\n";
        
        global $wpdb;
        
        $tables_to_test = [
            $wpdb->prefix . 'my_istymo_unified_leads',
            $wpdb->prefix . 'my_istymo_lead_actions'
        ];
        
        $all_tables_exist = true;
        
        foreach ($tables_to_test as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
            
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                echo "✅ {$table} - Existe ({$count} enregistrements)\n";
                $this->test_results['tables'][$table] = ['exists' => true, 'count' => $count];
            } else {
                echo "❌ {$table} - N'existe pas\n";
                $this->test_results['tables'][$table] = ['exists' => false, 'count' => 0];
                $all_tables_exist = false;
            }
        }
        
        $this->test_results['tables_exist'] = $all_tables_exist;
        echo "<strong>Résultat: " . ($all_tables_exist ? "✅ Toutes les tables existent" : "❌ Certaines tables manquent") . "</strong>\n\n";
    }
    
    /**
     * Test 3: Tester le Lead Actions Manager
     */
    private function test_3_lead_actions_manager() {
        echo "<h3>📞 Test 3: Lead Actions Manager</h3>\n";
        
        $tests_passed = 0;
        $total_tests = 0;
        
        // Test 3.1: Obtenir les types d'actions
        $total_tests++;
        $action_types = $this->actions_manager->get_action_types();
        if (!empty($action_types) && is_array($action_types)) {
            echo "✅ Types d'actions récupérés (" . count($action_types) . " types)\n";
            $tests_passed++;
        } else {
            echo "❌ Impossible de récupérer les types d'actions\n";
        }
        
        // Test 3.2: Obtenir les résultats possibles
        $total_tests++;
        $action_results = $this->actions_manager->get_action_results();
        if (!empty($action_results) && is_array($action_results)) {
            echo "✅ Résultats d'actions récupérés (" . count($action_results) . " résultats)\n";
            $tests_passed++;
        } else {
            echo "❌ Impossible de récupérer les résultats d'actions\n";
        }
        
        // Test 3.3: Validation des types d'actions
        $total_tests++;
        $valid_types = ['appel', 'email', 'sms', 'rdv', 'note'];
        $all_valid = true;
        
        foreach ($valid_types as $type) {
            if (!$this->actions_manager->is_valid_action_type($type)) {
                $all_valid = false;
                break;
            }
        }
        
        if ($all_valid) {
            echo "✅ Validation des types d'actions fonctionne\n";
            $tests_passed++;
        } else {
            echo "❌ Problème avec la validation des types d'actions\n";
        }
        
        // Test 3.4: Validation des résultats
        $total_tests++;
        $valid_results = ['reussi', 'echec', 'en_attente', 'reporte'];
        $all_valid = true;
        
        foreach ($valid_results as $result) {
            if (!$this->actions_manager->is_valid_result($result)) {
                $all_valid = false;
                break;
            }
        }
        
        if ($all_valid) {
            echo "✅ Validation des résultats fonctionne\n";
            $tests_passed++;
        } else {
            echo "❌ Problème avec la validation des résultats\n";
        }
        
        $this->test_results['actions_manager'] = $tests_passed / $total_tests;
        echo "<strong>Résultat: {$tests_passed}/{$total_tests} tests réussis</strong>\n\n";
    }
    
    /**
     * Test 4: Tester le Lead Workflow
     */
    private function test_4_lead_workflow() {
        echo "<h3>🔄 Test 4: Lead Workflow</h3>\n";
        
        $tests_passed = 0;
        $total_tests = 0;
        
        // Test 4.1: Obtenir tous les statuts
        $total_tests++;
        $statuses = $this->workflow_manager->get_all_statuses();
        if (!empty($statuses) && is_array($statuses)) {
            echo "✅ Statuts récupérés (" . count($statuses) . " statuts)\n";
            $tests_passed++;
        } else {
            echo "❌ Impossible de récupérer les statuts\n";
        }
        
        // Test 4.2: Transitions autorisées
        $total_tests++;
        $transitions = $this->workflow_manager->get_allowed_transitions('nouveau');
        if (is_array($transitions) && in_array('en_cours', $transitions)) {
            echo "✅ Transitions autorisées fonctionnent\n";
            $tests_passed++;
        } else {
            echo "❌ Problème avec les transitions autorisées\n";
        }
        
        // Test 4.3: Validation des transitions
        $total_tests++;
        $valid = $this->workflow_manager->is_transition_allowed('nouveau', 'en_cours');
        $invalid = $this->workflow_manager->is_transition_allowed('nouveau', 'gagne');
        
        if ($valid && !$invalid) {
            echo "✅ Validation des transitions fonctionne\n";
            $tests_passed++;
        } else {
            echo "❌ Problème avec la validation des transitions\n";
        }
        
        // Test 4.4: Actions suggérées
        $total_tests++;
        $suggested = $this->workflow_manager->get_suggested_actions('nouveau');
        if (!empty($suggested) && is_array($suggested)) {
            echo "✅ Actions suggérées récupérées (" . count($suggested) . " actions)\n";
            $tests_passed++;
        } else {
            echo "❌ Impossible de récupérer les actions suggérées\n";
        }
        
        $this->test_results['workflow'] = $tests_passed / $total_tests;
        echo "<strong>Résultat: {$tests_passed}/{$total_tests} tests réussis</strong>\n\n";
    }
    
    /**
     * Test 5: Tester le Lead Status Manager
     */
    private function test_5_lead_status_manager() {
        echo "<h3>🏷️ Test 5: Lead Status Manager</h3>\n";
        
        $tests_passed = 0;
        $total_tests = 0;
        
        // Test 5.1: Obtenir les statuts
        $total_tests++;
        $statuses = $this->status_manager->get_all_statuses();
        if (!empty($statuses) && is_array($statuses)) {
            echo "✅ Statuts récupérés (" . count($statuses) . " statuts)\n";
            $tests_passed++;
        } else {
            echo "❌ Impossible de récupérer les statuts\n";
        }
        
        // Test 5.2: Obtenir les priorités
        $total_tests++;
        $priorities = $this->status_manager->get_all_priorities();
        if (!empty($priorities) && is_array($priorities)) {
            echo "✅ Priorités récupérées (" . count($priorities) . " priorités)\n";
            $tests_passed++;
        } else {
            echo "❌ Impossible de récupérer les priorités\n";
        }
        
        // Test 5.3: Validation des statuts
        $total_tests++;
        $valid_statuses = ['nouveau', 'en_cours', 'qualifie', 'proposition', 'negocie', 'gagne', 'perdu', 'en_attente'];
        $all_valid = true;
        
        foreach ($valid_statuses as $status) {
            if (!$this->status_manager->is_valid_status($status)) {
                $all_valid = false;
                break;
            }
        }
        
        if ($all_valid) {
            echo "✅ Validation des statuts fonctionne\n";
            $tests_passed++;
        } else {
            echo "❌ Problème avec la validation des statuts\n";
        }
        
        $this->test_results['status_manager'] = $tests_passed / $total_tests;
        echo "<strong>Résultat: {$tests_passed}/{$total_tests} tests réussis</strong>\n\n";
    }
    
    /**
     * Test 6: Vérifier les handlers AJAX
     */
    private function test_6_ajax_handlers() {
        echo "<h3>⚡ Test 6: Handlers AJAX</h3>\n";
        
        $tests_passed = 0;
        $total_tests = 0;
        
        // Test 6.1: Vérifier que les actions AJAX sont enregistrées
        $total_tests++;
        $ajax_actions = [
            'my_istymo_add_lead_action',
            'my_istymo_update_lead_action',
            'my_istymo_delete_lead_action',
            'my_istymo_get_lead_action',
            'my_istymo_change_lead_status',
            'my_istymo_get_workflow_transitions',
            'my_istymo_validate_workflow_transition'
        ];
        
        $all_registered = true;
        foreach ($ajax_actions as $action) {
            if (!has_action("wp_ajax_{$action}")) {
                $all_registered = false;
                break;
            }
        }
        
        if ($all_registered) {
            echo "✅ Tous les handlers AJAX sont enregistrés\n";
            $tests_passed++;
        } else {
            echo "❌ Certains handlers AJAX manquent\n";
        }
        
        $this->test_results['ajax_handlers'] = $tests_passed / $total_tests;
        echo "<strong>Résultat: {$tests_passed}/{$total_tests} tests réussis</strong>\n\n";
    }
    
    /**
     * Test 7: Vérifier les templates
     */
    private function test_7_templates() {
        echo "<h3>📄 Test 7: Templates</h3>\n";
        
        $tests_passed = 0;
        $total_tests = 0;
        
        $template_files = [
            'templates/unified-leads-admin.php',
            'templates/unified-leads-config.php',
            'templates/lead-detail-modal.php'
        ];
        
        foreach ($template_files as $template) {
            $total_tests++;
            $file_path = plugin_dir_path(__FILE__) . $template;
            
            if (file_exists($file_path)) {
                echo "✅ {$template} - Existe\n";
                $tests_passed++;
            } else {
                echo "❌ {$template} - Manquant\n";
            }
        }
        
        $this->test_results['templates'] = $tests_passed / $total_tests;
        echo "<strong>Résultat: {$tests_passed}/{$total_tests} templates trouvés</strong>\n\n";
    }
    
    /**
     * Test 8: Vérifier les assets (CSS/JS)
     */
    private function test_8_assets() {
        echo "<h3>🎨 Test 8: Assets (CSS/JS)</h3>\n";
        
        $tests_passed = 0;
        $total_tests = 0;
        
        $asset_files = [
            'assets/css/unified-leads.css',
            'assets/js/unified-leads-admin.js',
            'assets/js/lead-actions.js',
            'assets/js/lead-workflow.js'
        ];
        
        foreach ($asset_files as $asset) {
            $total_tests++;
            $file_path = plugin_dir_path(__FILE__) . $asset;
            
            if (file_exists($file_path)) {
                $size = filesize($file_path);
                echo "✅ {$asset} - Existe ({$size} octets)\n";
                $tests_passed++;
            } else {
                echo "❌ {$asset} - Manquant\n";
            }
        }
        
        $this->test_results['assets'] = $tests_passed / $total_tests;
        echo "<strong>Résultat: {$tests_passed}/{$total_tests} assets trouvés</strong>\n\n";
    }
    
    /**
     * Afficher les résultats finaux
     */
    private function display_results() {
        echo "<h2>📊 Résultats Finaux</h2>\n";
        
        $total_score = 0;
        $total_tests = 0;
        
        foreach ($this->test_results as $category => $result) {
            if (is_numeric($result)) {
                $percentage = round($result * 100, 1);
                $status = $result >= 0.8 ? "✅" : ($result >= 0.6 ? "⚠️" : "❌");
                echo "<strong>{$status} {$category}: {$percentage}%</strong><br>\n";
                $total_score += $result;
                $total_tests++;
            }
        }
        
        if ($total_tests > 0) {
            $overall_score = $total_score / $total_tests;
            $overall_percentage = round($overall_score * 100, 1);
            $overall_status = $overall_score >= 0.8 ? "✅" : ($overall_score >= 0.6 ? "⚠️" : "❌");
            
            echo "<br><h3>{$overall_status} Score Global: {$overall_percentage}%</h3>\n";
            
            if ($overall_score >= 0.8) {
                echo "<p style='color: green;'><strong>🎉 Phase 3 prête pour la production !</strong></p>\n";
            } elseif ($overall_score >= 0.6) {
                echo "<p style='color: orange;'><strong>⚠️ Phase 3 fonctionnelle mais nécessite des améliorations</strong></p>\n";
            } else {
                echo "<p style='color: red;'><strong>❌ Phase 3 nécessite des corrections avant utilisation</strong></p>\n";
            }
        }
        
        echo "<br><p><em>Tests exécutés le " . current_time('d/m/Y H:i:s') . "</em></p>\n";
    }
}

// Exécuter les tests si le script est appelé directement
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test_suite = new Phase3_Test_Suite();
    $test_suite->run_all_tests();
}
?>
