<?php
if (!defined('ABSPATH')) exit;

/**
 * Script de test pour le système unifié de gestion des leads
 * Vérifie que l'infrastructure de la phase 1 fonctionne correctement
 */
class Unified_Leads_Test {
    
    private static $instance = null;
    private $leads_manager;
    private $status_manager;
    private $migration_manager;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->leads_manager = Unified_Leads_Manager::get_instance();
        $this->status_manager = Lead_Status_Manager::get_instance();
        $this->migration_manager = Unified_Leads_Migration::get_instance();
    }
    
    /**
     * Exécute tous les tests de l'infrastructure
     */
    public function run_all_tests() {
        $results = array(
            'tests_passed' => 0,
            'tests_failed' => 0,
            'total_tests' => 0,
            'details' => array()
        );
        
        // Test 1: Vérification des classes
        $results = $this->test_classes($results);
        
        // Test 2: Vérification des tables
        $results = $this->test_tables($results);
        
        // Test 3: Test des méthodes CRUD
        $results = $this->test_crud_operations($results);
        
        // Test 4: Test des statuts et priorités
        $results = $this->test_status_priorities($results);
        
        // Test 5: Test de migration
        $results = $this->test_migration($results);
        
        // Test 6: Test des performances
        $results = $this->test_performance($results);
        
        return $results;
    }
    
    /**
     * Test 1: Vérification des classes
     */
    private function test_classes($results) {
        $results['total_tests']++;
        
        $classes_to_test = array(
            'Unified_Leads_Manager',
            'Lead_Status_Manager',
            'Unified_Leads_Migration'
        );
        
        $all_classes_exist = true;
        $missing_classes = array();
        
        foreach ($classes_to_test as $class_name) {
            if (!class_exists($class_name)) {
                $all_classes_exist = false;
                $missing_classes[] = $class_name;
            }
        }
        
        if ($all_classes_exist) {
            $results['tests_passed']++;
            $results['details'][] = array(
                'test' => 'Classes PHP',
                'status' => 'PASSED',
                'message' => 'Toutes les classes sont chargées correctement'
            );
        } else {
            $results['tests_failed']++;
            $results['details'][] = array(
                'test' => 'Classes PHP',
                'status' => 'FAILED',
                'message' => 'Classes manquantes : ' . implode(', ', $missing_classes)
            );
        }
        
        return $results;
    }
    
    /**
     * Test 2: Vérification des tables
     */
    private function test_tables($results) {
        $results['total_tests']++;
        
        // Forcer la création des tables
        $this->leads_manager->create_tables();
        
        // Vérifier que les tables existent
        $tables_exist = $this->migration_manager->verify_tables_exist();
        
        if ($tables_exist) {
            $results['tests_passed']++;
            $results['details'][] = array(
                'test' => 'Tables de base de données',
                'status' => 'PASSED',
                'message' => 'Les tables ont été créées avec succès'
            );
        } else {
            $results['tests_failed']++;
            $results['details'][] = array(
                'test' => 'Tables de base de données',
                'status' => 'FAILED',
                'message' => 'Les tables n\'ont pas pu être créées'
            );
        }
        
        return $results;
    }
    
    /**
     * Test 3: Test des méthodes CRUD
     */
    private function test_crud_operations($results) {
        $results['total_tests']++;
        
        $test_data = array(
            'lead_type' => 'sci',
            'original_id' => 'TEST_CRUD_' . time(),
            'status' => 'nouveau',
            'priorite' => 'normale',
            'notes' => 'Test CRUD - ' . date('Y-m-d H:i:s'),
            'data_originale' => array(
                'siren' => 'TEST_CRUD_' . time(),
                'denomination' => 'Entreprise Test CRUD',
                'dirigeant' => 'Test CRUD',
                'adresse' => '123 Test CRUD',
                'ville' => 'Test',
                'code_postal' => '75000'
            )
        );
        
        $crud_tests = array();
        
        // Test CREATE
        $lead_id = $this->leads_manager->add_lead($test_data);
        if (!is_wp_error($lead_id)) {
            $crud_tests['create'] = true;
        } else {
            $crud_tests['create'] = false;
        }
        
        // Test READ
        if ($crud_tests['create']) {
            $leads = $this->leads_manager->get_leads(null, array('lead_type' => 'sci'));
            $found_lead = false;
            foreach ($leads as $lead) {
                if ($lead->original_id === $test_data['original_id']) {
                    $found_lead = true;
                    break;
                }
            }
            $crud_tests['read'] = $found_lead;
        } else {
            $crud_tests['read'] = false;
        }
        
        // Test UPDATE
        if ($crud_tests['read']) {
            $update_result = $this->leads_manager->update_lead($lead_id, array(
                'status' => 'en_cours',
                'notes' => 'Test CRUD - Mis à jour'
            ));
            $crud_tests['update'] = !is_wp_error($update_result);
        } else {
            $crud_tests['update'] = false;
        }
        
        // Test DELETE
        if ($crud_tests['update']) {
            $delete_result = $this->leads_manager->delete_lead($lead_id);
            $crud_tests['delete'] = !is_wp_error($delete_result);
        } else {
            $crud_tests['delete'] = false;
        }
        
        // Évaluer les résultats
        $all_crud_passed = array_reduce($crud_tests, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        if ($all_crud_passed) {
            $results['tests_passed']++;
            $results['details'][] = array(
                'test' => 'Opérations CRUD',
                'status' => 'PASSED',
                'message' => 'Toutes les opérations CRUD fonctionnent correctement'
            );
        } else {
            $results['tests_failed']++;
            $failed_operations = array();
            foreach ($crud_tests as $operation => $passed) {
                if (!$passed) {
                    $failed_operations[] = $operation;
                }
            }
            $results['details'][] = array(
                'test' => 'Opérations CRUD',
                'status' => 'FAILED',
                'message' => 'Opérations échouées : ' . implode(', ', $failed_operations)
            );
        }
        
        return $results;
    }
    
    /**
     * Test 4: Test des statuts et priorités
     */
    private function test_status_priorities($results) {
        $results['total_tests']++;
        
        $status_tests = array();
        
        // Test des statuts
        $statuses = $this->status_manager->get_available_statuses();
        $status_tests['statuses_loaded'] = !empty($statuses);
        
        // Test des priorités
        $priorities = $this->status_manager->get_available_priorities();
        $status_tests['priorities_loaded'] = !empty($priorities);
        
        // Test des transitions de statut
        $status_tests['transitions'] = $this->status_manager->can_transition_status('nouveau', 'en_cours');
        
        // Test des badges
        $status_badge = $this->status_manager->get_status_badge('nouveau');
        $status_tests['status_badge'] = !empty($status_badge);
        
        $priority_badge = $this->status_manager->get_priority_badge('normale');
        $status_tests['priority_badge'] = !empty($priority_badge);
        
        // Évaluer les résultats
        $all_status_passed = array_reduce($status_tests, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        if ($all_status_passed) {
            $results['tests_passed']++;
            $results['details'][] = array(
                'test' => 'Statuts et priorités',
                'status' => 'PASSED',
                'message' => 'Gestion des statuts et priorités fonctionnelle'
            );
        } else {
            $results['tests_failed']++;
            $failed_tests = array();
            foreach ($status_tests as $test => $passed) {
                if (!$passed) {
                    $failed_tests[] = $test;
                }
            }
            $results['details'][] = array(
                'test' => 'Statuts et priorités',
                'status' => 'FAILED',
                'message' => 'Tests échoués : ' . implode(', ', $failed_tests)
            );
        }
        
        return $results;
    }
    
    /**
     * Test 5: Test de migration
     */
    private function test_migration($results) {
        $results['total_tests']++;
        
        $migration_tests = array();
        
        // Test des statistiques de migration
        $stats = $this->migration_manager->get_migration_statistics();
        $migration_tests['statistics'] = is_array($stats) && isset($stats['unified_leads_total']);
        
        // Test de création de leads de test
        $test_leads_count = $this->create_test_leads_for_testing();
        $migration_tests['test_leads'] = $test_leads_count > 0;
        
        // Test de nettoyage
        if ($migration_tests['test_leads']) {
            $cleaned_count = $this->migration_manager->cleanup_test_data();
            $migration_tests['cleanup'] = $cleaned_count > 0;
        } else {
            $migration_tests['cleanup'] = false;
        }
        
        // Évaluer les résultats
        $all_migration_passed = array_reduce($migration_tests, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        if ($all_migration_passed) {
            $results['tests_passed']++;
            $results['details'][] = array(
                'test' => 'Migration et tests',
                'status' => 'PASSED',
                'message' => 'Système de migration et tests fonctionnel'
            );
        } else {
            $results['tests_failed']++;
            $failed_tests = array();
            foreach ($migration_tests as $test => $passed) {
                if (!$passed) {
                    $failed_tests[] = $test;
                }
            }
            $results['details'][] = array(
                'test' => 'Migration et tests',
                'status' => 'FAILED',
                'message' => 'Tests échoués : ' . implode(', ', $failed_tests)
            );
        }
        
        return $results;
    }
    
    /**
     * Test 6: Test des performances
     */
    private function test_performance($results) {
        $results['total_tests']++;
        
        $performance_tests = array();
        
        // Test de performance pour la récupération des leads
        $start_time = microtime(true);
        $leads = $this->leads_manager->get_leads();
        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        
        $performance_tests['query_time'] = $execution_time < 1.0; // Moins d'1 seconde
        
        // Test de performance pour les statistiques
        $start_time = microtime(true);
        $status_stats = $this->status_manager->get_status_statistics();
        $end_time = microtime(true);
        $stats_time = $end_time - $start_time;
        
        $performance_tests['stats_time'] = $stats_time < 0.5; // Moins de 0.5 seconde
        
        // Évaluer les résultats
        $all_performance_passed = array_reduce($performance_tests, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        if ($all_performance_passed) {
            $results['tests_passed']++;
            $results['details'][] = array(
                'test' => 'Performances',
                'status' => 'PASSED',
                'message' => sprintf('Requêtes rapides : %.3fs pour les leads, %.3fs pour les stats', $execution_time, $stats_time)
            );
        } else {
            $results['tests_failed']++;
            $failed_tests = array();
            foreach ($performance_tests as $test => $passed) {
                if (!$passed) {
                    $failed_tests[] = $test;
                }
            }
            $results['details'][] = array(
                'test' => 'Performances',
                'status' => 'FAILED',
                'message' => 'Tests de performance échoués : ' . implode(', ', $failed_tests)
            );
        }
        
        return $results;
    }
    
    /**
     * Crée des leads de test pour les tests de migration
     */
    private function create_test_leads_for_testing() {
        $test_leads = array(
            array(
                'lead_type' => 'sci',
                'original_id' => 'PERF_TEST_SCI_' . time(),
                'status' => 'nouveau',
                'priorite' => 'normale',
                'notes' => 'Lead de test performance',
                'data_originale' => array(
                    'siren' => 'PERF_TEST_SCI_' . time(),
                    'denomination' => 'Test Performance SCI',
                    'dirigeant' => 'Test Perf',
                    'adresse' => '123 Test Perf',
                    'ville' => 'Test',
                    'code_postal' => '75000'
                )
            ),
            array(
                'lead_type' => 'dpe',
                'original_id' => 'PERF_TEST_DPE_' . time(),
                'status' => 'en_cours',
                'priorite' => 'haute',
                'notes' => 'Lead de test performance DPE',
                'data_originale' => array(
                    'dpe_id' => 'PERF_TEST_DPE_' . time(),
                    'adresse_ban' => '456 Test Perf',
                    'code_postal_ban' => '75000',
                    'nom_commune_ban' => 'Test',
                    'etiquette_dpe' => 'D',
                    'etiquette_ges' => '4',
                    'surface_habitable_logement' => 100,
                    'annee_construction' => 2000,
                    'type_batiment' => 'Maison'
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
     * Génère un rapport de test détaillé
     */
    public function generate_test_report() {
        $results = $this->run_all_tests();
        
        $report = array(
            'date' => current_time('Y-m-d H:i:s'),
            'summary' => array(
                'total_tests' => $results['total_tests'],
                'passed' => $results['tests_passed'],
                'failed' => $results['tests_failed'],
                'success_rate' => round(($results['tests_passed'] / $results['total_tests']) * 100, 2)
            ),
            'details' => $results['details'],
            'recommendations' => array()
        );
        
        // Ajouter des recommandations basées sur les résultats
        if ($results['tests_failed'] > 0) {
            $report['recommendations'][] = 'Corriger les tests échoués avant de passer à la phase 2';
        }
        
        if ($results['tests_passed'] === $results['total_tests']) {
            $report['recommendations'][] = 'Tous les tests passent - Infrastructure prête pour la phase 2';
        }
        
        return $report;
    }
}

// Initialiser le gestionnaire de tests
Unified_Leads_Test::get_instance();
