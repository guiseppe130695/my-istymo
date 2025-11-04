<?php
/**
 * Gestionnaire principal pour l'Annuaire Notarial
 * 
 * @package My_Istymo
 * @subpackage Notaires
 * @version 1.0
 * @author Brio Guiseppe
 */

if (!defined('ABSPATH')) {
    exit; // Empêche l'accès direct au fichier
}

class Notaires_Manager {
    
    /**
     * Instance unique de la classe (Singleton)
     */
    private static $instance = null;
    
    /**
     * Nom de la table des notaires
     */
    private $table_notaires;
    
    /**
     * Nom de la table des favoris
     */
    private $table_favoris;
    
    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        global $wpdb;
        $this->table_notaires = $wpdb->prefix . 'my_istymo_notaires';
        $this->table_favoris = $wpdb->prefix . 'my_istymo_notaires_favoris';
    }
    
    /**
     * Récupère l'instance unique de la classe
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Récupère les notaires par codes postaux avec filtres optionnels
     * 
     * @param array $codes_postaux Codes postaux de l'utilisateur
     * @param array $filters Filtres additionnels
     * @param int $per_page Nombre d'éléments par page
     * @param int $page Numéro de page
     * @return array Liste des notaires
     */
    public function get_notaires_by_postal_codes($codes_postaux, $filters = [], $per_page = 20, $page = 1) {
        global $wpdb;
        
        if (empty($codes_postaux)) {
            return [];
        }
        
        $where_conditions = ['code_postal IN (' . implode(',', array_fill(0, count($codes_postaux), '%s')) . ')'];
        $where_values = $codes_postaux;
        
        // Filtres additionnels
        if (!empty($filters['statut'])) {
            $where_conditions[] = 'statut_notaire = %s';
            $where_values[] = sanitize_text_field($filters['statut']);
        }
        
        if (!empty($filters['ville'])) {
            $where_conditions[] = 'ville = %s';
            $where_values[] = sanitize_text_field($filters['ville']);
        }
        
        if (!empty($filters['langue'])) {
            $where_conditions[] = 'langues_parlees LIKE %s';
            $where_values[] = '%' . sanitize_text_field($filters['langue']) . '%';
        }
        
        if (!empty($filters['search'])) {
            $search_term = '%' . sanitize_text_field($filters['search']) . '%';
            $where_conditions[] = '(nom_office LIKE %s OR nom_notaire LIKE %s OR ville LIKE %s)';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Ajouter la pagination
        $offset = ($page - 1) * $per_page;
        $limit_clause = '';
        if ($per_page > 0) {
            $limit_clause = " LIMIT %d OFFSET %d";
            $where_values[] = $per_page;
            $where_values[] = $offset;
        }
        
        $sql = "SELECT * FROM {$this->table_notaires} 
                WHERE $where_clause 
                ORDER BY ville, nom_office
                $limit_clause";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $where_values));
        
        // Ajouter l'information des favoris pour chaque notaire
        if (!empty($results)) {
            $user_id = get_current_user_id();
            $notaire_ids = array_column($results, 'id');
            $favorites = $this->get_user_favorites($user_id, $notaire_ids);
            
            foreach ($results as $notaire) {
                $notaire->is_favorite = in_array($notaire->id, $favorites);
            }
        }
        
        return $results;
    }
    
    /**
     * Compte le nombre de notaires par codes postaux
     * 
     * @param array $codes_postaux Codes postaux de l'utilisateur
     * @param array $filters Filtres additionnels
     * @return int Nombre total de notaires
     */
    public function get_notaires_count($codes_postaux, $filters = []) {
        global $wpdb;
        
        if (empty($codes_postaux)) {
            return 0;
        }
        
        $where_conditions = ['code_postal IN (' . implode(',', array_fill(0, count($codes_postaux), '%s')) . ')'];
        $where_values = $codes_postaux;
        
        // Appliquer les mêmes filtres que get_notaires_by_postal_codes
        if (!empty($filters['statut'])) {
            $where_conditions[] = 'statut_notaire = %s';
            $where_values[] = sanitize_text_field($filters['statut']);
        }
        
        if (!empty($filters['ville'])) {
            $where_conditions[] = 'ville = %s';
            $where_values[] = sanitize_text_field($filters['ville']);
        }
        
        if (!empty($filters['langue'])) {
            $where_conditions[] = 'langues_parlees LIKE %s';
            $where_values[] = '%' . sanitize_text_field($filters['langue']) . '%';
        }
        
        if (!empty($filters['search'])) {
            $search_term = '%' . sanitize_text_field($filters['search']) . '%';
            $where_conditions[] = '(nom_office LIKE %s OR nom_notaire LIKE %s OR ville LIKE %s)';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT COUNT(*) FROM {$this->table_notaires} WHERE $where_clause";
        
        return (int) $wpdb->get_var($wpdb->prepare($sql, $where_values));
    }
    
    /**
     * Récupère un notaire par son ID
     * 
     * @param int $notaire_id ID du notaire
     * @return object|null Objet notaire ou null
     */
    public function get_notaire_by_id($notaire_id) {
        global $wpdb;
        
        $notaire_id = (int) $notaire_id;
        
        $sql = "SELECT * FROM {$this->table_notaires} WHERE id = %d";
        $notaire = $wpdb->get_row($wpdb->prepare($sql, $notaire_id));
        
        if ($notaire) {
            $user_id = get_current_user_id();
            $notaire->is_favorite = $this->is_favorite($user_id, $notaire_id);
        }
        
        return $notaire;
    }
    
    /**
     * Récupère les villes disponibles pour les codes postaux donnés
     * 
     * @param array $codes_postaux Codes postaux de l'utilisateur
     * @return array Liste des villes
     */
    public function get_available_cities($codes_postaux) {
        global $wpdb;
        
        if (empty($codes_postaux)) {
            return [];
        }
        
        $sql = "SELECT DISTINCT ville FROM {$this->table_notaires} 
                WHERE code_postal IN (" . implode(',', array_fill(0, count($codes_postaux), '%s')) . ")
                ORDER BY ville";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $codes_postaux));
        
        return array_column($results, 'ville');
    }
    
    /**
     * Récupère les langues disponibles pour les codes postaux donnés
     * 
     * @param array $codes_postaux Codes postaux de l'utilisateur
     * @return array Liste des langues
     */
    public function get_available_languages($codes_postaux) {
        global $wpdb;
        
        if (empty($codes_postaux)) {
            return [];
        }
        
        $sql = "SELECT DISTINCT langues_parlees FROM {$this->table_notaires} 
                WHERE code_postal IN (" . implode(',', array_fill(0, count($codes_postaux), '%s')) . ")
                AND langues_parlees IS NOT NULL 
                AND langues_parlees != ''";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $codes_postaux));
        
        $languages = [];
        foreach ($results as $result) {
            if (!empty($result->langues_parlees)) {
                $langs = explode(',', $result->langues_parlees);
                foreach ($langs as $lang) {
                    $lang = trim($lang);
                    if (!empty($lang) && !in_array($lang, $languages)) {
                        $languages[] = $lang;
                    }
                }
            }
        }
        
        sort($languages);
        return $languages;
    }
    
    /**
     * Récupère les favoris d'un utilisateur pour une liste de notaires
     * 
     * @param int $user_id ID de l'utilisateur
     * @param array $notaire_ids Liste des IDs de notaires
     * @return array Liste des IDs des notaires favoris
     */
    private function get_user_favorites($user_id, $notaire_ids) {
        global $wpdb;
        
        if (empty($notaire_ids)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($notaire_ids), '%d'));
        $values = array_merge([$user_id], $notaire_ids);
        
        $sql = "SELECT notaire_id FROM {$this->table_favoris} 
                WHERE user_id = %d AND notaire_id IN ($placeholders)";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $values));
        
        return array_column($results, 'notaire_id');
    }
    
    /**
     * Vérifie si un notaire est dans les favoris d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $notaire_id ID du notaire
     * @return bool True si favori, false sinon
     */
    public function is_favorite($user_id, $notaire_id) {
        global $wpdb;
        
        $sql = "SELECT COUNT(*) FROM {$this->table_favoris} 
                WHERE user_id = %d AND notaire_id = %d";
        
        return (int) $wpdb->get_var($wpdb->prepare($sql, $user_id, $notaire_id)) > 0;
    }
    
    /**
     * Ajoute un notaire aux favoris d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $notaire_id ID du notaire
     * @return bool|WP_Error True si succès, WP_Error en cas d'erreur
     */
    public function add_to_favorites($user_id, $notaire_id) {
        global $wpdb;
        
        // Vérifier que le notaire existe
        $notaire = $this->get_notaire_by_id($notaire_id);
        if (!$notaire) {
            return new WP_Error('notaire_not_found', 'Notaire non trouvé');
        }
        
        // Vérifier si déjà en favori
        if ($this->is_favorite($user_id, $notaire_id)) {
            return new WP_Error('already_favorite', 'Ce notaire est déjà dans vos favoris');
        }
        
        $result = $wpdb->insert(
            $this->table_favoris,
            [
                'user_id' => $user_id,
                'notaire_id' => $notaire_id,
                'date_ajout' => current_time('mysql')
            ],
            ['%d', '%d', '%s']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de l\'ajout aux favoris');
        }
        
        my_istymo_log("Notaire $notaire_id ajouté aux favoris de l'utilisateur $user_id", 'notaires');
        
        return true;
    }
    
    /**
     * Supprime un notaire des favoris d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $notaire_id ID du notaire
     * @return bool|WP_Error True si succès, WP_Error en cas d'erreur
     */
    public function remove_from_favorites($user_id, $notaire_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_favoris,
            [
                'user_id' => $user_id,
                'notaire_id' => $notaire_id
            ],
            ['%d', '%d']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la suppression des favoris');
        }
        
        my_istymo_log("Notaire $notaire_id supprimé des favoris de l'utilisateur $user_id", 'notaires');
        
        return true;
    }
    
    /**
     * Récupère tous les favoris d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Liste des notaires favoris
     */
    public function get_user_favorites_list($user_id) {
        global $wpdb;
        
        $sql = "SELECT n.*, f.date_ajout as date_favori 
                FROM {$this->table_notaires} n
                INNER JOIN {$this->table_favoris} f ON n.id = f.notaire_id
                WHERE f.user_id = %d
                ORDER BY f.date_ajout DESC";
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $user_id));
        
        foreach ($results as $notaire) {
            $notaire->is_favorite = true;
        }
        
        return $results;
    }
    
    /**
     * Exporte les favoris d'un utilisateur au format CSV
     * 
     * @param int $user_id ID de l'utilisateur
     * @return string Contenu CSV
     */
    public function export_favorites_csv($user_id) {
        $favorites = $this->get_user_favorites_list($user_id);
        
        if (empty($favorites)) {
            return '';
        }
        
        $csv_content = "Nom Office,Téléphone,Email,Adresse,Code Postal,Ville,Nom Notaire,Statut,Site Internet,Langues Parlées\n";
        
        foreach ($favorites as $notaire) {
            $csv_content .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $notaire->nom_office,
                $notaire->telephone_office,
                $notaire->email_office,
                $notaire->adresse,
                $notaire->code_postal,
                $notaire->ville,
                $notaire->nom_notaire,
                $notaire->statut_notaire,
                $notaire->site_internet,
                $notaire->langues_parlees
            );
        }
        
        return $csv_content;
    }
    
    /**
     * Vide complètement la table des notaires (pour l'import)
     * 
     * @return bool True si succès
     */
    public function truncate_notaires() {
        global $wpdb;
        
        // Vérifier que la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_notaires}'");
        if (!$table_exists) {
            my_istymo_log("La table {$this->table_notaires} n'existe pas", 'notaires');
            // Créer la table si elle n'existe pas
            if (function_exists('create_notaires_tables')) {
                create_notaires_tables();
            } else {
                return false;
            }
        }
        
        // Désactiver temporairement les vérifications de clés étrangères
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        
        // Utiliser DELETE au lieu de TRUNCATE car TRUNCATE ne fonctionne pas avec les clés étrangères
        // même si elles sont en ON DELETE CASCADE
        $result = $wpdb->query("DELETE FROM {$this->table_notaires}");
        
        // Réactiver les vérifications de clés étrangères
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        
        // Si DELETE a échoué, essayer TRUNCATE (peut fonctionner si pas de contraintes actives)
        if ($result === false) {
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
            $result = $wpdb->query("TRUNCATE TABLE {$this->table_notaires}");
            $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        }
        
        if ($result === false) {
            $error = $wpdb->last_error;
            my_istymo_log("Erreur lors du vidage de la table notaires : {$error}", 'notaires');
            return false;
        }
        
        // Réinitialiser l'auto-increment
        $wpdb->query("ALTER TABLE {$this->table_notaires} AUTO_INCREMENT = 1");
        
        my_istymo_log('Table notaires vidée avec succès', 'notaires');
        return true;
    }
    
    /**
     * Insère un nouveau notaire
     * 
     * @param array $data Données du notaire
     * @return int|false ID du notaire inséré ou false
     */
    public function insert_notaire($data) {
        global $wpdb;
        
        $defaults = [
            'date_import' => current_time('mysql'),
            'date_modification' => current_time('mysql')
        ];
        
        $data = array_merge($defaults, $data);
        
        $result = $wpdb->insert(
            $this->table_notaires,
            $data,
            [
                '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            ]
        );
        
        if ($result === false) {
            my_istymo_log('Erreur lors de l\'insertion du notaire: ' . $wpdb->last_error, 'notaires');
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Insère plusieurs notaires en une seule requête
     * 
     * @param array $notaires_data Liste des données de notaires
     * @return int Nombre de notaires insérés
     */
    public function bulk_insert_notaires($notaires_data) {
        global $wpdb;
        
        if (empty($notaires_data)) {
            return 0;
        }
        
        $values = [];
        $placeholders = [];
        
        foreach ($notaires_data as $data) {
            $values = array_merge($values, [
                $data['nom_office'],
                $data['telephone_office'],
                $data['langues_parlees'],
                $data['site_internet'],
                $data['email_office'],
                $data['adresse'],
                $data['code_postal'],
                $data['ville'],
                $data['nom_notaire'],
                $data['statut_notaire'],
                $data['url_office'],
                $data['page_source'],
                $data['date_extraction'],
                current_time('mysql'),
                current_time('mysql')
            ]);
            
            $placeholders[] = '(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)';
        }
        
        $sql = "INSERT INTO {$this->table_notaires} 
                (nom_office, telephone_office, langues_parlees, site_internet, email_office, 
                 adresse, code_postal, ville, nom_notaire, statut_notaire, url_office, 
                 page_source, date_extraction, date_import, date_modification) 
                VALUES " . implode(', ', $placeholders);
        
        $result = $wpdb->query($wpdb->prepare($sql, $values));
        
        if ($result === false) {
            my_istymo_log('Erreur lors de l\'insertion en masse des notaires: ' . $wpdb->last_error, 'notaires');
            return 0;
        }
        
        my_istymo_log("$result notaires insérés avec succès", 'notaires');
        return $result;
    }
}

