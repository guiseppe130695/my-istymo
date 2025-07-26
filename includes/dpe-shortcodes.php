<?php
if (!defined('ABSPATH')) exit;

/**
 * Gestionnaire des shortcodes DPE
 */
class DPE_Shortcodes {
    
    public function __construct() {
        // Enregistrer les shortcodes DPE
        add_shortcode('dpe_panel', array($this, 'dpe_panel_shortcode'));
        add_shortcode('dpe_campaigns', array($this, 'dpe_campaigns_shortcode'));

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
        // Charger le CSS DPE
        wp_enqueue_style(
            'dpe-frontend-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/dpe-style.css',
            array(),
            '1.0.5'
        );
        
        // ✅ CHANGÉ : Charger les scripts nécessaires
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
        
        // ✅ NOUVEAU : Charger le système unique de sélection DPE
        wp_enqueue_script(
            'dpe-selection-system',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dpe-selection-system.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // ✅ NOUVEAU : Charger le script d'envoi de lettres DPE
        wp_enqueue_script(
            'dpe-lettre-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dpe-lettre.js',
            array('jquery', 'dpe-favoris-script', 'dpe-selection-system'),
            '1.0.0',
            true
        );
        
        // ✅ NOUVEAU : Charger le script de paiement DPE
        wp_enqueue_script(
            'dpe-payment-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dpe-payment.js',
            array('jquery', 'dpe-lettre-script'),
            '1.0.0',
            true
        );
        
        // ✅ NOUVEAU : Charger le script de debug DPE (à supprimer après diagnostic)
        wp_enqueue_script(
            'dpe-debug-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dpe-debug.js',
            array('jquery', 'dpe-lettre-script'),
            '1.0.0',
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
            <?php if (!empty($atts['title'])): ?>
                <h1><?php echo esc_html($atts['title']); ?></h1>
            <?php endif; ?>

            <!-- ✅ Les styles CSS sont maintenant chargés depuis le fichier externe dpe-style.css -->

            <!-- Information pour les utilisateurs -->
            <div class="dpe-info" style="background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #004085;">
                <p style="margin: 0; font-size: 16px; line-height: 1.5;">
                    <strong>Aide à la prospection Lead DPE</strong><br><br>
                    L'obligation du Diagnostic de Performance Énergétique concerne toute personne désirant mettre en vente un bien immobilier. Facilitez votre prospection et anticipez les ventes à venir en consultant la liste des DPE réalisés sur vos secteurs d'activité.
                </p>
            </div>
            

            
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
                            <option value="Maison" selected>🏠 Maison</option>
                            <option value="Appartement">🏢 Appartement</option>
                            <option value="Immeuble">🏗️ Immeuble</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="keywordSearch">Recherche par adresse :</label>
                        <input type="text" name="keywordSearch" id="keywordSearch" placeholder="Ex: rue de la paix, avenue victor hugo...">
                    </div>
                    <button type="submit" id="search-btn" class="dpe-button">
                        🔍 Rechercher les DPE
                    </button>
                </div>

                <!-- ✅ NOUVEAU : Bouton d'envoi de courriers pour les maisons -->
                <button id="send-letters-btn" type="button" class="dpe-button secondary" disabled
                        data-tooltip="Prospectez directement les propriétaires de maisons en envoyant un courrier"
                        style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; border: none !important;">
                    📬 Créez une campagne d'envoi de courriers (<span id="selected-count">0</span>)
                </button>
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
                            <th style="text-align: center; min-width: 120px;">
                                📬 Envoi courrier<br>
                                <small style="font-size: 11px; color: #666; font-weight: normal;">(Maisons uniquement)</small>
                            </th>
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

        <style>
        /* ✅ NOUVEAU : Styles pour les checkboxes de sélection DPE */
        .send-letter-checkbox {
            width: 18px !important;
            height: 18px !important;
            cursor: pointer !important;
            margin: 0 !important;
            vertical-align: middle !important;
            display: block !important;
            margin: 0 auto !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .send-letter-checkbox:disabled {
            cursor: not-allowed !important;
            opacity: 0.5 !important;
        }

        .send-letter-checkbox:not(:disabled):hover {
            transform: scale(1.1) !important;
            transition: transform 0.2s ease !important;
        }

        /* Style pour la colonne Envoi courrier */
        .dpe-table th:last-child,
        .dpe-table td:last-child {
            text-align: center !important;
            min-width: 120px !important;
            display: table-cell !important;
            visibility: visible !important;
        }

        /* Style pour les cellules avec checkboxes */
        .dpe-table td:last-child {
            background-color: #f8f9fa !important;
            border-left: 2px solid #dee2e6 !important;
            padding: 10px !important;
        }

        /* Animation pour les checkboxes sélectionnées */
        .send-letter-checkbox:checked {
            animation: checkboxPulse 0.3s ease !important;
        }

        @keyframes checkboxPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* Style pour le bouton d'envoi */
        #send-letters-btn {
            transition: all 0.3s ease !important;
        }

        #send-letters-btn:not(:disabled):hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
        }

        #send-letters-btn:disabled {
            opacity: 0.6 !important;
            cursor: not-allowed !important;
        }
        
        /* ✅ NOUVEAU : Styles de débogage pour forcer l'affichage */
        .dpe-table {
            border-collapse: collapse !important;
            width: 100% !important;
        }
        
        .dpe-table th,
        .dpe-table td {
            border: 1px solid #ddd !important;
            padding: 8px !important;
            text-align: left !important;
        }
        
        /* Forcer l'affichage de la dernière colonne */
        .dpe-table tr td:last-child {
            display: table-cell !important;
            visibility: visible !important;
            width: 120px !important;
            min-width: 120px !important;
        }
        </style>

        <!-- ✅ NOUVEAU : POPUP LETTRE DPE -->
        <div id="dpe-letters-popup" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:10000; justify-content:center; align-items:center;">
            <div style="background:#fff; padding:25px; width:700px; max-width:95vw; max-height:95vh; overflow-y:auto; border-radius:12px;">
                <!-- Étape 1 : Liste des DPE sélectionnées -->
                <div class="step" id="dpe-step-1">
                    <h2>📋 DPE sélectionnées</h2>
                    <p style="color: #666; margin-bottom: 20px;">Vérifiez votre sélection avant de continuer</p>
                    <ul id="selected-dpe-list" style="max-height:350px; overflow-y:auto; border:1px solid #ddd; padding:15px; margin-bottom:25px; border-radius:6px; background-color: #f9f9f9; list-style: none;">
                        <!-- Les DPE sélectionnées seront ajoutées ici par JavaScript -->
                    </ul>
                    <div style="text-align: center;">
                        <button id="dpe-to-step-2" class="dpe-button" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; border: none !important; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px;">
                            ✍️ Rédiger le courriel →
                        </button>
                    </div>
                </div>

                <!-- Étape 2 : Contenu dynamique -->
                <div class="step" id="dpe-step-2" style="display:none;">
                    <!-- Le contenu sera généré par JavaScript -->
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
                
                // Si c'est la première recherche, initialiser la page à 1
                if (previousPageUrls.length === 0 && !currentPageUrl) {
                    currentPageNumber = 1;
                }

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
                    
                    // ✅ NOUVEAU : Checkbox pour l'envoi de courrier (maisons uniquement)
                    var letterCell = document.createElement('td');
                    
                    // Créer un conteneur pour la checkbox et le label
                    var checkboxContainer = document.createElement('div');
                    checkboxContainer.style.display = 'flex';
                    checkboxContainer.style.flexDirection = 'column';
                    checkboxContainer.style.alignItems = 'center';
                    checkboxContainer.style.gap = '5px';
                    
                    var letterCheckbox = document.createElement('input');
                    letterCheckbox.type = 'checkbox';
                    letterCheckbox.className = 'send-letter-checkbox';
                    letterCheckbox.setAttribute('data-numero-dpe', result.numero_dpe || '');
                    letterCheckbox.setAttribute('data-type-batiment', result.type_batiment || '');
                    letterCheckbox.setAttribute('data-adresse', result.adresse_ban || result.adresse_brut || '');
                    letterCheckbox.setAttribute('data-commune', result.nom_commune_ban || result.nom_commune_brut || '');
                    letterCheckbox.setAttribute('data-code-postal', result.code_postal_ban || result.code_postal_brut || '');
                    letterCheckbox.setAttribute('data-surface', result.surface_habitable_logement || '');
                    letterCheckbox.setAttribute('data-etiquette-dpe', result.etiquette_dpe || '');
                    letterCheckbox.setAttribute('data-etiquette-ges', result.etiquette_ges || '');
                    letterCheckbox.setAttribute('data-date-dpe', result.date_etablissement_dpe || result.date_reception_dpe || '');
                    
                    // Créer un label pour la checkbox
                    var checkboxLabel = document.createElement('span');
                    checkboxLabel.style.fontSize = '11px';
                    checkboxLabel.style.color = '#666';
                    checkboxLabel.style.textAlign = 'center';
                    checkboxLabel.style.lineHeight = '1.2';
                    
                    // Désactiver si ce n'est pas une maison
                    if (result.type_batiment && result.type_batiment.toLowerCase() !== 'maison') {
                        letterCheckbox.disabled = true;
                        letterCheckbox.title = 'Envoi de courrier disponible uniquement pour les maisons';
                        checkboxLabel.textContent = 'Non disponible';
                        checkboxLabel.style.color = '#999';
                    } else {
                        letterCheckbox.title = 'Sélectionner pour l\'envoi de courrier';
                        checkboxLabel.textContent = 'Sélectionner';
                        checkboxLabel.style.color = '#28a745';
                        checkboxLabel.style.fontWeight = 'bold';
                    }
                    
                    checkboxContainer.appendChild(letterCheckbox);
                    checkboxContainer.appendChild(checkboxLabel);
                    letterCell.appendChild(checkboxContainer);
                    row.appendChild(letterCell);

                    tbody.appendChild(row);
                });

                // Mettre à jour les informations de pagination
                updatePaginationInfo();
                showPaginationControls();
                
                // ✅ NOUVEAU : Log final pour vérifier que toutes les lignes ont été créées
                var finalRows = tbody.querySelectorAll('tr');
                var finalCheckboxes = tbody.querySelectorAll('.send-letter-checkbox');
                
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
                
                // ✅ NOUVEAU : Utiliser le système unique de sélection DPE
                if (typeof window.DPESelectionSystem !== 'undefined') {
                    window.DPESelectionSystem.reinitialize();
                } else {
                    // Fallback pour compatibilité
                    if (typeof window.restoreDPESelections === 'function') {
                        window.restoreDPESelections();
                    }
                    if (typeof window.updateDPESelectionUI === 'function') {
                        window.updateDPESelectionUI();
                    }
                    if (typeof window.reinitializeDPESelection === 'function') {
                        window.reinitializeDPESelection();
                    }
                }
            } else {
                tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px; color: #666;">Aucun résultat trouvé</td></tr>';
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
            // Utiliser le numéro de page actuel directement
            pageInfo.textContent = currentPageNumber + '/' + totalPages;
            
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
    
    /**
     * Shortcode [dpe_campaigns] - Affichage des campagnes DPE
     */
    public function dpe_campaigns_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => '📬 Mes Campagnes DPE',
            'show_empty_message' => 'true'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="dpe-error">Vous devez être connecté pour voir vos campagnes.</div>';
        }
        
        // Récupérer le gestionnaire de campagnes DPE
        $campaign_manager = dpe_campaign_manager();
        if (!$campaign_manager) {
            return '<div class="dpe-error">Erreur : Gestionnaire de campagnes non disponible.</div>';
        }
        
        $current_user_id = get_current_user_id();
        $view_campaign_id = isset($_GET['view']) ? intval($_GET['view']) : null;
        
        // Mode vue détaillée d'une campagne
        if ($view_campaign_id) {
            $campaign_details = $campaign_manager->get_campaign_details($view_campaign_id, $current_user_id);
            
            if (!$campaign_details) {
                return '<div class="dpe-error">Campagne non trouvée ou accès non autorisé.</div>';
            }
            
            // Passer les données au template
            $context = array(
                'campaign_details' => $campaign_details,
                'view_mode' => true,
                'title' => $atts['title']
            );
            
            return $this->render_template('dpe-campaigns', $context);
        }
        
        // Mode liste des campagnes
        $campaigns = $campaign_manager->get_user_campaigns($current_user_id);
        
        $context = array(
            'campaigns' => $campaigns,
            'view_mode' => false,
            'title' => $atts['title'],
            'show_empty_message' => ($atts['show_empty_message'] === 'true')
        );
        
        return $this->render_template('dpe-campaigns', $context);
    }
    
    /**
     * Rendre un template avec contexte
     */
    private function render_template($template_name, $context = array()) {
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'templates/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            return '<div class="dpe-error">Template ' . esc_html($template_name) . ' non trouvé.</div>';
        }
        
        // Extraire les variables du contexte
        extract($context);
        
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    

}

// Initialiser la classe des shortcodes DPE
new DPE_Shortcodes();
?> 