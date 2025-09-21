<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire des shortcodes DPE
 */
class DPE_Shortcodes {
    
    public function __construct() {
        // Enregistrer uniquement le shortcode principal DPE
        add_shortcode('dpe_panel', array($this, 'dpe_panel_shortcode'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'), 5);
        add_action('wp_head', array($this, 'force_enqueue_on_shortcode_pages'), 1);
        add_action('wp_footer', array($this, 'ensure_scripts_loaded'), 999);
    }
    
    /**
     * Force le chargement sur les pages avec shortcodes DPE
     */
    public function force_enqueue_on_shortcode_pages() {
        global $post;
        
        // Vérifier si on est sur une page avec le shortcode DPE principal
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dpe_panel')) {
            // Forcer le chargement immédiat
            $this->force_enqueue_assets([]);
        }
    }
    
    /**
     * S'assurer que les scripts sont chargés en footer
     */
    public function ensure_scripts_loaded() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dpe_panel')) {
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
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dpe_panel')) {
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
        // Charger Font Awesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // Charger le CSS de protection contre les thèmes en premier
        wp_enqueue_style(
            'theme-protection-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/theme-protection.css',
            array('font-awesome'),
            '1.0.0'
        );
        
        // Charger le CSS des composants génériques
        wp_enqueue_style(
            'components-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/components.css',
            array('theme-protection-style'),
            '1.0.0'
        );
        
        // Charger le CSS DPE
        wp_enqueue_style(
            'dpe-frontend-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/dpe-style.css',
            array('components-style'),
            '1.0.5'
        );
        
        // ✅ CHANGÉ : Charger les deux scripts nécessaires
        wp_enqueue_script(
            'dpe-favoris-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dpe-favoris.js',
            array('jquery'),
            '1.0.4',
            true
        );
        
        // ✅ AJOUTÉ : Charger aussi le script frontend pour la recherche
        wp_enqueue_script(
            'dpe-frontend-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dpe-frontend.js',
            array('jquery', 'dpe-favoris-script'),
            '1.0.4',
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
        <div class="my-istymo">
            <div class="frontend-wrapper">
            <?php if (!empty($atts['title'])): ?>
                <h1><i class="fas fa-search"></i> <?php echo esc_html($atts['title']); ?></h1>
            <?php endif; ?>

            <!-- Information pour les utilisateurs -->
            <div class="info-message">
                <p>
                    <i class="fas fa-info-circle"></i> <strong>Aide à la prospection Lead DPE</strong><br><br>
                    L'obligation du Diagnostic de Performance Énergétique concerne toute personne désirant mettre en vente un bien immobilier. Facilitez votre prospection et anticipez les ventes à venir en consultant la liste des DPE réalisés sur vos secteurs d'activité.
                </p>
            </div>
            

            
            <!-- Affichage des avertissements de configuration -->
            <?php
            $config_manager = dpe_config_manager();
            if ($atts['show_config_warnings'] === 'true' && !$config_manager->is_configured()) {
                echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <strong>Configuration manquante :</strong> Veuillez configurer vos tokens API dans l\'administration.</div>';
            }
            ?>

            <!-- ✅ FORMULAIRE DE RECHERCHE AJAX -->
            <form id="dpe-search-form" class="search-form">
                <div class="form-row">
                    <div class="form-field">
                        <label for="codePostal"><i class="fas fa-map-marker-alt"></i> Sélectionnez votre code postal :</label>
                        <select name="codePostal" id="codePostal" required>
                            <option value="">— Choisir un code postal —</option>
                            <?php foreach ($codesPostauxArray as $index => $value): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php echo ($index === 0) ? 'selected' : ''; ?>>
                                    <?php echo esc_html($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="buildingType"><i class="fas fa-building"></i> Type de bâtiment :</label>
                        <select name="buildingType" id="buildingType">
                            <option value="">— Tous les types —</option>
                            <option value="Maison"><i class="fas fa-home"></i> Maison</option>
                            <option value="Appartement"><i class="fas fa-building"></i> Appartement</option>
                            <option value="Immeuble"><i class="fas fa-city"></i> Immeuble</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="keywordSearch"><i class="fas fa-search"></i> Recherche par adresse :</label>
                        <input type="text" name="keywordSearch" id="keywordSearch" placeholder="Ex: rue de la paix, avenue victor hugo...">
                    </div>
                    <button type="submit" id="search-btn" class="btn btn-primary">
                        <i class="fas fa-search"></i> Rechercher les DPE
                    </button>
                </div>
            </form>

            <!-- ✅ ZONE DE CHARGEMENT -->
            <div id="search-loading" class="d-none">
                <div class="loading-spinner"></div>
                <span><i class="fas fa-spinner fa-spin"></i> Recherche en cours...</span>
            </div>

            <!-- ✅ AFFICHAGE DE L'URL DE LA REQUÊTE -->
            <div id="api-url-display" class="alert alert-info d-none">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong><i class="fas fa-link"></i> URL de la requête API :</strong>
                    <button type="button" onclick="document.getElementById('api-url-display').classList.add('d-none')" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Masquer</button>
                </div>
                <span id="current-api-url" style="font-family: monospace; word-break: break-all; font-size: 12px;"></span>
            </div>

            <!-- ✅ ZONE DES RÉSULTATS - STRUCTURE STABLE -->
            <div id="search-results" class="search-results d-none">
                <div id="results-header">
                    <h2 id="results-title"><i class="fas fa-list"></i> Résultats de recherche</h2>
                    <div id="pagination-info" class="d-none"></div>
                </div>
                
                <!-- ✅ TABLEAU DES RÉSULTATS - STRUCTURE STABLE -->
                <table class="data-table" id="results-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-heart" title="Favoris - Enregistrez les DPE pour les traiter dans la gestion des leads"></i></th>
                            <th><i class="fas fa-building"></i> Type bâtiment</th>
                            <th><i class="fas fa-calendar"></i> Date DPE</th>
                            <th><i class="fas fa-map-marker-alt"></i> Adresse</th>
                            <th><i class="fas fa-city"></i> Ville</th>
                            <th><i class="fas fa-expand-arrows-alt"></i> Surface</th>
                            <th><i class="fas fa-certificate"></i> Étiquette DPE</th>
                            <th><i class="fas fa-plus"></i> Complément adresse</th>
                            <th><i class="fas fa-map"></i> Géolocalisation</th>
                        </tr>
                    </thead>
                    <tbody id="results-tbody">
                        <!-- Les résultats seront insérés ici par JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- ✅ CONTRÔLES DE PAGINATION - HORS DE LA ZONE DES RÉSULTATS -->
            <div id="pagination-controls" class="pagination-controls d-none">
                <div class="pagination-main">
                    <button id="prev-page" class="pagination-btn" disabled><i class="fas fa-chevron-left"></i> Page précédente</button>
                    <span id="page-info" class="page-info">1/1</span>
                    <button id="next-page" class="pagination-btn" disabled>Page suivante <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            
            <!-- ✅ CACHE DES DONNÉES - ÉVITE LES RECHARGEMENTS -->
            <div id="data-cache" class="d-none">
                <span id="cached-title"></span>
                <span id="cached-page"></span>
                <span id="cached-total"></span>
            </div>

            <!-- ✅ ZONE D'ERREUR -->
            <div id="search-error" class="alert alert-danger d-none">
                <p id="error-message"><i class="fas fa-exclamation-circle"></i> <span id="error-text"></span></p>
            </div>
        </div>
        </div>

        <script>
        // Variables globales
        var currentPage = 1;
        var totalPages = 1;
        var totalResults = 0;
        var currentSearchParams = {
            codePostal: '<?php echo esc_js(!empty($codesPostauxArray) ? reset($codesPostauxArray) : ""); ?>',
            buildingType: '',
            keywordSearch: ''
        };

        // Variables pour la pagination - Version simplifiée et corrigée
        var nextPageUrl = null;
        var previousPageUrls = [];
        var currentPageUrl = null;
        var currentPageNumber = 1; // Numéro de page actuel

        // Fonction pour construire l'URL de l'API
        function buildApiUrl(page = 1) {
            var baseUrl = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines?size=50&sort=-date_reception_dpe&q_mode=complete&q_fields=code_postal_ban,type_batiment,adresse_ban,adresse_brut';
            
            // Construire la requête structurée pour les filtres
            var queryString = 'code_postal_ban:"' + currentSearchParams.codePostal + '"';
            
            // Ajouter le type de bâtiment si sélectionné
            if (currentSearchParams.buildingType) {
                queryString += ' AND type_batiment:"' + currentSearchParams.buildingType.toLowerCase() + '"';
            }
            
            // Ajouter le paramètre qs pour les filtres structurés
            baseUrl += '&qs=' + encodeURIComponent(queryString);
            
            // Ajouter la recherche textuelle avec le paramètre q si un mot-clé est spécifié
            if (currentSearchParams.keywordSearch && currentSearchParams.keywordSearch.trim()) {
                var keyword = currentSearchParams.keywordSearch.trim();
                baseUrl += '&q=' + encodeURIComponent(keyword);
            }

            // Debug conditionnel pour la production
            if (typeof window.myIstymoDebug !== 'undefined' && window.myIstymoDebug) {
                console.log('URL de recherche DPE:', baseUrl);
                console.log('Paramètres de recherche:', currentSearchParams);
            }

            return baseUrl;
        }

        // Fonction pour récupérer les données de l'API
        function fetchDataFromApi(url, successCallback, errorCallback) {
            // Afficher l'URL de la requête
            var urlDisplay = document.getElementById('api-url-display');
            var urlSpan = document.getElementById('current-api-url');
            if (urlDisplay && urlSpan) {
                urlSpan.textContent = url;
                urlDisplay.classList.remove('d-none');
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

        // Fonction pour nettoyer l'adresse (enlever code postal et commune)
        function cleanAddress(address) {
            if (!address) return 'Non spécifié';
            
            // Supprimer le code postal (5 chiffres) et la commune qui suivent
            var cleaned = address.replace(/\s+\d{5}\s+[A-Za-zÀ-ÿ\s-]+$/, '');
            
            // Si l'adresse est vide après nettoyage, retourner l'original
            return cleaned.trim() || address.trim();
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
            
            if (isNaN(dateObj.getTime())) {
                return dateString; // Retourner la chaîne originale si pas de date valide
            }
            
            // Formater en dd/MM/YY
            var day = String(dateObj.getDate()).padStart(2, '0');
            var month = String(dateObj.getMonth() + 1).padStart(2, '0');
            var year = String(dateObj.getFullYear()).slice(-2);
            
            return day + '/' + month + '/' + year;
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
                    favBtn.innerHTML = '<i class="far fa-heart"></i>';
                    favBtn.setAttribute('data-numero-dpe', result.numero_dpe || '');
                    favBtn.setAttribute('data-type-batiment', result.type_batiment || '');
                    favBtn.setAttribute('data-adresse', result.adresse_ban || result.adresse_brut || '');
                    favBtn.setAttribute('data-commune', result.nom_commune_ban || result.nom_commune_brut || '');
                    favBtn.setAttribute('data-code-postal', result.code_postal_ban || result.code_postal_brut || '');
                    favBtn.setAttribute('data-surface', result.surface_habitable_logement || '');
                    favBtn.setAttribute('data-etiquette-dpe', result.etiquette_dpe || '');
                    favBtn.setAttribute('data-date-dpe', result.date_etablissement_dpe || result.date_reception_dpe || '');
                    favBtn.title = 'Ajouter aux favoris - Enregistrez ce DPE pour le traiter dans la gestion des leads';
                    favCell.appendChild(favBtn);
                    row.appendChild(favCell);

                    // Type bâtiment
                    row.appendChild(createCell(result.type_batiment || 'Non spécifié'));
                    
                    // Date DPE
                    row.appendChild(createCell(formatDate(result.date_etablissement_dpe || result.date_reception_dpe)));
                    
                    // Adresse (nettoyée)
                    row.appendChild(createCell(cleanAddress(result.adresse_ban || result.adresse_brut)));
                    
                    // Commune
                    row.appendChild(createCell(result.nom_commune_ban || result.nom_commune_brut || 'Non spécifié'));
                    
                    // Surface
                    row.appendChild(createCell(result.surface_habitable_logement ? result.surface_habitable_logement + ' m²' : 'Non spécifié'));
                    
                    // Étiquette DPE
                    var dpeCell = document.createElement('td');
                    var dpeLabel = document.createElement('span');
                    dpeLabel.className = 'label ' + (result.etiquette_dpe || '');
                    dpeLabel.textContent = result.etiquette_dpe || 'Non spécifié';
                    dpeCell.appendChild(dpeLabel);
                    row.appendChild(dpeCell);
                    
                    // Complément adresse
                    var complementCell = document.createElement('td');
                    var complementText = result.complement_adresse_logement || '';
                    complementCell.textContent = complementText;
                    row.appendChild(complementCell);
                    
                    // Géolocalisation avec adresse simple
                    var geoCell = document.createElement('td');
                    
                    if (result.adresse_ban && result.adresse_ban.trim()) {
                        var geoLink = document.createElement('a');
                        geoLink.className = 'maps-link';
                        geoLink.href = 'https://www.google.com/maps/place/' + encodeURIComponent(result.adresse_ban.trim());
                        geoLink.target = '_blank';
                        geoLink.rel = 'noopener noreferrer';
                        geoLink.innerHTML = '<i class="fas fa-map-marker-alt"></i> Localiser';
                        geoLink.title = 'Localiser sur Google Maps';
                        geoCell.appendChild(geoLink);
                    } else {
                        geoCell.innerHTML = '<i class="fas fa-ban"></i> Non disponible';
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

        // Fonction pour mettre à jour les informations de pagination
        function updatePaginationInfo() {
            var pageInfo = document.getElementById('page-info');
            // Utiliser le numéro de page actuel directement
            pageInfo.textContent = currentPageNumber + '/' + totalPages;
            
            var paginationInfo = document.getElementById('pagination-info');
            paginationInfo.textContent = totalResults + ' résultat(s) trouvé(s)';
            paginationInfo.classList.remove('d-none');
        }

        // Fonction pour afficher les contrôles de pagination
        function showPaginationControls() {
            var controls = document.getElementById('pagination-controls');
            controls.classList.remove('d-none');
            
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
            controls.classList.add('d-none');
        }

        // Fonction pour effectuer une recherche
        function performSearch() {
            var codePostal = document.getElementById('codePostal').value;
            var buildingType = document.getElementById('buildingType').value;
            var keywordSearch = document.getElementById('keywordSearch').value;
            
            if (!codePostal) {
                alert('Veuillez sélectionner un code postal');
                return;
            }
            
            currentSearchParams.codePostal = codePostal;
            currentSearchParams.buildingType = buildingType;
            currentSearchParams.keywordSearch = keywordSearch;
            
            // Réinitialiser la pagination
            nextPageUrl = null;
            previousPageUrls = [];
            currentPageUrl = null;
            currentPageNumber = 1; // Réinitialiser le numéro de page
            
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
            document.getElementById('search-loading').classList.remove('d-none');
        }

        function hideLoading() {
            document.getElementById('search-loading').classList.add('d-none');
        }

        function showResults() {
            document.getElementById('search-results').classList.remove('d-none');
        }

        function hideResults() {
            document.getElementById('search-results').classList.add('d-none');
        }

        function showError(message) {
            var errorDiv = document.getElementById('search-error');
            document.getElementById('error-text').textContent = message;
            errorDiv.classList.remove('d-none');
        }

        function hideError() {
            document.getElementById('search-error').classList.add('d-none');
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
                
                // Décrémenter le numéro de page
                currentPageNumber--;
                
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
                
                // Incrémenter le numéro de page
                currentPageNumber++;
                
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
            // Initialiser les favoris DPE
            if (typeof window.dpeFavoris !== 'undefined' && typeof window.dpeFavoris.init === 'function') {
                window.dpeFavoris.init();
            }
            
            // Effectuer la recherche si un code postal est sélectionné
            if (document.getElementById('codePostal').value) {
                performSearch();
            }
        };
        
        // Initialisation alternative si window.onload ne fonctionne pas
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser les favoris DPE
            if (typeof window.dpeFavoris !== 'undefined' && typeof window.dpeFavoris.init === 'function') {
                window.dpeFavoris.init();
            }
            
            // Effectuer la recherche si un code postal est sélectionné
            if (document.getElementById('codePostal').value) {
                performSearch();
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    

}

// Initialiser la classe des shortcodes DPE
new DPE_Shortcodes();
?> 