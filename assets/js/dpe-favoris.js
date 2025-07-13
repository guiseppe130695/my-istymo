/**
 * ✅ NOUVEAU : Système de favoris DPE robuste avec délégation d'événements
 * Gestion des favoris pour les diagnostics de performance énergétique (DPE)
 * Compatible avec les boutons créés dynamiquement et la pagination
 */

(function() {
    'use strict';

    // ✅ Variables globales pour les favoris DPE
    let dpeFavoris = [];
    let isInitialized = false;

    /**
     * ✅ Initialisation du système de favoris DPE
     */
    function initDpeFavoris() {
        if (isInitialized) {
            console.log('✅ Système de favoris DPE déjà initialisé');
            return;
        }

        console.log('🚀 Initialisation du système de favoris DPE...');
        
        // Charger les favoris depuis le localStorage
        loadDpeFavoris();
        
        // Attacher les listeners avec délégation d'événements
        attachDpeFavorisListeners();
        
        // ✅ NOUVEAU : Observer les changements du DOM pour les boutons dynamiques
        setupDpeFavorisObserver();
        
        // Mettre à jour l'état visuel de tous les boutons
        updateDpeFavButtons();
        
        isInitialized = true;
        console.log('✅ Système de favoris DPE initialisé avec succès');
    }

    /**
     * ✅ Charger les favoris DPE depuis le localStorage
     */
    function loadDpeFavoris() {
        try {
            const stored = localStorage.getItem('dpe_favoris');
            dpeFavoris = stored ? JSON.parse(stored) : [];
            console.log('📋 Favoris DPE chargés:', dpeFavoris.length, 'éléments');
        } catch (error) {
            console.error('❌ Erreur lors du chargement des favoris DPE:', error);
            dpeFavoris = [];
        }
    }

    /**
     * ✅ Sauvegarder les favoris DPE dans le localStorage
     */
    function saveDpeFavoris() {
        try {
            localStorage.setItem('dpe_favoris', JSON.stringify(dpeFavoris));
            console.log('💾 Favoris DPE sauvegardés:', dpeFavoris.length, 'éléments');
        } catch (error) {
            console.error('❌ Erreur lors de la sauvegarde des favoris DPE:', error);
        }
    }

    /**
     * ✅ Attacher les listeners avec délégation d'événements (plus robuste)
     */
    function attachDpeFavorisListeners() {
        // ✅ Délégation d'événements sur le document pour capturer tous les boutons
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

        console.log('🎯 Listeners de favoris DPE attachés avec délégation d\'événements');
    }

    /**
     * ✅ Observer les changements du DOM pour détecter les nouveaux boutons
     */
    function setupDpeFavorisObserver() {
        if (!window.MutationObserver) {
            console.warn('⚠️ MutationObserver non supporté, fallback sur mise à jour manuelle');
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
                console.log('🔄 Nouveaux boutons favoris DPE détectés, mise à jour...');
                setTimeout(updateDpeFavButtons, 100);
            }
        });

        // Observer tout le document
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        console.log('👁️ Observer de favoris DPE configuré');
    }

    /**
     * ✅ Basculer l'état d'un favori DPE
     */
    function toggleDpeFavori(numeroDpe, button) {
        const index = dpeFavoris.findIndex(fav => fav.numero_dpe === numeroDpe);
        
        if (index === -1) {
            // Ajouter aux favoris
            const favoriData = {
                numero_dpe: numeroDpe,
                type_batiment: button.getAttribute('data-type-batiment') || '',
                adresse: button.getAttribute('data-adresse') || '',
                commune: button.getAttribute('data-commune') || '',
                code_postal: button.getAttribute('data-code-postal') || '',
                surface: button.getAttribute('data-surface') || '',
                etiquette_dpe: button.getAttribute('data-etiquette-dpe') || '',
                etiquette_ges: button.getAttribute('data-etiquette-ges') || '',
                date_dpe: button.getAttribute('data-date-dpe') || '',
                date_ajout: new Date().toISOString()
            };
            
            dpeFavoris.push(favoriData);
            console.log('⭐ DPE ajouté aux favoris:', numeroDpe);
        } else {
            // Retirer des favoris
            dpeFavoris.splice(index, 1);
            console.log('🗑️ DPE retiré des favoris:', numeroDpe);
        }
        
        // Sauvegarder et mettre à jour l'affichage
        saveDpeFavoris();
        updateDpeFavButtons();
        
        // ✅ Feedback visuel immédiat
        if (button) {
            updateDpeButtonState(button, index === -1);
        }
    }

    /**
     * ✅ Mettre à jour l'état visuel d'un bouton favoris
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
     * ✅ Mettre à jour tous les boutons favoris DPE
     */
    function updateDpeFavButtons() {
        const buttons = document.querySelectorAll('.favorite-btn, .fav-btn');
        
        buttons.forEach(function(button) {
            const numeroDpe = button.getAttribute('data-numero-dpe');
            if (numeroDpe) {
                const isFavori = dpeFavoris.some(fav => fav.numero_dpe === numeroDpe);
                updateDpeButtonState(button, isFavori);
            }
        });
        
        console.log('🔄 État des boutons favoris DPE mis à jour:', buttons.length, 'boutons');
    }

    /**
     * ✅ Vérifier si un DPE est en favoris
     */
    function isDpeFavori(numeroDpe) {
        return dpeFavoris.some(fav => fav.numero_dpe === numeroDpe);
    }

    /**
     * ✅ Obtenir tous les favoris DPE
     */
    function getDpeFavoris() {
        return [...dpeFavoris];
    }

    /**
     * ✅ Supprimer un favori DPE
     */
    function removeDpeFavori(numeroDpe) {
        const index = dpeFavoris.findIndex(fav => fav.numero_dpe === numeroDpe);
        if (index !== -1) {
            dpeFavoris.splice(index, 1);
            saveDpeFavoris();
            updateDpeFavButtons();
            console.log('🗑️ Favori DPE supprimé:', numeroDpe);
            return true;
        }
        return false;
    }

    /**
     * ✅ Vider tous les favoris DPE
     */
    function clearDpeFavoris() {
        dpeFavoris = [];
        saveDpeFavoris();
        updateDpeFavButtons();
        console.log('🗑️ Tous les favoris DPE supprimés');
    }

    /**
     * ✅ Fonction de rafraîchissement après changement de page
     */
    function refreshDpeFavorisAfterPageChange() {
        console.log('🔄 Rafraîchissement des favoris DPE après changement de page...');
        updateDpeFavButtons();
    }

    // ✅ Exposer les fonctions globalement pour compatibilité
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

    // ✅ Fonctions de compatibilité avec l'ancien système
    window.updateDpeFavButtons = updateDpeFavButtons;
    window.attachDpeFavorisListeners = attachDpeFavorisListeners;
    window.refreshDpeFavorisAfterPageChange = refreshDpeFavorisAfterPageChange;

    // ✅ Initialisation automatique quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDpeFavoris);
    } else {
        initDpeFavoris();
    }

    // ✅ Initialisation différée pour les cas où le script est chargé après le DOM
    setTimeout(initDpeFavoris, 100);

    console.log('📦 Module de favoris DPE chargé et prêt');

})(); 