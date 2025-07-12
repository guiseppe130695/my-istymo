document.addEventListener('DOMContentLoaded', function() {
    let favoris = [];
    let isInitialized = false;

    function updateFavButtons() {
        // ✅ AMÉLIORÉ : Vérifier que les favoris sont chargés
        if (!Array.isArray(favoris)) {
            console.log('⚠️ Favoris non encore chargés, tentative de chargement...');
            // Si les favoris ne sont pas encore chargés, les charger d'abord
            syncFavorisWithDB('get')
                .then(() => {
                    // Rappeler la fonction une fois les favoris chargés
                    setTimeout(updateFavButtons, 50);
                })
                .catch(error => {
                    console.warn('⚠️ Erreur lors du chargement des favoris:', error);
                });
            return;
        }
        
        const favButtons = document.querySelectorAll('.fav-btn');
        console.log(`🔄 Mise à jour de ${favButtons.length} boutons favoris avec ${favoris.length} favoris chargés`);
        
        favButtons.forEach(btn => {
            const siren = btn.getAttribute('data-siren');
            if (!siren) {
                return;
            }
            const isFavori = favoris.some(fav => fav.siren === siren);
            if (isFavori) {
                btn.textContent = '★';
                btn.classList.add('favori');
                btn.title = 'Retirer des favoris';
            } else {
                btn.textContent = '☆';
                btn.classList.remove('favori');
                btn.title = 'Ajouter aux favoris';
            }
        });
    }

    function syncFavorisWithDB(action, sciData = null) {
        const formData = new FormData();
        formData.append('action', 'sci_manage_favoris');
        formData.append('operation', action);
        formData.append('nonce', sci_ajax.nonce);
        if (sciData) {
            formData.append('sci_data', JSON.stringify(sciData));
        }
        return fetch(sci_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
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
            .then(() => {})
            .catch(error => {
                if (isCurrentlyFavori) {
                    favoris.push(sciData);
                } else {
                    favoris = favoris.filter(fav => fav.siren !== siren);
                }
                updateFavButtons();
                alert('Erreur lors de la synchronisation des favoris: ' + error.message);
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
            console.log('✅ Favoris déjà initialisés');
            return;
        }
        
        console.log('🔄 Initialisation des favoris...');
        syncFavorisWithDB('get')
            .then(() => {
                console.log(`✅ Favoris initialisés avec succès: ${favoris.length} favoris chargés`);
                attachFavorisListeners();
                updateContactStatus();
                updateFavButtons(); // ✅ NOUVEAU : Mettre à jour les boutons après initialisation
                isInitialized = true;
            })
            .catch(error => {
                console.error('❌ Erreur lors de l\'initialisation des favoris:', error);
                // ✅ NOUVEAU : Réessayer après un délai
                setTimeout(() => {
                    if (!isInitialized) {
                        console.log('🔄 Nouvelle tentative d\'initialisation des favoris...');
                        initializeFavoris();
                    }
                }, 2000);
            });
    }

    function attachFavorisListeners() {
        const favButtons = document.querySelectorAll('.fav-btn');
        favButtons.forEach(btn => {
            btn.removeEventListener('click', handleFavoriClick);
            btn.addEventListener('click', handleFavoriClick);
        });
    }

    function handleFavoriClick(event) {
        event.preventDefault();
        toggleFavori(this);
    }

    initializeFavoris();
    
    // ✅ NOUVEAU : Fonction pour forcer la mise à jour des favoris
    function forceUpdateFavoris() {
        console.log('🔄 Forçage de la mise à jour des favoris...');
        syncFavorisWithDB('get')
            .then(() => {
                updateFavButtons();
                updateContactStatus();
                console.log('✅ Mise à jour forcée des favoris terminée');
            })
            .catch(error => {
                console.error('❌ Erreur lors de la mise à jour forcée des favoris:', error);
            });
    }
    
    // Exposer les fonctions globalement
    window.updateFavButtons = updateFavButtons;
    window.updateContactStatus = updateContactStatus;
    window.attachFavorisListeners = attachFavorisListeners;
    window.syncFavorisWithDB = syncFavorisWithDB;
    window.toggleFavori = toggleFavori;
    window.initializeFavoris = initializeFavoris;
    window.forceUpdateFavoris = forceUpdateFavoris; // ✅ NOUVEAU : Fonction de mise à jour forcée
});