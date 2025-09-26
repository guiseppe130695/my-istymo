<?php
if (!defined('ABSPATH')) exit;

/**
 * Page d'administration principale pour la gestion des leads
 * Interface de gestion avec tableau, filtres et actions en lot
 * Peut être utilisée en mode admin ou en mode shortcode
 */
function unified_leads_admin_page($context = array()) {
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'));
    }
    
    // Charger Font Awesome pour les icônes
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
    
    // Valeurs par défaut pour le contexte
    $default_context = array(
        'title' => '',
        'show_filters' => true,
        'show_actions' => true,
        'per_page' => 20,
        'is_shortcode' => false
    );
    
    $context = wp_parse_args($context, $default_context);
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $status_manager = Lead_Status_Manager::get_instance();
    
    // Récupérer les filtres
    $filters = array();
    
    // Appliquer les filtres par défaut du shortcode si disponibles
    if (!empty($context['default_filters'])) {
        $filters = array_merge($filters, $context['default_filters']);
    }
    
    // Récupérer les filtres depuis l'URL (priorité sur les filtres par défaut)
    if (!empty($_GET['lead_type'])) $filters['lead_type'] = sanitize_text_field($_GET['lead_type']);
    if (!empty($_GET['status'])) $filters['status'] = sanitize_text_field($_GET['status']);
    if (!empty($_GET['priorite'])) $filters['priorite'] = sanitize_text_field($_GET['priorite']);
    if (!empty($_GET['date_from'])) $filters['date_from'] = sanitize_text_field($_GET['date_from']);
    if (!empty($_GET['date_to'])) $filters['date_to'] = sanitize_text_field($_GET['date_to']);
    
    // Récupérer les leads avec pagination
    $page = max(1, intval($_GET['paged'] ?? 1));
    $per_page = $context['per_page'];
    $offset = ($page - 1) * $per_page;
    
    $leads = $leads_manager->get_leads(null, $filters);
    $total_leads = count($leads);
    $leads = array_slice($leads, $offset, $per_page);
    $total_pages = ceil($total_leads / $per_page);
    
    // Récupérer les options pour les filtres
    $status_options = $status_manager->get_status_options();
    $priority_options = $status_manager->get_priority_options();
    
    ?>
    <div class="wrap unified-leads-container my-istymo">
        <?php if (!$context['is_shortcode']): ?>
        <div class="notice notice-info">
            <p><strong>Interface de Gestion</strong> - Gérez vos leads avec filtres, actions en lot et suivi des statuts.</p>
        </div>
        <?php endif; ?>
        
        <!-- Tableau des leads moderne -->
        <div class="my-istymo-leads-container">
            <!-- En-tête avec contrôles intégrés -->
            <div class="my-istymo-table-header">
                <div class="my-istymo-header-left">
                    
                    <?php if ($context['show_filters']): ?>
                    <!-- Filtres intégrés -->
                    <form method="get" class="my-istymo-inline-filters" style="display: flex; align-items: center; gap: 12px;" id="<?php echo $context['is_shortcode'] ? 'shortcode-filters-' . $context['shortcode_id'] : 'admin-filters'; ?>">
                        <?php if (!$context['is_shortcode']): ?>
                        <input type="hidden" name="page" value="unified-leads">
                        <?php else: ?>
                        <input type="hidden" name="shortcode_id" value="<?php echo $context['shortcode_id']; ?>">
                        <?php endif; ?>
                
                        <!-- Filtre par type -->
                        <div class="my-istymo-filter-group">
                            <select name="lead_type" class="my-istymo-filter-select">
                            <option value="">Tous les types</option>
                            <?php 
                            $current_lead_type = $_GET['lead_type'] ?? $filters['lead_type'] ?? '';
                            ?>
                            <option value="sci" <?php selected($current_lead_type, 'sci'); ?>>SCI</option>
                            <option value="dpe" <?php selected($current_lead_type, 'dpe'); ?>>DPE</option>
                        </select>
                    </div>
                    
                        <!-- Filtre par statut -->
                        <div class="my-istymo-filter-group">
                            <select name="status" class="my-istymo-filter-select">
                            <option value="">Tous les statuts</option>
                            <?php 
                            $current_status = $_GET['status'] ?? $filters['status'] ?? '';
                            // Générer les options de statut avec la valeur sélectionnée
                            $status_options_array = array(
                                'nouveau' => 'Nouveau',
                                'en_cours' => 'En cours',
                                'qualifie' => 'Qualifié',
                                'proposition' => 'Proposition',
                                'negociation' => 'Négociation',
                                'gagne' => 'Gagné',
                                'perdu' => 'Perdu'
                            );
                            foreach ($status_options_array as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '"' . selected($current_status, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                        <!-- Filtre par priorité -->
                        <div class="my-istymo-filter-group">
                            <select name="priorite" class="my-istymo-filter-select">
                            <option value="">Toutes les priorités</option>
                            <?php 
                            $current_priorite = $_GET['priorite'] ?? $filters['priorite'] ?? '';
                            // Générer les options de priorité avec la valeur sélectionnée
                            $priority_options_array = array(
                                'basse' => 'Basse',
                                'normale' => 'Normale',
                                'haute' => 'Haute'
                            );
                            foreach ($priority_options_array as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '"' . selected($current_priorite, $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                        <!-- Boutons d'action des filtres -->
                        <div class="my-istymo-filter-actions">
                            <button type="submit" class="my-istymo-btn my-istymo-btn-primary">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                            <button type="button" class="my-istymo-btn my-istymo-btn-secondary" onclick="testAjaxConnection()">
                                <i class="fas fa-wrench"></i> Test AJAX
                            </button>
                            <button type="button" class="my-istymo-btn my-istymo-btn-secondary" onclick="debugAjaxActions()">
                                <i class="fas fa-bug"></i> Debug
                            </button>
                            <?php if (!empty($_GET['lead_type']) || !empty($_GET['status']) || !empty($_GET['priorite']) || !empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($filters['lead_type']) || !empty($filters['status']) || !empty($filters['priorite'])): ?>
                            <?php if ($context['is_shortcode']): ?>
                            <a href="<?php echo remove_query_arg(array('lead_type', 'status', 'priorite', 'date_from', 'date_to', 'paged')); ?>" class="my-istymo-filter-reset-btn">
                                <i class="fas fa-times"></i> Réinitialiser
                            </a>
                            <?php else: ?>
                            <a href="?page=unified-leads" class="my-istymo-filter-reset-btn">
                                <i class="fas fa-times"></i> Réinitialiser
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
                

        </div>
            
            <?php if (!empty($leads)): ?>
                <div class="my-istymo-modern-table">
                    <table class="my-istymo-leads-table">
                        <thead>
                            <tr>
                                <th class="my-istymo-th-checkbox">
                                    <input type="checkbox" class="my-istymo-select-all">
                                </th>
                                <th class="my-istymo-th-company">
                                    <i class="fas fa-building"></i> Entreprise
                                </th>
                                <th class="my-istymo-th-category">
                                    <i class="fas fa-tags"></i> Catégorie
                                </th>
                                <th class="my-istymo-th-priority">
                                    <i class="fas fa-flag"></i> Priorité
                                </th>
                                <th class="my-istymo-th-location">
                                    <i class="fas fa-map-marker-alt"></i> Localisation
                                </th>
                                <th class="my-istymo-th-status">
                                    <i class="fas fa-info-circle"></i> Statut
                                </th>
                                <th class="my-istymo-th-actions"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $lead): 
                                // Extraire les données selon le type de lead
                                $company_name = '';
                                $domain = '';
                                $location = '';
                                $category = '';
                                
                                        if (!empty($lead->data_originale)) {
                                            if ($lead->lead_type === 'dpe') {
                                        $company_name = $lead->data_originale['adresse_ban'] ?? 'Bien immobilier';
                                        $domain = 'immobilier.com';
                                        $ville = $lead->data_originale['nom_commune_ban'] ?? '';
                                        $code_postal = $lead->data_originale['code_postal_ban'] ?? '';
                                        $location = $ville . ($code_postal ? ' (' . $code_postal . ')' : '');
                                        $category = 'Lead DPE';
                                            } elseif ($lead->lead_type === 'sci') {
                                        $company_name = $lead->data_originale['denomination'] ?? $lead->data_originale['raisonSociale'] ?? 'SCI';
                                        $domain = 'entreprise.com';
                                        $ville = $lead->data_originale['ville'] ?? '';
                                        $code_postal = $lead->data_originale['code_postal'] ?? '';
                                        $location = $ville . ($code_postal ? ' (' . $code_postal . ')' : '');
                                        $category = 'Lead SCI';
                                    }
                                }
                            ?>
                                <tr class="my-istymo-table-row">
                                    <td class="my-istymo-td-checkbox">
                                        <input type="checkbox" class="my-istymo-lead-checkbox" value="<?php echo $lead->id; ?>">
                                    </td>
                                    <td class="my-istymo-td-company">
                                        <div class="my-istymo-company-cell">
                                            <div class="my-istymo-company-icon">
                                                <?php if ($lead->lead_type === 'dpe'): ?>
                                                    <span class="my-istymo-icon my-istymo-icon-house">🏠</span>
                                                <?php else: ?>
                                                    <span class="my-istymo-icon my-istymo-icon-building">🏢</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="my-istymo-company-info">
                                                <div class="my-istymo-company-name"><?php echo esc_html($company_name ?: 'Lead #' . $lead->id); ?></div>
                                                <div class="my-istymo-company-id">ID: <?php echo esc_html($lead->original_id); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="my-istymo-td-category">
                                        <div class="my-istymo-category"><?php echo esc_html($category); ?></div>
                                    </td>
                                    <td class="my-istymo-td-priority">
                                        <?php 
                                        // Convertir les priorités en badges modernes
                                        $priority_class = '';
                                        $priority_text = '';
                                        switch($lead->priorite) {
                                            case 'haute':
                                                $priority_class = 'high';
                                                $priority_text = 'Haute';
                                                break;
                                            case 'normale':
                                                $priority_class = 'normal';
                                                $priority_text = 'Normale';
                                                break;
                                            case 'basse':
                                                $priority_class = 'low';
                                                $priority_text = 'Basse';
                                                break;
                                            default:
                                                $priority_class = 'normal';
                                                $priority_text = 'Normale';
                                        }
                                        ?>
                                        <span class="my-istymo-priority-badge my-istymo-priority-<?php echo $priority_class; ?>">
                                            <span class="my-istymo-priority-dot"></span>
                                            <?php echo $priority_text; ?>
                                        </span>
                                    </td>
                                    <td class="my-istymo-td-location">
                                        <div class="my-istymo-location"><?php echo esc_html($location ?: '—'); ?></div>
                                    </td>
                                    <td class="my-istymo-td-status">
                                        <?php 
                                        // Convertir les statuts en badges modernes
                                        $status_class = '';
                                        $status_text = '';
                                        switch($lead->status) {
                                            case 'nouveau':
                                                $status_class = 'pending';
                                                $status_text = 'Nouveau';
                                                break;
                                            case 'en_cours':
                                                $status_class = 'progress';
                                                $status_text = 'En cours';
                                                break;
                                            case 'qualifie':
                                                $status_class = 'completed';
                                                $status_text = 'Qualifié';
                                                break;
                                            case 'proposition':
                                                $status_class = 'warning';
                                                $status_text = 'Proposition';
                                                break;
                                            case 'negociation':
                                                $status_class = 'info';
                                                $status_text = 'Négociation';
                                                break;
                                            case 'gagne':
                                                $status_class = 'success';
                                                $status_text = 'Gagné';
                                                break;
                                            case 'perdu':
                                                $status_class = 'danger';
                                                $status_text = 'Perdu';
                                                break;
                                            case 'termine':
                                                $status_class = 'completed';
                                                $status_text = 'Terminé';
                                                break;
                                            default:
                                                $status_class = 'pending';
                                                $status_text = ucfirst($lead->status);
                                        }
                                        ?>
                                        <span class="my-istymo-status-badge my-istymo-status-<?php echo $status_class; ?>">
                                            <span class="my-istymo-status-dot"></span>
                                            <?php echo $status_text; ?>
                                            </span>
                                    </td>
                                    <td class="my-istymo-td-actions">
                                        <div class="my-istymo-actions-buttons">
                                            <button class="my-istymo-action-btn view-lead" data-lead-id="<?php echo $lead->id; ?>" onclick="openLeadDetailModal(<?php echo $lead->id; ?>); return false;" title="Voir les détails">
                                                <i class="fas fa-eye"></i> Voir
                                            </button>
                                            <button class="my-istymo-action-btn delete-lead" data-lead-id="<?php echo $lead->id; ?>" onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce lead ?')) { deleteLead(<?php echo $lead->id; ?>); } return false;" title="Supprimer">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo $total_leads; ?> éléments</span>
                        
                        <?php
                        if ($context['is_shortcode']) {
                            // En mode shortcode, utiliser l'URL actuelle
                            $current_url = remove_query_arg('paged', $_SERVER['REQUEST_URI']);
                            $pagination_args = array(
                                'base' => add_query_arg('paged', '%#%', $current_url),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $page
                            );
                        } else {
                            // En mode admin, utiliser l'URL de la page admin
                            $pagination_args = array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $total_pages,
                                'current' => $page
                            );
                        }
                        
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-leads">
                    <p>Aucun lead trouvé avec les critères actuels.</p>
                    <?php if (!$context['is_shortcode']): ?>
                    <a href="?page=unified-leads" class="button button-primary">Voir tous les leads</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal d'édition des leads -->
    <div id="edit-lead-modal" class="my-istymo-modal my-istymo-hidden">
        <div class="my-istymo-modal-content">
            <div class="my-istymo-modal-header">
                <h3>Modifier le Lead</h3>
                <button type="button" class="my-istymo-modal-close" onclick="closeEditLeadModal()">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            
            <form id="edit-lead-form" class="my-istymo-form">
                <input type="hidden" id="edit-lead-id" name="lead_id">
                
                <div class="my-istymo-form-group">
                    <label for="edit-lead-type">Type de Lead</label>
                    <select id="edit-lead-type" name="lead_type" class="my-istymo-select" required>
                        <option value="sci">SCI</option>
                        <option value="dpe">DPE</option>
                    </select>
                </div>
                
                <div class="my-istymo-form-group">
                    <label for="edit-lead-status">Statut</label>
                    <select id="edit-lead-status" name="status" class="my-istymo-select" required>
                        <?php foreach ($status_options as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="my-istymo-form-group">
                    <label for="edit-lead-priority">Priorité</label>
                    <select id="edit-lead-priority" name="priorite" class="my-istymo-select" required>
                        <?php foreach ($priority_options as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="my-istymo-form-group">
                    <label for="edit-lead-notes">Notes</label>
                    <textarea id="edit-lead-notes" name="notes" class="my-istymo-textarea" rows="4" placeholder="Ajoutez des notes sur ce lead..."></textarea>
                </div>
                
                <div class="my-istymo-form-actions">
                    <button type="button" class="my-istymo-btn my-istymo-btn-secondary" onclick="closeEditLeadModal()">
                        Annuler
                    </button>
                    <button type="submit" class="my-istymo-btn my-istymo-btn-primary">
                        Modifier le Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
    // Inclure le template du modal Lead Detail
    include_once plugin_dir_path(__FILE__) . 'lead-detail-modal.php';
    ?>
    
    <?php
    // Charger le CSS et JS seulement si pas déjà chargé (pour éviter les doublons)
    if (!wp_style_is('unified-leads-css', 'enqueued')) {
        wp_enqueue_style('unified-leads-css', plugin_dir_url(__FILE__) . '../assets/css/unified-leads.css', array(), '1.0.0');
    }
    if (!wp_style_is('lead-edit-modal-css', 'enqueued')) {
        wp_enqueue_style('lead-edit-modal-css', plugin_dir_url(__FILE__) . '../assets/css/lead-edit-modal.css', array(), '1.0.0');
    }
    if (!wp_script_is('unified-leads-admin', 'enqueued')) {
        wp_enqueue_script('unified-leads-admin', plugin_dir_url(__FILE__) . '../assets/js/unified-leads-admin.js', array('jquery'), '1.0.0', true);
    }
    
    // ✅ PHASE 3 : Charger les scripts pour les actions et workflow
    if (!wp_script_is('lead-actions', 'enqueued')) {
        wp_enqueue_script('lead-actions', plugin_dir_url(__FILE__) . '../assets/js/lead-actions.js', array('jquery', 'jquery-ui-tooltip'), '1.0.0', true);
    }
    if (!wp_script_is('lead-workflow', 'enqueued')) {
        wp_enqueue_script('lead-workflow', plugin_dir_url(__FILE__) . '../assets/js/lead-workflow.js', array('jquery'), '1.0.0', true);
    }
    
    // Localiser les scripts seulement si pas déjà fait
    if (!wp_script_is('unified-leads-admin', 'localized')) {
        wp_localize_script('unified-leads-admin', 'unifiedLeadsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_istymo_nonce')
        ));
    }
    
    // ✅ PHASE 3 : Variables pour les actions et workflow
    if (!wp_script_is('lead-actions', 'localized')) {
        wp_localize_script('lead-actions', 'leadActionsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_istymo_nonce')
        ));
    }
    
    if (!wp_script_is('lead-workflow', 'localized')) {
        wp_localize_script('lead-workflow', 'leadWorkflowAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_istymo_nonce')
        ));
    }
    
    // Script pour les filtres AJAX (admin et shortcode)
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Variables AJAX - utiliser les variables globales si disponibles, sinon fallback
        var ajaxUrl = (typeof unifiedLeadsAjax !== 'undefined' && unifiedLeadsAjax.ajaxurl) ? unifiedLeadsAjax.ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';
        var nonce = (typeof unifiedLeadsAjax !== 'undefined' && unifiedLeadsAjax.nonce) ? unifiedLeadsAjax.nonce : '<?php echo wp_create_nonce('my_istymo_nonce'); ?>';
        var isShortcode = <?php echo $context['is_shortcode'] ? 'true' : 'false'; ?>;
        var currentPage = <?php echo max(1, intval($_GET['paged'] ?? 1)); ?>;
        
        console.log('=== CONFIGURATION AJAX FILTRES ===');
        console.log('AJAX URL:', ajaxUrl);
        console.log('Nonce:', nonce);
        console.log('unifiedLeadsAjax disponible:', typeof unifiedLeadsAjax !== 'undefined');
        
        // Fonction pour filtrer les leads via AJAX
        function filterLeads(page = 1) {
            console.log('=== FILTRAGE LEADS ===');
            console.log('Page:', page);
            console.log('AJAX URL:', ajaxUrl);
            console.log('Nonce:', nonce);
            
            var formData = {
                action: 'filter_unified_leads',
                nonce: nonce,
                paged: page
            };
            
            // Récupérer les valeurs des filtres
            var leadType = $('select[name="lead_type"]').val();
            var status = $('select[name="status"]').val();
            var priorite = $('select[name="priorite"]').val();
            var dateFrom = $('input[name="date_from"]').val();
            var dateTo = $('input[name="date_to"]').val();
            
            console.log('Filtres:', {leadType, status, priorite, dateFrom, dateTo});
            
            if (leadType) formData.lead_type = leadType;
            if (status) formData.status = status;
            if (priorite) formData.priorite = priorite;
            if (dateFrom) formData.date_from = dateFrom;
            if (dateTo) formData.date_to = dateTo;
            
            // Afficher un indicateur de chargement
            $('.my-istymo-modern-table').html('<div class="my-istymo-loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>');
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                timeout: 10000, // Timeout de 10 secondes
                success: function(response) {
                    console.log('Réponse AJAX reçue:', response);
                    if (response.success) {
                        // Mettre à jour le tableau
                        $('.my-istymo-modern-table').html(response.data.html);
                        
                        // Ne pas mettre à jour l'URL pour garder une URL propre
                        // Les filtres sont gérés uniquement en mémoire via AJAX
                        
                        // Réinitialiser les gestionnaires d'événements
                        initTableEventHandlers();
                        
                        console.log('✅ Filtres appliqués avec succès');
                    } else {
                        console.error('❌ Erreur lors du filtrage:', response.data);
                        $('.my-istymo-modern-table').html('<div class="my-istymo-error"><i class="fas fa-exclamation-triangle"></i> Erreur lors du filtrage des leads: ' + (response.data || 'Erreur inconnue') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Erreur AJAX:', {xhr: xhr, status: status, error: error});
                    console.error('Response Text:', xhr.responseText);
                    console.error('Status Code:', xhr.status);
                    
                    var errorMessage = 'Erreur de connexion';
                    if (xhr.status === 0) {
                        errorMessage = 'Erreur de connexion - Vérifiez votre connexion internet';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Action AJAX non trouvée - Vérifiez la configuration';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Erreur serveur - Vérifiez les logs';
                    } else if (status === 'timeout') {
                        errorMessage = 'Timeout - La requête a pris trop de temps';
                    }
                    
                    $('.my-istymo-modern-table').html('<div class="my-istymo-error"><i class="fas fa-exclamation-triangle"></i> ' + errorMessage + '</div>');
                }
            });
        }
        
        // Fonction de test de connexion AJAX
        function testAjaxConnection() {
            console.log('=== TEST CONNEXION AJAX ===');
            console.log('URL:', ajaxUrl);
            console.log('Nonce:', nonce);
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'my_istymo_test_ajax',
                    nonce: nonce
                },
                timeout: 5000,
                success: function(response) {
                    console.log('✅ Test AJAX réussi:', response);
                    alert('Test AJAX réussi! Vérifiez la console pour les détails.');
                },
                error: function(xhr, status, error) {
                    console.error('❌ Test AJAX échoué:', {xhr: xhr, status: status, error: error});
                    console.error('Response:', xhr.responseText);
                    alert('Test AJAX échoué. Vérifiez la console pour les détails.');
                }
            });
        }
        
        // Fonction de debug des actions AJAX
        function debugAjaxActions() {
            console.log('=== DEBUG ACTIONS AJAX ===');
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'debug_ajax_actions',
                    nonce: nonce
                },
                success: function(response) {
                    console.log('✅ Actions AJAX:', response);
                    alert('Actions AJAX: ' + JSON.stringify(response.data.actions, null, 2));
                },
                error: function(xhr, status, error) {
                    console.error('❌ Debug AJAX échoué:', {xhr: xhr, status: status, error: error});
                    alert('Debug AJAX échoué. Vérifiez la console.');
                }
            });
        }
        
        // Exposer les fonctions globalement
        window.testAjaxConnection = testAjaxConnection;
        window.debugAjaxActions = debugAjaxActions;
        
        // Gestionnaire pour la soumission du formulaire de filtres
        $('.my-istymo-inline-filters').on('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            filterLeads(currentPage);
        });
        
        // Gestionnaire pour la pagination
        $(document).on('click', '.my-istymo-pagination-link', function(e) {
            e.preventDefault();
            var page = parseInt($(this).data('page'));
            if (page && page > 0) {
                currentPage = page;
                filterLeads(page);
            }
        });
        
        // Gestionnaire pour le bouton de réinitialisation
        $('.my-istymo-filter-reset-btn').on('click', function(e) {
            e.preventDefault();
            
            // Réinitialiser les filtres
            $('select[name="lead_type"]').val('');
            $('select[name="status"]').val('');
            $('select[name="priorite"]').val('');
            $('input[name="date_from"]').val('');
            $('input[name="date_to"]').val('');
            
            // Filtrer avec les filtres vides
            currentPage = 1;
            filterLeads(currentPage);
        });
        
        // Fonction pour initialiser les gestionnaires d'événements du tableau
        function initTableEventHandlers() {
            // Gestionnaire pour les boutons d'action des leads
            $('.view-lead-details').off('click').on('click', function(e) {
                e.preventDefault();
                var leadId = $(this).data('lead-id');
                if (typeof openLeadDetailModal === 'function') {
                    openLeadDetailModal(leadId);
                }
            });
            
            $('.edit-lead').off('click').on('click', function(e) {
                e.preventDefault();
                var leadId = $(this).data('lead-id');
                if (typeof openEditLeadModal === 'function') {
                    openEditLeadModal(leadId);
                }
            });
            
            $('.delete-lead').off('click').on('click', function(e) {
                e.preventDefault();
                var leadId = $(this).data('lead-id');
                if (confirm('Êtes-vous sûr de vouloir supprimer ce lead ?')) {
                    // Appeler la fonction de suppression existante
                    if (typeof deleteLead === 'function') {
                        deleteLead(leadId);
                    }
                }
            });
        }
        
        // Initialiser les gestionnaires d'événements au chargement
        initTableEventHandlers();
        
        // Les filtres sont maintenant gérés uniquement en mémoire
        // Pas de préservation depuis l'URL pour garder une URL propre
    });
    </script>
    <?php
    ?>
    

    
    <!-- Script pour le nouveau design du tableau -->
    <script>
    jQuery(document).ready(function($) {
        // Gestion des menus dropdown avec survol et clic
        let menuTimeout;
        
        // Fonction pour positionner le menu intelligemment - VERSION SIMPLIFIÉE
        function positionMenu(menuContainer) {
            const menu = menuContainer.find('.my-istymo-dropdown-menu');
            const button = menuContainer.find('.my-istymo-menu-trigger');
            
            // TOUJOURS utiliser position fixed pour éviter TOUS les problèmes de débordement
            menu.addClass('menu-fixed');
            
            // Temporairement afficher le menu pour mesurer ses dimensions
            menu.css({visibility: 'hidden', display: 'block', position: 'fixed'});
            const menuHeight = menu.outerHeight();
            const menuWidth = menu.outerWidth();
            menu.css({visibility: '', display: ''});
            
            // Obtenir les dimensions et positions du bouton
            const buttonRect = button[0].getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const windowWidth = window.innerWidth;
            
            // Calculer la position optimale
            let top = buttonRect.bottom + 5;
            let left = buttonRect.right - menuWidth;
            
            // Ajustements pour éviter les débordements
            // 1. Si pas assez d'espace en bas, placer au-dessus
            if (top + menuHeight > windowHeight - 20) {
                top = buttonRect.top - menuHeight - 5;
            }
            
            // 2. Si pas assez d'espace à droite, aligner à droite du bouton
            if (left < 10) {
                left = buttonRect.left;
            }
            
            // 3. Si le menu dépasse encore à droite, le placer à gauche du bouton
            if (left + menuWidth > windowWidth - 10) {
                left = buttonRect.left - menuWidth + buttonRect.width;
            }
            
            // 4. Dernière vérification - forcer dans la fenêtre
            if (left < 10) left = 10;
            if (left + menuWidth > windowWidth - 10) left = windowWidth - menuWidth - 10;
            if (top < 10) top = 10;
            if (top + menuHeight > windowHeight - 10) top = windowHeight - menuHeight - 10;
            
            // Appliquer la position calculée
            menu.css({
                position: 'fixed',
                top: top + 'px',
                left: left + 'px',
                right: 'auto',
                bottom: 'auto',
                'z-index': 10000
            });
            
            // Prévention agressive des scrollbars
            preventScrollbars();
        }
        
        // Fonction pour empêcher les scrollbars de façon agressive
        function preventScrollbars() {
            // Forcer tous les conteneurs à overflow visible
            $('.my-istymo, .my-istymo *').not('.my-istymo-dropdown-menu').each(function() {
                const $el = $(this);
                if (!$el.data('original-overflow-saved')) {
                    $el.data('original-overflow-saved', true);
                    $el.data('original-overflow', $el.css('overflow'));
                    $el.data('original-overflow-y', $el.css('overflow-y'));
                    $el.data('original-overflow-x', $el.css('overflow-x'));
                }
                
                $el.css({
                    'overflow-y': 'visible',
                    'overflow-x': $el.css('overflow-x') === 'scroll' || $el.css('overflow-x') === 'auto' ? 'auto' : 'visible'
                });
            });
        }
        
        
        
        
        
        
        // Gestion de la sélection multiple
        $('.my-istymo-select-all').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.my-istymo-lead-checkbox').prop('checked', isChecked);
        });
        
        // Vérifier si tous les éléments sont sélectionnés
        $('.my-istymo-lead-checkbox').on('change', function() {
            const totalCheckboxes = $('.my-istymo-lead-checkbox').length;
            const checkedCheckboxes = $('.my-istymo-lead-checkbox:checked').length;
            
            $('.my-istymo-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
        
        // Test simple pour vérifier que le modal existe
        console.log('Modal element found:', jQuery('#lead-detail-modal').length > 0);
        console.log('Modal functions available:', typeof openLeadDetailModal === 'function');
        
    });
    
    // Fonction openLeadDetailModal maintenant définie dans unified-leads-admin.js
    
    // Fonction pour fermer le modal de détail
    function closeLeadDetailModal() {
        console.log('Closing modal'); // Debug
        jQuery('#lead-detail-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
        jQuery('#lead-detail-modal').hide();
    }
    
    // Fonction pour initialiser le formulaire d'édition
    function initLeadEditForm() {
        // Gérer la soumission du formulaire d'édition
        jQuery('#lead-edit-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            
            const formData = jQuery(this).serialize();
            const submitBtn = jQuery(this).find('button[type="submit"]');
            
            // Désactiver le bouton pendant la sauvegarde
            submitBtn.prop('disabled', true).text('Sauvegarde...');
            
            jQuery.ajax({
                url: unifiedLeadsAjax.ajaxurl,
                type: 'POST',
                data: formData + '&action=my_istymo_update_lead&nonce=' + unifiedLeadsAjax.nonce,
                success: function(response) {
                    if (response.success) {
                        // Afficher un message de succès
                        jQuery('#lead-detail-content').prepend('<div class="my-istymo-success" style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><p>✅ Lead modifié avec succès!</p></div>');
                        
                        // Masquer le message après 3 secondes
                        setTimeout(function() {
                            jQuery('.my-istymo-success').fadeOut();
                        }, 3000);
                        
                        // Recharger le tableau pour refléter les changements
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                        
                    } else {
                        alert('Erreur lors de la modification: ' + (response.data || 'Erreur inconnue'));
                    }
                },
                error: function() {
                    alert('Erreur de communication avec le serveur');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text('💾 Sauvegarder les modifications');
                }
            });
        });
        
        // Gérer les boutons de fermeture dans le contenu
        jQuery('.my-istymo-modal-close[data-action="close-lead-detail"]').on('click', function() {
            closeleadDetailModal();
        });
    }
    
    // Fonction pour supprimer un lead
    function deleteLead(leadId) {
        // Vérifier si la fonction existante est disponible
        if (typeof deleteUnifiedLead === 'function') {
            deleteUnifiedLead(leadId);
            return;
        }
        
        // Sinon utiliser AJAX direct
        jQuery.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_unified_lead',
                lead_id: leadId,
                nonce: unifiedLeadsAjax.nonce
            },
            beforeSend: function() {
                // Désactiver le bouton pour éviter les doubles clics
                jQuery('[data-lead-id="' + leadId + '"]').prop('disabled', true);
            },
            success: function(response) {
                console.log('Response:', response); // Debug
                if (response && response.success) {
                    // Supprimer la ligne du tableau
                    jQuery('[data-lead-id="' + leadId + '"]').closest('tr').fadeOut(400, function() {
                        jQuery(this).remove();
                        updateLeadCount();
                        // Recharger la page si c'était le dernier lead
                        if (jQuery('.my-istymo-table-row').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert('Erreur lors de la suppression du lead: ' + (response && response.data ? response.data : 'Erreur inconnue'));
                    jQuery('[data-lead-id="' + leadId + '"]').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error); // Debug
                alert('Erreur de communication avec le serveur: ' + error);
                jQuery('[data-lead-id="' + leadId + '"]').prop('disabled', false);
            }
        });
    }
    
    // Fonction pour mettre à jour le compteur de leads
    function updateLeadCount() {
        const currentCount = jQuery('.my-istymo-table-row').length;
        const leadText = currentCount > 1 ? 'leads' : 'lead';
        jQuery('.my-istymo-results-count').text(currentCount + ' ' + leadText);
    }
    
    // Fonction pour générer le HTML moderne des détails du lead
    function generateModernLeadDetailHTML(leadData) {
        var html = '';
        
        // Première ligne - Informations de base en mode linéaire et petit
        html += '<div class="my-istymo-lead-summary-row">';
        
        // Statut avec liste déroulante éditable
        html += '<div class="my-istymo-summary-item">';
        html += '<span class="my-istymo-summary-label">Statut :</span>';
        html += '<select class="my-istymo-edit-select" id="edit-status-' + leadData.id + '" data-field="status">';
        html += '<option value="nouveau"' + (leadData.status === 'nouveau' ? ' selected' : '') + '>Nouveau</option>';
        html += '<option value="en_cours"' + (leadData.status === 'en_cours' ? ' selected' : '') + '>En cours</option>';
        html += '<option value="qualifie"' + (leadData.status === 'qualifie' ? ' selected' : '') + '>Qualifié</option>';
        html += '<option value="proposition"' + (leadData.status === 'proposition' ? ' selected' : '') + '>Proposition</option>';
        html += '<option value="negociation"' + (leadData.status === 'negociation' ? ' selected' : '') + '>Négociation</option>';
        html += '<option value="gagne"' + (leadData.status === 'gagne' ? ' selected' : '') + '>Gagné</option>';
        html += '<option value="perdu"' + (leadData.status === 'perdu' ? ' selected' : '') + '>Perdu</option>';
        html += '</select>';
        html += '</div>';
        
        // Priorité avec liste déroulante éditable
        html += '<div class="my-istymo-summary-item">';
        html += '<span class="my-istymo-summary-label">Priorité :</span>';
        html += '<select class="my-istymo-edit-select" id="edit-priorite-' + leadData.id + '" data-field="priorite">';
        html += '<option value="basse"' + (leadData.priorite === 'basse' ? ' selected' : '') + '>Basse</option>';
        html += '<option value="normale"' + (leadData.priorite === 'normale' ? ' selected' : '') + '>Normale</option>';
        html += '<option value="haute"' + (leadData.priorite === 'haute' ? ' selected' : '') + '>Haute</option>';
        html += '</select>';
        html += '</div>';
        
        // Dates
        html += '<div class="my-istymo-summary-item">';
        html += '<span class="my-istymo-summary-label">Créé le :</span>';
        html += '<span class="my-istymo-summary-value">' + (leadData.date_creation ? new Date(leadData.date_creation).toLocaleDateString('fr-FR') : '—') + '</span>';
        html += '</div>';
        
        html += '<div class="my-istymo-summary-item">';
        html += '<span class="my-istymo-summary-label">Modifié le :</span>';
        html += '<span class="my-istymo-summary-value">' + (leadData.date_modification ? new Date(leadData.date_modification).toLocaleDateString('fr-FR') : '—') + '</span>';
        html += '</div>';
        
        html += '</div>'; // Fin summary-row
        
        // Deuxième ligne - Container avec 2 colonnes
        html += '<div class="my-istymo-lead-detail-container">';
        
        // Colonne gauche - Informations du lead
        html += '<div class="my-istymo-lead-detail-left">';
        
        // Carte d'informations principales avec toutes les données SCI/DPE
        html += '<div class="my-istymo-info-card">';
        html += '<div class="my-istymo-card-header">';
        html += '<h4><span class="dashicons dashicons-info"></span> Informations ' + (leadData.lead_type === 'sci' ? 'SCI' : 'DPE') + '</h4>';
        html += '</div>';
        html += '<div class="my-istymo-card-content">';
        
        // Type de lead avec badge
        var typeIcon = leadData.lead_type === 'sci' ? '🏢' : '🏠';
        var typeText = leadData.lead_type === 'sci' ? 'Société Civile' : 'Bien Immobilier';
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Type :</span>';
        html += '<span class="my-istymo-info-value">' + typeIcon + ' ' + typeText + '</span>';
        html += '</div>';
        
        // ID original (SIREN pour SCI, DPE ID pour DPE)
        var idLabel = leadData.lead_type === 'sci' ? 'SIREN :' : 'DPE ID :';
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">' + idLabel + '</span>';
        html += '<span class="my-istymo-info-value">' + (leadData.original_id || '—') + '</span>';
        html += '</div>';
        
        // Informations spécifiques selon le type
        if (leadData.data_originale) {
            var data = leadData.data_originale;
            
            if (leadData.lead_type === 'sci') {
                // Informations SCI
                if (data.denomination || data.raisonSociale) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Dénomination :</span>';
                    html += '<span class="my-istymo-info-value">' + (data.denomination || data.raisonSociale || '—') + '</span>';
                    html += '</div>';
                }
                
                if (data.dirigeant) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Dirigeant :</span>';
                    html += '<span class="my-istymo-info-value">' + data.dirigeant + '</span>';
                    html += '</div>';
                }
                
                // Section Adresse et Localisation pour SCI
                html += '<div class="my-istymo-info-section">';
                
                // Construire l'adresse complète
                var adresseComplete = '';
                var adresseParts = [];
                
                if (data.adresse) {
                    adresseParts.push(data.adresse);
                }
                
                if (data.code_postal && data.ville) {
                    adresseParts.push(data.code_postal + ' ' + data.ville);
                } else if (data.code_postal) {
                    adresseParts.push(data.code_postal);
                } else if (data.ville) {
                    adresseParts.push(data.ville);
                }
                
                adresseComplete = adresseParts.join(', ');
                
                if (adresseComplete) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Adresse :</span>';
                    html += '<span class="my-istymo-info-value">' + adresseComplete + '</span>';
                    html += '</div>';
                }
                
                html += '</div>'; // Fin section localisation
                
                // Informations supplémentaires SCI
                if (data.siren) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">SIREN :</span>';
                    html += '<span class="my-istymo-info-value">' + data.siren + '</span>';
                    html += '</div>';
                }
                
            } else if (leadData.lead_type === 'dpe') {
                // Section Adresse et Localisation pour DPE
                html += '<div class="my-istymo-info-section">';
                
                // Construire l'adresse complète
                var adresseComplete = '';
                var adresseParts = [];
                
                if (data.adresse_ban) {
                    // Nettoyer l'adresse pour enlever le code postal et la ville
                    var adresseClean = data.adresse_ban;
                    if (data.code_postal_ban && data.nom_commune_ban) {
                        var pattern = new RegExp('\\s*' + data.code_postal_ban + '\\s*' + data.nom_commune_ban + '\\s*$', 'i');
                        adresseClean = adresseClean.replace(pattern, '').trim();
                    }
                    adresseParts.push(adresseClean || data.adresse_ban);
                }
                
                if (data.code_postal_ban && data.nom_commune_ban) {
                    adresseParts.push(data.code_postal_ban + ' ' + data.nom_commune_ban);
                } else if (data.code_postal_ban) {
                    adresseParts.push(data.code_postal_ban);
                } else if (data.nom_commune_ban) {
                    adresseParts.push(data.nom_commune_ban);
                }
                
                adresseComplete = adresseParts.join(', ');
                
                if (adresseComplete) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Adresse :</span>';
                    html += '<span class="my-istymo-info-value">' + adresseComplete + '</span>';
                    html += '</div>';
                }
                
                if (data.complement_adresse_logement) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Complément :</span>';
                    html += '<span class="my-istymo-info-value">' + data.complement_adresse_logement + '</span>';
                    html += '</div>';
                }
                
                html += '</div>'; // Fin section localisation
                
                // Section Caractéristiques du bien
                html += '<div class="my-istymo-info-section">';
                
                if (data.surface_habitable_logement) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Surface :</span>';
                    html += '<span class="my-istymo-info-value">' + data.surface_habitable_logement + ' m²</span>';
                    html += '</div>';
                }
                
                if (data.type_batiment) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Type Bâtiment :</span>';
                    html += '<span class="my-istymo-info-value">' + data.type_batiment + '</span>';
                    html += '</div>';
                }
                
                if (data.annee_construction) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Année Construction :</span>';
                    html += '<span class="my-istymo-info-value">' + data.annee_construction + '</span>';
                    html += '</div>';
                }
                
                html += '</div>'; // Fin section caractéristiques
                
                // Section Performance Énergétique
                html += '<div class="my-istymo-info-section">';
                
                if (data.etiquette_dpe) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Étiquette DPE :</span>';
                    html += '<span class="my-istymo-info-value my-istymo-dpe-badge my-istymo-dpe-' + data.etiquette_dpe.toLowerCase() + '">';
                    html += '<span class="my-istymo-badge-dot"></span>';
                    html += data.etiquette_dpe;
                    html += '</span>';
                    html += '</div>';
                }
                
                if (data.etiquette_ges) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Étiquette GES :</span>';
                    html += '<span class="my-istymo-info-value my-istymo-ges-badge my-istymo-ges-' + data.etiquette_ges.toLowerCase() + '">';
                    html += '<span class="my-istymo-badge-dot"></span>';
                    html += data.etiquette_ges;
                    html += '</span>';
                    html += '</div>';
                }
                
                if (data.conso_5_usages_ef_energie_n1) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Consommation :</span>';
                    html += '<span class="my-istymo-info-value">' + data.conso_5_usages_ef_energie_n1 + ' kWh/m²/an</span>';
                    html += '</div>';
                }
                
                if (data.emission_ges_5_usages_energie_n1) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Émissions GES :</span>';
                    html += '<span class="my-istymo-info-value">' + data.emission_ges_5_usages_energie_n1 + ' kgCO₂/m²/an</span>';
                    html += '</div>';
                }
                
                html += '</div>'; // Fin section performance énergétique
                
                // Informations supplémentaires DPE
                
                if (data.date_etablissement_dpe) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Date DPE :</span>';
                    html += '<span class="my-istymo-info-value">' + new Date(data.date_etablissement_dpe).toLocaleDateString('fr-FR') + '</span>';
                    html += '</div>';
                }
                
                // Bouton pour voir les détails du DPE
                if (data._id) {
                    html += '<div class="my-istymo-info-row" style="margin-top: 16px;">';
                    html += '<a href="https://observatoire-dpe-audit.ademe.fr/afficher-dpe/' + data._id + '" target="_blank" class="my-istymo-btn my-istymo-btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">';
                    html += '<span class="dashicons dashicons-external"></span>';
                    html += 'Voir les détails du DPE';
                    html += '</a>';
                    html += '</div>';
                }
            }
        }
        
        html += '</div>'; // Fin card-content
        html += '</div>'; // Fin info-card
        

        
        html += '</div>'; // Fin colonne gauche
        
        // Colonne droite - Notes et historique
        html += '<div class="my-istymo-lead-detail-right">';
        
        // Carte des notes (déplacée dans la colonne droite)
        html += '<div class="my-istymo-info-card">';
        html += '<div class="my-istymo-card-header">';
        html += '<h4><span class="dashicons dashicons-edit"></span> Notes</h4>';
        html += '</div>';
        html += '<div class="my-istymo-card-content">';
        
        html += '<textarea class="my-istymo-edit-textarea" id="edit-notes-' + leadData.id + '" data-field="notes" placeholder="Ajouter des notes pour ce lead..." rows="6">' + (leadData.notes || '') + '</textarea>';
        
        html += '</div>'; // Fin card-content
        html += '</div>'; // Fin info-card
        

        
        html += '</div>'; // Fin colonne droite
        
        html += '</div>'; // Fin container principal
        
        // Boutons d'action en bas du modal
        html += '<div class="my-istymo-modal-actions" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e5e5e5; text-align: right;">';
        html += '<button type="button" class="my-istymo-btn my-istymo-btn-primary" onclick="saveLeadChanges(' + leadData.id + ');">';
        html += '<span class="dashicons dashicons-saved"></span> Sauvegarder';
        html += '</button>';

        html += '</div>';
        
        return html;
    }
    
    // Fonction pour sauvegarder les modifications du lead
    function saveLeadChanges(leadId) {
        console.log('💾 Sauvegarde des modifications pour le lead:', leadId);
        
        // Récupérer les valeurs des champs éditables
        var status = jQuery('#edit-status-' + leadId).val();
        var priorite = jQuery('#edit-priorite-' + leadId).val();
        var notes = jQuery('#edit-notes-' + leadId).val();
        
        // Validation des données
        if (!status || !priorite) {
            alert('❌ Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        // Afficher un indicateur de chargement
        var saveButton = jQuery('#lead-detail-modal .my-istymo-btn-primary');
        var originalText = saveButton.html();
        saveButton.html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> Sauvegarde...');
        saveButton.prop('disabled', true);
        
        // Envoyer les données via AJAX
        jQuery.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'my_istymo_update_lead_from_modal',
                lead_id: leadId,
                status: status,
                priorite: priorite,
                notes: notes,
                nonce: unifiedLeadsAjax.nonce
            },
            success: function(response) {
                console.log('📡 Réponse de sauvegarde:', response);
                
                // Arrêter l'animation immédiatement
                saveButton.html(originalText);
                saveButton.prop('disabled', false);
                
                if (response && response.success) {
                    // Afficher une notification de succès
                    showToastNotification('Modifications sauvegardées avec succès !', 'success');
                    
                    // Fermer le modal
                    closeLeadDetailModal();
                    
                    // Recharger la liste des leads pour afficher les modifications
                    loadLeads();
                } else {
                    showToastNotification('Erreur lors de la sauvegarde: ' + (response && response.data ? response.data : 'Erreur inconnue'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Erreur AJAX:', xhr, status, error);
                
                // Arrêter l'animation immédiatement
                saveButton.html(originalText);
                saveButton.prop('disabled', false);
                
                showToastNotification('Erreur de communication avec le serveur: ' + error, 'error');
            },
            complete: function() {
                // S'assurer que l'animation est arrêtée (fallback)
                if (saveButton.prop('disabled')) {
                    saveButton.html(originalText);
                    saveButton.prop('disabled', false);
                }
            }
        });
    }
    
    // Fonction pour afficher les notifications toast
    function showToastNotification(message, type = 'success') {
        // Supprimer les notifications existantes
        jQuery('.my-istymo-toast-notification').remove();
        
        // Créer la notification
        var notification = jQuery('<div class="my-istymo-toast-notification ' + type + '">');
        
        // Ajouter l'icône selon le type
        var icon = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';
        notification.html('<span class="dashicons ' + icon + '"></span>' + message);
        
        // Ajouter au body
        jQuery('body').append(notification);
        
        // Afficher avec animation
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        // Masquer automatiquement après 3 secondes
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }
    </script>
    
    <!-- Modal supprimé - fonctionnalité simplifiée -->
    

    

    
    <?php
}
