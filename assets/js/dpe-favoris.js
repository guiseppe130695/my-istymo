document.addEventListener('DOMContentLoaded', function() {
    let favoris = [];
    let isInitialized = false;

    function updateFavButtons() {
        // ✅ AMÉLIORÉ : Vérifier que les favoris sont chargés
        if (!Array.isArray(favoris)) {
            console.log('⚠️ Favoris DPE non encore chargés, tentative de chargement...');
            // Si les favoris ne sont pas encore chargés, les charger d'abord
            syncFavorisWithDB('get')
                .then(() => {
                    // Rappeler la fonction une fois les favoris chargés
                    setTimeout(updateFavButtons, 50);
                })
                .catch(error => {
                    console.warn('⚠️ Erreur lors du chargement des favoris DPE:', error);
                });
            return;
        }
        
        const favButtons = document.querySelectorAll('.favorite-btn');
        console.log(`🔄 Mise à jour de ${favButtons.length} boutons favoris DPE avec ${favoris.length} favoris chargés`);
        
        favButtons.forEach(btn => {
            const numeroDpe = btn.getAttribute('data-numero-dpe');
            if (!numeroDpe) {
                return;
            }
            const isFavori = favoris.some(fav => fav.numero_dpe === numeroDpe);
            if (isFavori) {
                btn.textContent = '★';
                btn.classList.add('active');
                btn.title = 'Retirer des favoris';
            } else {
                btn.textContent = '☆';
                btn.classList.remove('active');
                btn.title = 'Ajouter aux favoris';
            }
        });
    }

    function syncFavorisWithDB(action, dpeData = null) {
        const formData = new FormData();
        formData.append('action', 'dpe_manage_favoris');
        formData.append('operation', action);
        formData.append('nonce', dpe_ajax.nonce);
        if (dpeData) {
            formData.append('dpe_data', JSON.stringify(dpeData));
        }
        return fetch(dpe_ajax.ajax_url, {
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
        const numeroDpe = btn.getAttribute('data-numero-dpe');
        if (!numeroDpe) {
            return;
        }
        const dpeData = {
            numero_dpe: numeroDpe,
            type_batiment: btn.getAttribute('data-type-batiment') || '',
            adresse: btn.getAttribute('data-adresse') || '',
            commune: btn.getAttribute('data-commune') || '',
            code_postal: btn.getAttribute('data-code-postal') || '',
            surface: btn.getAttribute('data-surface') || '',
            etiquette_dpe: btn.getAttribute('data-etiquette-dpe') || '',
            etiquette_ges: btn.getAttribute('data-etiquette-ges') || '',
            date_dpe: btn.getAttribute('data-date-dpe') || ''
        };
        const isCurrentlyFavori = favoris.some(fav => fav.numero_dpe === numeroDpe);
        const action = isCurrentlyFavori ? 'remove' : 'add';
        if (isCurrentlyFavori) {
            favoris = favoris.filter(fav => fav.numero_dpe !== numeroDpe);
        } else {
            favoris.push(dpeData);
        }
        updateFavButtons();
        syncFavorisWithDB(action, dpeData)
            .then(() => {})
            .catch(error => {
                if (isCurrentlyFavori) {
                    favoris.push(dpeData);
                } else {
                    favoris = favoris.filter(fav => fav.numero_dpe !== numeroDpe);
                }
                updateFavButtons();
                alert('Erreur lors de la synchronisation des favoris DPE: ' + error.message);
            });
    }

    function initializeFavoris() {
        if (isInitialized) {
            console.log('✅ Favoris DPE déjà initialisés');
            return;
        }
        
        console.log('🔄 Initialisation des favoris DPE...');
        syncFavorisWithDB('get')
            .then(() => {
                console.log(`✅ Favoris DPE initialisés avec succès: ${favoris.length} favoris chargés`);
                attachFavorisListeners();
                updateFavButtons(); // ✅ NOUVEAU : Mettre à jour les boutons après initialisation
                isInitialized = true;
            })
            .catch(error => {
                console.error('❌ Erreur lors de l\'initialisation des favoris DPE:', error);
                // ✅ NOUVEAU : Réessayer après un délai
                setTimeout(() => {
                    if (!isInitialized) {
                        console.log('🔄 Nouvelle tentative d\'initialisation des favoris DPE...');
                        initializeFavoris();
                    }
                }, 2000);
            });
    }

    function attachFavorisListeners() {
        const favButtons = document.querySelectorAll('.favorite-btn');
        console.log(`🔗 Attachement des listeners sur ${favButtons.length} boutons favoris DPE`);
        
        favButtons.forEach((btn, index) => {
            // Supprimer l'ancien listener s'il existe
            btn.removeEventListener('click', handleFavoriClick);
            
            // Ajouter le nouveau listener
            btn.addEventListener('click', handleFavoriClick);
            
            // Vérifier que le listener est bien attaché
            const numeroDpe = btn.getAttribute('data-numero-dpe');
            console.log(`✅ Listener attaché sur bouton ${index + 1} (DPE: ${numeroDpe})`);
        });
    }

    function handleFavoriClick(event) {
        event.preventDefault();
        toggleFavori(this);
    }

    initializeFavoris();
    
    // ✅ NOUVEAU : Fonction pour forcer la mise à jour des favoris
    function forceUpdateFavoris() {
        console.log('🔄 Forçage de la mise à jour des favoris DPE...');
        syncFavorisWithDB('get')
            .then(() => {
                updateFavButtons();
                attachFavorisListeners();
                console.log('✅ Mise à jour forcée des favoris DPE terminée');
            })
            .catch(error => {
                console.error('❌ Erreur lors de la mise à jour forcée des favoris DPE:', error);
            });
    }
    
    // ✅ NOUVEAU : Fonction pour réinitialiser après changement de page
    function refreshFavorisAfterPageChange() {
        console.log('🔄 Réinitialisation des favoris après changement de page...');
        updateFavButtons();
        attachFavorisListeners();
        console.log('✅ Réinitialisation des favoris terminée');
    }
    
    // Exposer les fonctions globalement
    window.updateFavButtons = updateFavButtons;
    window.attachFavorisListeners = attachFavorisListeners;
    window.syncFavorisWithDB = syncFavorisWithDB;
    window.toggleFavori = toggleFavori;
    window.initializeFavoris = initializeFavoris;
    window.forceUpdateFavoris = forceUpdateFavoris; // ✅ NOUVEAU : Fonction de mise à jour forcée
    window.refreshFavorisAfterPageChange = refreshFavorisAfterPageChange; // ✅ NOUVEAU : Fonction de réinitialisation après changement de page
}); 