<?php
/**
 * Page principale de l'Annuaire Notarial
 * 
 * @package My_Istymo
 * @subpackage Notaires
 * @version 1.0
 * @author Brio Guiseppe
 */

if (!defined('ABSPATH')) {
    exit; // Empêche l'accès direct au fichier
}

/**
 * Fonction principale pour afficher la page des notaires
 */
function notaires_afficher_panel() {
    // Enqueue des assets
    wp_enqueue_style('notaires-admin-css', plugin_dir_url(__FILE__) . '../assets/css/notaires-admin.css', array(), '1.0');
    wp_enqueue_script('notaires-admin-js', plugin_dir_url(__FILE__) . '../assets/js/notaires-admin.js', array('jquery'), '1.0', true);
    
    // Localiser les variables JavaScript
    wp_localize_script('notaires-admin-js', 'notairesAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_notaires_nonce')
    ));
    
    // Ajouter le nonce dans le head
    add_action('admin_head', function() {
        echo '<meta name="notaires-nonce" content="' . wp_create_nonce('my_istymo_notaires_nonce') . '">';
    });
    
    // Vérifier que l'utilisateur est connecté
    if (!is_user_logged_in()) {
        echo '<div class="wrap"><h1>Accès refusé</h1><p>Vous devez être connecté pour accéder à cette page.</p></div>';
        return;
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Récupérer les codes postaux de l'utilisateur
    $codes_postaux = sci_get_user_postal_codes($user_id);
    
    if (empty($codes_postaux)) {
        echo '<div class="wrap">';
        echo '<h1>🏛️ Annuaire Notarial</h1>';
        echo '<div class="notice notice-warning">';
        echo '<p><strong>Configuration requise :</strong> Veuillez configurer vos codes postaux dans votre profil pour accéder à l\'annuaire notarial.</p>';
        echo '<p><a href="' . admin_url('profile.php') . '" class="button button-primary">Configurer mes codes postaux</a></p>';
        echo '</div>';
        echo '</div>';
        return;
    }
    
    // Récupérer les filtres depuis l'URL
    $filters = [];
    if (!empty($_GET['ville'])) $filters['ville'] = sanitize_text_field($_GET['ville']);
    if (!empty($_GET['langue'])) $filters['langue'] = sanitize_text_field($_GET['langue']);
    if (!empty($_GET['statut'])) $filters['statut'] = sanitize_text_field($_GET['statut']);
    if (!empty($_GET['search'])) $filters['search'] = sanitize_text_field($_GET['search']);
    
    // Pagination
    $page = max(1, intval($_GET['paged'] ?? 1));
    $per_page = 20;
    
    // Récupérer les notaires
    $notaires_manager = Notaires_Manager::get_instance();
    $notaires = $notaires_manager->get_notaires_by_postal_codes($codes_postaux, $filters, $per_page, $page);
    $total_notaires = $notaires_manager->get_notaires_count($codes_postaux, $filters);
    $total_pages = ceil($total_notaires / $per_page);
    
    // Récupérer les options pour les filtres
    $available_cities = $notaires_manager->get_available_cities($codes_postaux);
    $available_languages = $notaires_manager->get_available_languages($codes_postaux);
    
    // Récupérer les favoris de l'utilisateur
    $favoris_handler = Notaires_Favoris_Handler::get_instance();
    $favorites_stats = $favoris_handler->get_favorites_stats($user_id);
    
    ?>
    <div class="wrap notaires-container my-istymo">
        <h1>🏛️ Annuaire Notarial</h1>
        
        <!-- Informations utilisateur -->
        <div class="my-istymo-info-box">
            <h3>📍 Votre zone géographique</h3>
            <p><strong>Codes postaux configurés :</strong> <?php echo implode(', ', $codes_postaux); ?></p>
            <p><strong>Notaires disponibles :</strong> <?php echo $total_notaires; ?></p>
            <p><strong>Mes favoris :</strong> <?php echo $favorites_stats['total_favorites']; ?></p>
        </div>
        
        <!-- Filtres -->
        <div class="my-istymo-filters-section">
            <form method="GET" class="my-istymo-inline-filters">
                <input type="hidden" name="page" value="notaires-panel">
                
                <div class="my-istymo-filter-row">
                    <div class="my-istymo-filter-group">
                        <label for="ville">Ville :</label>
                        <select name="ville" id="ville">
                            <option value="">Toutes les villes</option>
                            <?php foreach ($available_cities as $city): ?>
                                <option value="<?php echo esc_attr($city); ?>" <?php selected($filters['ville'] ?? '', $city); ?>>
                                    <?php echo esc_html($city); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="my-istymo-filter-group">
                        <label for="langue">Langue :</label>
                        <select name="langue" id="langue">
                            <option value="">Toutes les langues</option>
                            <?php foreach ($available_languages as $language): ?>
                                <option value="<?php echo esc_attr($language); ?>" <?php selected($filters['langue'] ?? '', $language); ?>>
                                    <?php echo esc_html($language); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="my-istymo-filter-group">
                        <label for="statut">Statut :</label>
                        <select name="statut" id="statut">
                            <option value="">Tous les statuts</option>
                            <option value="actif" <?php selected($filters['statut'] ?? '', 'actif'); ?>>Actif</option>
                            <option value="inactif" <?php selected($filters['statut'] ?? '', 'inactif'); ?>>Inactif</option>
                            <option value="suspendu" <?php selected($filters['statut'] ?? '', 'suspendu'); ?>>Suspendu</option>
                        </select>
                    </div>
                    
                    <div class="my-istymo-filter-group">
                        <label for="search">Recherche :</label>
                        <input type="text" name="search" id="search" value="<?php echo esc_attr($filters['search'] ?? ''); ?>" placeholder="Nom office, notaire, ville...">
                    </div>
                    
                    <div class="my-istymo-filter-actions">
                        <button type="submit" class="button button-primary">Filtrer</button>
                        <a href="<?php echo admin_url('admin.php?page=notaires-panel'); ?>" class="button">Réinitialiser</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Actions rapides -->
        <div class="my-istymo-actions-section">
            <div class="my-istymo-action-buttons">
                <a href="<?php echo admin_url('admin.php?page=notaires-panel&view=favorites'); ?>" class="button">
                    <span class="dashicons dashicons-star-filled"></span> Mes Favoris (<?php echo $favorites_stats['total_favorites']; ?>)
                </a>
                <button type="button" class="button" onclick="exportFavorites()">
                    <span class="dashicons dashicons-download"></span> Exporter mes favoris
                </button>
                <?php if (current_user_can('manage_options')): ?>
                    <a href="<?php echo admin_url('admin.php?page=notaires-import'); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-upload"></span> Import CSV
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tableau des notaires -->
        <div class="my-istymo-table-section">
            <?php if (empty($notaires)): ?>
                <div class="my-istymo-empty-state">
                    <h3>Aucun notaire trouvé</h3>
                    <p>Il n'y a aucun notaire disponible dans votre zone géographique avec les filtres appliqués.</p>
                    <a href="<?php echo admin_url('admin.php?page=notaires-panel'); ?>" class="button button-primary">Voir tous les notaires</a>
                </div>
            <?php else: ?>
                <div class="my-istymo-modern-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="5%">Favori</th>
                                <th width="20%">Office</th>
                                <th width="15%">Notaire</th>
                                <th width="15%">Adresse</th>
                                <th width="10%">Code Postal</th>
                                <th width="10%">Ville</th>
                                <th width="10%">Téléphone</th>
                                <th width="10%">Email</th>
                                <th width="5%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notaires as $notaire): ?>
                                <tr data-notaire-id="<?php echo $notaire->id; ?>">
                                    <td class="favorite-cell">
                                        <button type="button" class="favorite-toggle <?php echo $notaire->is_favorite ? 'favorited' : ''; ?>" 
                                                data-notaire-id="<?php echo $notaire->id; ?>"
                                                title="<?php echo $notaire->is_favorite ? 'Supprimer des favoris' : 'Ajouter aux favoris'; ?>">
                                            <span class="dashicons dashicons-star-<?php echo $notaire->is_favorite ? 'filled' : 'empty'; ?>"></span>
                                        </button>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html($notaire->nom_office); ?></strong>
                                        <?php if ($notaire->site_internet): ?>
                                            <br><a href="<?php echo esc_url($notaire->site_internet); ?>" target="_blank" class="website-link">
                                                <span class="dashicons dashicons-external"></span> Site web
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($notaire->nom_notaire); ?></td>
                                    <td><?php echo esc_html($notaire->adresse); ?></td>
                                    <td><?php echo esc_html($notaire->code_postal); ?></td>
                                    <td><?php echo esc_html($notaire->ville); ?></td>
                                    <td>
                                        <?php if ($notaire->telephone_office): ?>
                                            <a href="tel:<?php echo esc_attr($notaire->telephone_office); ?>" class="phone-link">
                                                <?php echo esc_html($notaire->telephone_office); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($notaire->email_office): ?>
                                            <a href="mailto:<?php echo esc_attr($notaire->email_office); ?>" class="email-link">
                                                <?php echo esc_html($notaire->email_office); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small view-details" 
                                                data-notaire-id="<?php echo $notaire->id; ?>"
                                                title="Voir les détails">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="my-istymo-pagination">
                        <?php
                        $base_url = admin_url('admin.php?page=notaires-panel');
                        $query_params = $_GET;
                        unset($query_params['paged']);
                        
                        if (!empty($query_params)) {
                            $base_url .= '&' . http_build_query($query_params);
                        }
                        
                        // Page précédente
                        if ($page > 1): ?>
                            <a href="<?php echo $base_url . '&paged=' . ($page - 1); ?>" class="button">
                                <span class="dashicons dashicons-arrow-left-alt2"></span> Précédent
                            </a>
                        <?php endif; ?>
                        
                        <!-- Numéros de pages -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="<?php echo $base_url . '&paged=' . $i; ?>" 
                               class="button <?php echo $i === $page ? 'button-primary' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Page suivante -->
                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo $base_url . '&paged=' . ($page + 1); ?>" class="button">
                                Suivant <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </a>
                        <?php endif; ?>
                        
                        <div class="pagination-info">
                            Page <?php echo $page; ?> sur <?php echo $total_pages; ?> 
                            (<?php echo $total_notaires; ?> notaires au total)
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal pour les détails du notaire -->
    <div id="notaire-detail-modal" class="my-istymo-modal" style="display: none;">
        <div class="my-istymo-modal-content">
            <div class="my-istymo-modal-header">
                <h2>Détails du notaire</h2>
                <button type="button" class="my-istymo-modal-close">&times;</button>
            </div>
            <div class="my-istymo-modal-body" id="notaire-detail-content">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>
    
    <!-- Scripts et styles -->
    <script>
    jQuery(document).ready(function($) {
        // Variables globales pour AJAX
        var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var nonce = '<?php echo wp_create_nonce('my_istymo_notaires_nonce'); ?>';
        
        // Gestionnaire pour les favoris
        $('.favorite-toggle').on('click', function() {
            var button = $(this);
            var notaireId = button.data('notaire-id');
            var isFavorited = button.hasClass('favorited');
            
            // Désactiver le bouton pendant la requête
            button.prop('disabled', true);
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'toggle_notaire_favorite',
                    notaire_id: notaireId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.is_favorite) {
                            button.addClass('favorited');
                            button.find('.dashicons').removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
                            button.attr('title', 'Supprimer des favoris');
                        } else {
                            button.removeClass('favorited');
                            button.find('.dashicons').removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
                            button.attr('title', 'Ajouter aux favoris');
                        }
                        
                        // Mettre à jour le compteur de favoris
                        updateFavoritesCount();
                    } else {
                        alert('Erreur : ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Erreur de communication avec le serveur');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        });
        
        // Gestionnaire pour voir les détails
        $('.view-details').on('click', function() {
            var notaireId = $(this).data('notaire-id');
            showNotaireDetails(notaireId);
        });
        
        // Fermer le modal
        $('.my-istymo-modal-close, .my-istymo-modal').on('click', function(e) {
            if (e.target === this) {
                $('#notaire-detail-modal').hide();
            }
        });
        
        // Fonction pour afficher les détails d'un notaire
        function showNotaireDetails(notaireId) {
            $('#notaire-detail-content').html('<div class="loading"><span class="dashicons dashicons-update"></span> Chargement...</div>');
            $('#notaire-detail-modal').show();
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_notaire_details',
                    notaire_id: notaireId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#notaire-detail-content').html(response.data.html);
                    } else {
                        $('#notaire-detail-content').html('<p>Erreur lors du chargement des détails</p>');
                    }
                },
                error: function() {
                    $('#notaire-detail-content').html('<p>Erreur de communication avec le serveur</p>');
                }
            });
        }
        
        // Fonction pour mettre à jour le compteur de favoris
        function updateFavoritesCount() {
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_favorites_count',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.favorites-count').text(response.data.count);
                    }
                }
            });
        }
        
        // Fonction pour exporter les favoris
        window.exportFavorites = function() {
            if (!confirm('Voulez-vous exporter vos favoris au format CSV ?')) {
                return;
            }
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'export_notaires_favorites',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Créer un lien de téléchargement
                        var blob = new Blob([response.data.csv_content], { type: 'text/csv' });
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        window.URL.revokeObjectURL(url);
                    } else {
                        alert('Erreur lors de l\'export : ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Erreur de communication avec le serveur');
                }
            });
        };
    });
    </script>
    
    <style>
    .notaires-container {
        max-width: 1200px;
    }
    
    .my-istymo-info-box {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 15px;
        margin: 20px 0;
    }
    
    .my-istymo-filters-section {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .my-istymo-filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: end;
    }
    
    .my-istymo-filter-group {
        display: flex;
        flex-direction: column;
        min-width: 150px;
    }
    
    .my-istymo-filter-group label {
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .my-istymo-filter-actions {
        display: flex;
        gap: 10px;
    }
    
    .my-istymo-actions-section {
        margin: 20px 0;
    }
    
    .my-istymo-action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .favorite-toggle {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        border-radius: 3px;
    }
    
    .favorite-toggle:hover {
        background: #f0f0f0;
    }
    
    .favorite-toggle.favorited .dashicons {
        color: #ffb900;
    }
    
    .phone-link, .email-link, .website-link {
        text-decoration: none;
    }
    
    .phone-link:hover, .email-link:hover, .website-link:hover {
        text-decoration: underline;
    }
    
    .my-istymo-pagination {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
        flex-wrap: wrap;
    }
    
    .pagination-info {
        margin-left: auto;
        font-style: italic;
        color: #666;
    }
    
    .my-istymo-empty-state {
        text-align: center;
        padding: 40px;
        background: #f9f9f9;
        border-radius: 4px;
    }
    
    .my-istymo-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .my-istymo-modal-content {
        background: #fff;
        border-radius: 4px;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
        width: 90%;
    }
    
    .my-istymo-modal-header {
        padding: 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .my-istymo-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
    }
    
    .my-istymo-modal-body {
        padding: 20px;
    }
    
    .loading {
        text-align: center;
        padding: 20px;
    }
    
    .loading .dashicons {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
        .my-istymo-filter-row {
            flex-direction: column;
        }
        
        .my-istymo-filter-group {
            min-width: 100%;
        }
        
        .my-istymo-action-buttons {
            flex-direction: column;
        }
        
        .my-istymo-pagination {
            justify-content: center;
        }
        
        .pagination-info {
            margin-left: 0;
            text-align: center;
            width: 100%;
        }
    }
    </style>
    <?php
}

/**
 * Fonction pour afficher la page d'import CSV
 */
function notaires_import_page() {
    // Enqueue des assets
    wp_enqueue_style('notaires-admin-css', plugin_dir_url(__FILE__) . '../assets/css/notaires-admin.css', array(), '1.0');
    wp_enqueue_script('notaires-admin-js', plugin_dir_url(__FILE__) . '../assets/js/notaires-admin.js', array('jquery'), '1.0', true);
    
    // Localiser les variables JavaScript
    wp_localize_script('notaires-admin-js', 'notairesAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_notaires_nonce')
    ));
    
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        echo '<div class="wrap"><h1>Accès refusé</h1><p>Vous n\'avez pas les permissions nécessaires pour accéder à cette page.</p></div>';
        return;
    }
    
    // Traitement de l'upload si formulaire soumis
    if (isset($_POST['import_csv']) && wp_verify_nonce($_POST['notaires_import_nonce'], 'notaires_import_action')) {
        $import_result = handle_csv_upload();
        display_import_result($import_result);
    }
    
    ?>
    <div class="wrap notaires-import-container">
        <h1>📥 Import CSV - Annuaire Notarial</h1>
        
        <div class="my-istymo-info-box">
            <h3>📋 Instructions d'import</h3>
            <ul>
                <li><strong>Format :</strong> Fichier CSV avec encodage UTF-8</li>
                <li><strong>Colonnes requises :</strong> nom_office, telephone_office, langues_parlees, site_internet, email_office, adresse, code_postal, ville, nom_notaire, statut_notaire, url_office, page_source, date_extraction</li>
                <li><strong>Remplacement :</strong> L'import remplace complètement les données existantes</li>
                <li><strong>Limite :</strong> Taille maximale de 10 MB</li>
            </ul>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="my-istymo-import-form">
            <?php wp_nonce_field('notaires_import_action', 'notaires_import_nonce'); ?>
            
            <div class="my-istymo-upload-section">
                <h3>Sélectionner le fichier CSV</h3>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                <p class="description">Sélectionnez le fichier CSV contenant les données des notaires</p>
            </div>
            
            <div class="my-istymo-import-options">
                <h3>Options d'import</h3>
                <label>
                    <input type="checkbox" name="preview_only" value="1" checked>
                    Mode prévisualisation (recommandé pour le premier import)
                </label>
                <p class="description">En mode prévisualisation, les données ne seront pas importées en base</p>
            </div>
            
            <div class="my-istymo-import-actions">
                <button type="submit" name="import_csv" class="button button-primary button-large">
                    <span class="dashicons dashicons-upload"></span> Importer le fichier CSV
                </button>
            </div>
        </form>
        
        <!-- Historique des imports -->
        <div class="my-istymo-import-history">
            <h3>📊 Historique des imports</h3>
            <?php display_import_history(); ?>
        </div>
    </div>
    
    <style>
    .notaires-import-container {
        max-width: 800px;
    }
    
    .my-istymo-import-form {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .my-istymo-upload-section,
    .my-istymo-import-options,
    .my-istymo-import-actions {
        margin: 20px 0;
    }
    
    .my-istymo-upload-section input[type="file"] {
        width: 100%;
        padding: 10px;
        border: 2px dashed #ddd;
        border-radius: 4px;
        background: #f9f9f9;
    }
    
    .my-istymo-import-actions {
        text-align: center;
        padding: 20px 0;
        border-top: 1px solid #ddd;
    }
    
    .my-istymo-import-history {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 20px;
        margin: 20px 0;
    }
    </style>
    <?php
}

/**
 * Gère l'upload et le traitement du fichier CSV
 */
function handle_csv_upload() {
    $result = [
        'success' => false,
        'message' => '',
        'data' => null
    ];
    
    // Vérifier qu'un fichier a été uploadé
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $result['message'] = 'Aucun fichier uploadé ou erreur lors de l\'upload';
        return $result;
    }
    
    $file = $_FILES['csv_file'];
    
    // Vérifier le type de fichier
    if ($file['type'] !== 'text/csv' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
        $result['message'] = 'Le fichier doit être au format CSV';
        return $result;
    }
    
    // Vérifier la taille du fichier (10 MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        $result['message'] = 'Le fichier est trop volumineux (maximum 10 MB)';
        return $result;
    }
    
    // Déplacer le fichier vers un dossier temporaire
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/my-istymo-temp/';
    
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }
    
    $temp_file = $temp_dir . 'notaires_import_' . time() . '.csv';
    
    if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
        $result['message'] = 'Erreur lors du déplacement du fichier';
        return $result;
    }
    
    // Traiter le fichier CSV
    $import_handler = Notaires_Import_Handler::get_instance();
    
    // Mode prévisualisation ou import réel
    $preview_only = isset($_POST['preview_only']);
    
    if ($preview_only) {
        // Mode prévisualisation
        $validation = $import_handler->validate_csv_structure($temp_file);
        $parsing = $import_handler->parse_csv_data($temp_file, 10); // Limiter à 10 lignes pour la prévisualisation
        
        $result['success'] = true;
        $result['message'] = 'Prévisualisation réussie';
        $result['data'] = [
            'validation' => $validation,
            'parsing' => $parsing,
            'preview_only' => true
        ];
    } else {
        // Import réel
        $import_result = $import_handler->process_csv_file($temp_file);
        
        $result['success'] = $import_result['success'];
        $result['message'] = $import_result['success'] ? 'Import réussi' : 'Échec de l\'import';
        $result['data'] = $import_result;
    }
    
    // Supprimer le fichier temporaire
    unlink($temp_file);
    
    return $result;
}

/**
 * Affiche le résultat de l'import
 */
function display_import_result($result) {
    if ($result['success']) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>✅ ' . esc_html($result['message']) . '</strong></p>';
        
        if ($result['data']['preview_only']) {
            echo '<p>Mode prévisualisation activé - Aucune donnée n\'a été importée en base.</p>';
        }
        
        echo '</div>';
        
        // Afficher les détails
        if ($result['data']['validation']) {
            echo '<div class="notice notice-info">';
            echo '<h4>📋 Validation de la structure :</h4>';
            echo '<ul>';
            echo '<li>Colonnes trouvées : ' . count($result['data']['validation']['columns_found']) . '</li>';
            echo '<li>Colonnes manquantes : ' . count($result['data']['validation']['columns_missing']) . '</li>';
            echo '<li>Colonnes supplémentaires : ' . count($result['data']['validation']['columns_extra']) . '</li>';
            echo '</ul>';
            echo '</div>';
        }
        
        if ($result['data']['parsing']) {
            echo '<div class="notice notice-info">';
            echo '<h4>📊 Parsing des données :</h4>';
            echo '<ul>';
            echo '<li>Lignes totales : ' . $result['data']['parsing']['total_rows'] . '</li>';
            echo '<li>Lignes valides : ' . $result['data']['parsing']['valid_rows'] . '</li>';
            echo '<li>Lignes invalides : ' . $result['data']['parsing']['invalid_rows'] . '</li>';
            echo '</ul>';
            echo '</div>';
        }
        
        if (!$result['data']['preview_only'] && $result['data']['import']) {
            echo '<div class="notice notice-success">';
            echo '<h4>💾 Import en base :</h4>';
            echo '<p>Notaires importés : ' . $result['data']['import']['imported_count'] . '</p>';
            echo '</div>';
        }
        
    } else {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>❌ ' . esc_html($result['message']) . '</strong></p>';
        echo '</div>';
    }
}

/**
 * Affiche l'historique des imports
 */
function display_import_history() {
    // Cette fonction pourrait récupérer l'historique depuis la base de données
    // Pour l'instant, on affiche un message simple
    echo '<p>Aucun import précédent enregistré.</p>';
    echo '<p>L\'historique des imports sera disponible dans une prochaine version.</p>';
}
