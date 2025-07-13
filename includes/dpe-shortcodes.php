<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire des shortcodes pour le plugin DPE
 */
class DPE_Shortcodes {
    
    public function __construct() {
        // Enregistrer les shortcodes DPE
        add_shortcode('dpe_panel', array($this, 'dpe_panel_shortcode'));
        add_shortcode('dpe_panel_simple', array($this, 'dpe_panel_simple_shortcode'));
        add_shortcode('dpe_favoris', array($this, 'dpe_favoris_shortcode'));
        
        // Chargement des assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'), 5);
        add_action('wp_head', array($this, 'force_enqueue_on_shortcode_pages'), 1);
        add_action('wp_footer', array($this, 'ensure_scripts_loaded'), 999);
        
        // AJAX handlers DPE
        add_action('wp_ajax_dpe_search_ajax', array($this, 'dpe_search_ajax'));
        add_action('wp_ajax_nopriv_dpe_search_ajax', array($this, 'dpe_search_ajax'));
    }
    
    /**
     * Force le chargement sur les pages avec shortcodes DPE
     */
    public function force_enqueue_on_shortcode_pages() {
        global $post;
        
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'dpe_panel') ||
            has_shortcode($post->post_content, 'dpe_panel_simple') ||
            has_shortcode($post->post_content, 'dpe_favoris')
        )) {
            $this->force_enqueue_assets([]);
        }
    }
    
    /**
     * S'assurer que les scripts sont chargés en footer
     */
    public function ensure_scripts_loaded() {
        global $post;
        
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'dpe_panel') ||
            has_shortcode($post->post_content, 'dpe_panel_simple') ||
            has_shortcode($post->post_content, 'dpe_favoris')
        )) {
            if (!wp_script_is('dpe-frontend-favoris', 'done')) {
                $this->force_enqueue_assets([]);
            }
        }
    }
    
    /**
     * Enqueue les scripts pour le frontend
     */
    public function enqueue_frontend_scripts() {
        global $post;
        
        $should_load = false;
        
        // Vérifier le post actuel
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'dpe_panel') ||
            has_shortcode($post->post_content, 'dpe_panel_simple') ||
            has_shortcode($post->post_content, 'dpe_favoris')
        )) {
            $should_load = true;
        }
        
        // Vérifier via les paramètres GET
        if (!$should_load && (
            isset($_GET['dpe_view']) || 
            strpos($_SERVER['REQUEST_URI'] ?? '', 'dpe') !== false
        )) {
            $should_load = true;
        }
        
        // Vérifier le contenu de la page
        if (!$should_load && (
            is_page() || 
            is_single() || 
            is_front_page() ||
            is_home()
        )) {
            $content = get_the_content();
            if (strpos($content, '[dpe_') !== false) {
                $should_load = true;
            }
        }
        
        if ($should_load) {
            $this->force_enqueue_assets([]);
        }
    }
    
    /**
     * Force le chargement des assets DPE
     */
    private function force_enqueue_assets($codesPostauxArray = []) {
        // CSS DPE
        if (!wp_style_is('dpe-frontend-style', 'enqueued')) {
            wp_enqueue_style(
                'dpe-frontend-style',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/dpe-style.css',
                array(),
                '1.0.2'
            );
        }
        
        // JavaScript DPE
        if (!wp_script_is('dpe-frontend-favoris', 'enqueued')) {
            wp_enqueue_script(
                'dpe-frontend-favoris',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/favoris.js',
                array(),
                '1.0.1',
                true
            );
        }
        
        // Localiser les variables pour AJAX
        wp_localize_script('dpe-frontend-favoris', 'dpe_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dpe_favoris_nonce'),
            'codes_postaux' => $codesPostauxArray
        ));
    }
    
    /**
     * Shortcode pour le panneau DPE principal
     */
    public function dpe_panel_shortcode($atts) {
        // Récupérer les codes postaux de l'utilisateur
        $current_user = wp_get_current_user();
        $codePostal = get_field('code_postal_user', 'user_' . $current_user->ID);
        $codesPostauxArray = [];
        
        if ($codePostal) {
            $codePostal = str_replace(' ', '', $codePostal);
            $codesPostauxArray = explode(';', $codePostal);
        }
        
        // Forcer le chargement des assets avec les codes postaux
        $this->force_enqueue_assets($codesPostauxArray);
        
        $atts = shortcode_atts(array(
            'title' => 'Recherche DPE',
            'show_config_warnings' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="dpe-error">Vous devez être connecté pour utiliser cette fonctionnalité.</div>';
        }
        
        ob_start();
        ?>
        <div class="dpe-frontend-wrapper">
            <h1><?php echo esc_html($atts['title']); ?></h1>
            
            <!-- Information pour les utilisateurs -->
            <div class="dpe-info">
                <p>
                    💡 Recherchez les diagnostics de performance énergétique par code postal.
                </p>
            </div>
            
            <!-- Affichage des avertissements de configuration -->
            <?php if ($atts['show_config_warnings'] === 'true'): ?>
                <?php
                // Vérifier si la configuration API est complète
                $config_manager = sci_config_manager();
                if (!$config_manager->is_configured()) {
                    echo '<div class="dpe-error"><strong>⚠️ Configuration manquante :</strong> Veuillez configurer vos tokens API dans l\'administration.</div>';
                }
                ?>
            <?php endif; ?>

            <!-- Formulaire de recherche AJAX -->
            <form id="dpe-search-form" class="dpe-form">
                <div class="form-group-left">
                    <div class="form-group">
                        <label for="codePostal">Sélectionnez votre code postal :</label>
                        <select name="codePostal" id="codePostal" required>
                            <option value="">— Choisir un code postal —</option>
                            <?php foreach ($codesPostauxArray as $index => $value): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php echo ($index === 0) ? 'selected' : ''; ?>>
                                <?php echo esc_html($value); ?>
                            </option>
                        <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" id="search-btn" class="dpe-button">
                        🔍 Rechercher les DPE
                    </button>
                </div>
            </form>

            <!-- Zone de chargement -->
            <div id="search-loading" style="display: none;">
                <div class="loading-spinner"></div>
                <span>Recherche en cours...</span>
            </div>

            <!-- Zone des résultats -->
            <div id="search-results" style="display: none;">
                <div id="results-header">
                    <h2 id="results-title">📋 Résultats de recherche DPE</h2>
                    <div id="pagination-info" style="display: none;"></div>
                </div>

                <!-- Tableau des résultats -->
                <table class="dpe-table" id="results-table">
                    <thead>
                        <tr>
                            <th>Favoris</th>
                            <th>Adresse</th>
                            <th>Code postal</th>
                            <th>Ville</th>
                            <th>DPE</th>
                            <th>GES</th>
                            <th>Date</th>
                            <th>Géolocalisation</th>
                        </tr>
                    </thead>
                    <tbody id="results-tbody">
                        <!-- Les résultats seront insérés ici par JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Contrôles de pagination -->
            <div id="pagination-controls" style="display: none;">
                <div class="pagination-main">
                    <button id="prev-page" disabled>⬅️ Page précédente</button>
                    <span id="page-info">1/1</span>
                    <button id="next-page" disabled>Page suivante ➡️</button>
                </div>
            </div>
            
            <!-- Cache des données -->
            <div id="data-cache" style="display: none;">
                <span id="cached-title"></span>
                <span id="cached-page"></span>
                <span id="cached-total"></span>
            </div>

            <!-- Zone d'erreur -->
            <div id="search-error" style="display: none;" class="dpe-error">
                <p id="error-message"></p>
            </div>
        </div>
        
        <!-- ✅ Styles CSS chargés depuis dpe-style.css -->
        
        <!-- JavaScript pour la gestion DPE -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.getElementById('dpe-search-form');
            const searchBtn = document.getElementById('search-btn');
            const loadingDiv = document.getElementById('search-loading');
            const resultsDiv = document.getElementById('search-results');
            const errorDiv = document.getElementById('search-error');
            const resultsTable = document.getElementById('results-table');
            const resultsTbody = document.getElementById('results-tbody');
            const paginationControls = document.getElementById('pagination-controls');
            const prevBtn = document.getElementById('prev-page');
            const nextBtn = document.getElementById('next-page');
            const pageInfo = document.getElementById('page-info');
            
            let currentPage = 1;
            let totalPages = 1;
            let currentResults = [];
            
            // ✅ NOUVEAU : Charger automatiquement les résultats au chargement de la page
            function autoLoadResults() {
                const codePostalSelect = document.getElementById('codePostal');
                if (codePostalSelect && codePostalSelect.value) {
                    performSearch(1); // Charger la première page automatiquement
                }
            }
            
            // Gestionnaire de soumission du formulaire
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                performSearch();
            });
            
            // ✅ NOUVEAU : Gestionnaire de changement de code postal
            document.getElementById('codePostal').addEventListener('change', function() {
                if (this.value) {
                    performSearch(1); // Recharger avec la première page
                }
            });
            
            // Fonction de recherche
            function performSearch(page = 1) {
                const codePostal = document.getElementById('codePostal').value;
                
                if (!codePostal) {
                    showError('Veuillez sélectionner un code postal');
                    return;
                }
                
                // Afficher le chargement
                showLoading();
                hideError();
                hideResults();
                
                // Préparer les données
                const formData = new FormData();
                formData.append('action', 'dpe_search_ajax');
                formData.append('code_postal', codePostal);
                formData.append('page', page);
                formData.append('nonce', dpe_ajax.nonce);
                
                // Effectuer la requête AJAX
                fetch(dpe_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    if (data.success) {
                        displayResults(data.data.results, data.data.pagination);
                    } else {
                        showError(data.data || 'Erreur lors de la recherche');
                    }
                })
                .catch(error => {
                    hideLoading();
                    showError('Erreur de connexion: ' + error.message);
                });
            }
            
            // Afficher les résultats
            function displayResults(results, pagination) {
                currentResults = results;
                currentPage = pagination.current_page;
                totalPages = pagination.total_pages;
                
                // Vider le tableau
                resultsTbody.innerHTML = '';
                
                // Ajouter les résultats
                results.forEach(result => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td style="text-align: center;">
                            <button class="favorite-btn" data-id="${result.id}" data-type="dpe">
                                ${isFavorite(result.id, 'dpe') ? '❤️' : '🤍'}
                            </button>
                        </td>
                        <td>${result.adresse || ''}</td>
                        <td>${result.code_postal || ''}</td>
                        <td>${result.ville || ''}</td>
                        <td><span class="dpe-label ${result.dpe || ''}">${result.dpe || ''}</span></td>
                        <td><span class="ges-label">${result.ges || ''}</span></td>
                        <td>${result.date || ''}</td>
                        <td>
                            ${result.adresse ? `<a href="https://maps.google.com/?q=${encodeURIComponent(result.adresse + ', ' + result.code_postal + ' ' + result.ville)}" target="_blank">Localiser</a>` : ''}
                        </td>
                    `;
                    resultsTbody.appendChild(row);
                });
                
                // Gérer la pagination
                updatePagination();
                
                // Afficher les résultats
                showResults();
                
                            // Attacher les événements aux boutons favoris
            attachFavoriteEvents();
        }
        
        // ✅ NOUVEAU : Appeler le chargement automatique après l'initialisation
        autoLoadResults();
        
        // Mettre à jour la pagination
            function updatePagination() {
                if (totalPages > 1) {
                    pageInfo.textContent = `${currentPage}/${totalPages}`;
                    prevBtn.disabled = currentPage <= 1;
                    nextBtn.disabled = currentPage >= totalPages;
                    paginationControls.style.display = 'block';
                } else {
                    paginationControls.style.display = 'none';
                }
            }
            
            // Gestionnaires de pagination
            prevBtn.addEventListener('click', function() {
                if (currentPage > 1) {
                    performSearch(currentPage - 1);
                }
            });
            
            nextBtn.addEventListener('click', function() {
                if (currentPage < totalPages) {
                    performSearch(currentPage + 1);
                }
            });
            
            // Fonctions d'affichage/masquage
            function showLoading() {
                loadingDiv.style.display = 'block';
                searchBtn.disabled = true;
            }
            
            function hideLoading() {
                loadingDiv.style.display = 'none';
                searchBtn.disabled = false;
            }
            
            function showResults() {
                resultsDiv.style.display = 'block';
            }
            
            function hideResults() {
                resultsDiv.style.display = 'none';
            }
            
            function showError(message) {
                document.getElementById('error-message').textContent = message;
                errorDiv.style.display = 'block';
            }
            
            function hideError() {
                errorDiv.style.display = 'none';
            }
            
            // Fonctions pour les favoris
            function isFavorite(id, type) {
                const favorites = JSON.parse(localStorage.getItem('dpe_favorites') || '[]');
                return favorites.some(fav => fav.id === id && fav.type === type);
            }
            
            function toggleFavorite(id, type) {
                const favorites = JSON.parse(localStorage.getItem('dpe_favorites') || '[]');
                const index = favorites.findIndex(fav => fav.id === id && fav.type === type);
                
                if (index > -1) {
                    favorites.splice(index, 1);
                } else {
                    favorites.push({ id, type, timestamp: Date.now() });
                }
                
                localStorage.setItem('dpe_favorites', JSON.stringify(favorites));
            }
            
            function attachFavoriteEvents() {
                document.querySelectorAll('.favorite-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        const type = this.dataset.type;
                        
                        toggleFavorite(id, type);
                        
                        // Mettre à jour l'affichage
                        if (isFavorite(id, type)) {
                            this.textContent = '❤️';
                            this.classList.add('favorited');
                        } else {
                            this.textContent = '🤍';
                            this.classList.remove('favorited');
                        }
                    });
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour embarquer le template dpe-panel-simple.php
     */
    public function dpe_panel_simple_shortcode($atts) {
        // Récupérer les codes postaux de l'utilisateur
        $current_user = wp_get_current_user();
        $codePostal = get_field('code_postal_user', 'user_' . $current_user->ID);
        $codesPostauxArray = [];
        
        if ($codePostal) {
            $codePostal = str_replace(' ', '', $codePostal);
            $codesPostauxArray = explode(';', $codePostal);
        }
        
        // Vérifier si l'utilisateur est connecté
        if (!is_user_logged_in()) {
            return '<div class="dpe-error">Vous devez être connecté pour utiliser cette fonctionnalité.</div>';
        }
        
        // Préparer le contexte pour le template
        $context = array(
            'codesPostauxArray' => $codesPostauxArray,
            'config_manager' => sci_config_manager(),
            'favoris_handler' => dpe_favoris_handler(),
            'dpe_handler' => null // Pas de gestionnaire DPE spécifique pour le moment
        );
        
        // Forcer le chargement des assets avec les codes postaux
        $this->force_enqueue_assets($codesPostauxArray);
        
        // Charger le template
        ob_start();
        sci_load_template('dpe-panel-simple.php', $context);
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour afficher les favoris DPE
     */
    public function dpe_favoris_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Mes favoris DPE'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="dpe-error">Vous devez être connecté pour voir vos favoris.</div>';
        }
        
        ob_start();
        ?>
        <div class="dpe-favoris-wrapper">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            <div id="dpe-favoris-list">
                <!-- Les favoris seront chargés ici par JavaScript -->
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            function loadFavorites() {
                const favorites = JSON.parse(localStorage.getItem('dpe_favorites') || '[]');
                const dpeFavorites = favorites.filter(fav => fav.type === 'dpe');
                const container = document.getElementById('dpe-favoris-list');
                
                if (dpeFavorites.length === 0) {
                    container.innerHTML = '<p>Aucun favori DPE pour le moment.</p>';
                    return;
                }
                
                let html = '<div class="dpe-favoris-grid">';
                dpeFavorites.forEach(fav => {
                    const date = new Date(fav.timestamp);
                    const formattedDate = date.toLocaleDateString('fr-FR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: '2-digit'
                    });
                    
                    html += `
                        <div class="dpe-favori-item">
                            <div class="favori-header">
                                <span class="favori-id">ID: ${fav.id}</span>
                                <span class="favori-date">${formattedDate}</span>
                                <button class="remove-favori" data-id="${fav.id}" data-type="${fav.type}">❌</button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                container.innerHTML = html;
                
                // Attacher les événements de suppression
                document.querySelectorAll('.remove-favori').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        const type = this.dataset.type;
                        
                        const favorites = JSON.parse(localStorage.getItem('dpe_favorites') || '[]');
                        const index = favorites.findIndex(fav => fav.id === id && fav.type === type);
                        
                        if (index > -1) {
                            favorites.splice(index, 1);
                            localStorage.setItem('dpe_favorites', JSON.stringify(favorites));
                            loadFavorites(); // Recharger la liste
                        }
                    });
                });
            }
            
            loadFavorites();
        });
        </script>
        
        <!-- ✅ Styles CSS chargés depuis dpe-style.css -->
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler pour la recherche DPE
     */
    public function dpe_search_ajax() {
        // Vérification de sécurité
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'dpe_favoris_nonce')) {
            wp_send_json_error('Nonce invalide');
            return;
        }
        
        $code_postal = sanitize_text_field($_POST['code_postal'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        
        if (empty($code_postal)) {
            wp_send_json_error('Code postal manquant');
            return;
        }
        
        // Simuler des résultats DPE pour le test
        $results = $this->simulate_dpe_results($code_postal, $page);
        
        wp_send_json_success([
            'results' => $results['data'],
            'pagination' => $results['pagination']
        ]);
    }
    
    /**
     * Simulation des résultats DPE pour le test
     */
    private function simulate_dpe_results($code_postal, $page) {
        $page_size = 50; // ✅ MODIFIÉ : 50 DPE par page
        $total_results = 150; // ✅ MODIFIÉ : Simuler 150 résultats au total
        $total_pages = ceil($total_results / $page_size);
        
        $results = [];
        for ($i = 1; $i <= $page_size; $i++) {
            $result_id = ($page - 1) * $page_size + $i;
            if ($result_id <= $total_results) {
                // ✅ AMÉLIORÉ : Générer des données plus réalistes
                $dpe_classes = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
                $ges_classes = ['1', '2', '3', '4', '5', '6', '7'];
                $villes = ['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier'];
                
                $results[] = [
                    'id' => 'DPE_' . $code_postal . '_' . $result_id,
                    'adresse' => $result_id . ' Rue de la Paix',
                    'code_postal' => $code_postal,
                    'ville' => $villes[array_rand($villes)],
                    'dpe' => $dpe_classes[array_rand($dpe_classes)],
                    'ges' => $ges_classes[array_rand($ges_classes)],
                    'date' => date('d/m/Y', strtotime('-' . rand(1, 365) . ' days'))
                ];
            }
        }
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_results' => $total_results,
                'page_size' => $page_size
            ]
        ];
    }
}

// Initialiser la classe
new DPE_Shortcodes(); 