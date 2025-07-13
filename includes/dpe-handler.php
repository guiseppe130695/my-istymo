<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire principal pour l'API DPE ADEME
 */
class DPE_Handler {
    
    private static $instance = null;
    private $config_manager;
    private $favoris_handler;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config_manager = dpe_config_manager();
        $this->favoris_handler = dpe_favoris_handler();
        
        // AJAX handlers
        add_action('wp_ajax_dpe_search_ajax', array($this, 'ajax_search_dpe'));
        add_action('wp_ajax_nopriv_dpe_search_ajax', array($this, 'ajax_search_dpe'));
        add_action('wp_ajax_test_dpe_api', array($this, 'ajax_test_api'));
    }
    
    /**
     * AJAX: Recherche DPE
     */
    public function ajax_search_dpe() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dpe_search_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $code_postal = sanitize_text_field($_POST['code_postal'] ?? '');
        $adresse = sanitize_text_field($_POST['adresse'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $page_size = intval($_POST['page_size'] ?? 50);
        
        if (empty($code_postal) && empty($adresse)) {
            wp_send_json_error('Code postal ou adresse requis');
            return;
        }
        
        // Valider les paramètres de pagination
        $page = max(1, $page);
        $max_page_size = $this->config_manager->get_dpe_max_page_size();
        $page_size = max(1, min($max_page_size, $page_size));
        
        // Effectuer la recherche
        $resultats = $this->search_dpe_data($code_postal, $adresse, $page, $page_size);
        
        if (is_wp_error($resultats)) {
            wp_send_json_error($resultats->get_error_message());
            return;
        }
        
        // ✅ DEBUG : Retourner la réponse JSON brute pour voir la structure
        wp_send_json_success([
            'raw_json_response' => $resultats['raw_data'] ?? $resultats['data'],
            'total_results' => count($resultats['data']),
            'pagination' => $resultats['pagination'],
            'debug_info' => [
                'code_postal' => $code_postal,
                'adresse' => $adresse,
                'page' => $page,
                'page_size' => $page_size
            ]
        ]);
    }
    
    /**
     * Recherche dans l'API DPE ADEME
     */
    public function search_dpe_data($code_postal = '', $adresse = '', $page = 1, $page_size = 50) {
        // ✅ ADAPTÉ : Utiliser la nouvelle structure d'URL DPE
        $api_url = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines';
        
        // ✅ ADAPTÉ : Utiliser la structure de votre exemple
        $params = array(
            'size' => $page_size,
            'sort' => '-date_reception_dpe'  // Tri par date de réception décroissante, champ API correct
        );
        
        // Ajouter les filtres selon les critères
        if (!empty($code_postal)) {
            // ✅ ADAPTÉ : Utiliser le format de votre exemple
            $params['Code_postal_(brut)_eq'] = $code_postal;
        }
        
        if (!empty($adresse)) {
            // Pour l'adresse, utiliser la recherche simple
            $params['q'] = $adresse;
        }
        
        // Construire l'URL avec les paramètres
        $url = add_query_arg($params, $api_url);
        
        // Log pour debug
        my_istymo_log("=== RECHERCHE DPE API ===", 'dpe');
        my_istymo_log("URL: $url", 'dpe');
        my_istymo_log("Code postal: $code_postal", 'dpe');
        my_istymo_log("Adresse: $adresse", 'dpe');
        
        // Effectuer la requête
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/DPE-Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            my_istymo_log("❌ Erreur réseau DPE: " . $response->get_error_message(), 'dpe');
            return new WP_Error('api_error', 'Erreur de connexion à l\'API ADEME: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        
        my_istymo_log("Code HTTP DPE: $status_code", 'dpe');
        my_istymo_log("Content-Type: " . $headers->get('content-type'), 'dpe');
        my_istymo_log("Body preview: " . substr($body, 0, 200) . "...", 'dpe');
        
        if ($status_code !== 200) {
            my_istymo_log("❌ Erreur HTTP DPE: $status_code", 'dpe');
            return new WP_Error('api_error', 'Erreur API ADEME (HTTP ' . $status_code . ')');
        }
        
        // Vérifier si le contenu est du JSON
        $content_type = $headers->get('content-type');
        if (strpos($content_type, 'application/json') === false) {
            my_istymo_log("❌ Content-Type invalide: $content_type", 'dpe');
            my_istymo_log("❌ Body complet reçu: " . substr($body, 0, 500), 'dpe');
            return new WP_Error('content_type_error', 'L\'API a retourné du HTML au lieu du JSON attendu. Vérifiez l\'URL de l\'API dans la configuration.');
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            my_istymo_log("❌ Erreur JSON DPE: " . json_last_error_msg(), 'dpe');
            my_istymo_log("Body complet: $body", 'dpe');
            return new WP_Error('json_error', 'Erreur de décodage JSON: ' . json_last_error_msg());
        }
        
        // Vérifier la structure des données
        if (!isset($data['results']) || !is_array($data['results'])) {
            my_istymo_log("❌ Structure de données invalide", 'dpe');
            return new WP_Error('data_error', 'Structure de données invalide - résultats manquants');
        }
        
        my_istymo_log("✅ Recherche DPE réussie: " . count($data['results']) . " résultats", 'dpe');
        
        // ✅ DEBUG : Logger la structure complète des données
        my_istymo_log("Structure JSON complète: " . json_encode($data, JSON_PRETTY_PRINT), 'dpe');
        
        // Préparer les informations de pagination
        $pagination = array(
            'current_page' => $page,
            'page_size' => $page_size,
            'total' => $data['total'] ?? 0,
            'has_next' => !empty($data['next']),
            'next_url' => $data['next'] ?? null
        );
        
        return array(
            'data' => $data['results'],
            'raw_data' => $data,  // ✅ DEBUG : Données JSON brutes
            'pagination' => $pagination
        );
    }
    
    /**
     * Test de connexion à l'API DPE
     */
    public function test_api_connection() {
        // ✅ ADAPTÉ : Utiliser la nouvelle URL DPE
        $api_url = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines';
        
        lettre_laposte_log("=== TEST CONNEXION API DPE ===");
        lettre_laposte_log("URL testée: $api_url");
        
        // ✅ ADAPTÉ : Test simple avec un seul résultat selon votre exemple
        $test_url = add_query_arg(array(
            'size' => 1,
            'sort' => '-Date_réception_DPE'
        ), $api_url);
        
        $response = wp_remote_get($test_url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/DPE-Plugin-Test'
            )
        ));
        
        if (is_wp_error($response)) {
            lettre_laposte_log("❌ Erreur de connexion: " . $response->get_error_message());
            return array(
                'success' => false,
                'error' => 'Erreur de connexion: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        
        lettre_laposte_log("Code HTTP: $status_code");
        lettre_laposte_log("Content-Type: " . $headers->get('content-type'));
        lettre_laposte_log("Taille réponse: " . strlen($body) . " caractères");
        
        if ($status_code !== 200) {
            lettre_laposte_log("❌ Erreur HTTP: $status_code");
            return array(
                'success' => false,
                'error' => "Erreur HTTP $status_code"
            );
        }
        
        $content_type = $headers->get('content-type');
        if (strpos($content_type, 'application/json') === false) {
            lettre_laposte_log("❌ Content-Type invalide: $content_type");
            return array(
                'success' => false,
                'error' => "Content-Type invalide: $content_type"
            );
        }
        
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            lettre_laposte_log("❌ Erreur JSON: " . json_last_error_msg());
            return array(
                'success' => false,
                'error' => 'Erreur de décodage JSON: ' . json_last_error_msg()
            );
        }
        
        lettre_laposte_log("✅ Test réussi - Structure valide");
        return array(
            'success' => true,
            'message' => 'Connexion API réussie',
            'data_structure' => array_keys($data)
        );
    }
    
    /**
     * AJAX: Test de connexion API
     */
    public function ajax_test_api() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'test_dpe_api_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissions insuffisantes');
            return;
        }
        
        // Effectuer le test
        $result = $this->test_api_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    /**
     * Formater les résultats DPE pour l'affichage
     */
    public function format_dpe_results($data) {
        $formatted = array();
        $user_id = get_current_user_id();
        
        foreach ($data as $item) {
            $formatted[] = array(
                'id' => $item['_id'] ?? '',
                'adresse' => $item['adresse_ban'] ?? '',
                'code_postal' => $item['code_postal_ban'] ?? '',
                'commune' => $item['nom_commune_ban'] ?? '',
                'etiquette_dpe' => $item['etiquette_dpe'] ?? '',
                'etiquette_ges' => $item['etiquette_ges'] ?? '',
                'conso_energie' => $item['conso_5_usages_ef_energie_n1'] ?? 0,
                'emission_ges' => $item['emission_ges_5_usages_energie_n1'] ?? 0,
                'surface' => $item['surface_habitable_logement'] ?? 0,
                'annee_construction' => $item['annee_construction'] ?? '',
                'type_batiment' => $item['type_batiment'] ?? '',
                'date_dpe' => $item['date_etablissement_dpe'] ?? '',
                'numero_dpe' => $item['numero_dpe'] ?? '',
                'complement_adresse' => $item['complement_adresse_logement'] ?? '',
                'geolocalisation' => array(
                    'x' => $item['coordonnee_cartographique_x_ban'] ?? 0,
                    'y' => $item['coordonnee_cartographique_y_ban'] ?? 0
                ),
                'is_favori' => $user_id ? $this->favoris_handler->is_favori($user_id, $item['_id'] ?? '') : false,
                'raw_data' => $item
            );
        }
        
        return $formatted;
    }
    
    /**
     * Obtenir les détails complets d'un DPE
     */
    public function get_dpe_details($dpe_id) {
        // ✅ ADAPTÉ : Utiliser la nouvelle URL DPE
        $api_url = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines';
        $url = add_query_arg(array('q' => "_id:\"$dpe_id\""), $api_url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/DPE-Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Erreur de connexion à l\'API ADEME');
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return new WP_Error('api_error', 'Erreur API ADEME (HTTP ' . $status_code . ')');
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Erreur de décodage JSON');
        }
        
        if (empty($data['results'])) {
            return new WP_Error('not_found', 'DPE non trouvé');
        }
        
        return $data['results'][0];
    }
    
    /**
     * Obtenir les statistiques DPE pour un code postal
     */
    public function get_dpe_stats($code_postal) {
        $resultats = $this->search_dpe_data($code_postal, '', 1, 1000);
        
        if (is_wp_error($resultats)) {
            return $resultats;
        }
        
        $data = $resultats['data'];
        $stats = array(
            'total' => count($data),
            'etiquettes_dpe' => array(),
            'etiquettes_ges' => array(),
            'moyenne_conso' => 0,
            'moyenne_ges' => 0,
            'types_batiment' => array()
        );
        
        $total_conso = 0;
        $total_ges = 0;
        $count_conso = 0;
        $count_ges = 0;
        
        foreach ($data as $item) {
            // Étiquettes DPE
            $dpe = $item['etiquette_dpe'] ?? '';
            if ($dpe) {
                $stats['etiquettes_dpe'][$dpe] = ($stats['etiquettes_dpe'][$dpe] ?? 0) + 1;
            }
            
            // Étiquettes GES
            $ges = $item['etiquette_ges'] ?? '';
            if ($ges) {
                $stats['etiquettes_ges'][$ges] = ($stats['etiquettes_ges'][$ges] ?? 0) + 1;
            }
            
            // Consommation énergétique
            $conso = floatval($item['conso_5_usages_ef_energie_n1'] ?? 0);
            if ($conso > 0) {
                $total_conso += $conso;
                $count_conso++;
            }
            
            // Émissions GES
            $ges_emission = floatval($item['emission_ges_5_usages_energie_n1'] ?? 0);
            if ($ges_emission > 0) {
                $total_ges += $ges_emission;
                $count_ges++;
            }
            
            // Types de bâtiment
            $type = $item['type_batiment'] ?? '';
            if ($type) {
                $stats['types_batiment'][$type] = ($stats['types_batiment'][$type] ?? 0) + 1;
            }
        }
        
        // Calculer les moyennes
        if ($count_conso > 0) {
            $stats['moyenne_conso'] = round($total_conso / $count_conso, 2);
        }
        
        if ($count_ges > 0) {
            $stats['moyenne_ges'] = round($total_ges / $count_ges, 2);
        }
        
        return $stats;
    }
}

// Fonction helper pour accéder au gestionnaire
function dpe_handler() {
    return DPE_Handler::get_instance();
} 