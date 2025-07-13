<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire des shortcodes DPE
 */
class DPE_Shortcodes {
    
    public function __construct() {
        // Enregistrer les shortcodes DPE
        add_shortcode('dpe_panel', array($this, 'dpe_panel_shortcode'));
        add_shortcode('dpe_favoris', array($this, 'dpe_favoris_shortcode'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'), 5);
        add_action('wp_head', array($this, 'force_enqueue_on_shortcode_pages'), 1);
        add_action('wp_footer', array($this, 'ensure_scripts_loaded'), 999);
    }
    
    /**
     * Force le chargement sur les pages avec shortcodes DPE
     */
    public function force_enqueue_on_shortcode_pages() {
        global $post;
        
        // Vérifier si on est sur une page avec un shortcode DPE
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'dpe_panel') ||
            has_shortcode($post->post_content, 'dpe_favoris')
        )) {
            // Forcer le chargement immédiat
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
            has_shortcode($post->post_content, 'dpe_favoris')
        )) {
            // Vérifier si les scripts sont chargés, sinon les charger
            if (!wp_script_is('dpe-frontend-style', 'done')) {
                $this->force_enqueue_assets([]);
            }
        }
    }
    
    /**
     * Enqueue les scripts pour le frontend avec détection renforcée
     */
    public function enqueue_frontend_scripts() {
        global $post;
        
        $should_load = false;
        
        // Méthode 1 : Vérifier le post actuel
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'dpe_panel') ||
            has_shortcode($post->post_content, 'dpe_favoris')
        )) {
            $should_load = true;
        }
        
        // Méthode 2 : Vérifier via les paramètres GET (pour les pages dynamiques)
        if (!$should_load && (
            isset($_GET['dpe_view']) || 
            strpos($_SERVER['REQUEST_URI'] ?? '', 'dpe') !== false
        )) {
            $should_load = true;
        }
        
        // Méthode 3 : Forcer sur certaines pages spécifiques
        if (!$should_load && (
            is_page() || 
            is_single() || 
            is_front_page() ||
            is_home()
        )) {
            // Vérifier le contenu de la page actuelle
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
     * Force le chargement des assets
     */
    private function force_enqueue_assets($codesPostauxArray = []) {
        // Charger le CSS DPE
        wp_enqueue_style(
            'dpe-frontend-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/dpe-style.css',
            array(),
            '1.0.4'
        );
        
        // ✅ CHANGÉ : Charger les deux scripts nécessaires
        wp_enqueue_script(
            'dpe-favoris-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dpe-favoris.js',
            array('jquery'),
            '1.0.3',
            true
        );
        
        // ✅ AJOUTÉ : Charger aussi le script frontend pour la recherche
        wp_enqueue_script(
            'dpe-frontend-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dpe-frontend.js',
            array('jquery', 'dpe-favoris-script'),
            '1.0.3',
            true
        );
        
        // Localiser le script avec les données nécessaires
        wp_localize_script('dpe-favoris-script', 'dpe_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dpe_favoris_nonce'),
            'codes_postaux' => $codesPostauxArray
        ));
    }
    
    /**
     * Shortcode pour le panneau principal DPE
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
            'title' => '',
            'show_config_warnings' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="dpe-error">Vous devez être connecté pour utiliser cette fonctionnalité.</div>';
        }
        
        ob_start();
        ?>
        <div class="dpe-frontend-wrapper">
            <h1><?php echo !empty($atts['title']) ? esc_html($atts['title']) : 'DPE – Recherche de Diagnostics'; ?></h1>

            <!-- ✅ Les styles CSS sont maintenant chargés depuis le fichier externe dpe-style.css -->

            <!-- Information pour les utilisateurs -->
            <div class="dpe-info" style="background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #004085;">
                <p style="margin: 0; font-size: 16px; line-height: 1.5;">
                    Recherchez les diagnostics de performance énergétique (DPE) par code postal. Consultez les étiquettes énergétiques et les informations détaillées.
                </p>
            </div>
            
            <!-- Affichage du code postal par défaut -->
            <?php if (!empty($codesPostauxArray)): ?>
            <div class="dpe-default-postal" style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 12px; margin-bottom: 15px; color: #155724;">
                <p style="margin: 0; font-size: 14px; line-height: 1.4;">
                    <strong>Codes postaux disponibles :</strong> <?php echo esc_html(implode(', ', $codesPostauxArray)); ?>
                    <span style="color: #0c5460; font-style: italic;">(le premier sera sélectionné automatiquement)</span>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Affichage des avertissements de configuration -->
            <?php
            $config_manager = dpe_config_manager();
            if ($atts['show_config_warnings'] === 'true' && !$config_manager->is_configured()) {
                echo '<div class="dpe-error"><strong>⚠️ Configuration manquante :</strong> Veuillez configurer vos tokens API dans l\'administration.</div>';
            }
            ?>

            <!-- ✅ FORMULAIRE DE RECHERCHE AJAX -->
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
                    <div class="form-group">
                        <label for="buildingType">Type de bâtiment :</label>
                        <select name="buildingType" id="buildingType">
                            <option value="">— Tous les types —</option>
                            <option value="Maison">Maison</option>
                            <option value="Appartement">Appartement</option>
                            <option value="Immeuble">Immeuble</option>
                        </select>
                    </div>
                    <button type="submit" id="search-btn" class="dpe-button">
                        Rechercher les DPE
                    </button>
                </div>
            </form>

            <!-- ✅ ZONE DE CHARGEMENT -->
            <div id="search-loading" style="display: none;">
                <div class="loading-spinner"></div>
                <span>Recherche en cours...</span>
            </div>

            <!-- ✅ AFFICHAGE DE L'URL DE LA REQUÊTE -->
            <div id="api-url-display" style="display: none; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin: 15px 0; font-family: monospace; font-size: 12px; word-break: break-all;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <strong>URL de la requête API :</strong>
                    <button type="button" onclick="document.getElementById('api-url-display').style.display='none'" style="background: #dc3545; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 10px; cursor: pointer;">Masquer</button>
                </div>
                <span id="current-api-url" style="color: #0073aa;"></span>
            </div>

            <!-- ✅ ZONE DES RÉSULTATS - STRUCTURE STABLE -->
            <div id="search-results" style="display: none;">
                <div id="results-header">
                    <h2 id="results-title">Résultats de recherche</h2>
                    <div id="pagination-info" style="display: none;"></div>
                </div>
                
                <!-- ✅ TABLEAU DES RÉSULTATS - STRUCTURE STABLE -->
                <table class="dpe-table" id="results-table">
                    <thead>
                        <tr>
                            <th>Favoris</th>
                            <th>Type bâtiment</th>
                            <th>Date DPE</th>
                            <th>Adresse</th>
                            <th>Commune</th>
                            <th>Surface</th>
                            <th>Étiquette DPE</th>
                            <th>Étiquette GES</th>
                            <th>Géolocalisation</th>
                        </tr>
                    </thead>
                    <tbody id="results-tbody">
                        <!-- Les résultats seront insérés ici par JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- ✅ CONTRÔLES DE PAGINATION - HORS DE LA ZONE DES RÉSULTATS -->
            <div id="pagination-controls" style="display: none; margin-top: 20px; text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                <div class="pagination-main" style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                    <button id="prev-page" disabled style="padding: 10px 20px; font-size: 14px; font-weight: 500; border: none; border-radius: 0; background: #fff; color: #333; cursor: pointer; transition: all 0.2s ease; box-shadow: none;">Page précédente</button>
                    <span id="page-info" style="background: #0073aa; color: white; padding: 8px 15px; border-radius: 4px; font-size: 14px; font-weight: 500;">1/1</span>
                    <button id="next-page" disabled style="padding: 10px 20px; font-size: 14px; font-weight: 500; border: none; border-radius: 0; background: #fff; color: #333; cursor: pointer; transition: all 0.2s ease; box-shadow: none;">Page suivante</button>
                </div>
            </div>
            
            <!-- ✅ CACHE DES DONNÉES - ÉVITE LES RECHARGEMENTS -->
            <div id="data-cache" style="display: none;">
                <span id="cached-title"></span>
                <span id="cached-page"></span>
                <span id="cached-total"></span>
            </div>

            <!-- ✅ ZONE D'ERREUR -->
            <div id="search-error" style="display: none;" class="dpe-error">
                <p id="error-message"></p>
            </div>
        </div>

        <script>
        // Variables globales
        var currentPage = 1;
        var totalPages = 1;
        var totalResults = 0;
        var currentSearchParams = {
            codePostal: '<?php echo esc_js(!empty($codesPostauxArray) ? reset($codesPostauxArray) : ""); ?>'
        };

        // Variables pour la pagination
        var nextPageUrl = null;
        var previousPageUrls = [];
        var currentPageUrl = null;

        // Fonction pour construire l'URL de l'API
        function buildApiUrl(page = 1) {
            var baseUrl = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines?size=50&sort=-date_reception_dpe&q_mode=complete&q_fields=code_postal_ban,type_batiment';
            
            // Construire la requête structurée
            var queryString = 'code_postal_ban:"' + currentSearchParams.codePostal + '"';
            
            // Ajouter le type de bâtiment si sélectionné
            if (currentSearchParams.buildingType) {
                queryString += ' AND type_batiment:"' + currentSearchParams.buildingType.toLowerCase() + '"';
            }
            
            baseUrl += '&qs=' + encodeURIComponent(queryString);

            return baseUrl;
        }

        // Fonction pour récupérer les données de l'API
        function fetchDataFromApi(url, successCallback, errorCallback) {
            // Afficher l'URL de la requête
            var urlDisplay = document.getElementById('api-url-display');
            var urlSpan = document.getElementById('current-api-url');
            if (urlDisplay && urlSpan) {
                urlSpan.textContent = url;
                urlDisplay.style.display = 'block';
            }
            
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, true);

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var parsedResponse = JSON.parse(xhr.responseText);
                    
                    // Gérer la pagination
                    nextPageUrl = parsedResponse.next || null;
                    currentPageUrl = url;
                    
                    successCallback(parsedResponse);
                } else {
                    errorCallback();
                }
            };

            xhr.onerror = function() {
                errorCallback();
            };

            xhr.send();
        }

        // Fonction pour afficher les résultats
        function displayResults(data) {
            var tbody = document.getElementById('results-tbody');
            tbody.innerHTML = '';

            if (data.results && data.results.length > 0) {
                totalResults = data.total;
                
                // Calculer le nombre total de pages (approximatif)
                totalPages = Math.ceil(totalResults / 50);

                data.results.forEach(function (result) {
                    var row = document.createElement('tr');
                    
                    // Bouton favoris
                    var favCell = document.createElement('td');
                    var favBtn = document.createElement('button');
                    favBtn.className = 'favorite-btn';
                    favBtn.innerHTML = '☆';
                    favBtn.setAttribute('data-numero-dpe', result.numero_dpe || '');
                    favBtn.setAttribute('data-type-batiment', result.type_batiment || '');
                    favBtn.setAttribute('data-adresse', result.adresse_ban || result.adresse_brut || '');
                    favBtn.setAttribute('data-commune', result.nom_commune_ban || result.nom_commune_brut || '');
                    favBtn.setAttribute('data-code-postal', result.code_postal_ban || result.code_postal_brut || '');
                    favBtn.setAttribute('data-surface', result.surface_habitable_logement || '');
                    favBtn.setAttribute('data-etiquette-dpe', result.etiquette_dpe || '');
                    favBtn.setAttribute('data-etiquette-ges', result.etiquette_ges || '');
                    favBtn.setAttribute('data-date-dpe', result.date_etablissement_dpe || result.date_reception_dpe || '');
                    favBtn.title = 'Ajouter aux favoris';
                    favCell.appendChild(favBtn);
                    row.appendChild(favCell);

                    // Type bâtiment
                    row.appendChild(createCell(result.type_batiment || 'Non spécifié'));
                    
                    // Date DPE
                    row.appendChild(createCell(formatDate(result.date_etablissement_dpe || result.date_reception_dpe)));
                    
                    // Adresse
                    row.appendChild(createCell(result.adresse_ban || result.adresse_brut || 'Non spécifié'));
                    
                    // Commune
                    row.appendChild(createCell(result.nom_commune_ban || result.nom_commune_brut || 'Non spécifié'));
                    
                    // Surface
                    row.appendChild(createCell(result.surface_habitable_logement ? result.surface_habitable_logement + ' m²' : 'Non spécifié'));
                    
                    // Étiquette DPE
                    var dpeCell = document.createElement('td');
                    var dpeLabel = document.createElement('span');
                    dpeLabel.className = 'dpe-label ' + (result.etiquette_dpe || '');
                    dpeLabel.textContent = result.etiquette_dpe || 'Non spécifié';
                    dpeCell.appendChild(dpeLabel);
                    row.appendChild(dpeCell);
                    
                    // Étiquette GES
                    var gesCell = document.createElement('td');
                    var gesLabel = document.createElement('span');
                    gesLabel.className = 'dpe-label ' + (result.etiquette_ges || '');
                    gesLabel.textContent = result.etiquette_ges || 'Non spécifié';
                    gesCell.appendChild(gesLabel);
                    row.appendChild(gesCell);
                    
                    // Géolocalisation avec adresse simple
                    var geoCell = document.createElement('td');
                    
                    if (result.adresse_ban && result.adresse_ban.trim()) {
                        var geoLink = document.createElement('a');
                        geoLink.className = 'maps-link';
                        geoLink.href = 'https://www.google.com/maps/place/' + encodeURIComponent(result.adresse_ban.trim());
                        geoLink.target = '_blank';
                        geoLink.rel = 'noopener noreferrer';
                        geoLink.innerHTML = 'Localiser';
                        geoLink.title = 'Localiser sur Google Maps';
                        geoCell.appendChild(geoLink);
                    } else {
                        geoCell.textContent = 'Non disponible';
                    }
                    row.appendChild(geoCell);

                    tbody.appendChild(row);
                });

                // Mettre à jour les informations de pagination
                updatePaginationInfo();
                showPaginationControls();
                
                        // Réinitialiser les favoris après affichage des résultats
        if (typeof window.refreshFavorisAfterPageChange === 'function') {
            window.refreshFavorisAfterPageChange();
        } else if (typeof window.updateFavButtons === 'function') {
            window.updateFavButtons();
            if (typeof window.attachFavorisListeners === 'function') {
                window.attachFavorisListeners();
            }
        }
        
        // ✅ Utiliser le système de favoris DPE existant
        if (typeof window.dpeFavoris !== 'undefined' && typeof window.dpeFavoris.updateButtons === 'function') {
            window.dpeFavoris.updateButtons();
        } else if (typeof window.updateDpeFavButtons === 'function') {
            window.updateDpeFavButtons();
        }
        
        // ✅ NOUVEAU : Initialiser les favoris après affichage des résultats
        if (typeof window.dpeFavoris !== 'undefined' && typeof window.dpeFavoris.init === 'function') {
            window.dpeFavoris.init();
        }
            } else {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px; color: #666;">Aucun résultat trouvé</td></tr>';
                hidePaginationControls();
            }
        }

        // Fonction pour créer une cellule
        function createCell(content) {
            var cell = document.createElement('td');
            cell.textContent = content;
            return cell;
        }

        // Fonction pour formater la date en format dd/MM/YY
        function formatDate(dateString) {
            if (!dateString) return 'Non spécifié';
            
            // Essayer de parser la date dans différents formats
            var dateObj = new Date(dateString);
            if (isNaN(dateObj.getTime())) {
                // Essayer le format YYYY-MM-DD
                var parts = dateString.split('-');
                if (parts.length === 3) {
                    dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
                } else {
                    return dateString; // Retourner la chaîne originale si pas de date valide
                }
            }
            
            if (isNaN(dateObj.getTime()) || dateObj.getFullYear() < 1900) {
                return dateString;
            }
            
            // Formater en dd/MM/YY
            var day = String(dateObj.getDate()).padStart(2, '0');
            var month = String(dateObj.getMonth() + 1).padStart(2, '0');
            var year = String(dateObj.getFullYear()).slice(-2); // Prendre seulement les 2 derniers chiffres
            
            return day + '/' + month + '/' + year;
        }

        // Fonction pour mettre à jour les informations de pagination
        function updatePaginationInfo() {
            var pageInfo = document.getElementById('page-info');
            pageInfo.textContent = currentPage + '/' + totalPages;
            
            var paginationInfo = document.getElementById('pagination-info');
            paginationInfo.textContent = totalResults + ' résultat(s) trouvé(s)';
            paginationInfo.style.display = 'block';
        }

        // Fonction pour afficher les contrôles de pagination
        function showPaginationControls() {
            var controls = document.getElementById('pagination-controls');
            controls.style.display = 'block';
            
            var prevBtn = document.getElementById('prev-page');
            var nextBtn = document.getElementById('next-page');
            
            // Activer/désactiver le bouton précédent
            prevBtn.disabled = previousPageUrls.length === 0;
            
            // Activer/désactiver le bouton suivant
            nextBtn.disabled = !nextPageUrl;
        }

        // Fonction pour masquer les contrôles de pagination
        function hidePaginationControls() {
            var controls = document.getElementById('pagination-controls');
            controls.style.display = 'none';
        }

        // Fonction pour effectuer une recherche
        function performSearch() {
            var codePostal = document.getElementById('codePostal').value;
            var buildingType = document.getElementById('buildingType').value;
            
            if (!codePostal) {
                alert('Veuillez sélectionner un code postal');
                return;
            }
            
            currentSearchParams.codePostal = codePostal;
            currentSearchParams.buildingType = buildingType;
            currentPage = 1;
            
            // Réinitialiser la pagination
            nextPageUrl = null;
            previousPageUrls = [];
            currentPageUrl = null;
            
            showLoading();
            hideError();
            
            var url = buildApiUrl();
            fetchDataFromApi(url, function(data) {
                hideLoading();
                displayResults(data);
                showResults();
            }, function() {
                hideLoading();
                showError('Erreur lors de la récupération des données depuis l\'API');
            });
        }

        // Fonctions d'affichage/masquage
        function showLoading() {
            document.getElementById('search-loading').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('search-loading').style.display = 'none';
        }

        function showResults() {
            document.getElementById('search-results').style.display = 'block';
        }

        function hideResults() {
            document.getElementById('search-results').style.display = 'none';
        }

        function showError(message) {
            var errorDiv = document.getElementById('search-error');
            document.getElementById('error-message').textContent = message;
            errorDiv.style.display = 'block';
        }

        function hideError() {
            document.getElementById('search-error').style.display = 'none';
        }

        // Gestionnaires d'événements
        document.getElementById('dpe-search-form').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });

        document.getElementById('prev-page').addEventListener('click', function() {
            if (previousPageUrls.length > 0) {
                // Récupérer l'URL précédente
                var previousUrl = previousPageUrls.pop();
                
                // Sauvegarder l'URL actuelle comme "next" pour pouvoir revenir
                if (currentPageUrl) {
                    nextPageUrl = currentPageUrl;
                }
                
                currentPage--;
                
                showLoading();
                fetchDataFromApi(previousUrl, function(data) {
                    hideLoading();
                    displayResults(data);
                }, function() {
                    hideLoading();
                    showError('Erreur lors de la récupération de la page précédente');
                });
            }
        });

        document.getElementById('next-page').addEventListener('click', function() {
            if (nextPageUrl) {
                // Sauvegarder l'URL actuelle pour pouvoir revenir
                if (currentPageUrl) {
                    previousPageUrls.push(currentPageUrl);
                }
                
                currentPage++;
                
                showLoading();
                fetchDataFromApi(nextPageUrl, function(data) {
                    hideLoading();
                    displayResults(data);
                }, function() {
                    hideLoading();
                    showError('Erreur lors de la récupération de la page suivante');
                });
            }
        });

        // Chargement initial
        window.onload = function () {
            console.log('🔄 Shortcode DPE - window.onload exécuté');
            
            // Initialiser les favoris DPE
            if (typeof window.dpeFavoris !== 'undefined' && typeof window.dpeFavoris.init === 'function') {
                console.log('✅ Initialisation des favoris DPE...');
                window.dpeFavoris.init();
            } else {
                console.warn('⚠️ Système de favoris DPE non disponible');
            }
            
            // Effectuer la recherche si un code postal est sélectionné
            if (document.getElementById('codePostal').value) {
                console.log('🔍 Lancement de la recherche automatique...');
                performSearch();
            }
        };
        
        // ✅ NOUVEAU : Initialisation alternative si window.onload ne fonctionne pas
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔄 Shortcode DPE - DOMContentLoaded exécuté');
            
            // Initialiser les favoris DPE
            if (typeof window.dpeFavoris !== 'undefined' && typeof window.dpeFavoris.init === 'function') {
                console.log('✅ Initialisation des favoris DPE (DOMContentLoaded)...');
                window.dpeFavoris.init();
            } else {
                console.warn('⚠️ Système de favoris DPE non disponible (DOMContentLoaded)');
            }
            
            // Effectuer la recherche si un code postal est sélectionné
            if (document.getElementById('codePostal').value) {
                console.log('🔍 Lancement de la recherche automatique (DOMContentLoaded)...');
                performSearch();
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode pour les favoris DPE
     */
    public function dpe_favoris_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Mes Favoris DPE',
            'show_empty_message' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="dpe-error">Vous devez être connecté pour voir vos favoris DPE.</div>';
        }
        
        $user_id = get_current_user_id();
        $favoris_handler = dpe_favoris_handler();
        $favoris = $favoris_handler->get_user_favoris($user_id);
        
        // Forcer le chargement des assets
        $this->force_enqueue_assets([]);
        
        // Fonctions utilitaires
        function dpe_class($val) {
            $val = strtoupper(trim($val));
            return in_array($val, ['A','B','C','D','E','F','G']) ? $val : '';
        }
        
        function formatDateFr($dateString) {
            if (empty($dateString)) {
                return 'Non spécifié';
            }
            
            // Essayer de parser la date
            $date = DateTime::createFromFormat('Y-m-d', $dateString);
            if (!$date) {
                $date = DateTime::createFromFormat('d/m/Y', $dateString);
            }
            if (!$date) {
                $date = new DateTime($dateString);
            }
            
            if (!$date || $date->format('Y') < 1900) {
                return $dateString; // Retourner la chaîne originale si pas de date valide
            }
            
            // Formater en dd/MM/YY
            return $date->format('d/m/Y');
        }
        
        function createGoogleMapsLink($adresse, $codePostal, $commune) {
            if (empty($adresse)) {
                return 'Non disponible';
            }
            
            $adresseSimple = trim($adresse);
            $adresseSimple = preg_replace('/\s+/', ' ', $adresseSimple); // Nettoyer les espaces multiples
            
            if (empty($adresseSimple)) {
                return 'Non disponible';
            }
            
            $mapsUrl = 'https://www.google.com/maps/place/' . urlencode($adresseSimple);
            
            return sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer" class="maps-link" title="Localiser sur Google Maps">Localiser</a>',
                esc_url($mapsUrl)
            );
        }
        
        ob_start();
        ?>
        <div class="dpe-frontend-wrapper">
            <h1>🏠 <?php echo esc_html($atts['title']); ?></h1>

            <!-- ✅ INFORMATION POUR LES UTILISATEURS -->
            <div class="dpe-info" style="background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #004085;">
                <p style="margin: 0; font-size: 16px; line-height: 1.5;">
                    💡 Consultez vos diagnostics de performance énergétique favoris.
                </p>
            </div>
            
            <!-- ✅ STATISTIQUES DES FAVORIS -->
            <div class="dpe-default-postal" style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 12px; margin-bottom: 15px; color: #155724;">
                <p style="margin: 0; font-size: 14px; line-height: 1.4;">
                    📊 <strong>Total des favoris :</strong> <?php echo count($favoris); ?> diagnostic(s) sauvegardé(s)
                </p>
            </div>

            <!-- ✅ ZONE DES RÉSULTATS -->
            <div id="dpe-favoris-results">
                <?php if (empty($favoris)): ?>
                    <div class="dpe-info" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #856404;">
                        <p style="margin: 0; font-size: 16px; line-height: 1.5;">
                            📭 Vous n'avez pas encore de favoris DPE. 
                            Utilisez le shortcode <code>[dpe_panel]</code> pour rechercher et ajouter des DPE à vos favoris.
                        </p>
                    </div>
                <?php else: ?>
                    <!-- ✅ TABLEAU DES FAVORIS -->
                    <table class="dpe-table" id="dpe-favoris-table">
                        <thead>
                            <tr>
                                <th>Adresse</th>
                                <th>Commune</th>
                                <th>Type d'habitation</th>
                                <th>Surface</th>
                                <th>Étiquette DPE</th>
                                <th>Étiquette GES</th>
                                <th>Date DPE</th>
                                <th>Géolocalisation</th>
                                <th>Supprimer</th>
                            </tr>
                        </thead>
                        <tbody id="dpe-favoris-tbody">
                            <?php foreach ($favoris as $favori): ?>
                                <tr data-dpe-id="<?php echo esc_attr($favori->dpe_id); ?>">
                                    <td class="adresse"><?php echo esc_html($favori->adresse_ban ?: 'Non spécifié'); ?></td>
                                    <td class="commune"><?php echo esc_html($favori->nom_commune_ban ?: 'Non spécifié'); ?></td>
                                    <td class="type-batiment"><?php echo esc_html($favori->type_batiment ?: 'Non spécifié'); ?></td>
                                    <td class="surface"><?php echo $favori->surface_habitable_logement ? esc_html($favori->surface_habitable_logement . ' m²') : 'Non spécifié'; ?></td>
                                    <td>
                                        <span class="dpe-label <?php echo dpe_class($favori->etiquette_dpe); ?>">
                                            <?php echo esc_html($favori->etiquette_dpe ?: 'Non spécifié'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="ges-label <?php echo dpe_class($favori->etiquette_ges); ?>">
                                            <?php echo esc_html($favori->etiquette_ges ?: 'Non spécifié'); ?>
                                        </span>
                                    </td>
                                    <td class="date-dpe"><?php echo formatDateFr($favori->date_etablissement_dpe); ?></td>
                                    <td class="geolocalisation">
                                        <?php 
                                        // ✅ NOUVEAU : Utiliser la fonction pour créer le lien Google Maps
                                        echo createGoogleMapsLink(
                                            $favori->adresse_ban,
                                            $favori->code_postal_ban,
                                            $favori->nom_commune_ban
                                        );
                                        ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-remove-favori" title="Supprimer ce favori" onclick="removeFavori('<?php echo esc_js($favori->dpe_id); ?>')" style="background:none;border:none;cursor:pointer;font-size:18px;color:#e30613;">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- ✅ Les styles CSS sont maintenant chargés depuis le fichier externe dpe-style.css -->

        <script>
        function removeFavori(dpeId) {
            if (!confirm('Êtes-vous sûr de vouloir retirer ce diagnostic de vos favoris ?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'dpe_manage_favoris');
            formData.append('operation', 'remove');
            formData.append('nonce', '<?php echo wp_create_nonce("dpe_favoris_nonce"); ?>');
            formData.append('dpe_data', JSON.stringify({numero_dpe: dpeId}));
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer la ligne du tableau
                    const row = document.querySelector(`tr[data-dpe-id="${dpeId}"]`);
                    if (row) {
                        row.remove();
                        
                        // Mettre à jour le compteur
                        const totalRows = document.querySelectorAll('#dpe-favoris-tbody tr').length;
                        const statsElement = document.querySelector('.dpe-default-postal p');
                        if (statsElement) {
                            statsElement.innerHTML = `📊 <strong>Total des favoris :</strong> ${totalRows} diagnostic(s) sauvegardé(s)`;
                        }
                        
                        // Si plus de favoris, afficher le message
                        if (totalRows === 0) {
                            location.reload(); // Recharger pour afficher le message "aucun favori"
                        }
                    }
                } else {
                    alert('Erreur lors de la suppression : ' + (data.data || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                alert('Erreur de connexion : ' + error.message);
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }
}

// Initialiser la classe des shortcodes DPE
new DPE_Shortcodes();
?> 