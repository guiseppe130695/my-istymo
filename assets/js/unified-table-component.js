/**
 * Composant de tableau unifié - JavaScript
 * Fonctionnalités communes pour tous les tableaux
 * Basé sur le design des leads mais réutilisable
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Configuration globale
    const TABLE_CONFIG = {
        animationDuration: 200,
        menuTimeout: 200,
        zIndex: 10000
    };
    
    // Variables globales
    let menuTimeout;
    let activeMenus = new Set();
    
    /**
     * Initialisation du composant de tableau
     */
    function initUnifiedTable() {
        setupEventListeners();
        setupResponsiveBehavior();
        setupAccessibility();
    }
    
    /**
     * Configuration des écouteurs d'événements
     */
    function setupEventListeners() {
        // Gestion des menus dropdown
        setupDropdownMenus();
        
        // Gestion des checkboxes
        setupCheckboxes();
        
        // Gestion des filtres
        setupFilters();
        
      // Gestion du tri des colonnes
        setupColumnSorting();
        
        // Gestion des actions en lot
        setupBulkActions();
        
        // Gestion de la pagination
        setupPagination();
    }
    
    /**
     * Configuration des menus dropdown
     */
    function setupDropdownMenus() {
        // Plus de gestion de menus déroulants - remplacé par des boutons directs
        console.log('Actions menu setup: Using direct buttons instead of dropdown menus');
    }
    
    /**
     * Positionner un menu intelligemment
     */
    function positionMenu(menuContainer) {
        const menu = menuContainer.find('.my-istymo-dropdown-menu');
        const button = menuContainer.find('.my-istymo-menu-trigger');
        
        menu.addClass('menu-fixed');
        
        // Temporairement afficher le menu pour mesurer ses dimensions
        menu.css({
            visibility: 'hidden',
            display: 'block',
            position: 'fixed'
        });
        
        const menuHeight = menu.outerHeight();
        const menuWidth = menu.outerWidth();
        
        menu.css({
            visibility: '',
            display: ''
        });
        
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
            'z-index': TABLE_CONFIG.zIndex
        });
    }
    
    /**
     * Fermer tous les menus ouverts
     */
    function closeAllMenus() {
        $('.my-istymo-dropdown-menu').removeClass('show');
        activeMenus.clear();
    }
    
    /**
     * Configuration des checkboxes
     */
    function setupCheckboxes() {
        // Gestion de la sélection multiple
        $(document).on('change', '.my-istymo-select-all', function() {
            const isChecked = $(this).is(':checked');
            const table = $(this).closest('table');
            table.find('.my-istymo-item-checkbox').prop('checked', isChecked);
            updateBulkActionsVisibility();
        });
        
        // Vérifier si tous les éléments sont sélectionnés
        $(document).on('change', '.my-istymo-item-checkbox', function() {
            const table = $(this).closest('table');
            const totalCheckboxes = table.find('.my-istymo-item-checkbox').length;
            const checkedCheckboxes = table.find('.my-istymo-item-checkbox:checked').length;
            
            table.find('.my-istymo-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
            updateBulkActionsVisibility();
        });
    }
    
    /**
     * Configuration des filtres
     */
    function setupFilters() {
        // Gestion de la soumission des filtres
        $(document).on('submit', '.my-istymo-inline-filters', function(e) {
            // Permettre la soumission normale pour les filtres
            // Le comportement peut être surchargé par des gestionnaires spécifiques
        });
        
        // Gestion de la réinitialisation des filtres
        $(document).on('click', '.my-istymo-filter-reset-btn', function(e) {
            // Le comportement par défaut est de naviguer vers l'URL sans paramètres
            // Peut être surchargé si nécessaire
        });
        
        // Filtres en temps réel (optionnel)
        $(document).on('input', '.my-istymo-filter-input', function() {
            const filterInput = $(this);
            const filterValue = filterInput.val().toLowerCase();
            const table = filterInput.closest('.my-istymo-table-container').find('table');
            const columnIndex = filterInput.data('column-index');
            
            if (filterValue.length > 2 || filterValue.length === 0) {
                filterTableRows(table, columnIndex, filterValue);
            }
        });
    }
    
    /**
     * Filtrer les lignes du tableau en temps réel
     */
    function filterTableRows(table, columnIndex, filterValue) {
        const rows = table.find('tbody tr');
        
        rows.each(function() {
            const row = $(this);
            const cell = row.find(`td:eq(${columnIndex})`);
            const cellText = cell.text().toLowerCase();
            
            if (filterValue === '' || cellText.includes(filterValue)) {
                row.show();
            } else {
                row.hide();
            }
        });
        
        updateEmptyState(table);
    }
    
    /**
     * Configuration du tri des colonnes
     */
    function setupColumnSorting() {
        $(document).on('click', '.my-istymo-sortable', function() {
            const column = $(this);
            const table = column.closest('table');
            const columnIndex = column.index();
            const currentSort = column.data('sort') || 'none';
            
            // Toggle du tri
            const newSort = currentSort === 'asc' ? 'desc' : 'asc';
            
            // Mettre à jour l'indicateur de tri
            table.find('.my-istymo-sortable').removeClass('sort-asc sort-desc').data('sort', 'none');
            column.addClass(`sort-${newSort}`).data('sort', newSort);
            
            // Trier le tableau
            sortTable(table, columnIndex, newSort);
        });
    }
    
    /**
     * Trier le tableau
     */
    function sortTable(table, columnIndex, direction) {
        const tbody = table.find('tbody');
        const rows = tbody.find('tr').toArray();
        
        rows.sort(function(a, b) {
            const aValue = $(a).find(`td:eq(${columnIndex})`).text().trim();
            const bValue = $(b).find(`td:eq(${columnIndex})`).text().trim();
            
            // Essayer de convertir en nombres si possible
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return direction === 'asc' ? aNum - bNum : bNum - aNum;
            }
            
            // Tri alphabétique
            if (direction === 'asc') {
                return aValue.localeCompare(bValue);
            } else {
                return bValue.localeCompare(aValue);
            }
        });
        
        // Réorganiser les lignes
        tbody.empty().append(rows);
    }
    
    /**
     * Configuration des actions en lot
     */
    function setupBulkActions() {
        // Mettre à jour la visibilité des actions en lot
        updateBulkActionsVisibility();
        
        // Gestion des actions en lot
        $(document).on('click', '.my-istymo-bulk-action', function(e) {
            e.preventDefault();
            
            const action = $(this).data('action');
            const selectedItems = getSelectedItems();
            
            if (selectedItems.length === 0) {
                showNotification('Veuillez sélectionner au moins un élément', 'warning');
                return;
            }
            
            if (confirm(`Êtes-vous sûr de vouloir effectuer cette action sur ${selectedItems.length} élément(s) ?`)) {
                executeBulkAction(action, selectedItems);
            }
        });
    }
    
    /**
     * Obtenir les éléments sélectionnés
     */
    function getSelectedItems() {
        const selectedItems = [];
        $('.my-istymo-item-checkbox:checked').each(function() {
            selectedItems.push($(this).val());
        });
        return selectedItems;
    }
    
    /**
     * Mettre à jour la visibilité des actions en lot
     */
    function updateBulkActionsVisibility() {
        const selectedCount = $('.my-istymo-item-checkbox:checked').length;
        const bulkActions = $('.my-istymo-bulk-actions');
        
        if (selectedCount > 0) {
            bulkActions.show();
            bulkActions.find('.my-istymo-selected-count').text(selectedCount);
        } else {
            bulkActions.hide();
        }
    }
    
    /**
     * Exécuter une action en lot
     */
    function executeBulkAction(action, items) {
        // Cette fonction peut être surchargée selon les besoins
        console.log(`Exécution de l'action "${action}" sur ${items.length} éléments:`, items);
        
        // Exemple d'implémentation AJAX
        if (typeof unifiedTableAjax !== 'undefined') {
            $.ajax({
                url: unifiedTableAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_bulk_action',
                    bulk_action: action,
                    items: items,
                    nonce: unifiedTableAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Action effectuée avec succès', 'success');
                        // Recharger le tableau ou mettre à jour les données
                        location.reload();
                    } else {
                        showNotification('Erreur lors de l\'exécution de l\'action', 'error');
                    }
                },
                error: function() {
                    showNotification('Erreur de communication avec le serveur', 'error');
                }
            });
        }
    }
    
    /**
     * Configuration de la pagination
     */
    function setupPagination() {
        // Gestion des liens de pagination
        $(document).on('click', '.tablenav-pages a', function(e) {
            // Le comportement par défaut est de naviguer vers la nouvelle page
            // Peut être surchargé pour AJAX si nécessaire
        });
    }
    
    /**
     * Configuration du comportement responsive
     */
    function setupResponsiveBehavior() {
        // Gestion du responsive pour mobile
        if (window.innerWidth <= 768) {
            setupMobileBehavior();
        }
        
        // Écouter les changements de taille d'écran
        $(window).on('resize', function() {
            if (window.innerWidth <= 768) {
                setupMobileBehavior();
            } else {
                setupDesktopBehavior();
            }
        });
    }
    
    /**
     * Configuration pour mobile
     */
    function setupMobileBehavior() {
        // Adapter l'affichage du tableau pour mobile
        $('.my-istymo-unified-table').addClass('mobile-view');
    }
    
    /**
     * Configuration pour desktop
     */
    function setupDesktopBehavior() {
        // Restaurer le comportement desktop
        $('.my-istymo-unified-table').removeClass('mobile-view');
    }
    
    /**
     * Configuration de l'accessibilité
     */
    function setupAccessibility() {
        // Gestion du clavier pour les menus
        $(document).on('keydown', '.my-istymo-menu-trigger', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
        
        // Gestion du clavier pour les checkboxes
        $(document).on('keydown', '.my-istymo-item-checkbox, .my-istymo-select-all', function(e) {
            if (e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
        
        // Ajouter les attributs ARIA
        $('.my-istymo-menu-trigger').attr({
            'aria-haspopup': 'true',
            'aria-expanded': 'false'
        });
        
        $('.my-istymo-dropdown-menu').attr({
            'role': 'menu',
            'aria-hidden': 'true'
        });
        
        // Mettre à jour les attributs ARIA lors de l'ouverture/fermeture des menus
        $(document).on('click', '.my-istymo-menu-trigger', function() {
            const menu = $(this).siblings('.my-istymo-dropdown-menu');
            const isVisible = menu.hasClass('show');
            
            $(this).attr('aria-expanded', isVisible);
            menu.attr('aria-hidden', !isVisible);
        });
    }
    
    /**
     * Mettre à jour l'état vide du tableau
     */
    function updateEmptyState(table) {
        const visibleRows = table.find('tbody tr:visible').length;
        const emptyState = table.siblings('.no-items');
        
        if (visibleRows === 0) {
            if (emptyState.length === 0) {
                table.after('<div class="no-items"><p>Aucun élément trouvé avec les critères actuels.</p></div>');
            }
            emptyState.show();
        } else {
            emptyState.hide();
        }
    }
    
    /**
     * Afficher une notification
     */
    function showNotification(message, type = 'info') {
        // Supprimer les notifications existantes
        $('.my-istymo-notification').remove();
        
        // Créer la notification
        const notification = $(`
            <div class="my-istymo-notification ${type}">
                <span class="my-istymo-notification-message">${message}</span>
                <button class="my-istymo-notification-close" aria-label="Fermer">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `);
        
        // Ajouter au body
        $('body').append(notification);
        
        // Afficher avec animation
        setTimeout(function() {
            notification.addClass('show');
        }, 100);
        
        // Gestion de la fermeture
        notification.find('.my-istymo-notification-close').on('click', function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        });
        
        // Masquer automatiquement après 5 secondes
        setTimeout(function() {
            if (notification.hasClass('show')) {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }
        }, 5000);
    }
    
    /**
     * Fonction utilitaire pour formater les dates
     */
    function formatDate(dateString, format = 'd/m/Y') {
        if (!dateString) return '—';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '—';
        
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        
        return format.replace('d', day).replace('m', month).replace('Y', year);
    }
    
    /**
     * Fonction utilitaire pour formater les nombres
     */
    function formatNumber(number, decimals = 0) {
        if (isNaN(number)) return '—';
        
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    }
    
    /**
     * Fonction utilitaire pour tronquer le texte
     */
    function truncateText(text, maxLength = 50) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    /**
     * Fonction utilitaire pour valider les données
     */
    function validateData(data, rules) {
        const errors = [];
        
        for (const [field, rule] of Object.entries(rules)) {
            const value = data[field];
            
            if (rule.required && (!value || value.trim() === '')) {
                errors.push(`Le champ "${rule.label}" est requis`);
            }
            
            if (rule.minLength && value && value.length < rule.minLength) {
                errors.push(`Le champ "${rule.label}" doit contenir au moins ${rule.minLength} caractères`);
            }
            
            if (rule.maxLength && value && value.length > rule.maxLength) {
                errors.push(`Le champ "${rule.label}" ne peut pas dépasser ${rule.maxLength} caractères`);
            }
            
            if (rule.pattern && value && !rule.pattern.test(value)) {
                errors.push(`Le champ "${rule.label}" n'est pas au bon format`);
            }
        }
        
        return errors;
    }
    
    /**
     * Fonction utilitaire pour débouncer les appels
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Fonction utilitaire pour throttler les appels
     */
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    // Initialisation du composant
    initUnifiedTable();
    
    // Exposer les fonctions utilitaires globalement
    window.UnifiedTableUtils = {
        formatDate,
        formatNumber,
        truncateText,
        validateData,
        debounce,
        throttle,
        showNotification,
        getSelectedItems,
        updateBulkActionsVisibility
    };
    
    // Exposer les fonctions pour les actions personnalisées
    window.UnifiedTableActions = {
        executeBulkAction,
        filterTableRows,
        sortTable,
        closeAllMenus
    };
});
