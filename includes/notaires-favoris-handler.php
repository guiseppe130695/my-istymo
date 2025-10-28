<?php
/**
 * Gestionnaire de favoris pour l'Annuaire Notarial
 * 
 * @package My_Istymo
 * @subpackage Notaires
 * @version 1.0
 * @author Brio Guiseppe
 */

if (!defined('ABSPATH')) {
    exit; // Empêche l'accès direct au fichier
}

class Notaires_Favoris_Handler {
    
    /**
     * Instance unique de la classe (Singleton)
     */
    private static $instance = null;
    
    /**
     * Instance du gestionnaire de notaires
     */
    private $notaires_manager;
    
    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        $this->notaires_manager = Notaires_Manager::get_instance();
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
     * Ajoute un notaire aux favoris d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $notaire_id ID du notaire
     * @return array Résultat de l'opération
     */
    public function add_to_favorites($user_id, $notaire_id) {
        $result = [
            'success' => false,
            'message' => '',
            'notaire_id' => $notaire_id
        ];
        
        // Vérifier que l'utilisateur existe
        if (!$user_id || !get_user_by('id', $user_id)) {
            $result['message'] = 'Utilisateur non trouvé';
            return $result;
        }
        
        // Vérifier que le notaire existe
        $notaire = $this->notaires_manager->get_notaire_by_id($notaire_id);
        if (!$notaire) {
            $result['message'] = 'Notaire non trouvé';
            return $result;
        }
        
        // Ajouter aux favoris
        $add_result = $this->notaires_manager->add_to_favorites($user_id, $notaire_id);
        
        if (is_wp_error($add_result)) {
            $result['message'] = $add_result->get_error_message();
            return $result;
        }
        
        $result['success'] = true;
        $result['message'] = 'Notaire ajouté aux favoris avec succès';
        
        return $result;
    }
    
    /**
     * Supprime un notaire des favoris d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $notaire_id ID du notaire
     * @return array Résultat de l'opération
     */
    public function remove_from_favorites($user_id, $notaire_id) {
        $result = [
            'success' => false,
            'message' => '',
            'notaire_id' => $notaire_id
        ];
        
        // Vérifier que l'utilisateur existe
        if (!$user_id || !get_user_by('id', $user_id)) {
            $result['message'] = 'Utilisateur non trouvé';
            return $result;
        }
        
        // Supprimer des favoris
        $remove_result = $this->notaires_manager->remove_from_favorites($user_id, $notaire_id);
        
        if (is_wp_error($remove_result)) {
            $result['message'] = $remove_result->get_error_message();
            return $result;
        }
        
        $result['success'] = true;
        $result['message'] = 'Notaire supprimé des favoris avec succès';
        
        return $result;
    }
    
    /**
     * Bascule l'état favori d'un notaire pour un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $notaire_id ID du notaire
     * @return array Résultat de l'opération
     */
    public function toggle_favorite($user_id, $notaire_id) {
        $result = [
            'success' => false,
            'message' => '',
            'notaire_id' => $notaire_id,
            'is_favorite' => false
        ];
        
        // Vérifier si le notaire est déjà en favori
        $is_favorite = $this->notaires_manager->is_favorite($user_id, $notaire_id);
        
        if ($is_favorite) {
            // Supprimer des favoris
            $operation_result = $this->remove_from_favorites($user_id, $notaire_id);
            $result['is_favorite'] = false;
        } else {
            // Ajouter aux favoris
            $operation_result = $this->add_to_favorites($user_id, $notaire_id);
            $result['is_favorite'] = true;
        }
        
        $result['success'] = $operation_result['success'];
        $result['message'] = $operation_result['message'];
        
        return $result;
    }
    
    /**
     * Récupère tous les favoris d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param array $filters Filtres optionnels
     * @param int $per_page Nombre d'éléments par page
     * @param int $page Numéro de page
     * @return array Liste des notaires favoris
     */
    public function get_user_favorites($user_id, $filters = [], $per_page = 20, $page = 1) {
        $result = [
            'success' => false,
            'data' => [],
            'total' => 0,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => 0
        ];
        
        // Vérifier que l'utilisateur existe
        if (!$user_id || !get_user_by('id', $user_id)) {
            return $result;
        }
        
        // Récupérer tous les favoris
        $favorites = $this->notaires_manager->get_user_favorites_list($user_id);
        
        if (empty($favorites)) {
            $result['success'] = true;
            return $result;
        }
        
        // Appliquer les filtres
        $filtered_favorites = $this->apply_filters_to_favorites($favorites, $filters);
        
        // Calculer la pagination
        $total = count($filtered_favorites);
        $total_pages = ceil($total / $per_page);
        
        // Appliquer la pagination
        $offset = ($page - 1) * $per_page;
        $paginated_favorites = array_slice($filtered_favorites, $offset, $per_page);
        
        $result['success'] = true;
        $result['data'] = $paginated_favorites;
        $result['total'] = $total;
        $result['total_pages'] = $total_pages;
        
        return $result;
    }
    
    /**
     * Applique les filtres aux favoris
     * 
     * @param array $favorites Liste des favoris
     * @param array $filters Filtres à appliquer
     * @return array Favoris filtrés
     */
    private function apply_filters_to_favorites($favorites, $filters) {
        if (empty($filters)) {
            return $favorites;
        }
        
        $filtered = [];
        
        foreach ($favorites as $notaire) {
            $include = true;
            
            // Filtre par ville
            if (!empty($filters['ville']) && $notaire->ville !== $filters['ville']) {
                $include = false;
            }
            
            // Filtre par langue
            if (!empty($filters['langue']) && strpos($notaire->langues_parlees, $filters['langue']) === false) {
                $include = false;
            }
            
            // Filtre par statut
            if (!empty($filters['statut']) && $notaire->statut_notaire !== $filters['statut']) {
                $include = false;
            }
            
            // Filtre par recherche textuelle
            if (!empty($filters['search'])) {
                $search_term = strtolower($filters['search']);
                $searchable_text = strtolower($notaire->nom_office . ' ' . $notaire->nom_notaire . ' ' . $notaire->ville);
                
                if (strpos($searchable_text, $search_term) === false) {
                    $include = false;
                }
            }
            
            if ($include) {
                $filtered[] = $notaire;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Exporte les favoris d'un utilisateur au format CSV
     * 
     * @param int $user_id ID de l'utilisateur
     * @param array $filters Filtres optionnels
     * @return array Résultat de l'export
     */
    public function export_favorites_csv($user_id, $filters = []) {
        $result = [
            'success' => false,
            'csv_content' => '',
            'filename' => '',
            'count' => 0
        ];
        
        // Récupérer les favoris
        $favorites_result = $this->get_user_favorites($user_id, $filters, 0, 1); // 0 = pas de limite
        
        if (!$favorites_result['success']) {
            $result['csv_content'] = 'Erreur lors de la récupération des favoris';
            return $result;
        }
        
        $favorites = $favorites_result['data'];
        
        if (empty($favorites)) {
            $result['csv_content'] = 'Aucun favori à exporter';
            return $result;
        }
        
        // Générer le CSV
        $csv_content = $this->notaires_manager->export_favorites_csv($user_id);
        
        if (empty($csv_content)) {
            $result['csv_content'] = 'Erreur lors de la génération du CSV';
            return $result;
        }
        
        // Générer le nom de fichier
        $user = get_user_by('id', $user_id);
        $username = $user ? $user->user_login : 'user_' . $user_id;
        $date = current_time('Y-m-d_H-i-s');
        $filename = "notaires_favoris_{$username}_{$date}.csv";
        
        $result['success'] = true;
        $result['csv_content'] = $csv_content;
        $result['filename'] = $filename;
        $result['count'] = count($favorites);
        
        return $result;
    }
    
    /**
     * Exporte les favoris d'un utilisateur au format PDF
     * 
     * @param int $user_id ID de l'utilisateur
     * @param array $filters Filtres optionnels
     * @return array Résultat de l'export
     */
    public function export_favorites_pdf($user_id, $filters = []) {
        $result = [
            'success' => false,
            'pdf_content' => '',
            'filename' => '',
            'count' => 0
        ];
        
        // Récupérer les favoris
        $favorites_result = $this->get_user_favorites($user_id, $filters, 0, 1);
        
        if (!$favorites_result['success']) {
            return $result;
        }
        
        $favorites = $favorites_result['data'];
        
        if (empty($favorites)) {
            return $result;
        }
        
        // Générer le PDF
        $pdf_content = $this->generate_pdf_content($favorites);
        
        if (empty($pdf_content)) {
            return $result;
        }
        
        // Générer le nom de fichier
        $user = get_user_by('id', $user_id);
        $username = $user ? $user->user_login : 'user_' . $user_id;
        $date = current_time('Y-m-d_H-i-s');
        $filename = "notaires_favoris_{$username}_{$date}.pdf";
        
        $result['success'] = true;
        $result['pdf_content'] = $pdf_content;
        $result['filename'] = $filename;
        $result['count'] = count($favorites);
        
        return $result;
    }
    
    /**
     * Génère le contenu PDF des favoris
     * 
     * @param array $favorites Liste des favoris
     * @return string Contenu PDF
     */
    private function generate_pdf_content($favorites) {
        // Cette fonction nécessiterait l'utilisation de TCPDF ou une autre librairie PDF
        // Pour l'instant, on retourne un contenu HTML simple
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h1>Mes Notaires Favoris</h1>';
        $html .= '<p>Export généré le ' . current_time('d/m/Y à H:i') . '</p>';
        $html .= '<p>Nombre de notaires : ' . count($favorites) . '</p>';
        
        foreach ($favorites as $notaire) {
            $html .= '<div style="border: 1px solid #ccc; margin: 10px 0; padding: 10px;">';
            $html .= '<h3>' . esc_html($notaire->nom_office) . '</h3>';
            $html .= '<p><strong>Notaire :</strong> ' . esc_html($notaire->nom_notaire) . '</p>';
            $html .= '<p><strong>Adresse :</strong> ' . esc_html($notaire->adresse) . '</p>';
            $html .= '<p><strong>Code postal :</strong> ' . esc_html($notaire->code_postal) . '</p>';
            $html .= '<p><strong>Ville :</strong> ' . esc_html($notaire->ville) . '</p>';
            $html .= '<p><strong>Téléphone :</strong> ' . esc_html($notaire->telephone_office) . '</p>';
            $html .= '<p><strong>Email :</strong> ' . esc_html($notaire->email_office) . '</p>';
            $html .= '<p><strong>Site web :</strong> ' . esc_html($notaire->site_internet) . '</p>';
            $html .= '<p><strong>Langues :</strong> ' . esc_html($notaire->langues_parlees) . '</p>';
            $html .= '</div>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Récupère les statistiques des favoris d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Statistiques
     */
    public function get_favorites_stats($user_id) {
        $stats = [
            'total_favorites' => 0,
            'by_city' => [],
            'by_language' => [],
            'by_status' => [],
            'recent_additions' => 0
        ];
        
        // Récupérer tous les favoris
        $favorites_result = $this->get_user_favorites($user_id, [], 0, 1);
        
        if (!$favorites_result['success']) {
            return $stats;
        }
        
        $favorites = $favorites_result['data'];
        $stats['total_favorites'] = count($favorites);
        
        // Statistiques par ville
        $cities = [];
        foreach ($favorites as $notaire) {
            $city = $notaire->ville;
            $cities[$city] = ($cities[$city] ?? 0) + 1;
        }
        arsort($cities);
        $stats['by_city'] = array_slice($cities, 0, 10, true); // Top 10
        
        // Statistiques par langue
        $languages = [];
        foreach ($favorites as $notaire) {
            if (!empty($notaire->langues_parlees)) {
                $langs = explode(',', $notaire->langues_parlees);
                foreach ($langs as $lang) {
                    $lang = trim($lang);
                    if (!empty($lang)) {
                        $languages[$lang] = ($languages[$lang] ?? 0) + 1;
                    }
                }
            }
        }
        arsort($languages);
        $stats['by_language'] = array_slice($languages, 0, 10, true); // Top 10
        
        // Statistiques par statut
        $statuses = [];
        foreach ($favorites as $notaire) {
            $status = $notaire->statut_notaire;
            $statuses[$status] = ($statuses[$status] ?? 0) + 1;
        }
        $stats['by_status'] = $statuses;
        
        // Ajouts récents (derniers 7 jours)
        $recent_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        foreach ($favorites as $notaire) {
            if (isset($notaire->date_favori) && $notaire->date_favori >= $recent_date) {
                $stats['recent_additions']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Supprime tous les favoris d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Résultat de l'opération
     */
    public function clear_all_favorites($user_id) {
        $result = [
            'success' => false,
            'message' => '',
            'deleted_count' => 0
        ];
        
        // Vérifier que l'utilisateur existe
        if (!$user_id || !get_user_by('id', $user_id)) {
            $result['message'] = 'Utilisateur non trouvé';
            return $result;
        }
        
        global $wpdb;
        $table_favoris = $wpdb->prefix . 'my_istymo_notaires_favoris';
        
        $deleted_count = $wpdb->delete(
            $table_favoris,
            ['user_id' => $user_id],
            ['%d']
        );
        
        if ($deleted_count === false) {
            $result['message'] = 'Erreur lors de la suppression des favoris';
            return $result;
        }
        
        $result['success'] = true;
        $result['message'] = 'Tous les favoris ont été supprimés avec succès';
        $result['deleted_count'] = $deleted_count;
        
        my_istymo_log("Tous les favoris supprimés pour l'utilisateur $user_id ($deleted_count éléments)", 'notaires');
        
        return $result;
    }
}

