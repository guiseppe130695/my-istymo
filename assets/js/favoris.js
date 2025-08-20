document.addEventListener('DOMContentLoaded', function() {
    let favoris = [];
    let isInitialized = false;

    // ✅ DIAGNOSTIC : Vérifier les variables AJAX
    console.log('=== DIAGNOSTIC FAVORIS ===');
    console.log('sci_ajax disponible:', typeof sci_ajax !== 'undefined');
    if (typeof sci_ajax !== 'undefined') {
        console.log('sci_ajax.ajax_url:', sci_ajax.ajax_url);
        console.log('sci_ajax.nonce:', sci_ajax.nonce);
    } else {
        console.error('❌ sci_ajax n\'est pas disponible !');
        // Essayer de récupérer les variables depuis window
        if (typeof window.sci_ajax !== 'undefined') {
            console.log('sci_ajax trouvé dans window.sci_ajax');
            window.sci_ajax = window.sci_ajax;
        }
    }

    // ✅ TEST AJAX SIMPLE
    function testAjaxConnection() {
        if (typeof sci_ajax === 'undefined') {
            console.error('❌ Impossible de tester AJAX : sci_ajax non disponible');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'sci_manage_favoris');
        formData.append('operation', 'get');
        formData.append('nonce', sci_ajax.nonce);

        console.log('🧪 Test AJAX en cours...');
        
        fetch(sci_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('📡 Réponse reçue:', response.status, response.statusText);
            return response.text(); // Utiliser text() au lieu de json() pour voir la réponse brute
        })
        .then(data => {
            console.log('📄 Données reçues:', data);
            try {
                const jsonData = JSON.parse(data);
                console.log('✅ JSON valide:', jsonData);
            } catch (e) {
                console.error('❌ Réponse non-JSON:', data);
                console.error('❌ Erreur de parsing:', e.message);
            }
        })
        .catch(error => {
            console.error('❌ Erreur AJAX:', error);
        });
    }

    // Lancer le test après 1 seconde
    setTimeout(testAjaxConnection, 1000);

    // Diagnostic supprimé pour la production

    function updateFavButtons() {
        // Vérifier que les favoris sont chargés
        if (!Array.isArray(favoris)) {
            // Si les favoris ne sont pas encore chargés, les charger d'abord
            syncFavorisWithDB('get')
                .then(() => {
                    // Rappeler la fonction une fois les favoris chargés
                    setTimeout(updateFavButtons, 50);
                })
                .catch(error => {
                    // Erreur silencieuse
                });
            return;
        }
        
        const favButtons = document.querySelectorAll('.fav-btn, .favorite-btn');
        
        if (favButtons.length === 0) {
            return;
        }
        
        let updatedCount = 0;
        favButtons.forEach(btn => {
            const siren = btn.getAttribute('data-siren');
            if (!siren) {
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
    }

    function syncFavorisWithDB(action, sciData = null) {
        if (typeof sci_ajax === 'undefined') {
            return Promise.reject(new Error('Variables AJAX non disponibles'));
        }

        const formData = new FormData();
        formData.append('action', 'sci_manage_favoris');
        formData.append('operation', action);
        formData.append('nonce', sci_ajax.nonce);
        if (sciData) {
            formData.append('sci_data', JSON.stringify(sciData));
        }
        
        console.log('🔄 syncFavorisWithDB - Action:', action, 'Data:', sciData);
        
        return fetch(sci_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('📡 Réponse reçue:', response.status, response.statusText);
            return response.text(); // Utiliser text() pour voir la réponse brute
        })
        .then(data => {
            console.log('📄 Données reçues:', data);
            
            // Essayer de parser le JSON
            try {
                const jsonData = JSON.parse(data);
                console.log('✅ JSON parsé avec succès:', jsonData);
                
                if (jsonData.success) {
                    if (action === 'get') {
                        favoris = jsonData.data || [];
                        updateFavButtons();
                    }
                    return jsonData;
                } else {
                    throw new Error(jsonData.data || 'Erreur inconnue');
                }
            } catch (e) {
                console.error('❌ Erreur de parsing JSON:', e.message);
                console.error('❌ Données reçues:', data);
                throw new Error('Réponse serveur invalide: ' + data.substring(0, 100));
            }
        })
        .catch(error => {
            console.error('❌ Erreur dans syncFavorisWithDB:', error);
            throw error;
        });
    }

    function toggleFavori(btn) {
        const siren = btn.getAttribute('data-siren');
        if (!siren) {
            return;
        }
        
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
        
        if (isCurrentlyFavori) {
            favoris = favoris.filter(fav => fav.siren !== siren);
        } else {
            favoris.push(sciData);
        }
        updateFavButtons();
        syncFavorisWithDB(action, sciData)
            .then(() => {
                // Succès silencieux
            })
            .catch(error => {
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
            return;
        }
        
        syncFavorisWithDB('get')
            .then(() => {
                attachFavorisListeners();
                setupFavorisObserver(); // ✅ NOUVEAU : Configurer le MutationObserver
                updateContactStatus();
                updateFavButtons(); // ✅ NOUVEAU : Mettre à jour les boutons après initialisation
                isInitialized = true;
            })
            .catch(error => {
                // ✅ NOUVEAU : Réessayer après un délai
                setTimeout(() => {
                    if (!isInitialized) {
                        initializeFavoris();
                    }
                }, 2000);
            });
    }

    function attachFavorisListeners() {
        // ✅ NOUVEAU : Utiliser la délégation d'événements au lieu d'attacher des listeners individuels
        
        // Supprimer l'ancien listener de délégation s'il existe
        document.removeEventListener('click', handleFavoriClickDelegated);
        
        // Ajouter le nouveau listener de délégation
        document.addEventListener('click', handleFavoriClickDelegated);
    }

    // ✅ NOUVEAU : Gestionnaire de clic avec délégation d'événements
    function handleFavoriClickDelegated(event) {
        // Vérifier si le clic vient d'un bouton favori
        const target = event.target;
        if (target.matches('.fav-btn, .favorite-btn')) {
            event.preventDefault();
            toggleFavori(target);
        }
    }



    // ✅ NOUVEAU : Test manuel des boutons favoris
    function testFavorisButtons() {
        // Tests supprimés pour la production
    }

    // ✅ NOUVEAU : MutationObserver pour détecter automatiquement les nouveaux boutons favoris
    function setupFavorisObserver() {
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
        }
    }

    // ✅ NOUVEAU : Test AJAX simple
    function testAjaxHandler() {
        // Tests supprimés pour la production
    }

    initializeFavoris();
    
    // Tests supprimés pour la production
    
    // ✅ NOUVEAU : Fonction pour forcer la mise à jour des favoris
    function forceUpdateFavoris() {
        syncFavorisWithDB('get')
            .then(() => {
                updateFavButtons();
                updateContactStatus();
                attachFavorisListeners();
            })
            .catch(error => {
                // Fallback - essayer de mettre à jour avec les favoris actuels
                updateFavButtons();
            });
    }

    // ✅ NOUVEAU : Fonction pour réinitialiser après changement de page
    function refreshFavorisAfterPageChange() {
        updateFavButtons();
        attachFavorisListeners();
        updateContactStatus();
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