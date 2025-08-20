<?php
if (!defined('ABSPATH')) exit;

/**
 * Page de configuration pour le système unifié de gestion des leads
 * Permet de tester l'infrastructure et de gérer la migration
 */
function unified_leads_config_page() {
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'));
    }
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $status_manager = Lead_Status_Manager::get_instance();
    $migration_manager = Unified_Leads_Migration::get_instance();
    
    // Traitement des actions
    if (isset($_POST['action']) && wp_verify_nonce($_POST['unified_leads_nonce'], 'unified_leads_action')) {
        switch ($_POST['action']) {
            case 'update_structure':
                $leads_manager->update_table_structure();
                $structure_message = "Structure de la table mise à jour avec succès";
                break;
            case 'test_infrastructure':
                $test_results = $migration_manager->test_infrastructure();
                break;
            case 'migrate_all':
                $sci_count = $leads_manager->migrate_sci_favorites();
                $dpe_count = $leads_manager->migrate_dpe_favorites();
                $migration_message = "Migration terminée : {$sci_count} SCI + {$dpe_count} DPE migrés";
                break;
            case 'cleanup_test':
                $cleaned_count = $migration_manager->cleanup_test_data();
                $cleanup_message = "Nettoyage terminé : {$cleaned_count} leads de test supprimés";
                break;
            case 'run_tests':
                $test_manager = Unified_Leads_Test::get_instance();
                $test_report = $test_manager->generate_test_report();
                break;
        }
    }
    
    // Récupérer les statistiques
    $stats = $migration_manager->get_migration_statistics();
    $status_stats = $status_manager->get_status_statistics();
    $priority_stats = $status_manager->get_priority_statistics();
    
    ?>
    <div class="wrap my-istymo">
        <h1>⚙️ Configuration - Système Unifié de Gestion des Leads</h1>
        
        <div class="notice notice-info">
            <p><strong>Configuration et Maintenance</strong> - Cette page permet de configurer, tester et maintenir l'infrastructure du système de leads.</p>
        </div>
        
        <!-- Statistiques générales -->
        <div class="my-istymo-card">
            <h2>📊 Statistiques Générales</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>Leads Unifiés</h3>
                    <div class="stat-number"><?php echo $stats['unified_leads_total']; ?></div>
                    <div class="stat-details">
                        <span>SCI: <?php echo $stats['unified_leads_sci']; ?></span>
                        <span>DPE: <?php echo $stats['unified_leads_dpe']; ?></span>
                    </div>
                </div>
                
                <div class="stat-item">
                    <h3>Favoris Originaux</h3>
                    <div class="stat-number"><?php echo $stats['original_sci_favorites'] + $stats['original_dpe_favorites']; ?></div>
                    <div class="stat-details">
                        <span>SCI: <?php echo $stats['original_sci_favorites']; ?></span>
                        <span>DPE: <?php echo $stats['original_dpe_favorites']; ?></span>
                    </div>
                </div>
                
                <div class="stat-item">
                    <h3>Progrès Migration</h3>
                    <div class="stat-number"><?php echo round(($stats['migration_progress']['sci'] + $stats['migration_progress']['dpe']) / 2, 1); ?>%</div>
                    <div class="stat-details">
                        <span>SCI: <?php echo $stats['migration_progress']['sci']; ?>%</span>
                        <span>DPE: <?php echo $stats['migration_progress']['dpe']; ?>%</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions de maintenance -->
        <div class="my-istymo-card">
            <h2>🔧 Maintenance et Tests</h2>
            
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('unified_leads_action', 'unified_leads_nonce'); ?>
                <input type="hidden" name="action" value="update_structure">
                <button type="submit" class="button button-secondary">Mettre à Jour la Structure</button>
                <p class="description">Met à jour la structure des tables si nécessaire (ajoute les colonnes manquantes)</p>
            </form>
            
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('unified_leads_action', 'unified_leads_nonce'); ?>
                <input type="hidden" name="action" value="test_infrastructure">
                <button type="submit" class="button button-primary">Tester l'Infrastructure</button>
                <p class="description">Teste la création des tables, la migration et crée des leads de test</p>
            </form>
            
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('unified_leads_action', 'unified_leads_nonce'); ?>
                <input type="hidden" name="action" value="migrate_all">
                <button type="submit" class="button button-secondary">Migrer Tous les Favoris</button>
                <p class="description">Migre tous les favoris SCI et DPE vers le système unifié</p>
            </form>
            
            <form method="post">
                <?php wp_nonce_field('unified_leads_action', 'unified_leads_nonce'); ?>
                <input type="hidden" name="action" value="cleanup_test">
                <button type="submit" class="button button-link-delete">Nettoyer les Données de Test</button>
                <p class="description">Supprime tous les leads de test créés</p>
            </form>
            
            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('unified_leads_action', 'unified_leads_nonce'); ?>
                <input type="hidden" name="action" value="run_tests">
                <button type="submit" class="button button-primary">Exécuter les Tests Complets</button>
                <p class="description">Teste l'infrastructure complète et génère un rapport détaillé</p>
            </form>
            
            <?php if (isset($test_results)): ?>
                <div class="notice notice-success">
                    <h3>Résultats du Test</h3>
                    <ul>
                        <li>Tables créées : <?php echo $test_results['tables_created'] ? '✅ Oui' : '❌ Non'; ?></li>
                        <li>Migration SCI : <?php echo $test_results['sci_migration']; ?> leads</li>
                        <li>Migration DPE : <?php echo $test_results['dpe_migration']; ?> leads</li>
                        <li>Leads de test créés : <?php echo $test_results['test_leads']; ?></li>
                    </ul>
                    <?php if (!empty($test_results['errors'])): ?>
                        <div class="notice notice-error">
                            <h4>Erreurs :</h4>
                            <ul>
                                <?php foreach ($test_results['errors'] as $error): ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($migration_message)): ?>
                <div class="notice notice-success">
                    <p><?php echo esc_html($migration_message); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($structure_message)): ?>
                <div class="notice notice-success">
                    <p><?php echo esc_html($structure_message); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($cleanup_message)): ?>
                <div class="notice notice-success">
                    <p><?php echo esc_html($cleanup_message); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($test_report)): ?>
                <div class="notice notice-info">
                    <h3>📊 Rapport de Tests Complets</h3>
                    <div class="test-summary">
                        <p><strong>Résumé :</strong> <?php echo $test_report['summary']['passed']; ?> tests réussis sur <?php echo $test_report['summary']['total_tests']; ?> (<?php echo $test_report['summary']['success_rate']; ?>%)</p>
                    </div>
                    
                    <div class="test-details">
                        <h4>Détails des tests :</h4>
                        <ul>
                            <?php foreach ($test_report['details'] as $test): ?>
                                <li>
                                    <span class="test-status <?php echo strtolower($test['status']); ?>">
                                        <?php echo $test['status'] === 'PASSED' ? '✅' : '❌'; ?>
                                    </span>
                                    <strong><?php echo esc_html($test['test']); ?></strong> : <?php echo esc_html($test['message']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <?php if (!empty($test_report['recommendations'])): ?>
                        <div class="test-recommendations">
                            <h4>Recommandations :</h4>
                            <ul>
                                <?php foreach ($test_report['recommendations'] as $recommendation): ?>
                                    <li><?php echo esc_html($recommendation); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Statuts et priorités -->
        <div class="my-istymo-card">
            <h2>📈 Répartition par Statut</h2>
            <div class="status-grid">
                <?php foreach ($status_stats as $status => $data): ?>
                    <div class="status-item">
                        <div class="status-badge" style="background-color: <?php echo $data['color']; ?>">
                            <?php echo $data['label']; ?>
                        </div>
                        <div class="status-count"><?php echo $data['count']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="my-istymo-card">
            <h2>🎯 Répartition par Priorité</h2>
            <div class="priority-grid">
                <?php foreach ($priority_stats as $priority => $data): ?>
                    <div class="priority-item">
                        <div class="priority-badge" style="background-color: <?php echo $data['color']; ?>">
                            <?php echo $data['label']; ?>
                        </div>
                        <div class="priority-count"><?php echo $data['count']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Liste des leads récents -->
        <div class="my-istymo-card">
            <h2>📋 Leads Récents</h2>
            <?php
            $recent_leads = $leads_manager->get_leads(null, array());
            if (!empty($recent_leads)):
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>ID Original</th>
                            <th>Statut</th>
                            <th>Priorité</th>
                            <th>Date Création</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recent_leads, 0, 10) as $lead): ?>
                            <tr>
                                <td>
                                    <span class="lead-type-badge lead-type-<?php echo $lead->lead_type; ?>">
                                        <?php echo strtoupper($lead->lead_type); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($lead->original_id); ?></td>
                                <td><?php echo $status_manager->get_status_badge($lead->status, false); ?></td>
                                <td><?php echo $status_manager->get_priority_badge($lead->priorite, false); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($lead->date_creation)); ?></td>
                                <td><?php echo esc_html(substr($lead->notes, 0, 50)) . (strlen($lead->notes) > 50 ? '...' : ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Aucun lead trouvé. Exécutez la migration pour commencer.</p>
            <?php endif; ?>
        </div>
        
        <!-- Informations techniques -->
        <div class="my-istymo-card">
            <h2>🔧 Informations Techniques</h2>
            <table class="form-table">
                <tr>
                    <th>Tables créées</th>
                    <td><?php echo $migration_manager->verify_tables_exist() ? '✅ Oui' : '❌ Non'; ?></td>
                </tr>
                <tr>
                    <th>Gestionnaire de leads</th>
                    <td><?php echo class_exists('Unified_Leads_Manager') ? '✅ Chargé' : '❌ Non chargé'; ?></td>
                </tr>
                <tr>
                    <th>Gestionnaire de statuts</th>
                    <td><?php echo class_exists('Lead_Status_Manager') ? '✅ Chargé' : '❌ Non chargé'; ?></td>
                </tr>
                <tr>
                    <th>Gestionnaire de migration</th>
                    <td><?php echo class_exists('Unified_Leads_Migration') ? '✅ Chargé' : '❌ Non chargé'; ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php
    // Charger le CSS des leads
    wp_enqueue_style('unified-leads-css', plugin_dir_url(__FILE__) . '../assets/css/unified-leads.css', array(), '1.0.0');
    ?>
    <style>
        .my-istymo .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .my-istymo .stat-item {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .my-istymo .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #0073aa;
            margin: 10px 0;
        }
        
        .my-istymo .stat-details {
            display: flex;
            justify-content: space-around;
            font-size: 0.9em;
            color: #666;
        }
        
        .my-istymo .status-grid, .my-istymo .priority-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .my-istymo .status-item, .my-istymo .priority-item {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        
        .my-istymo .status-badge, .my-istymo .priority-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .my-istymo .status-count, .my-istymo .priority-count {
            font-size: 1.5em;
            font-weight: bold;
            color: #0073aa;
        }
        
        .my-istymo .lead-type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .my-istymo .lead-type-sci {
            background-color: #0073aa;
            color: white;
        }
        
        .my-istymo .lead-type-dpe {
            background-color: #46b450;
            color: white;
        }
        
        .my-istymo .my-istymo-card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .my-istymo .my-istymo-card h2 {
            margin-top: 0;
            color: #23282d;
        }
    </style>
    <?php
}
