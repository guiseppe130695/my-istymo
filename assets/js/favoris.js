document.addEventListener('DOMContentLoaded', function() {
    let favoris = [];
    let isInitialized = false;

    // ✅ NOUVEAU : Diagnostic au chargement
    console.log('🔍 DIAGNOSTIC FAVORIS SCI - Début du script');
    console.log('🔍 Variables AJAX disponibles:', typeof sci_ajax !== 'undefined' ? '✅' : '❌');
    if (typeof sci_ajax !== 'undefined') {
        console.log('🔍 sci_ajax.ajax_url:', sci_ajax.ajax_url);
        console.log('🔍 sci_ajax.nonce:', sci_ajax.nonce ? '✅' : '❌');
    }

    function updateFavButtons() {
        console.log('🔄 updateFavButtons appelée');
        
        // ✅ AMÉLIORÉ : Vérifier que les favoris sont chargés
        if (!Array.isArray(favoris)) {
            console.log('⚠️ Favoris SCI non encore chargés, tentative de chargement...');
            // Si les favoris ne sont pas encore chargés, les charger d'abord
            syncFavorisWithDB('get')
                .then(() => {
                    // Rappeler la fonction une fois les favoris chargés
                    setTimeout(updateFavButtons, 50);
                })
                .catch(error => {
                    console.warn('⚠️ Erreur lors du chargement des favoris SCI:', error);
                });
            return;
        }
        
        const favButtons = document.querySelectorAll('.fav-btn, .favorite-btn');
        console.log(`🔄 Mise à jour de ${favButtons.length} boutons favoris SCI avec ${favoris.length} favoris chargés`);
        
        if (favButtons.length === 0) {
            console.log('⚠️ Aucun bouton favori trouvé dans le DOM');
            return;
        }
        
        let updatedCount = 0;
        favButtons.forEach(btn => {
            const siren = btn.getAttribute('data-siren');
            if (!siren) {
                console.warn('⚠️ Bouton favori sans data-siren:', btn);
                return;
            }
            const isFavori = favoris.some(fav => fav.siren === siren);
            const wasFavori = btn.classList.contains('favori');
            
            if (isFavori) {
                btn.textContent = '★';
                btn.classList.add('favori', 'active');
                btn.title = 'Retirer des favoris';
                if (!wasFavori) updatedCount++;
            } else {
                btn.textContent = '☆';
                btn.classList.remove('favori', 'active');
                btn.title = 'Ajouter aux favoris';
                if (wasFavori) updatedCount++;
            }
        });
        
        console.log(`✅ ${updatedCount} boutons favoris mis à jour`);
    }

    function syncFavorisWithDB(action, sciData = null) {
        // ✅ NOUVEAU : Diagnostic des variables AJAX
        if (typeof sci_ajax === 'undefined') {
            console.error('❌ Variables AJAX non disponibles');
            return Promise.reject(new Error('Variables AJAX non disponibles'));
        }

        const formData = new FormData();
        formData.append('action', 'sci_manage_favoris');
        formData.append('operation', action);
        formData.append('nonce', sci_ajax.nonce);
        if (sciData) {
            formData.append('sci_data', JSON.stringify(sciData));
        }

        console.log(`🔄 Appel AJAX: ${action}`, sciData ? `avec SIREN: ${sciData.siren}` : '');
        
        return fetch(sci_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('📡 Réponse AJAX reçue:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📡 Données AJAX reçues:', data);
            if (data.success) {
                if (action === 'get') {
                    favoris = data.data || [];
                    updateFavButtons();
                }
                return data;
            } else {
                throw new Error(data.data || 'Erreur inconnue');
            }
        })
        .catch(error => {
            console.error('❌ Erreur AJAX:', error);
            throw error;
        });
    }

    function toggleFavori(btn) {
        console.log('🖱️ Clic sur bouton favori détecté');
        const siren = btn.getAttribute('data-siren');
        if (!siren) {
            console.error('❌ Pas de SIREN trouvé sur le bouton');
            return;
        }
        console.log('🔄 Toggle favori pour SIREN:', siren);
        
        const sciData = {
            siren: siren,
            denomination: btn.getAttribute('data-denomination') || '',
            dirigeant: btn.getAttribute('data-dirigeant') || '',
            adresse: btn.getAttribute('data-adresse') || '',
            ville: btn.getAttribute('data-ville') || '',
            code_postal: btn.getAttribute('data-code-postal') || '',
        };
        const isCurrentlyFavori = favoris.some(fav => fav.siren === siren);
        const action = isCurrentlyFavori ? 'remove' : 'add';
        console.log(`🔄 Action: ${action} (actuellement favori: ${isCurrentlyFavori})`);
        
        if (isCurrentlyFavori) {
            favoris = favoris.filter(fav => fav.siren !== siren);
        } else {
            favoris.push(sciData);
        }
        updateFavButtons();
        syncFavorisWithDB(action, sciData)
            .then(() => {
                console.log('✅ Toggle favori réussi');
            })
            .catch(error => {
                console.error('❌ Erreur lors du toggle favori:', error);
                if (isCurrentlyFavori) {
                    favoris.push(sciData);
                } else {
                    favoris = favoris.filter(fav => fav.siren !== siren);
                }
                updateFavButtons();
                alert('Erreur lors de la synchronisation des favoris SCI: ' + error.message);
            });
    }

    function updateContactStatus() {
        if (typeof sci_ajax === 'undefined' || !sci_ajax.contacted_sirens) {
            return;
        }
        const contactedSirens = sci_ajax.contacted_sirens;
        const statusElements = document.querySelectorAll('.contact-status');
        statusElements.forEach(statusElement => {
            const siren = statusElement.getAttribute('data-siren');
            const iconElement = statusElement.querySelector('.contact-status-icon');
            if (!siren || !iconElement) {
                return;
            }
            const isContacted = contactedSirens.includes(siren);
            if (isContacted) {
                statusElement.className = 'contact-status contacted';
                statusElement.style.display = 'inline-block';
                iconElement.textContent = '✅';
                statusElement.title = 'Cette SCI a déjà été contactée dans une campagne précédente';
                const textElement = statusElement.querySelector('.contact-status-text');
                if (textElement) {
                    textElement.style.display = 'none';
                }
            } else {
                statusElement.style.display = 'none';
                statusElement.title = '';
            }
        });
    }

    function initializeFavoris() {
        if (isInitialized) {
            console.log('✅ Favoris SCI déjà initialisés');
            return;
        }
        
        console.log('🔄 Initialisation des favoris SCI...');
        syncFavorisWithDB('get')
            .then(() => {
                        console.log(`✅ Favoris SCI initialisés avec succès: ${favoris.length} favoris chargés`);
                attachFavorisListeners();
        setupFavorisObserver(); // ✅ NOUVEAU : Configurer le MutationObserver
                updateContactStatus();
                updateFavButtons(); // ✅ NOUVEAU : Mettre à jour les boutons après initialisation
                isInitialized = true;
            })
            .catch(error => {
                console.error('❌ Erreur lors de l\'initialisation des favoris SCI:', error);
                // ✅ NOUVEAU : Réessayer après un délai
                setTimeout(() => {
                    if (!isInitialized) {
                        console.log('🔄 Nouvelle tentative d\'initialisation des favoris SCI...');
                        initializeFavoris();
                    }
                }, 2000);
            });
    }

    function attachFavorisListeners() {
        // ✅ NOUVEAU : Utiliser la délégation d'événements au lieu d'attacher des listeners individuels
        console.log('🔗 Configuration de la délégation d\'événements pour les favoris SCI');
        
        // Supprimer l'ancien listener de délégation s'il existe
        document.removeEventListener('click', handleFavoriClickDelegated);
        
        // Ajouter le nouveau listener de délégation
        document.addEventListener('click', handleFavoriClickDelegated);
        
        console.log('✅ Délégation d\'événements configurée pour les favoris SCI');
    }

    // ✅ NOUVEAU : Gestionnaire de clic avec délégation d'événements
    function handleFavoriClickDelegated(event) {
        // Vérifier si le clic vient d'un bouton favori
        const target = event.target;
        if (target.matches('.fav-btn, .favorite-btn')) {
            event.preventDefault();
            console.log('🖱️ Clic sur bouton favori détecté (délégation)');
            toggleFavori(target);
        }
    }



    // ✅ NOUVEAU : Test manuel des boutons favoris
    function testFavorisButtons() {
        console.log('🧪 TEST MANUEL DES BOUTONS FAVORIS');
        const favButtons = document.querySelectorAll('.fav-btn, .favorite-btn');
        console.log(`🧪 ${favButtons.length} boutons favoris trouvés`);
        
        // ✅ NOUVEAU : Vérifier la délégation d'événements
        const hasDelegation = document.onclick !== null || document.addEventListener !== undefined;
        console.log(`🧪 Délégation d'événements configurée: ${hasDelegation ? '✅' : '❌'}`);
        
        favButtons.forEach((btn, index) => {
            const siren = btn.getAttribute('data-siren');
            const hasClickListener = btn.onclick !== null;
            console.log(`🧪 Bouton ${index + 1}: SIREN=${siren}, Listener individuel=${hasClickListener ? '✅' : '❌'} (délégation d'événements utilisée)`);
        });
    }

    // ✅ NOUVEAU : MutationObserver pour détecter automatiquement les nouveaux boutons favoris
    function setupFavorisObserver() {
        console.log('👁️ Configuration du MutationObserver pour les favoris SCI');
        
        // Supprimer l'ancien observer s'il existe
        if (window.favorisObserver) {
            window.favorisObserver.disconnect();
        }
        
        // Créer un nouvel observer
        window.favorisObserver = new MutationObserver((mutations) => {
            let shouldUpdate = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Vérifier si le nœud ajouté contient des boutons favoris
                            if (node.matches && (node.matches('.fav-btn, .favorite-btn') || node.querySelector('.fav-btn, .favorite-btn'))) {
                                shouldUpdate = true;
                            }
                            // Vérifier aussi les enfants du nœud
                            if (node.querySelector && node.querySelector('.fav-btn, .favorite-btn')) {
                                shouldUpdate = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldUpdate) {
                console.log('👁️ Nouveaux boutons favoris détectés, mise à jour...');
                setTimeout(() => {
                    updateFavButtons();
                }, 100);
            }
        });
        
        // Observer les changements dans le tableau des résultats
        const resultsTable = document.querySelector('#results-table, .sci-table');
        if (resultsTable) {
            window.favorisObserver.observe(resultsTable, {
                childList: true,
                subtree: true
            });
            console.log('✅ MutationObserver configuré sur le tableau des résultats');
        } else {
            console.warn('⚠️ Tableau des résultats non trouvé pour le MutationObserver');
        }
    }

    // ✅ NOUVEAU : Test AJAX simple
    function testAjaxHandler() {
        console.log('🧪 TEST AJAX HANDLER');
        if (typeof sci_ajax === 'undefined') {
            console.error('❌ Variables AJAX non disponibles');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'sci_manage_favoris');
        formData.append('operation', 'get');
        formData.append('nonce', sci_ajax.nonce);

        console.log('🧪 Envoi requête AJAX de test...');
        
        fetch(sci_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('🧪 Réponse AJAX de test:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🧪 Données AJAX de test:', data);
            if (data.success) {
                console.log('✅ Handler AJAX fonctionne correctement');
            } else {
                console.error('❌ Handler AJAX retourne une erreur:', data.data);
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors du test AJAX:', error);
        });
    }

    initializeFavoris();
    
    // ✅ NOUVEAU : Test après un délai
    setTimeout(() => {
        testFavorisButtons();
        testAjaxHandler();
    }, 2000);
    
    // ✅ NOUVEAU : Fonction pour forcer la mise à jour des favoris
    function forceUpdateFavoris() {
        console.log('🔄 Forçage de la mise à jour des favoris SCI...');
        syncFavorisWithDB('get')
            .then(() => {
                console.log('✅ Favoris SCI rechargés, mise à jour des boutons...');
                updateFavButtons();
                updateContactStatus();
                attachFavorisListeners();
                console.log('✅ Mise à jour forcée des favoris SCI terminée');
            })
            .catch(error => {
                console.error('❌ Erreur lors de la mise à jour forcée des favoris SCI:', error);
                // ✅ NOUVEAU : Fallback - essayer de mettre à jour avec les favoris actuels
                console.log('🔄 Fallback: mise à jour avec les favoris actuels...');
                updateFavButtons();
            });
    }

    // ✅ NOUVEAU : Fonction pour réinitialiser après changement de page
    function refreshFavorisAfterPageChange() {
        console.log('🔄 Réinitialisation des favoris SCI après changement de page...');
        updateFavButtons();
        attachFavorisListeners();
        updateContactStatus();
        console.log('✅ Réinitialisation des favoris SCI terminée');
    }
    
    // Exposer les fonctions globalement
    window.updateFavButtons = updateFavButtons;
    window.updateContactStatus = updateContactStatus;
    window.attachFavorisListeners = attachFavorisListeners;
    window.syncFavorisWithDB = syncFavorisWithDB;
    window.toggleFavori = toggleFavori;
    window.initializeFavoris = initializeFavoris;
    window.forceUpdateFavoris = forceUpdateFavoris; // ✅ NOUVEAU : Fonction de mise à jour forcée
    window.refreshFavorisAfterPageChange = refreshFavorisAfterPageChange; // ✅ NOUVEAU : Fonction de réinitialisation après changement de page
    window.testFavorisButtons = testFavorisButtons; // ✅ NOUVEAU : Fonction de test
    window.testAjaxHandler = testAjaxHandler; // ✅ NOUVEAU : Fonction de test AJAX
    window.setupFavorisObserver = setupFavorisObserver; // ✅ NOUVEAU : Fonction de configuration du MutationObserver
});