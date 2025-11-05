/**
 * JavaScript pour le système Lead Vendeur
 * Gestion de la pagination, favoris et interactions
 */

(function($) {
    'use strict';

    // Variables globales
    let currentPage = 1;
    let isLoading = false;

    /**
     * Initialisation du système Lead Vendeur
     */
    function initLeadVendeur() {
        console.log('Initialisation Lead Vendeur...');
        
        if (typeof leadVendeurAjax === 'undefined') {
            console.warn('Lead Vendeur: Variables AJAX non disponibles');
            return;
        }

        console.log('Variables AJAX trouvées:', leadVendeurAjax);

        // Attacher les événements
        attachEventListeners();
        console.log('Event listeners attachés');
        
        // Charger la première page automatiquement
        loadPage(1);
    }

    /**
     * Attacher les listeners d'événements
     */
    function attachEventListeners() {
        // Gestion des clics sur les boutons de pagination
        $(document).on("click", ".pagination-btn, .pagination-number", function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var page = $button.data("page");
            
            if (page && !$button.hasClass("disabled") && !$button.hasClass("current")) {
                loadPage(page);
            }
        });
        
        // Gestion des favoris
        $(document).on("click", ".favorite-btn", function(e) {
            e.preventDefault();
            
            var $toggle = $(this);
            var entryId = $toggle.data("entry-id");
            var $row = $toggle.closest(".lead-vendeur-row");
            
            if ($toggle.hasClass("loading")) {
                return; // Éviter les clics multiples
            }
            
            $toggle.addClass("loading");
            
            $.ajax({
                url: simpleFavoritesAjax.ajax_url,
                type: "POST",
                data: {
                    action: "simple_favorites_toggle",
                    entry_id: entryId,
                    form_id: leadVendeurAjax.form_id,
                    nonce: simpleFavoritesAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.action === "added") {
                            $toggle.addClass("favori-active");
                            $toggle.html('★'); // Étoile pleine
                            $row.addClass("favori-row");
                        } else {
                            $toggle.removeClass("favori-active");
                            $toggle.html('☆'); // Étoile vide
                            $row.removeClass("favori-row");
                        }
                    } else {
                        showMessage("Erreur: " + response.data, "error");
                    }
                },
                error: function() {
                    showMessage("Erreur de connexion", "error");
                },
                complete: function() {
                    $toggle.removeClass("loading");
                }
            });
        });

        // Gestion des clics sur les détails
        $(document).on("click", ".view-lead-details, .view-lead", function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Bouton détails cliqué, entryId:', $(this).data("entry-id") || $(this).data("lead-id"));
            var entryId = $(this).data("entry-id") || $(this).data("lead-id");
            if (entryId) {
                showLeadDetails(entryId);
            }
        });
    }

    /**
     * Charger une page spécifique
     */
    function loadPage(page) {
        if (isLoading) return;
        
        var $tableBody = $("#lead-vendeur-table-body");
        var $paginationContainer = $("#lead-vendeur-pagination-container");
        var $paginationInfo = $("#lead-vendeur-pagination-info");
        
        isLoading = true;
        currentPage = page;
        
        // Afficher l'indicateur de chargement
        $tableBody.html('<tr><td colspan="100%" style="text-align: center; padding: 20px;"><div class="loading-spinner"></div><p>Chargement des données...</p></td></tr>');
        $paginationContainer.html('<div style="text-align: center; padding: 20px;"><div class="loading-spinner"></div></div>');
        
        // Requête AJAX
        $.ajax({
            url: leadVendeurAjax.ajax_url,
            type: "POST",
            data: {
                action: "lead_vendeur_pagination",
                nonce: leadVendeurAjax.nonce,
                page: page,
                per_page: leadVendeurAjax.per_page
            },
            success: function(response) {
                if (response.success) {
                    // Mettre à jour le tableau
                    $tableBody.html(response.data.table_html);
                    
                    // Mettre à jour la pagination
                    $paginationContainer.html(response.data.pagination_html);
                    
                    // Mettre à jour les informations de pagination
                    var info = response.data.pagination_info;
                    if (info.total_pages > 1) {
                        $paginationInfo.html(
                            '<span id="page-info">Page ' + info.current_page + ' sur ' + info.total_pages + '</span>' +
                            '<span style="margin-left: 15px; color: #666;">Affichage des entrées ' + info.start_entry + ' à ' + info.end_entry + ' sur ' + info.total_entries + '</span>'
                        );
                    }
                    
                    // Animation pour les nouvelles lignes
                    $(".lead-vendeur-row").each(function(index) {
                        $(this).css("opacity", "0").delay(index * 50).animate({
                            opacity: 1
                        }, 300);
                    });
                } else {
                    console.error("Erreur lors du chargement de la page:", response.data);
                    $tableBody.html('<tr><td colspan="100%" style="text-align: center; padding: 20px; color: #d63384;">Erreur lors du chargement des données</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error("Erreur AJAX:", error);
                $tableBody.html('<tr><td colspan="100%" style="text-align: center; padding: 20px; color: #d63384;">Erreur de connexion</td></tr>');
            },
            complete: function() {
                isLoading = false;
            }
        });
    }

    /**
     * Afficher les détails d'un lead
     */
    function showLeadDetails(entryId) {
        console.log('showLeadDetails appelée avec entryId:', entryId);
        
        if (typeof leadVendeurAjax === 'undefined') {
            console.warn('Lead Vendeur: Variables AJAX non disponibles');
            return;
        }

        console.log('Variables AJAX disponibles:', leadVendeurAjax);

        // Supprimer tout modal existant avant d'en créer un nouveau
        $('.lead-details-modal').remove();
        
        // Afficher un modal de chargement
        var $modal = $('<div class="lead-details-modal"><div class="lead-details-modal-content"><div class="loading-container"><div class="spinner"></div><p>Chargement des détails...</p></div></div></div>');
        $('body').append($modal);
        
        // Forcer l'affichage avec des styles inline
        $modal.css({
            'display': 'flex',
            'z-index': '100000',
            'visibility': 'visible',
            'opacity': '1',
            'position': 'fixed',
            'top': '0',
            'left': '0',
            'width': '100%',
            'height': '100%'
        });
        
        console.log('Modal ajouté au DOM', $modal.length);
        console.log('Modal visible:', $modal.is(':visible'));
        console.log('Modal CSS:', $modal.css('display'), $modal.css('z-index'));

        $.ajax({
            url: leadVendeurAjax.ajax_url,
            type: "POST",
            data: {
                action: "lead_vendeur_get_entry_details",
                entry_id: entryId,
                nonce: leadVendeurAjax.nonce
            },
            success: function(response) {
                console.log('Réponse AJAX reçue:', response);
                if (response.success) {
                    console.log('Données reçues:', response.data);
                    $modal.find('.lead-details-modal-content').html(response.data);
                } else {
                    console.error('Erreur dans la réponse:', response);
                    $modal.find('.lead-details-modal-content').html('<p>Erreur lors du chargement des détails.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', xhr, status, error);
                $modal.find('.lead-details-modal-content').html('<p>Erreur de connexion.</p>');
            }
        });

        // Fermer le modal en cliquant à l'extérieur
        $modal.on('click', function(e) {
            if (e.target === this) {
                $modal.remove();
            }
        });

        // Bouton de fermeture
        $modal.on('click', '.lead-details-modal-close', function() {
            $modal.remove();
        });
    }

    /**
     * Afficher un message à l'utilisateur
     */
    function showMessage(message, type) {
        var $message = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.lead-vendeur-table-container').before($message);
        setTimeout(function() {
            $message.fadeOut();
        }, 3000);
    }

    /**
     * Rafraîchir la page actuelle
     */
    function refreshCurrentPage() {
        loadPage(currentPage);
    }

    // Exposer les fonctions globalement
    window.leadVendeur = {
        init: initLeadVendeur,
        loadPage: loadPage,
        refresh: refreshCurrentPage,
        showDetails: showLeadDetails
    };
    
    // Fonction globale pour compatibilité avec les autres systèmes
    window.openLeadDetailModal = function(leadId) {
        console.log('openLeadDetailModal appelée avec leadId:', leadId);
        console.log('Variables AJAX disponibles:', typeof leadVendeurAjax !== 'undefined' ? leadVendeurAjax : 'NON DÉFINI');
        if (typeof showLeadDetails === 'function') {
            showLeadDetails(leadId);
        } else {
            console.error('showLeadDetails n\'est pas définie');
        }
    };

    // Initialisation automatique
    $(document).ready(function() {
        console.log('Document ready - Initialisation Lead Vendeur');
        console.log('Script lead-vendeur.js chargé');
        initLeadVendeur();
    });
    
    // Debug global
    console.log('Script lead-vendeur.js initialisé');

})(jQuery);
