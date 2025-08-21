<?php
if (!defined('ABSPATH')) exit;

/**
 * Page d'administration principale pour la gestion des leads
 * Interface de gestion avec tableau, filtres et actions en lot
 */
function unified_leads_admin_page() {
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'));
    }
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $status_manager = Lead_Status_Manager::get_instance();
    

    
    // Récupérer les filtres
    $filters = array();
    if (!empty($_GET['lead_type'])) $filters['lead_type'] = sanitize_text_field($_GET['lead_type']);
    if (!empty($_GET['status'])) $filters['status'] = sanitize_text_field($_GET['status']);
    if (!empty($_GET['priorite'])) $filters['priorite'] = sanitize_text_field($_GET['priorite']);
    if (!empty($_GET['date_from'])) $filters['date_from'] = sanitize_text_field($_GET['date_from']);
    if (!empty($_GET['date_to'])) $filters['date_to'] = sanitize_text_field($_GET['date_to']);
    
    // Récupérer les leads avec pagination
    $page = max(1, intval($_GET['paged'] ?? 1));
    $per_page = 20;
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
        <h1>📋 Gestion des Leads</h1>
        
        <div class="notice notice-info">
            <p><strong>Interface de Gestion</strong> - Gérez vos leads avec filtres, actions en lot et suivi des statuts.</p>
        </div>
        
        <!-- Les filtres sont maintenant intégrés dans l'en-tête du tableau moderne -->
        

        

        
        <!-- Tableau des leads moderne -->
        <div class="my-istymo-leads-container">
            <!-- En-tête avec contrôles intégrés -->
            <div class="my-istymo-table-header">
                <div class="my-istymo-header-left">
                    
                    <!-- Filtres intégrés -->
                    <form method="get" class="my-istymo-inline-filters" style="display: flex; align-items: center; gap: 12px;">
                <input type="hidden" name="page" value="unified-leads">
                
                        <!-- Filtre par type -->
                        <div class="my-istymo-filter-group">
                            <select name="lead_type" class="my-istymo-filter-select">
                            <option value="">Tous les types</option>
                            <option value="sci" <?php selected($_GET['lead_type'] ?? '', 'sci'); ?>>SCI</option>
                            <option value="dpe" <?php selected($_GET['lead_type'] ?? '', 'dpe'); ?>>DPE</option>
                        </select>
                    </div>
                    
                        <!-- Filtre par statut -->
                        <div class="my-istymo-filter-group">
                            <select name="status" class="my-istymo-filter-select">
                            <option value="">Tous les statuts</option>
                            <?php echo $status_options; ?>
                        </select>
                    </div>
                    
                        <!-- Filtre par priorité -->
                        <div class="my-istymo-filter-group">
                            <select name="priorite" class="my-istymo-filter-select">
                            <option value="">Toutes les priorités</option>
                            <?php echo $priority_options; ?>
                        </select>
                    </div>
                    
                        <!-- Boutons d'action des filtres -->
                        <div class="my-istymo-filter-actions">
                            <button type="submit" class="my-istymo-filter-apply-btn">
                                <span class="dashicons dashicons-filter"></span> Filtrer
                            </button>
                            <?php if (!empty($_GET['lead_type']) || !empty($_GET['status']) || !empty($_GET['priorite']) || !empty($_GET['date_from']) || !empty($_GET['date_to'])): ?>
                            <a href="?page=unified-leads" class="my-istymo-filter-reset-btn">
                                <span class="dashicons dashicons-dismiss"></span> Réinitialiser
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    </div>
                    
                <div class="my-istymo-header-right">
                    <!-- Compteur de résultats -->
                    <div class="my-istymo-results-count">
                        <?php echo $total_leads; ?> lead<?php echo $total_leads > 1 ? 's' : ''; ?>
                    </div>
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
                                    <span class="dashicons dashicons-admin-home"></span> Entreprise
                                </th>
                                <th class="my-istymo-th-category">
                                    <span class="dashicons dashicons-category"></span> Catégorie
                                </th>
                                <th class="my-istymo-th-priority">
                                    <span class="dashicons dashicons-flag"></span> Priorité
                                </th>
                                <th class="my-istymo-th-location">
                                    <span class="dashicons dashicons-location"></span> Localisation
                                </th>
                                <th class="my-istymo-th-status">
                                    <span class="dashicons dashicons-info"></span> Statut
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
                                        $category = 'Immobilier';
                                            } elseif ($lead->lead_type === 'sci') {
                                        $company_name = $lead->data_originale['denomination'] ?? $lead->data_originale['raisonSociale'] ?? 'SCI';
                                        $domain = 'entreprise.com';
                                        $ville = $lead->data_originale['ville'] ?? '';
                                        $code_postal = $lead->data_originale['code_postal'] ?? '';
                                        $location = $ville . ($code_postal ? ' (' . $code_postal . ')' : '');
                                        $category = 'Société Civile';
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
                                            case 'termine':
                                                $status_class = 'completed';
                                                $status_text = 'Terminé';
                                                break;
                                            default:
                                                $status_class = 'pending';
                                                $status_text = 'Nouveau';
                                        }
                                        ?>
                                        <span class="my-istymo-status-badge my-istymo-status-<?php echo $status_class; ?>">
                                            <span class="my-istymo-status-dot"></span>
                                            <?php echo $status_text; ?>
                                            </span>
                                    </td>
                                    <td class="my-istymo-td-actions">
                                        <div class="my-istymo-actions-menu">
                                            <button class="my-istymo-menu-trigger" data-lead-id="<?php echo $lead->id; ?>">
                                                <span class="dashicons dashicons-ellipsis"></span>
                                            </button>
                                            <div class="my-istymo-dropdown-menu">
                                                <a href="#" class="view-lead" data-lead-id="<?php echo $lead->id; ?>" onclick="console.log('Link clicked'); openLeadDetailModal(<?php echo $lead->id; ?>); return false;">
                                                    <span class="dashicons dashicons-visibility"></span> Voir
                                                </a>
                                                <a href="#" class="delete-lead" data-lead-id="<?php echo $lead->id; ?>" onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce lead ?')) { deleteLead(<?php echo $lead->id; ?>); } return false;">
                                                    <span class="dashicons dashicons-trash"></span> Supprimer
                                                </a>
                                            </div>
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
                        $pagination_args = array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $page
                        );
                        
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-leads">
                    <p>Aucun lead trouvé avec les critères actuels.</p>
                    <a href="?page=unified-leads" class="button button-primary">Voir tous les leads</a>
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
    // Charger le CSS et JS
    wp_enqueue_style('unified-leads-css', plugin_dir_url(__FILE__) . '../assets/css/unified-leads.css', array(), '1.0.0');
    wp_enqueue_style('lead-edit-modal-css', plugin_dir_url(__FILE__) . '../assets/css/lead-edit-modal.css', array(), '1.0.0');
    wp_enqueue_script('unified-leads-admin', plugin_dir_url(__FILE__) . '../assets/js/unified-leads-admin.js', array('jquery'), '1.0.0', true);
    
    // ✅ PHASE 3 : Charger les scripts pour les actions et workflow
    wp_enqueue_script('lead-actions', plugin_dir_url(__FILE__) . '../assets/js/lead-actions.js', array('jquery', 'jquery-ui-tooltip'), '1.0.0', true);
    wp_enqueue_script('lead-workflow', plugin_dir_url(__FILE__) . '../assets/js/lead-workflow.js', array('jquery'), '1.0.0', true);
    
    wp_localize_script('unified-leads-admin', 'unifiedLeadsAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_nonce')
    ));
    
    // ✅ PHASE 3 : Variables pour les actions et workflow
    wp_localize_script('lead-actions', 'leadActionsAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_nonce')
    ));
    
    wp_localize_script('lead-workflow', 'leadWorkflowAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_nonce')
    ));
    ?>
    
    <style>
        /* Design professionnel et minimaliste pour la page leads */
        .my-istymo .wrap {
            max-width: none !important;
            width: 100% !important;
            background: #fafafa;
        }
        

        
        /* Colonnes du tableau - Largeurs optimisées */
        
        .my-istymo .leads-table th:nth-child(1),
        .my-istymo .leads-table td:nth-child(1) {
            width: 80px; /* Type - réduit */
            min-width: 80px;
            max-width: 80px;
        }
        
        .my-istymo .leads-table th:nth-child(2),
        .my-istymo .leads-table td:nth-child(2) {
            width: 120px; /* ID Original - réduit */
            min-width: 120px;
            max-width: 120px;
            word-break: break-all;
            font-size: 12px;
        }
        
        .my-istymo .leads-table th:nth-child(3),
        .my-istymo .leads-table td:nth-child(3) {
            width: 100px; /* Statut */
            min-width: 100px;
        }
        
        .my-istymo .leads-table th:nth-child(4),
        .my-istymo .leads-table td:nth-child(4) {
            width: 90px; /* Priorité */
            min-width: 90px;
        }
        
        .my-istymo .leads-table th:nth-child(5),
        .my-istymo .leads-table td:nth-child(5) {
            width: 110px; /* Date Création */
            min-width: 110px;
        }
        
        .my-istymo .leads-table th:nth-child(6),
        .my-istymo .leads-table td:nth-child(6) {
            width: 120px; /* Notes - réduit */
            min-width: 120px;
        }
        
        .my-istymo .leads-table th:nth-child(7),
        .my-istymo .leads-table td:nth-child(7) {
            width: 120px; /* Actions - optimisé avec icônes */
            min-width: 120px;
        }
        
        /* Optimisation des badges de type */
        .my-istymo .lead-type-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Optimisation des actions */
        .my-istymo .row-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }
        
        .my-istymo .row-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #f8f9fa;
            border: 1px solid #e5e5e5;
        }
        
        .my-istymo .row-actions a:hover {
            background: #0073aa;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,115,170,0.2);
        }
        
        .my-istymo .row-actions .delete a:hover {
            background: #dc3545;
            border-color: #dc3545;
        }
        
        /* Optimisation des notes */
        .my-istymo .lead-notes {
            font-size: 12px;
            color: #666;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .my-istymo .lead-notes:hover {
            color: #333;
        }
        
        /* Styles pour les modals */
        .my-istymo-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .my-istymo-modal.my-istymo-show {
            display: block;
        }
        
        .my-istymo-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .my-istymo-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .my-istymo-modal-header h3 {
            margin: 0;
            color: #1d2327;
            font-size: 20px;
            font-weight: 600;
        }
        
        .my-istymo-modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .my-istymo-modal-close:hover {
            background: #f0f0f0;
            color: #333;
        }
        
        .my-istymo-form-group {
            margin-bottom: 20px;
        }
        
        .my-istymo-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1d2327;
        }
        
        .my-istymo-select,
        .my-istymo-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .my-istymo-select:focus,
        .my-istymo-textarea:focus {
            outline: none;
            border-color: #0073aa;
            box-shadow: 0 0 0 4px rgba(0,115,170,0.15);
        }
        
        .my-istymo-form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .my-istymo-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .my-istymo-btn-primary {
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
            color: white;
        }
        
        .my-istymo-btn-secondary {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e5e5e5;
        }
        
        .my-istymo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
                 
        
        .my-istymo .lead-notes {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #666;
            font-size: 13px;
        }
        
        /* Actions sur les lignes */
        .my-istymo .row-actions {
            font-size: 12px;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
                 .my-istymo .row-actions a {
             color: #0073aa;
             text-decoration: none;
             padding: 6px 12px;
             border-radius: 6px;
             transition: all 0.3s ease;
             font-weight: 500;
             border: 1px solid transparent;
         }
         
         .my-istymo .row-actions a:hover {
             background: linear-gradient(135deg, rgba(0,115,170,0.1) 0%, rgba(0,115,170,0.05) 100%);
             color: #005a87;
             border-color: rgba(0,115,170,0.2);
             transform: translateY(-1px);
             box-shadow: 0 2px 6px rgba(0,115,170,0.15);
         }
         
         .my-istymo .row-actions .delete a:hover {
             background: linear-gradient(135deg, rgba(220,53,69,0.1) 0%, rgba(220,53,69,0.05) 100%);
             color: #dc3545;
             border-color: rgba(220,53,69,0.2);
             box-shadow: 0 2px 6px rgba(220,53,69,0.15);
         }
        
        /* Modals - Design amélioré */
        .my-istymo .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 100000;
            backdrop-filter: blur(2px);
        }
        
        .my-istymo .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 32px;
            border-radius: 12px;
            min-width: 450px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border: 1px solid #e5e5e5;
        }
        
        .my-istymo .modal-content h3 {
            margin: 0 0 24px 0;
            color: #1d2327;
            font-size: 18px;
            font-weight: 500;
        }
        
                 .my-istymo .modal-content select,
         .my-istymo .modal-content textarea {
             width: 100%;
             margin-bottom: 24px;
             padding: 16px 20px;
             border: 2px solid #e5e5e5;
             border-radius: 10px;
             font-size: 14px;
             font-weight: 500;
             color: #1d2327;
             background: #fff;
             transition: all 0.3s ease;
             box-shadow: 0 2px 6px rgba(0,0,0,0.05);
         }
         
         .my-istymo .modal-content select:hover,
         .my-istymo .modal-content textarea:hover {
             border-color: #0073aa;
             box-shadow: 0 4px 12px rgba(0,115,170,0.1);
         }
         
         .my-istymo .modal-content select:focus,
         .my-istymo .modal-content textarea:focus {
             outline: none;
             border-color: #0073aa;
             box-shadow: 0 0 0 4px rgba(0,115,170,0.15);
             transform: translateY(-1px);
         }
         
         .my-istymo .modal-content textarea {
             resize: vertical;
             min-height: 120px;
             font-family: inherit;
             line-height: 1.5;
         }
        
                 .my-istymo .modal-actions {
             display: flex;
             gap: 16px;
             justify-content: flex-end;
             margin-top: 8px;
         }
         
         .my-istymo .modal-actions .button {
             padding: 12px 24px;
             border-radius: 10px;
             font-weight: 600;
             font-size: 13px;
             text-transform: uppercase;
             letter-spacing: 0.5px;
             transition: all 0.3s ease;
             border: none;
             cursor: pointer;
             box-shadow: 0 2px 8px rgba(0,0,0,0.1);
         }
         
         .my-istymo .modal-actions .button-primary {
             background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
             color: white;
             box-shadow: 0 3px 10px rgba(0,115,170,0.3);
         }
         
         .my-istymo .modal-actions .button-primary:hover {
             background: linear-gradient(135deg, #005a87 0%, #004466 100%);
             box-shadow: 0 6px 20px rgba(0,115,170,0.4);
             transform: translateY(-2px);
         }
         
         .my-istymo .modal-actions .button-secondary {
             background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
             color: #1d2327;
             border: 2px solid #e5e5e5;
             box-shadow: 0 2px 6px rgba(0,0,0,0.08);
         }
         
         .my-istymo .modal-actions .button-secondary:hover {
             background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
             border-color: #0073aa;
             color: #0073aa;
             box-shadow: 0 4px 12px rgba(0,115,170,0.15);
             transform: translateY(-2px);
         }
        
        /* État vide */
        .my-istymo .no-leads {
            text-align: center;
            padding: 60px 40px;
            color: #666;
            background: #fafbfc;
            border-radius: 8px;
            border: 2px dashed #e5e5e5;
        }
        
                 .my-istymo .no-leads p {
             font-size: 16px;
             margin-bottom: 24px;
             color: #666;
             font-weight: 500;
         }
         
         .my-istymo .no-leads .button {
             padding: 14px 28px;
             border-radius: 10px;
             font-weight: 600;
             font-size: 14px;
             text-transform: uppercase;
             letter-spacing: 0.5px;
             transition: all 0.3s ease;
             border: none;
             cursor: pointer;
             background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
             color: white;
             box-shadow: 0 3px 10px rgba(0,115,170,0.3);
             text-decoration: none;
             display: inline-block;
         }
         
         .my-istymo .no-leads .button:hover {
             background: linear-gradient(135deg, #005a87 0%, #004466 100%);
             box-shadow: 0 6px 20px rgba(0,115,170,0.4);
             transform: translateY(-2px);
         }
        
        /* Pagination - Design amélioré */
        .my-istymo .tablenav-pages {
            margin-top: 24px;
            text-align: center;
            padding: 20px;
            background: #fafbfc;
            border-radius: 8px;
            border: 1px solid #e5e5e5;
        }
        
        .my-istymo .tablenav-pages .page-numbers {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 4px;
            border: 1px solid #e5e5e5;
            text-decoration: none;
            color: #0073aa;
            border-radius: 6px;
            transition: all 0.2s ease;
            background: #fff;
        }
        
        .my-istymo .tablenav-pages .page-numbers:hover {
            background: #f8f9fa;
            border-color: #0073aa;
        }
        
        .my-istymo .tablenav-pages .current {
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
            color: white;
            border-color: #0073aa;
            box-shadow: 0 2px 4px rgba(0,115,170,0.2);
        }
        
        .my-istymo .displaying-num {
            color: #666;
            font-size: 14px;
            margin-right: 20px;
        }
        
        /* Responsive */
        .my-istymo .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .my-istymo .bulk-actions-row {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }
            
            .my-istymo .bulk-actions {
                flex-direction: column;
                gap: 8px;
            }
            
            .my-istymo .modal-content {
                min-width: 90%;
                margin: 20px;
                padding: 24px;
            }
            
            .my-istymo .row-actions {
                flex-direction: column;
                gap: 4px;
            }
        }
        
        /* ✅ PHASE 3 : Styles pour les modals d'actions et workflow */
        .my-istymo .action-modal,
        .my-istymo .workflow-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .my-istymo .action-modal-content,
        .my-istymo .workflow-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .my-istymo .action-modal h3,
        .my-istymo .workflow-modal h3 {
            margin-top: 0;
            color: #1d2327;
            font-size: 24px;
            font-weight: 600;
        }
        
        .my-istymo .action-form-group,
        .my-istymo .workflow-form-group {
            margin-bottom: 20px;
        }
        
        .my-istymo .action-form-group label,
        .my-istymo .workflow-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1d2327;
        }
        
        .my-istymo .action-form-group input,
        .my-istymo .action-form-group select,
        .my-istymo .action-form-group textarea,
        .my-istymo .workflow-form-group input,
        .my-istymo .workflow-form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e5e5;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .my-istymo .action-form-group input:focus,
        .my-istymo .action-form-group select:focus,
        .my-istymo .action-form-group textarea:focus,
        .my-istymo .workflow-form-group input:focus,
        .my-istymo .workflow-form-group select:focus {
            outline: none;
            border-color: #0073aa;
            box-shadow: 0 0 0 4px rgba(0,115,170,0.15);
        }
        
        .my-istymo .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .my-istymo .modal-actions .button {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .my-istymo .modal-actions .button-primary {
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
            color: white;
            border: none;
        }
        
        .my-istymo .modal-actions .button-secondary {
            background: #f8f9fa;
            color: #6c757d;
            border: 2px solid #e5e5e5;
        }
        
        .my-istymo .modal-actions .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
    
    <!-- Script pour le nouveau design du tableau -->
    <script>
    jQuery(document).ready(function($) {
        // Gestion des menus dropdown
        $('.my-istymo-menu-trigger').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Fermer tous les autres menus
            $('.my-istymo-dropdown-menu').removeClass('show').hide();
            
            // Ouvrir le menu de ce bouton
            const menu = $(this).siblings('.my-istymo-dropdown-menu');
            menu.addClass('show').show();
        });
        
        // Fermer les menus en cliquant ailleurs
        $(document).on('click', function() {
            $('.my-istymo-dropdown-menu').removeClass('show').hide();
        });
        
        // Empêcher la fermeture du menu en cliquant dessus
        $('.my-istymo-dropdown-menu').on('click', function(e) {
            e.stopPropagation();
        });
        
        // Fermer le menu après avoir cliqué sur une action
        $('.my-istymo-dropdown-menu a').on('click', function() {
            $(this).closest('.my-istymo-dropdown-menu').removeClass('show').hide();
        });
        
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
        
        // Ajouter un bouton de test temporaire
        jQuery('body').append('<button id="test-modal-btn" style="position: fixed; top: 10px; right: 10px; z-index: 999999; background: red; color: white; padding: 10px;">Test Modal</button>');
        jQuery('#test-modal-btn').on('click', function() {
            console.log('Test button clicked');
            openLeadDetailModal(1);
        });
    });
    
    // Fonction pour ouvrir le modal de détail d'un lead
    function openLeadDetailModal(leadId) {
        console.log('Opening modal for lead ID:', leadId); // Debug
        
        // Affichage simple du modal
        const modal = jQuery('#lead-detail-modal');
        console.log('Modal element exists:', modal.length);
        
        modal.css('display', 'block');
        console.log('Modal display set to block');
        
        // Charger les détails via AJAX
        jQuery.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'my_istymo_get_lead_detail_content',
                lead_id: leadId,
                nonce: unifiedLeadsAjax.nonce
            },
            beforeSend: function() {
                jQuery('#lead-detail-content').html('<div style="text-align: center; padding: 20px;"><p>🔄 Chargement des détails...</p></div>');
            },
            success: function(response) {
                console.log('AJAX Response:', response); // Debug
                if (response && response.success) {
                    // Mettre à jour le titre du modal
                    jQuery('#lead-modal-title').text('Lead #' + response.data.lead_id + ' - ' + response.data.lead_type.toUpperCase());
                    
                    // Charger le contenu
                    jQuery('#lead-detail-content').html(response.data.html);
                    
                    // Initialiser le formulaire d'édition après le chargement
                    initLeadEditForm();
                } else {
                    jQuery('#lead-detail-content').html('<div style="color: red; padding: 20px;"><p>❌ Erreur: ' + (response && response.data ? response.data : 'Impossible de charger les détails') + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                jQuery('#lead-detail-content').html('<div style="color: red; padding: 20px;"><p>❌ Erreur de communication avec le serveur: ' + error + '</p></div>');
            }
        });
    }
    
    // Fonction pour fermer le modal de détail
    function closeleadDetailModal() {
        console.log('Closing modal'); // Debug
        jQuery('#lead-detail-modal').css('display', 'none');
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
    </script>
    
    <!-- Modal pour visualiser/modifier un lead -->
    <div id="lead-detail-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999;">
        <div onclick="closeleadDetailModal()" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                <h3 id="lead-modal-title" style="margin: 0; color: #333;">Détails du Lead</h3>
                <button onclick="closeleadDetailModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>
            </div>
            <div id="lead-detail-content">
                <p>Chargement des détails...</p>
            </div>
        </div>
    </div>
    
    <!-- ✅ PHASE 3 : Modals pour les actions et workflow -->
    <div id="add-action-modal" class="my-istymo-modal my-istymo-hidden">
        <div class="my-istymo-modal-overlay"></div>
        <div class="my-istymo-modal-content">
            <h3>📝 Ajouter une Action</h3>
            <form id="add-action-form">
                <input type="hidden" name="lead_id" id="action-lead-id">
                <div class="action-form-group">
                    <label for="action-type">Type d'action :</label>
                    <select name="action_type" id="action-type" required>
                        <option value="">Sélectionner un type</option>
                        <option value="appel">📞 Appel téléphonique</option>
                        <option value="email">📧 Email</option>
                        <option value="sms">💬 SMS</option>
                        <option value="rdv">📅 Rendez-vous</option>
                        <option value="note">📝 Note</option>
                    </select>
                </div>
                <div class="action-form-group">
                    <label for="action-description">Description :</label>
                    <textarea name="description" id="action-description" rows="4" placeholder="Décrivez l'action..." required></textarea>
                </div>
                <div class="action-form-group">
                    <label for="action-result">Résultat :</label>
                    <select name="result" id="action-result">
                        <option value="en_attente">⏳ En attente</option>
                        <option value="reussi">✅ Réussi</option>
                        <option value="echec">❌ Échec</option>
                        <option value="reporte">📅 Reporté</option>
                    </select>
                </div>
                <div class="action-form-group">
                    <label for="action-scheduled-date">Date programmée (optionnel) :</label>
                    <input type="datetime-local" name="scheduled_date" id="action-scheduled-date">
                </div>
                <div class="my-istymo-modal-actions">
                    <button type="submit" class="my-istymo-btn my-istymo-btn-primary">Ajouter l'action</button>
                    <button type="button" class="my-istymo-btn my-istymo-btn-secondary my-istymo-modal-close">Annuler</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="change-status-modal" class="my-istymo-modal my-istymo-hidden">
        <div class="my-istymo-modal-overlay"></div>
        <div class="my-istymo-modal-content">
            <h3>🔄 Changer le Statut</h3>
            <form id="change-status-form">
                <input type="hidden" name="lead_id" id="status-lead-id">
                <input type="hidden" name="current_status" id="current-status">
                <div class="workflow-form-group">
                    <label for="new-status">Nouveau statut :</label>
                    <select name="new_status" id="new-status" required>
                        <option value="">Sélectionner un statut</option>
                        <option value="nouveau">🆕 Nouveau</option>
                        <option value="en_cours">🔄 En cours</option>
                        <option value="qualifie">✅ Qualifié</option>
                        <option value="proposition">📋 Proposition</option>
                        <option value="negocie">💼 Négociation</option>
                        <option value="gagne">🏆 Gagné</option>
                        <option value="perdu">❌ Perdu</option>
                        <option value="en_attente">⏳ En attente</option>
                    </select>
                </div>
                <div class="workflow-form-group">
                    <label for="status-notes">Notes (optionnel) :</label>
                    <textarea name="notes" id="status-notes" rows="3" placeholder="Notes sur le changement de statut..."></textarea>
                </div>
                <div class="my-istymo-modal-actions">
                    <button type="submit" class="my-istymo-btn my-istymo-btn-primary">Changer le statut</button>
                    <button type="button" class="my-istymo-btn my-istymo-btn-secondary my-istymo-modal-close">Annuler</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php
}
