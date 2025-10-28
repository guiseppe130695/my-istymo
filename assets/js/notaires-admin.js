/**
 * Scripts JavaScript pour l'Annuaire Notarial
 * 
 * @package My_Istymo
 * @subpackage Notaires
 * @version 1.0
 * @author Brio Guiseppe
 */

(function($) {
    'use strict';
    
    // Variables globales
    let notairesAjax = {
        ajaxurl: ajaxurl || '/wp-admin/admin-ajax.php',
        nonce: ''
    };
    
    // Initialisation
    $(document).ready(function() {
        initNotairesAdmin();
    });
    
    /**
     * Initialise l'interface d'administration des notaires
     */
    function initNotairesAdmin() {
        console.log('🏛️ Initialisation Annuaire Notarial');
        
        // Récupérer le nonce depuis le DOM
        notairesAjax.nonce = $('meta[name="notaires-nonce"]').attr('content') || 
                            $('#notaires-nonce').val() || 
                            window.notairesNonce || '';
        
        if (!notairesAjax.nonce) {
            console.error('❌ Nonce non trouvé pour l\'Annuaire Notarial');
            return;
        }
        
        // Initialiser les gestionnaires d'événements
        initEventHandlers();
        
        // Initialiser les filtres
        initFilters();
        
        // Initialiser les modals
        initModals();
        
        // Initialiser les animations
        initAnimations();
        
        console.log('✅ Annuaire Notarial initialisé avec succès');
    }
    
    /**
     * Initialise les gestionnaires d'événements
     */
    function initEventHandlers() {
        // Gestionnaire pour les favoris
        $(document).on('click', '.favorite-toggle', handleFavoriteToggle);
        
        // Gestionnaire pour voir les détails
        $(document).on('click', '.view-details', handleViewDetails);
        
        // Gestionnaire pour fermer les modals
        $(document).on('click', '.my-istymo-modal-close, .my-istymo-modal', handleModalClose);
        
        // Gestionnaire pour les liens de contact
        $(document).on('click', '.phone-link, .email-link', handleContactClick);
        
        // Gestionnaire pour l'export des favoris
        $(document).on('click', '.export-favorites-btn', handleExportFavorites);
        
        // Gestionnaire pour la recherche en temps réel
        $(document).on('input', '#search', debounce(handleSearch, 500));
        
        // Gestionnaire pour les changements de filtres
        $(document).on('change', '.my-istymo-filter-group select', handleFilterChange);
        
        // Gestionnaire pour la pagination
        $(document).on('click', '.my-istymo-pagination .button', handlePagination);
        
        // Gestionnaire pour les raccourcis clavier
        $(document).on('keydown', handleKeyboardShortcuts);
        
        console.log('✅ Gestionnaires d\'événements initialisés');
    }
    
    /**
     * Gère le basculement des favoris
     */
    function handleFavoriteToggle(e) {
        e.preventDefault();
        
        const button = $(this);
        const notaireId = button.data('notaire-id');
        
        if (!notaireId) {
            showNotification('ID notaire manquant', 'error');
            return;
        }
        
        // Désactiver le bouton pendant la requête
        button.prop('disabled', true);
        const originalContent = button.html();
        
        // Animation de chargement
        button.html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span>');
        
        $.ajax({
            url: notairesAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_notaire_favorite',
                notaire_id: notaireId,
                nonce: notairesAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateFavoriteButton(button, response.data.is_favorite);
                    updateFavoritesCount();
                    showNotification(response.data.message, 'success');
                    
                    // Animation de succès
                    button.addClass('success-animation');
                    setTimeout(() => button.removeClass('success-animation'), 1000);
                } else {
                    showNotification('Erreur : ' + response.data.message, 'error');
                    button.html(originalContent);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX favoris:', error);
                showNotification('Erreur de communication avec le serveur', 'error');
                button.html(originalContent);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    }
    
    /**
     * Met à jour l'apparence du bouton favori
     */
    function updateFavoriteButton(button, isFavorite) {
        const icon = button.find('.dashicons');
        
        if (isFavorite) {
            button.addClass('favorited');
            icon.removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
            button.attr('title', 'Supprimer des favoris');
        } else {
            button.removeClass('favorited');
            icon.removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
            button.attr('title', 'Ajouter aux favoris');
        }
        
        // Mettre à jour le texte si présent
        const textSpan = button.find('span:not(.dashicons)');
        if (textSpan.length) {
            textSpan.text(isFavorite ? 'Supprimer des favoris' : 'Ajouter aux favoris');
        }
    }
    
    /**
     * Gère l'affichage des détails d'un notaire
     */
    function handleViewDetails(e) {
        e.preventDefault();
        
        const button = $(this);
        const notaireId = button.data('notaire-id');
        
        if (!notaireId) {
            showNotification('ID notaire manquant', 'error');
            return;
        }
        
        showNotaireDetails(notaireId);
    }
    
    /**
     * Affiche les détails d'un notaire dans un modal
     */
    function showNotaireDetails(notaireId) {
        const modal = $('#notaire-detail-modal');
        const content = $('#notaire-detail-content');
        
        // Afficher le modal avec animation
        modal.show();
        content.html('<div class="loading"><span class="dashicons dashicons-update"></span> Chargement des détails...</div>');
        
        $.ajax({
            url: notairesAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_notaire_details',
                notaire_id: notaireId,
                nonce: notairesAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data.html);
                    
                    // Réinitialiser les gestionnaires d'événements dans le modal
                    initModalEventHandlers();
                } else {
                    content.html('<div class="error-message"><p>Erreur lors du chargement des détails</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX détails:', error);
                content.html('<div class="error-message"><p>Erreur de communication avec le serveur</p></div>');
            }
        });
    }
    
    /**
     * Initialise les gestionnaires d'événements dans le modal
     */
    function initModalEventHandlers() {
        // Gestionnaire pour les favoris dans le modal
        $('.notaire-details-actions .favorite-toggle').off('click').on('click', handleFavoriteToggle);
    }
    
    /**
     * Gère la fermeture des modals
     */
    function handleModalClose(e) {
        if (e.target === this) {
            $('.my-istymo-modal').hide();
        }
    }
    
    /**
     * Gère les clics sur les liens de contact
     */
    function handleContactClick(e) {
        const link = $(this);
        const type = link.hasClass('phone-link') ? 'téléphone' : 'email';
        const value = link.text().trim();
        
        // Analytics ou tracking si nécessaire
        console.log(`Contact ${type} cliqué: ${value}`);
        
        // Pas de preventDefault pour permettre l'action native
    }
    
    /**
     * Gère l'export des favoris
     */
    function handleExportFavorites(e) {
        e.preventDefault();
        
        if (!confirm('Voulez-vous exporter vos favoris au format CSV ?')) {
            return;
        }
        
        const button = $(this);
        const originalText = button.text();
        
        // Animation de chargement
        button.prop('disabled', true).text('Export en cours...');
        
        $.ajax({
            url: notairesAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'export_notaires_favorites',
                nonce: notairesAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    downloadCSV(response.data.csv_content, response.data.filename);
                    showNotification(`Export réussi : ${response.data.count} notaires exportés`, 'success');
                } else {
                    showNotification('Erreur lors de l\'export : ' + response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX export:', error);
                showNotification('Erreur de communication avec le serveur', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    /**
     * Télécharge un fichier CSV
     */
    function downloadCSV(content, filename) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }
    }
    
    /**
     * Gère la recherche en temps réel
     */
    function handleSearch(e) {
        const searchTerm = $(this).val();
        
        if (searchTerm.length >= 2 || searchTerm.length === 0) {
            applyFilters();
        }
    }
    
    /**
     * Gère les changements de filtres
     */
    function handleFilterChange() {
        applyFilters();
    }
    
    /**
     * Applique les filtres et recharge les données
     */
    function applyFilters() {
        const filters = {
            ville: $('select[name="ville"]').val(),
            langue: $('select[name="langue"]').val(),
            statut: $('select[name="statut"]').val(),
            search: $('input[name="search"]').val()
        };
        
        // Supprimer les filtres vides
        Object.keys(filters).forEach(key => {
            if (!filters[key]) {
                delete filters[key];
            }
        });
        
        loadNotaires(filters, 1);
    }
    
    /**
     * Charge les notaires avec filtres et pagination
     */
    function loadNotaires(filters = {}, page = 1) {
        const tableContainer = $('.my-istymo-modern-table');
        
        // Afficher l'indicateur de chargement
        tableContainer.html('<div class="loading"><span class="dashicons dashicons-update"></span> Chargement des notaires...</div>');
        
        const data = {
            action: 'filter_notaires',
            nonce: notairesAjax.nonce,
            paged: page
        };
        
        // Ajouter les filtres
        Object.assign(data, filters);
        
        $.ajax({
            url: notairesAjax.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    tableContainer.html(response.data.html);
                    
                    // Mettre à jour la pagination
                    updatePagination(response.data.page, response.data.total_pages, response.data.total);
                    
                    // Réinitialiser les gestionnaires d'événements
                    initTableEventHandlers();
                    
                    console.log(`✅ ${response.data.total} notaires chargés`);
                } else {
                    tableContainer.html('<div class="error-message"><p>Erreur lors du chargement des notaires</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX chargement:', error);
                tableContainer.html('<div class="error-message"><p>Erreur de communication avec le serveur</p></div>');
            }
        });
    }
    
    /**
     * Met à jour la pagination
     */
    function updatePagination(currentPage, totalPages, totalItems) {
        const pagination = $('.my-istymo-pagination');
        const info = pagination.find('.pagination-info');
        
        if (info.length) {
            info.text(`Page ${currentPage} sur ${totalPages} (${totalItems} notaires au total)`);
        }
        
        // Mettre à jour les liens de pagination
        pagination.find('.button').each(function() {
            const button = $(this);
            const page = button.data('page') || parseInt(button.text());
            
            if (page === currentPage) {
                button.addClass('button-primary').removeClass('button');
            } else {
                button.removeClass('button-primary').addClass('button');
            }
        });
    }
    
    /**
     * Gère la pagination
     */
    function handlePagination(e) {
        e.preventDefault();
        
        const button = $(this);
        const page = button.data('page') || parseInt(button.text());
        
        if (page && page > 0) {
            const filters = getCurrentFilters();
            loadNotaires(filters, page);
            
            // Scroll vers le haut du tableau
            $('.my-istymo-table-section')[0].scrollIntoView({ behavior: 'smooth' });
        }
    }
    
    /**
     * Récupère les filtres actuels
     */
    function getCurrentFilters() {
        return {
            ville: $('select[name="ville"]').val(),
            langue: $('select[name="langue"]').val(),
            statut: $('select[name="statut"]').val(),
            search: $('input[name="search"]').val()
        };
    }
    
    /**
     * Initialise les gestionnaires d'événements du tableau
     */
    function initTableEventHandlers() {
        // Les gestionnaires sont déjà attachés via $(document).on()
        // Cette fonction peut être utilisée pour des initialisations spécifiques
        console.log('✅ Gestionnaires du tableau réinitialisés');
    }
    
    /**
     * Met à jour le compteur de favoris
     */
    function updateFavoritesCount() {
        $.ajax({
            url: notairesAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_favorites_count',
                nonce: notairesAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.favorites-count').text(response.data.count);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur mise à jour compteur favoris:', error);
            }
        });
    }
    
    /**
     * Initialise les filtres
     */
    function initFilters() {
        // Auto-submit du formulaire de filtres
        $('.my-istymo-inline-filters').on('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
        
        console.log('✅ Filtres initialisés');
    }
    
    /**
     * Initialise les modals
     */
    function initModals() {
        // Fermer le modal avec Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.my-istymo-modal').hide();
            }
        });
        
        console.log('✅ Modals initialisés');
    }
    
    /**
     * Initialise les animations
     */
    function initAnimations() {
        // Animation d'apparition des éléments
        $('.my-istymo-modern-table tr').each(function(index) {
            $(this).css('animation-delay', (index * 0.1) + 's');
        });
        
        console.log('✅ Animations initialisées');
    }
    
    /**
     * Gère les raccourcis clavier
     */
    function handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + F pour focus sur la recherche
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            $('#search').focus();
        }
        
        // Ctrl/Cmd + E pour export des favoris
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            $('.export-favorites-btn').click();
        }
    }
    
    /**
     * Affiche une notification
     */
    function showNotification(message, type = 'info') {
        // Supprimer les notifications existantes
        $('.notaires-notification').remove();
        
        const notification = $(`
            <div class="notaires-notification ${type}">
                <span class="dashicons ${getNotificationIcon(type)}"></span>
                <span class="message">${message}</span>
                <button type="button" class="close-notification">&times;</button>
            </div>
        `);
        
        // Ajouter au body
        $('body').append(notification);
        
        // Afficher avec animation
        setTimeout(() => notification.addClass('show'), 100);
        
        // Auto-masquer après 5 secondes
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        // Gestionnaire pour fermer manuellement
        notification.find('.close-notification').on('click', function() {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        });
    }
    
    /**
     * Retourne l'icône appropriée pour le type de notification
     */
    function getNotificationIcon(type) {
        const icons = {
            success: 'dashicons-yes-alt',
            error: 'dashicons-warning',
            warning: 'dashicons-warning',
            info: 'dashicons-info'
        };
        
        return icons[type] || icons.info;
    }
    
    /**
     * Fonction de debounce pour optimiser les recherches
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
     * Fonction utilitaire pour formater les nombres
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
    
    /**
     * Fonction utilitaire pour valider les emails
     */
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Fonction utilitaire pour valider les téléphones
     */
    function isValidPhone(phone) {
        const re = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        return re.test(phone);
    }
    
    // Exposer certaines fonctions globalement si nécessaire
    window.NotairesAdmin = {
        showNotaireDetails: showNotaireDetails,
        loadNotaires: loadNotaires,
        showNotification: showNotification,
        updateFavoritesCount: updateFavoritesCount
    };
    
})(jQuery);

// Styles CSS pour les notifications (injectés dynamiquement)
const notificationStyles = `
<style>
.notaires-notification {
    position: fixed;
    top: 32px;
    right: 20px;
    background: #fff;
    border-left: 4px solid #007cba;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 15px 20px;
    max-width: 400px;
    z-index: 999999;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.notaires-notification.show {
    transform: translateX(0);
}

.notaires-notification.success {
    border-left-color: #46b450;
}

.notaires-notification.error {
    border-left-color: #dc3232;
}

.notaires-notification.warning {
    border-left-color: #ffb900;
}

.notaires-notification .dashicons {
    font-size: 18px;
}

.notaires-notification.success .dashicons {
    color: #46b450;
}

.notaires-notification.error .dashicons {
    color: #dc3232;
}

.notaires-notification.warning .dashicons {
    color: #ffb900;
}

.notaires-notification .message {
    flex: 1;
    font-size: 14px;
    line-height: 1.4;
}

.notaires-notification .close-notification {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notaires-notification .close-notification:hover {
    color: #333;
}

.success-animation {
    animation: successPulse 0.6s ease;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); background: #d4edda; }
    100% { transform: scale(1); }
}

@media (max-width: 768px) {
    .notaires-notification {
        right: 10px;
        left: 10px;
        max-width: none;
        transform: translateY(-100%);
    }
    
    .notaires-notification.show {
        transform: translateY(0);
    }
}
</style>
`;

// Injecter les styles
document.head.insertAdjacentHTML('beforeend', notificationStyles);

