/**
 * NOUVEAU : Système de favoris DPE robuste avec délégation d'événements
 * Gestion des favoris pour les diagnostics de performance énergétique (DPE)
 * Compatible avec les boutons créés dynamiquement et la pagination
 */

(function() {
    'use strict';

    // Variables globales pour les favoris DPE
    let dpeFavoris = [];
    let isInitialized = false;

    /**
     * Initialisation du système de favoris DPE
     */
    function initDpeFavoris() {
        if (isInitialized) {
            return;
        }

        // Charger les favoris depuis le localStorage
        loadDpeFavoris();
        
        // Attacher les listeners avec délégation d'événements
        attachDpeFavorisListeners();
        
        // NOUVEAU : Observer les changements du DOM pour les boutons dynamiques
        setupDpeFavorisObserver();
        
        // Mettre à jour l'état visuel de tous les boutons
        updateDpeFavButtons();
        
        isInitialized = true;
    }

    /**
     *  Charger les favoris DPE depuis la base de données
     */
    function loadDpeFavoris() {
        // Vérifier que les variables AJAX sont disponibles
        if (typeof dpe_ajax === 'undefined') {
            dpeFavoris = [];
            return;
        }

        const formData = new FormData();
        formData.append('action', 'dpe_manage_favoris');
        formData.append('operation', 'get');
        formData.append('nonce', dpe_ajax.nonce);

        fetch(dpe_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                dpeFavoris = data.data || [];
                updateDpeFavButtons();
            } else {
                dpeFavoris = [];
            }
        })
        .catch(error => {
            dpeFavoris = [];
        });
    }

    /**
     *  Sauvegarder les favoris DPE dans la base de données
     */
    function saveDpeFavoris() {
        // Cette fonction n'est plus utilisée car on sauvegarde directement via AJAX
    }

    /**
     *  Attacher les listeners avec délégation d'événements (plus robuste)
     */
    function attachDpeFavorisListeners() {
        //  Délégation d'événements sur le document pour capturer tous les boutons
        document.addEventListener('click', function(event) {
            const target = event.target;
            
            // Vérifier si c'est un bouton favoris DPE
            if (target.matches('.favorite-btn, .fav-btn') && target.hasAttribute('data-numero-dpe')) {
                event.preventDefault();
                event.stopPropagation();
                
                const numeroDpe = target.getAttribute('data-numero-dpe');
                if (numeroDpe) {
                    toggleDpeFavori(numeroDpe, target);
                }
            }
        });
    }

    /**
     *  Observer les changements du DOM pour détecter les nouveaux boutons
     */
    function setupDpeFavorisObserver() {
        if (!window.MutationObserver) {
            return;
        }

        const observer = new MutationObserver(function(mutations) {
            let shouldUpdate = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Vérifier si des boutons favoris ont été ajoutés
                            if (node.matches('.favorite-btn, .fav-btn') || 
                                node.querySelector('.favorite-btn, .fav-btn')) {
                                shouldUpdate = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldUpdate) {
                setTimeout(updateDpeFavButtons, 100);
            }
        });

        // Observer tout le document
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     *  Basculer l'état d'un favori DPE
     */
    function toggleDpeFavori(numeroDpe, button) {
        const index = dpeFavoris.findIndex(fav => fav.numero_dpe === numeroDpe);
        const isCurrentlyFavori = index !== -1;
        const action = isCurrentlyFavori ? 'remove' : 'add';
        
        // Préparer les données du favori
        const favoriData = {
            numero_dpe: numeroDpe,
            type_batiment: button.getAttribute('data-type-batiment') || '',
            adresse: button.getAttribute('data-adresse') || '',
            commune: button.getAttribute('data-commune') || '',
            code_postal: button.getAttribute('data-code-postal') || '',
            surface: button.getAttribute('data-surface') || '',
            etiquette_dpe: button.getAttribute('data-etiquette-dpe') || '',
            etiquette_ges: button.getAttribute('data-etiquette-ges') || '',
            date_dpe: button.getAttribute('data-date-dpe') || ''
        };
        
        // Mettre à jour l'état local immédiatement pour le feedback visuel
        if (isCurrentlyFavori) {
            dpeFavoris.splice(index, 1);
        } else {
            dpeFavoris.push(favoriData);
        }
        
        // Mettre à jour l'affichage immédiatement
        updateDpeFavButtons();
        
        // Feedback visuel immédiat
        if (button) {
            updateDpeButtonState(button, !isCurrentlyFavori);
        }
        
        // Synchroniser avec la base de données via AJAX
        if (typeof dpe_ajax === 'undefined') {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'dpe_manage_favoris');
        formData.append('operation', action);
        formData.append('nonce', dpe_ajax.nonce);
        formData.append('dpe_data', JSON.stringify(favoriData));
        
        fetch(dpe_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Succès silencieux
            } else {
                // Annuler les changements locaux en cas d'erreur
                if (isCurrentlyFavori) {
                    dpeFavoris.push(favoriData);
                } else {
                    dpeFavoris.splice(dpeFavoris.findIndex(fav => fav.numero_dpe === numeroDpe), 1);
                }
                updateDpeFavButtons();
                if (button) {
                    updateDpeButtonState(button, isCurrentlyFavori);
                }
                alert(`Erreur lors de l'${action === 'add' ? 'ajout' : 'suppression'} du favori DPE: ` + (data.data || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            // Annuler les changements locaux en cas d'erreur
            if (isCurrentlyFavori) {
                dpeFavoris.push(favoriData);
            } else {
                dpeFavoris.splice(dpeFavoris.findIndex(fav => fav.numero_dpe === numeroDpe), 1);
            }
            updateDpeFavButtons();
            if (button) {
                updateDpeButtonState(button, isCurrentlyFavori);
            }
            alert(`Erreur de connexion lors de l'${action === 'add' ? 'ajout' : 'suppression'} du favori DPE: ` + error.message);
        });
    }

    /**
     *  Mettre à jour l'état visuel d'un bouton favoris
     */
    function updateDpeButtonState(button, isFavori) {
        if (isFavori) {
            button.classList.add('active', 'favori');
            button.innerHTML = '★';
            button.title = 'Retirer des favoris';
        } else {
            button.classList.remove('active', 'favori');
            button.innerHTML = '☆';
            button.title = 'Ajouter aux favoris';
        }
    }

    /**
     *  Mettre à jour tous les boutons favoris DPE
     */
    function updateDpeFavButtons() {
        const buttons = document.querySelectorAll('.favorite-btn, .fav-btn');
        
        buttons.forEach(function(button) {
            const numeroDpe = button.getAttribute('data-numero-dpe');
            if (numeroDpe) {
                // Vérifier si le DPE est en favoris en utilisant le bon champ de la DB
                const isFavori = dpeFavoris.some(fav => 
                    fav.numero_dpe === numeroDpe || 
                    fav.dpe_id === numeroDpe || 
                    fav._id === numeroDpe
                );
                updateDpeButtonState(button, isFavori);
            }
        });
        
        // Logs supprimés pour la production
    }

    /**
     *  Vérifier si un DPE est en favoris
     */
    function isDpeFavori(numeroDpe) {
        return dpeFavoris.some(fav => 
            fav.numero_dpe === numeroDpe || 
            fav.dpe_id === numeroDpe || 
            fav._id === numeroDpe
        );
    }

    /**
     *  Obtenir tous les favoris DPE
     */
    function getDpeFavoris() {
        return [...dpeFavoris];
    }

    /**
     *  Supprimer un favori DPE
     */
    function removeDpeFavori(numeroDpe) {
        const index = dpeFavoris.findIndex(fav => 
            fav.numero_dpe === numeroDpe || 
            fav.dpe_id === numeroDpe || 
            fav._id === numeroDpe
        );
        if (index !== -1) {
            dpeFavoris.splice(index, 1);
            updateDpeFavButtons();
            return true;
        }
        return false;
    }

    /**
     *  Vider tous les favoris DPE
     */
    function clearDpeFavoris() {
        dpeFavoris = [];
        updateDpeFavButtons();
    }

    /**
     *  Fonction de rafraîchissement après changement de page
     */
    function refreshDpeFavorisAfterPageChange() {
        updateDpeFavButtons();
    }

    //  Exposer les fonctions globalement pour compatibilité
    window.dpeFavoris = {
        init: initDpeFavoris,
        toggle: toggleDpeFavori,
        isFavori: isDpeFavori,
        getFavoris: getDpeFavoris,
        remove: removeDpeFavori,
        clear: clearDpeFavoris,
        updateButtons: updateDpeFavButtons,
        refreshAfterPageChange: refreshDpeFavorisAfterPageChange
    };

    //  Fonctions de compatibilité avec l'ancien système
    window.updateDpeFavButtons = updateDpeFavButtons;
    window.attachDpeFavorisListeners = attachDpeFavorisListeners;
    window.refreshDpeFavorisAfterPageChange = refreshDpeFavorisAfterPageChange;

    //  Initialisation automatique quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDpeFavoris);
    } else {
        initDpeFavoris();
    }

    //  Initialisation différée pour les cas où le script est chargé après le DOM
    setTimeout(initDpeFavoris, 100);

    // Module de favoris DPE chargé et prêt

})(); 