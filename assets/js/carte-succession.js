/**
 * JavaScript pour le système Carte de Succession
 * Gestion de la pagination, favoris et interactions
 */

(function($) {
    'use strict';

    // Variables globales
    let currentPage = 1;
    let isLoading = false;

    /**
     * Initialisation du système Carte de Succession
     */
    function initCarteSuccession() {
        if (typeof carteSuccessionAjax === 'undefined') {
            console.warn('Carte Succession: Variables AJAX non disponibles');
            return;
        }

        // Attacher les événements
        attachEventListeners();
        
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
            var $row = $toggle.closest(".carte-succession-row");
            
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
                    form_id: carteSuccessionAjax.form_id,
                    nonce: simpleFavoritesAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.action === "added") {
                            $toggle.addClass("favori-active");
                            $toggle.html('★'); // Étoile pleine
                            $row.addClass("favori-row");
                            showMessage("Carte de succession ajoutée aux favoris", "success");
                        } else {
                            $toggle.removeClass("favori-active");
                            $toggle.html('☆'); // Étoile vide
                            $row.removeClass("favori-row");
                            showMessage("Carte de succession retirée des favoris", "success");
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
        $(document).on("click", ".carte-details-btn", function(e) {
            e.preventDefault();
            var entryId = $(this).data("entry-id");
            showCarteDetails(entryId);
        });
    }

    /**
     * Charger une page spécifique
     */
    function loadPage(page) {
        if (isLoading) return;
        
        var $tableBody = $("#carte-succession-table-body");
        var $paginationContainer = $("#carte-succession-pagination-container");
        
        isLoading = true;
        currentPage = page;
        
        // Afficher l'indicateur de chargement
        $tableBody.html('<tr><td colspan="100%" style="text-align: center; padding: 20px;"><div class="table-loading-spinner"></div><p>Chargement des données...</p></td></tr>');
        $paginationContainer.html('<div style="text-align: center; padding: 20px;"><div class="table-loading-spinner"></div></div>');
        
        // Requête AJAX
        $.ajax({
            url: carteSuccessionAjax.ajax_url,
            type: "POST",
            data: {
                action: "carte_succession_pagination",
                nonce: carteSuccessionAjax.nonce,
                page: page,
                per_page: carteSuccessionAjax.per_page
            },
            success: function(response) {
                if (response.success) {
                    // Mettre à jour le tableau
                    $tableBody.html(response.data.table_html);
                    
                    // Mettre à jour la pagination
                    $paginationContainer.html(response.data.pagination_html);
                    
                    
                    // Animation pour les nouvelles lignes
                    $(".carte-succession-row").each(function(index) {
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
     * Afficher les détails d'une carte de succession
     */
    function showCarteDetails(entryId) {
        if (typeof carteSuccessionAjax === 'undefined') {
            console.warn('Carte Succession: Variables AJAX non disponibles');
            return;
        }

        // Afficher un modal de chargement avec la structure correcte
        var $modal = $('<div class="carte-details-modal show"><div class="carte-details-modal-content"><div class="loading-spinner"><div class="spinner"></div></div><p class="loading-text">Chargement des détails...</p></div></div>');
        $('body').append($modal);

        $.ajax({
            url: carteSuccessionAjax.ajax_url,
            type: "POST",
            data: {
                action: "carte_succession_get_entry_details",
                entry_id: entryId,
                nonce: carteSuccessionAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $modal.find('.carte-details-modal-content').html(response.data);
                } else {
                    $modal.find('.carte-details-modal-content').html('<p>Erreur lors du chargement des détails.</p>');
                }
            },
            error: function() {
                $modal.find('.carte-details-modal-content').html('<p>Erreur de connexion.</p>');
            }
        });

        // Fermer le modal en cliquant à l'extérieur
        $modal.on('click', function(e) {
            if (e.target === this) {
                $modal.removeClass('show');
                setTimeout(function() {
                    $modal.remove();
                }, 300);
            }
        });

        // Bouton de fermeture
        $modal.on('click', '.lead-details-modal-close', function() {
            $modal.removeClass('show');
            setTimeout(function() {
                $modal.remove();
            }, 300);
        });
    }

    /**
     * Afficher un message à l'utilisateur
     */
    function showMessage(message, type) {
        var $message = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.carte-succession-table-container').before($message);
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

    /**
     * Basculer l'état d'un favori
     */
    function toggleFavorite(entryId, isCurrentlyFavorite) {
        var $toggle = $('.favorite-btn[data-entry-id="' + entryId + '"]');
        var $row = $toggle.closest('.carte-succession-row');
        
        if ($toggle.hasClass('loading')) {
            return;
        }
        
        $toggle.addClass('loading');
        
        $.ajax({
            url: simpleFavoritesAjax.ajax_url,
            type: "POST",
            data: {
                action: "simple_favorites_toggle",
                entry_id: entryId,
                form_id: carteSuccessionAjax.form_id,
                nonce: simpleFavoritesAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.action === "added") {
                        $toggle.addClass("favori-active");
                        $toggle.html('★');
                        $row.addClass("favori-row");
                        showMessage("Carte de succession ajoutée aux favoris", "success");
                    } else {
                        $toggle.removeClass("favori-active");
                        $toggle.html('☆');
                        $row.removeClass("favori-row");
                        showMessage("Carte de succession retirée des favoris", "success");
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
    }

    /**
     * Exporter les données (fonctionnalité future)
     */
    function exportData(format) {
        showMessage("Fonctionnalité d'export en cours de développement", "info");
    }

    /**
     * Filtrer les données (fonctionnalité future)
     */
    function filterData(criteria) {
        showMessage("Fonctionnalité de filtrage en cours de développement", "info");
    }

    // Exposer les fonctions globalement
    window.carteSuccession = {
        init: initCarteSuccession,
        loadPage: loadPage,
        refresh: refreshCurrentPage,
        showDetails: showCarteDetails,
        toggleFavorite: toggleFavorite,
        exportData: exportData,
        filterData: filterData
    };

    // Initialisation automatique
    $(document).ready(function() {
        initCarteSuccession();
    });

})(jQuery);

