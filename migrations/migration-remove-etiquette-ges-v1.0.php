<?php
/**
 * Migration : Suppression de la colonne etiquette_ges
 * Version : 1.0
 * Date : 2025-01-27
 * 
 * Cette migration supprime définitivement la colonne etiquette_ges 
 * de la table dpe_favoris et met à jour le schéma de base de données.
 */

if (!defined('ABSPATH')) {
    exit;
}

class My_Istymo_Migration_Remove_Etiquette_GES {
    
    private $version = '1.0';
    private $migration_key = 'my_istymo_migration_remove_etiquette_ges';
    
    public function __construct() {
        add_action('admin_init', array($this, 'check_and_run_migration'));
    }
    
    /**
     * Vérifie si la migration doit être exécutée
     */
    public function check_and_run_migration() {
        $current_version = get_option($this->migration_key, '0.0');
        
        if (version_compare($current_version, $this->version, '<')) {
            $this->run_migration();
        }
    }
    
    /**
     * Exécute la migration
     */
    public function run_migration() {
        global $wpdb;
        
        try {
            // Log de début de migration
            $this->log("🚀 Début de la migration : Suppression de etiquette_ges v{$this->version}");
            
            // 1. Vérifier si la colonne existe
            $table_name = $wpdb->prefix . 'dpe_favoris';
            $column_exists = $this->column_exists($table_name, 'etiquette_ges');
            
            if (!$column_exists) {
                $this->log("ℹ️ La colonne etiquette_ges n'existe pas dans {$table_name}");
                $this->mark_migration_complete();
                return;
            }
            
            // 2. Créer une sauvegarde des données si nécessaire
            $this->create_backup_table();
            
            // 3. Supprimer l'index sur etiquette_ges
            $this->remove_index($table_name, 'etiquette_ges');
            
            // 4. Supprimer la colonne etiquette_ges
            $this->remove_column($table_name, 'etiquette_ges');
            
            // 5. Marquer la migration comme terminée
            $this->mark_migration_complete();
            
            $this->log("✅ Migration terminée avec succès !");
            
        } catch (Exception $e) {
            $this->log("❌ Erreur lors de la migration : " . $e->getMessage());
            
            // En cas d'erreur, ne pas marquer comme terminé
            throw $e;
        }
    }
    
    /**
     * Vérifie si une colonne existe dans une table
     */
    private function column_exists($table_name, $column_name) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SHOW COLUMNS FROM `{$table_name}` LIKE %s",
            $column_name
        );
        
        $result = $wpdb->get_results($query);
        return !empty($result);
    }
    
    /**
     * Crée une table de sauvegarde
     */
    private function create_backup_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'dpe_favoris';
        $backup_table = $table_name . '_backup_' . date('Y_m_d_H_i_s');
        
        $sql = "CREATE TABLE `{$backup_table}` AS SELECT * FROM `{$table_name}`";
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            throw new Exception("Impossible de créer la table de sauvegarde : {$backup_table}");
        }
        
        $this->log("💾 Sauvegarde créée : {$backup_table}");
    }
    
    /**
     * Supprime un index
     */
    private function remove_index($table_name, $index_name) {
        global $wpdb;
        
        // Vérifier si l'index existe
        $indexes = $wpdb->get_results("SHOW INDEX FROM `{$table_name}` WHERE Key_name = '{$index_name}'");
        
        if (!empty($indexes)) {
            $sql = "ALTER TABLE `{$table_name}` DROP INDEX `{$index_name}`";
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                throw new Exception("Impossible de supprimer l'index {$index_name} de {$table_name}");
            }
            
            $this->log("🗑️ Index supprimé : {$index_name}");
        } else {
            $this->log("ℹ️ Index {$index_name} n'existe pas");
        }
    }
    
    /**
     * Supprime une colonne
     */
    private function remove_column($table_name, $column_name) {
        global $wpdb;
        
        $sql = "ALTER TABLE `{$table_name}` DROP COLUMN `{$column_name}`";
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            throw new Exception("Impossible de supprimer la colonne {$column_name} de {$table_name}");
        }
        
        $this->log("🗑️ Colonne supprimée : {$column_name}");
    }
    
    /**
     * Marque la migration comme terminée
     */
    private function mark_migration_complete() {
        update_option($this->migration_key, $this->version);
        $this->log("✅ Migration marquée comme terminée (version {$this->version})");
    }
    
    /**
     * Log des messages de migration
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}";
        
        // Log dans le fichier de log WordPress
        error_log("MY-ISTYMO MIGRATION: {$log_message}");
        
        // Log dans un fichier dédié
        $log_file = plugin_dir_path(__FILE__) . '../logs/migration.log';
        $log_dir = dirname($log_file);
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Méthode pour forcer l'exécution manuelle (pour tests)
     */
    public function force_run() {
        delete_option($this->migration_key);
        $this->run_migration();
    }
    
    /**
     * Rollback de la migration (si nécessaire)
     */
    public function rollback() {
        global $wpdb;
        
        try {
            $this->log("🔄 Début du rollback de la migration");
            
            $table_name = $wpdb->prefix . 'dpe_favoris';
            
            // Ajouter la colonne etiquette_ges
            $sql = "ALTER TABLE `{$table_name}` ADD COLUMN `etiquette_ges` varchar(10) NOT NULL DEFAULT ''";
            $wpdb->query($sql);
            
            // Recréer l'index
            $sql = "ALTER TABLE `{$table_name}` ADD INDEX `etiquette_ges` (`etiquette_ges`)";
            $wpdb->query($sql);
            
            // Marquer comme non migré
            delete_option($this->migration_key);
            
            $this->log("✅ Rollback terminé avec succès");
            
        } catch (Exception $e) {
            $this->log("❌ Erreur lors du rollback : " . $e->getMessage());
            throw $e;
        }
    }
}

// Initialiser la migration
new My_Istymo_Migration_Remove_Etiquette_GES();

/**
 * Fonction helper pour exécuter manuellement la migration
 */
function my_istymo_force_migration_remove_etiquette_ges() {
    $migration = new My_Istymo_Migration_Remove_Etiquette_GES();
    $migration->force_run();
}

/**
 * Fonction helper pour rollback de la migration
 */
function my_istymo_rollback_migration_remove_etiquette_ges() {
    $migration = new My_Istymo_Migration_Remove_Etiquette_GES();
    $migration->rollback();
}
