<?php
if (!defined('ABSPATH')) exit;

/**
 * Composant de tableau unifié réutilisable
 * Basé sur le design des leads mais adaptable à tous types de données
 * 
 * @param array $config Configuration du tableau
 * @param array $data Données à afficher
 * @param array $context Contexte d'utilisation
 */
function unified_table_component($config = array(), $data = array(), $context = array()) {
    // Configuration par défaut
    $default_config = array(
        'title' => '📋 Tableau de données',
        'columns' => array(),
        'show_filters' => true,
        'show_actions' => true,
        'show_checkboxes' => true,
        'per_page' => 20,
        'is_shortcode' => false,
        'table_id' => 'unified-table',
        'filters' => array(),
        'actions' => array(),
        'empty_message' => 'Aucune donnée trouvée avec les critères actuels.',
        'empty_action_text' => 'Voir toutes les données',
        'empty_action_url' => ''
    );
    
    $config = wp_parse_args($config, $default_config);
    
    // Contexte par défaut
    $default_context = array(
        'page_slug' => '',
        'shortcode_id' => '',
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_nonce')
    );
    
    $context = wp_parse_args($context, $default_context);
    
    // Pagination
    $page = max(1, intval($_GET['paged'] ?? 1));
    $per_page = $config['per_page'];
    $offset = ($page - 1) * $per_page;
    
    $total_items = count($data);
    $items = array_slice($data, $offset, $per_page);
    $total_pages = ceil($total_items / $per_page);
    
    ?>
    <div class="wrap unified-table-container my-istymo">
        <h1><?php echo esc_html($config['title']); ?></h1>
        
        <?php if (!$config['is_shortcode']): ?>
        <div class="notice notice-info">
            <p><strong>Interface de Gestion</strong> - Gérez vos données avec filtres, actions en lot et suivi des statuts.</p>
        </div>
        <?php endif; ?>
        
        <!-- Tableau unifié moderne -->
        <div class="my-istymo-table-container">
            <!-- En-tête avec contrôles intégrés -->
            <div class="my-istymo-table-header">
                <div class="my-istymo-header-left">
                    
                    <?php if ($config['show_filters'] && !empty($config['filters'])): ?>
                    <!-- Filtres intégrés -->
                    <form method="get" class="my-istymo-inline-filters" style="display: flex; align-items: center; gap: 12px;" id="<?php echo $config['is_shortcode'] ? 'shortcode-filters-' . $context['shortcode_id'] : 'admin-filters'; ?>">
                        <?php if (!$config['is_shortcode'] && !empty($context['page_slug'])): ?>
                        <input type="hidden" name="page" value="<?php echo esc_attr($context['page_slug']); ?>">
                        <?php elseif ($config['is_shortcode']): ?>
                        <input type="hidden" name="shortcode_id" value="<?php echo esc_attr($context['shortcode_id']); ?>">
                        <?php endif; ?>
                
                        <?php foreach ($config['filters'] as $filter_key => $filter_config): ?>
                        <div class="my-istymo-filter-group">
                            <?php if ($filter_config['type'] === 'select'): ?>
                                <select name="<?php echo esc_attr($filter_key); ?>" class="my-istymo-filter-select">
                                    <option value=""><?php echo esc_html($filter_config['placeholder'] ?? 'Tous'); ?></option>
                                    <?php foreach ($filter_config['options'] as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($_GET[$filter_key] ?? '', $value); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($filter_config['type'] === 'date'): ?>
                                <input type="date" name="<?php echo esc_attr($filter_key); ?>" class="my-istymo-filter-input" value="<?php echo esc_attr($_GET[$filter_key] ?? ''); ?>" placeholder="<?php echo esc_attr($filter_config['placeholder'] ?? ''); ?>">
                            <?php elseif ($filter_config['type'] === 'text'): ?>
                                <input type="text" name="<?php echo esc_attr($filter_key); ?>" class="my-istymo-filter-input" value="<?php echo esc_attr($_GET[$filter_key] ?? ''); ?>" placeholder="<?php echo esc_attr($filter_config['placeholder'] ?? ''); ?>">
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    
                        <!-- Boutons d'action des filtres -->
                        <div class="my-istymo-filter-actions">
                            <button type="submit" class="my-istymo-btn my-istymo-btn-primary">
                                <span class="dashicons dashicons-filter"></span> Filtrer
                            </button>
                            <?php 
                            $has_filters = false;
                            foreach ($config['filters'] as $filter_key => $filter_config) {
                                if (!empty($_GET[$filter_key])) {
                                    $has_filters = true;
                                    break;
                                }
                            }
                            if ($has_filters): 
                            ?>
                            <a href="<?php echo esc_url(remove_query_arg(array_keys($config['filters']))); ?>" class="my-istymo-filter-reset-btn">
                                <span class="dashicons dashicons-dismiss"></span> Réinitialiser
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($items)): ?>
                <div class="my-istymo-modern-table">
                    <table class="my-istymo-unified-table" id="<?php echo esc_attr($config['table_id']); ?>">
                        <thead>
                            <tr>
                                <?php if ($config['show_checkboxes']): ?>
                                <th class="my-istymo-th-checkbox">
                                    <input type="checkbox" class="my-istymo-select-all">
                                </th>
                                <?php endif; ?>
                                
                                <?php foreach ($config['columns'] as $column_key => $column_config): ?>
                                <th class="my-istymo-th-<?php echo esc_attr($column_key); ?>" <?php echo isset($column_config['width']) ? 'style="width: ' . esc_attr($column_config['width']) . ';"' : ''; ?>>
                                    <?php if (!empty($column_config['icon'])): ?>
                                    <span class="dashicons dashicons-<?php echo esc_attr($column_config['icon']); ?>"></span>
                                    <?php endif; ?>
                                    <?php echo esc_html($column_config['label']); ?>
                                </th>
                                <?php endforeach; ?>
                                
                                <?php if ($config['show_actions']): ?>
                                <th class="my-istymo-th-actions"></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr class="my-istymo-table-row" data-item-id="<?php echo esc_attr($item->id ?? $item['id'] ?? ''); ?>">
                                    <?php if ($config['show_checkboxes']): ?>
                                    <td class="my-istymo-td-checkbox">
                                        <input type="checkbox" class="my-istymo-item-checkbox" value="<?php echo esc_attr($item->id ?? $item['id'] ?? ''); ?>">
                                    </td>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($config['columns'] as $column_key => $column_config): ?>
                                    <td class="my-istymo-td-<?php echo esc_attr($column_key); ?>">
                                        <?php echo render_table_cell($item, $column_key, $column_config); ?>
                                    </td>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($config['show_actions']): ?>
                                    <td class="my-istymo-td-actions">
                                        <div class="my-istymo-actions-buttons">
                                            <?php foreach ($config['actions'] as $action_key => $action_config): ?>
                                            <button class="my-istymo-action-btn <?php echo esc_attr($action_key); ?>" data-item-id="<?php echo esc_attr($item->id ?? $item['id'] ?? ''); ?>" onclick="<?php echo esc_attr($action_config['onclick'] ?? ''); ?> return false;" title="<?php echo esc_attr($action_config['label']); ?>">
                                                <span class="dashicons dashicons-<?php echo esc_attr($action_config['icon'] ?? 'admin-generic'); ?>"></span> <?php echo esc_html($action_config['label']); ?>
                                            </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo $total_items; ?> éléments</span>
                        
                        <?php
                        if ($config['is_shortcode']) {
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
                <div class="no-items">
                    <p><?php echo esc_html($config['empty_message']); ?></p>
                    <?php if (!$config['is_shortcode'] && !empty($config['empty_action_url'])): ?>
                    <a href="<?php echo esc_url($config['empty_action_url']); ?>" class="button button-primary"><?php echo esc_html($config['empty_action_text']); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    // Charger les styles et scripts
    if (!wp_style_is('unified-leads-css', 'enqueued')) {
        wp_enqueue_style('unified-leads-css', plugin_dir_url(__FILE__) . '../assets/css/unified-leads.css', array(), '1.0.0');
    }
    if (!wp_script_is('unified-table-component', 'enqueued')) {
        wp_enqueue_script('unified-table-component', plugin_dir_url(__FILE__) . '../assets/js/unified-table-component.js', array('jquery'), '1.0.0', true);
    }
    
    // Localiser les scripts
    if (!wp_script_is('unified-table-component', 'localized')) {
        wp_localize_script('unified-table-component', 'unifiedTableAjax', array(
            'ajaxurl' => $context['ajax_url'],
            'nonce' => $context['nonce'],
            'table_id' => $config['table_id']
        ));
    }
    
    // Script spécifique pour les filtres en mode shortcode
    if ($config['is_shortcode']) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Gestion des filtres en mode shortcode
            $('#<?php echo 'shortcode-filters-' . $context['shortcode_id']; ?>').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                var currentUrl = window.location.href.split('?')[0];
                var newUrl = currentUrl + '?' + formData;
                
                // Mettre à jour l'URL sans recharger la page
                window.history.pushState({}, '', newUrl);
                
                // Recharger le contenu via AJAX ou recharger la page
                window.location.reload();
            });
            
            // Préserver les valeurs des filtres après rechargement
            var urlParams = new URLSearchParams(window.location.search);
            <?php foreach ($config['filters'] as $filter_key => $filter_config): ?>
            var <?php echo esc_js($filter_key); ?> = urlParams.get('<?php echo esc_js($filter_key); ?>');
            if (<?php echo esc_js($filter_key); ?>) {
                $('<?php echo $filter_config['type'] === 'select' ? 'select' : 'input'; ?>[name="<?php echo esc_js($filter_key); ?>"]').val(<?php echo esc_js($filter_key); ?>);
            }
            <?php endforeach; ?>
        });
        </script>
        <?php
    }
    ?>
    
    <!-- Script pour le design du tableau -->
    <script>
    jQuery(document).ready(function($) {
        // Gestion des menus dropdown avec survol et clic
        let menuTimeout;
        
        // Fonction pour positionner le menu intelligemment
        function positionMenu(menuContainer) {
            const menu = menuContainer.find('.my-istymo-dropdown-menu');
            const button = menuContainer.find('.my-istymo-menu-trigger');
            
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
            if (top + menuHeight > windowHeight - 20) {
                top = buttonRect.top - menuHeight - 5;
            }
            
            if (left < 10) {
                left = buttonRect.left;
            }
            
            if (left + menuWidth > windowWidth - 10) {
                left = buttonRect.left - menuWidth + buttonRect.width;
            }
            
            // Dernière vérification - forcer dans la fenêtre
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
        }
        
        
        
        
        // Gestion de la sélection multiple
        $('.my-istymo-select-all').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.my-istymo-item-checkbox').prop('checked', isChecked);
        });
        
        // Vérifier si tous les éléments sont sélectionnés
        $('.my-istymo-item-checkbox').on('change', function() {
            const totalCheckboxes = $('.my-istymo-item-checkbox').length;
            const checkedCheckboxes = $('.my-istymo-item-checkbox:checked').length;
            
            $('.my-istymo-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        });
    });
    </script>
    
    <?php
}

/**
 * Fonction pour rendre une cellule de tableau selon sa configuration
 * 
 * @param mixed $item L'élément de données
 * @param string $column_key La clé de la colonne
 * @param array $column_config La configuration de la colonne
 * @return string HTML de la cellule
 */
function render_table_cell($item, $column_key, $column_config) {
    $value = '';
    
    // Extraire la valeur selon le type d'objet
    if (is_object($item)) {
        $value = $item->$column_key ?? '';
    } elseif (is_array($item)) {
        $value = $item[$column_key] ?? '';
    }
    
    // Appliquer le formatage selon le type de cellule
    switch ($column_config['type'] ?? 'text') {
        case 'badge':
            return render_badge_cell($value, $column_config);
            
        case 'icon_text':
            return render_icon_text_cell($value, $column_config, $item);
            
        case 'date':
            return render_date_cell($value, $column_config);
            
        case 'status':
            return render_status_cell($value, $column_config);
            
        case 'priority':
            return render_priority_cell($value, $column_config);
            
        case 'company':
            return render_company_cell($value, $column_config, $item);
            
        default:
            return '<div class="my-istymo-cell-text">' . esc_html($value) . '</div>';
    }
}

/**
 * Rendre une cellule avec badge
 */
function render_badge_cell($value, $config) {
    $badge_class = $config['badge_class'] ?? 'default';
    $badge_text = $config['badge_text'] ?? $value;
    
    return '<span class="my-istymo-badge my-istymo-badge-' . esc_attr($badge_class) . '">' . esc_html($badge_text) . '</span>';
}

/**
 * Rendre une cellule avec icône et texte
 */
function render_icon_text_cell($value, $config, $item) {
    $icon = $config['icon'] ?? 'admin-generic';
    $text = $config['text'] ?? $value;
    
    return '<div class="my-istymo-icon-text-cell">
                <span class="dashicons dashicons-' . esc_attr($icon) . '"></span>
                <span>' . esc_html($text) . '</span>
            </div>';
}

/**
 * Rendre une cellule de date
 */
function render_date_cell($value, $config) {
    if (empty($value)) return '—';
    
    $format = $config['format'] ?? 'd/m/Y';
    $date = is_numeric($value) ? date($format, $value) : date($format, strtotime($value));
    
    return '<div class="my-istymo-date-cell">' . esc_html($date) . '</div>';
}

/**
 * Rendre une cellule de statut
 */
function render_status_cell($value, $config) {
    $status_map = $config['status_map'] ?? array();
    $status_config = $status_map[$value] ?? array('class' => 'default', 'text' => $value);
    
    return '<span class="my-istymo-status-badge my-istymo-status-' . esc_attr($status_config['class']) . '">
                <span class="my-istymo-status-dot"></span>
                ' . esc_html($status_config['text']) . '
            </span>';
}

/**
 * Rendre une cellule de priorité
 */
function render_priority_cell($value, $config) {
    $priority_map = $config['priority_map'] ?? array();
    $priority_config = $priority_map[$value] ?? array('class' => 'normal', 'text' => $value);
    
    return '<span class="my-istymo-priority-badge my-istymo-priority-' . esc_attr($priority_config['class']) . '">
                <span class="my-istymo-priority-dot"></span>
                ' . esc_html($priority_config['text']) . '
            </span>';
}

/**
 * Rendre une cellule d'entreprise
 */
function render_company_cell($value, $config, $item) {
    $icon = $config['icon'] ?? 'admin-home';
    $subtitle = $config['subtitle'] ?? '';
    
    // Extraire le sous-titre si c'est une propriété
    if (!empty($subtitle) && is_object($item)) {
        $subtitle_value = $item->$subtitle ?? '';
    } elseif (!empty($subtitle) && is_array($item)) {
        $subtitle_value = $item[$subtitle] ?? '';
    } else {
        $subtitle_value = '';
    }
    
    return '<div class="my-istymo-company-cell">
                <div class="my-istymo-company-icon">
                    <span class="dashicons dashicons-' . esc_attr($icon) . '"></span>
                </div>
                <div class="my-istymo-company-info">
                    <div class="my-istymo-company-name">' . esc_html($value) . '</div>
                    ' . (!empty($subtitle_value) ? '<div class="my-istymo-company-id">' . esc_html($subtitle_value) . '</div>' : '') . '
                </div>
            </div>';
}
