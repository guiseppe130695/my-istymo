<?php
/**
 * Interface d'administration des migrations
 * Permet de gérer et surveiller les migrations de base de données
 */

if (!defined('ABSPATH')) {
    exit;
}

class My_Istymo_Migration_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_run_migration', array($this, 'handle_run_migration'));
        add_action('admin_post_rollback_migration', array($this, 'handle_rollback_migration'));
    }
    
    /**
     * Ajouter le menu d'administration
     */
    public function add_admin_menu() {
        add_submenu_page(
            'my-istymo-settings',
            'Migrations DB',
            'Migrations',
            'manage_options',
            'my-istymo-migrations',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Page d'administration des migrations
     */
    public function admin_page() {
        $current_migration = get_option('my_istymo_migration_remove_etiquette_ges', '0.0');
        $log_file = plugin_dir_path(__FILE__) . '../logs/migration.log';
        ?>
        <div class="wrap">
            <h1>🗄️ Migrations de Base de Données</h1>
            
            <div class="notice notice-info">
                <p><strong>ℹ️ Information :</strong> Cette page permet de gérer les migrations de structure de base de données pour My Istymo.</p>
            </div>
            
            <div class="postbox">
                <h2 class="hndle">📊 État des Migrations</h2>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Migration</th>
                                <th>Version Actuelle</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Suppression colonne etiquette_ges</strong></td>
                                <td><?php echo esc_html($current_migration); ?></td>
                                <td>
                                    <?php if (version_compare($current_migration, '1.0', '>=')): ?>
                                        <span style="color: green;">✅ Terminée</span>
                                    <?php else: ?>
                                        <span style="color: orange;">⏳ En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (version_compare($current_migration, '1.0', '<')): ?>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                            <input type="hidden" name="action" value="run_migration">
                                            <?php wp_nonce_field('migration_action', 'migration_nonce'); ?>
                                            <input type="submit" class="button button-primary" value="🚀 Exécuter" 
                                                   onclick="return confirm('Êtes-vous sûr de vouloir exécuter cette migration ?')">
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                            <input type="hidden" name="action" value="rollback_migration">
                                            <?php wp_nonce_field('migration_action', 'migration_nonce'); ?>
                                            <input type="submit" class="button button-secondary" value="🔄 Rollback" 
                                                   onclick="return confirm('Êtes-vous sûr de vouloir annuler cette migration ?')">
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle">📋 Logs de Migration</h2>
                <div class="inside">
                    <?php if (file_exists($log_file)): ?>
                        <textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px;">
<?php echo esc_textarea(file_get_contents($log_file)); ?>
                        </textarea>
                        <p>
                            <a href="<?php echo plugin_dir_url(__FILE__) . '../logs/migration.log'; ?>" 
                               class="button" target="_blank">📥 Télécharger les logs</a>
                        </p>
                    <?php else: ?>
                        <p>Aucun log de migration trouvé.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle">⚠️ Informations Importantes</h2>
                <div class="inside">
                    <ul>
                        <li><strong>Sauvegarde :</strong> Une sauvegarde automatique est créée avant chaque migration</li>
                        <li><strong>Rollback :</strong> Vous pouvez annuler une migration si nécessaire</li>
                        <li><strong>Logs :</strong> Toutes les opérations sont loggées pour traçabilité</li>
                        <li><strong>Sécurité :</strong> Les migrations sont exécutées avec des vérifications de sécurité</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <style>
        .postbox {
            margin-top: 20px;
        }
        .postbox .inside {
            padding: 15px;
        }
        </style>
        <?php
    }
    
    /**
     * Gérer l'exécution d'une migration
     */
    public function handle_run_migration() {
        if (!current_user_can('manage_options')) {
            wp_die('Permission refusée');
        }
        
        if (!wp_verify_nonce($_POST['migration_nonce'], 'migration_action')) {
            wp_die('Nonce invalide');
        }
        
        try {
            // Forcer l'exécution de la migration
            my_istymo_force_migration_remove_etiquette_ges();
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>✅ Migration exécutée avec succès !</p></div>';
            });
            
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>❌ Erreur lors de la migration : ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
        
        wp_redirect(admin_url('admin.php?page=my-istymo-migrations'));
        exit;
    }
    
    /**
     * Gérer le rollback d'une migration
     */
    public function handle_rollback_migration() {
        if (!current_user_can('manage_options')) {
            wp_die('Permission refusée');
        }
        
        if (!wp_verify_nonce($_POST['migration_nonce'], 'migration_action')) {
            wp_die('Nonce invalide');
        }
        
        try {
            // Exécuter le rollback
            my_istymo_rollback_migration_remove_etiquette_ges();
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>✅ Rollback exécuté avec succès !</p></div>';
            });
            
        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>❌ Erreur lors du rollback : ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
        
        wp_redirect(admin_url('admin.php?page=my-istymo-migrations'));
        exit;
    }
}

// Initialiser l'interface d'administration
new My_Istymo_Migration_Admin();
