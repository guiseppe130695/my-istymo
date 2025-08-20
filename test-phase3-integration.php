<?php
/**
 * Test d'Intégration de la Phase 3
 * 
 * Ce fichier vérifie que toutes les fonctionnalités de la phase 3
 * sont bien branchées au système actuel.
 * 
 * @package My_Istymo
 * @since 1.0.0
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class Phase3_Integration_Test {
    
    private $test_results = [];
    
    public function __construct() {
        $this->run_all_tests();
    }
    
    /**
     * Exécute tous les tests d'intégration
     */
    public function run_all_tests() {
        echo "<h2>🧪 Test d'Intégration de la Phase 3</h2>";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px; border-radius: 8px;'>";
        
        $this->test_1_classes_loaded();
        $this->test_2_tables_exist();
        $this->test_3_ajax_handlers_registered();
        $this->test_4_scripts_loaded();
        $this->test_5_templates_available();
        $this->test_6_workflow_functionality();
        $this->test_7_actions_functionality();
        
        $this->display_results();
        
        echo "</div>";
    }
    
    /**
     * Test 1 : Vérifier que les classes sont chargées
     */
    private function test_1_classes_loaded() {
        $this->test_results['classes'] = [];
        
        // Vérifier Lead_Actions_Manager
        if (class_exists('Lead_Actions_Manager')) {
            $this->test_results['classes']['Lead_Actions_Manager'] = '✅ Classe chargée';
        } else {
            $this->test_results['classes']['Lead_Actions_Manager'] = '❌ Classe manquante';
        }
        
        // Vérifier Lead_Workflow
        if (class_exists('Lead_Workflow')) {
            $this->test_results['classes']['Lead_Workflow'] = '✅ Classe chargée';
        } else {
            $this->test_results['classes']['Lead_Workflow'] = '❌ Classe manquante';
        }
        
        // Vérifier Unified_Leads_Manager
        if (class_exists('Unified_Leads_Manager')) {
            $this->test_results['classes']['Unified_Leads_Manager'] = '✅ Classe chargée';
        } else {
            $this->test_results['classes']['Unified_Leads_Manager'] = '❌ Classe manquante';
        }
    }
    
    /**
     * Test 2 : Vérifier que les tables existent
     */
    private function test_2_tables_exist() {
        global $wpdb;
        $this->test_results['tables'] = [];
        
        // Vérifier la table des leads
        $leads_table = $wpdb->prefix . 'my_istymo_unified_leads';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$leads_table'") == $leads_table;
        $this->test_results['tables']['unified_leads'] = $table_exists ? '✅ Table existe' : '❌ Table manquante';
        
        // Vérifier la table des actions
        $actions_table = $wpdb->prefix . 'my_istymo_lead_actions';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$actions_table'") == $actions_table;
        $this->test_results['tables']['lead_actions'] = $table_exists ? '✅ Table existe' : '❌ Table manquante';
    }
    
    /**
     * Test 3 : Vérifier que les handlers AJAX sont enregistrés
     */
    private function test_3_ajax_handlers_registered() {
        $this->test_results['ajax_handlers'] = [];
        
        // Vérifier les handlers d'actions
        $action_handlers = [
            'my_istymo_add_lead_action',
            'my_istymo_update_lead_action',
            'my_istymo_delete_lead_action',
            'my_istymo_get_lead_action'
        ];
        
        foreach ($action_handlers as $handler) {
            if (function_exists($handler)) {
                $this->test_results['ajax_handlers'][$handler] = '✅ Fonction existe';
            } else {
                $this->test_results['ajax_handlers'][$handler] = '❌ Fonction manquante';
            }
        }
        
        // Vérifier les handlers de workflow
        $workflow_handlers = [
            'my_istymo_validate_workflow_transition',
            'my_istymo_get_workflow_transitions',
            'my_istymo_get_workflow_step_info'
        ];
        
        foreach ($workflow_handlers as $handler) {
            if (function_exists($handler)) {
                $this->test_results['ajax_handlers'][$handler] = '✅ Fonction existe';
            } else {
                $this->test_results['ajax_handlers'][$handler] = '❌ Fonction manquante';
            }
        }
    }
    
    /**
     * Test 4 : Vérifier que les scripts sont chargés
     */
    private function test_4_scripts_loaded() {
        $this->test_results['scripts'] = [];
        
        // Vérifier les fichiers JavaScript
        $script_files = [
            'assets/js/lead-actions.js',
            'assets/js/lead-workflow.js',
            'assets/js/unified-leads-admin.js'
        ];
        
        foreach ($script_files as $script) {
            $file_path = plugin_dir_path(__FILE__) . $script;
            if (file_exists($file_path)) {
                $this->test_results['scripts'][$script] = '✅ Fichier existe';
            } else {
                $this->test_results['scripts'][$script] = '❌ Fichier manquant';
            }
        }
    }
    
    /**
     * Test 5 : Vérifier que les templates sont disponibles
     */
    private function test_5_templates_available() {
        $this->test_results['templates'] = [];
        
        // Vérifier les fichiers de template
        $template_files = [
            'templates/unified-leads-admin.php',
            'templates/lead-detail-modal.php',
            'templates/unified-leads-config.php'
        ];
        
        foreach ($template_files as $template) {
            $file_path = plugin_dir_path(__FILE__) . $template;
            if (file_exists($file_path)) {
                $this->test_results['templates'][$template] = '✅ Template existe';
            } else {
                $this->test_results['templates'][$template] = '❌ Template manquant';
            }
        }
    }
    
    /**
     * Test 6 : Vérifier la fonctionnalité de workflow
     */
    private function test_6_workflow_functionality() {
        $this->test_results['workflow'] = [];
        
        if (class_exists('Lead_Workflow')) {
            $workflow = Lead_Workflow::get_instance();
            
            // Tester les transitions autorisées
            $transitions = $workflow->get_allowed_transitions('nouveau');
            if (is_array($transitions) && !empty($transitions)) {
                $this->test_results['workflow']['transitions'] = '✅ Transitions fonctionnelles';
            } else {
                $this->test_results['workflow']['transitions'] = '❌ Transitions non fonctionnelles';
            }
            
            // Tester la validation
            $validation = $workflow->validate_transition('nouveau', 'en_cours', []);
            if (!is_wp_error($validation)) {
                $this->test_results['workflow']['validation'] = '✅ Validation fonctionnelle';
            } else {
                $this->test_results['workflow']['validation'] = '❌ Validation non fonctionnelle';
            }
        } else {
            $this->test_results['workflow']['class'] = '❌ Classe Lead_Workflow non disponible';
        }
    }
    
    /**
     * Test 7 : Vérifier la fonctionnalité des actions
     */
    private function test_7_actions_functionality() {
        $this->test_results['actions'] = [];
        
        if (class_exists('Lead_Actions_Manager')) {
            $actions = Lead_Actions_Manager::get_instance();
            
            // Tester les types d'actions
            $action_types = $actions->get_action_types();
            if (is_array($action_types) && !empty($action_types)) {
                $this->test_results['actions']['types'] = '✅ Types d\'actions disponibles';
            } else {
                $this->test_results['actions']['types'] = '❌ Types d\'actions manquants';
            }
            
            // Tester les résultats d'actions
            $action_results = $actions->get_action_results();
            if (is_array($action_results) && !empty($action_results)) {
                $this->test_results['actions']['results'] = '✅ Résultats d\'actions disponibles';
            } else {
                $this->test_results['actions']['results'] = '❌ Résultats d\'actions manquants';
            }
        } else {
            $this->test_results['actions']['class'] = '❌ Classe Lead_Actions_Manager non disponible';
        }
    }
    
    /**
     * Afficher les résultats des tests
     */
    private function display_results() {
        echo "<h3>📊 Résultats des Tests</h3>";
        
        foreach ($this->test_results as $category => $tests) {
            echo "<h4>🔍 $category</h4>";
            echo "<ul>";
            foreach ($tests as $test => $result) {
                echo "<li><strong>$test:</strong> $result</li>";
            }
            echo "</ul>";
        }
        
        // Calculer le score global
        $total_tests = 0;
        $passed_tests = 0;
        
        foreach ($this->test_results as $category => $tests) {
            foreach ($tests as $test => $result) {
                $total_tests++;
                if (strpos($result, '✅') !== false) {
                    $passed_tests++;
                }
            }
        }
        
        $score = round(($passed_tests / $total_tests) * 100, 1);
        
        echo "<h3>🎯 Score Global: $passed_tests/$total_tests ($score%)</h3>";
        
        if ($score >= 90) {
            echo "<p style='color: green; font-weight: bold;'>🎉 Phase 3 parfaitement intégrée !</p>";
        } elseif ($score >= 70) {
            echo "<p style='color: orange; font-weight: bold;'>⚠️ Phase 3 partiellement intégrée. Vérifiez les éléments manquants.</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Phase 3 mal intégrée. Vérifiez l'installation.</p>";
        }
    }
}

// Exécuter le test si on est dans l'admin
if (is_admin()) {
    new Phase3_Integration_Test();
}
