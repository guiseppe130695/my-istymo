(function() {
    if (window.sciAdminInitialized && window.sciAdminInitialized === true) {
        return;
    }
    
    window.sciAdminInitialized = true;
    
    if (!window.sciCache) {
        window.sciCache = {
            title: '',
            currentPage: 1,
            totalPages: 1,
            totalResults: 0,
            codePostal: '',
            pageSize: 50,
            isSearching: false,
            searchTimeout: null,
            lastUpdate: 0
        };
    }
    
    const cache = window.sciCache;
    
    // NOUVEAU : Fonction pour mettre à jour le cache
    function updateCache(key, value) {
        cache[key] = value;
        cache.lastUpdate = Date.now();
    }
    
    // NOUVEAU : Fonction pour vérifier si les données ont changé
    function hasDataChanged(key, newValue) {
        return cache[key] !== newValue;
    }
    
    // NOUVEAU : Fonction pour forcer la mise à jour de la pagination
    function forceUpdatePagination() {
        const elements = getElements();
        if (elements && elements.pageInfo) {
            const newPageText = `${cache.currentPage}/${cache.totalPages}`;
            elements.pageInfo.textContent = newPageText;
        }
    }
    
    // AMÉLIORÉ : Fonction pour obtenir les paramètres de pagination
    function getCurrentPaginationParams() {
        return { 
            page: cache.currentPage, 
            codePostal: cache.codePostal 
        };
    }
    
    // NOUVEAU : Fonction pour mettre à jour le contenu du tableau de manière optimisée
    function updateTableContent(results) {
        const elements = getElements();
        if (!elements) return;
        
        const currentRowCount = elements.resultsTbody.children.length;
        const newRowCount = results.length;
        
        if (currentRowCount > 0) {
            elements.resultsTbody.innerHTML = '';
        }
        
        results.forEach((result, index) => {
            const row = createResultRow(result, index);
            elements.resultsTbody.appendChild(row);
        });
    }
    
    function getElements() {
        const elements = {
            searchForm: document.getElementById('sci-search-form'),
            codePostalSelect: document.getElementById('codePostal'),
            searchBtn: document.getElementById('search-btn'),
            searchLoading: document.getElementById('search-loading'),
            searchResults: document.getElementById('search-results'),
            searchError: document.getElementById('search-error'),
            resultsTitle: document.getElementById('results-title'),
            paginationInfo: document.getElementById('pagination-info'),
            resultsTbody: document.getElementById('results-tbody'),
            prevPageBtn: document.getElementById('prev-page'),
            nextPageBtn: document.getElementById('next-page'),
            pageInfo: document.getElementById('page-info')
        };
        
        const criticalElements = ['searchForm', 'codePostalSelect', 'searchBtn', 'searchLoading', 'searchResults', 'searchError', 'resultsTbody'];
        const missingCriticalElements = criticalElements.filter(name => !elements[name]);
        
        if (missingCriticalElements.length > 0) {
            return null;
        }
        
        return elements;
    }
    
    function performSearch(codePostal, page = 1, pageSize = 50) {
        const elements = getElements();
        if (!elements) return;
        if (cache.isSearching) {
            return;
        }
        if (cache.searchTimeout) {
            clearTimeout(cache.searchTimeout);
            cache.searchTimeout = null;
        }
        cache.isSearching = true;
        
        if (!codePostal) {
            displayError('Code postal manquant pour la recherche');
            cache.isSearching = false;
            return;
        }
        
        // MODIFIÉ : Ne mettre à jour que le code postal et la taille de page
        updateCache('codePostal', codePostal);
        updateCache('pageSize', pageSize);
        
                        // Logs supprimés pour la production
        elements.searchLoading.style.display = 'block';
        elements.searchResults.style.display = 'none';
        elements.searchError.style.display = 'none';
        
        const paginationControls = document.getElementById('pagination-controls');
        if (paginationControls) {
            paginationControls.style.display = 'block';
        }
        elements.searchBtn.disabled = true;
        elements.searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recherche...';
        const formData = new FormData();
        formData.append('action', 'sci_inpi_search_ajax');
        formData.append('code_postal', codePostal);
        formData.append('page', page);
        formData.append('page_size', pageSize);
        formData.append('nonce', sci_ajax.nonce);
        const timeoutPromise = new Promise((_, reject) => {
            cache.searchTimeout = setTimeout(() => {
                reject(new Error('Timeout de la requête (30s)'));
            }, 30000);
        });
        Promise.race([
            fetch(sci_ajax.ajax_url, {
                method: 'POST',
                body: formData
            }),
            timeoutPromise
        ])
        .then(response => {
            if (cache.searchTimeout) clearTimeout(cache.searchTimeout);
            return response.json();
        })
        .then(data => {
            if (cache.searchTimeout) clearTimeout(cache.searchTimeout);
            cache.isSearching = false;
            elements.searchLoading.style.display = 'none';
            elements.searchBtn.disabled = false;
            elements.searchBtn.innerHTML = '<i class="fas fa-search"></i> Rechercher les SCI';
            if (data.success) {
                displayResults(data.data);
            } else {
                displayError(data.data || 'Erreur lors de la recherche');
            }
        })
        .catch(error => {
            if (cache.searchTimeout) clearTimeout(cache.searchTimeout);
            cache.isSearching = false;
            elements.searchLoading.style.display = 'none';
            elements.searchBtn.disabled = false;
            elements.searchBtn.innerHTML = '<i class="fas fa-search"></i> Rechercher les SCI';
            displayError('Erreur réseau lors de la recherche: ' + error.message);
        });
    }
    
    function displayResults(data) {
        const elements = getElements();
        if (!elements) return;
        const { results, pagination } = data;
        
                        // Logs supprimés pour la production
        
        //  VALIDATION : Vérifier que les données de pagination sont valides
        if (!pagination || typeof pagination.current_page === 'undefined' || typeof pagination.total_pages === 'undefined') {
            console.error(' Données de pagination invalides:', pagination);
            displayError('Erreur: données de pagination manquantes');
            return;
        }
        
        //  MODIFIÉ : Récupérer le code postal actuel depuis le select
        const currentCodePostal = elements.codePostalSelect ? elements.codePostalSelect.value : '';
        
        //  NOUVEAU : Mettre à jour le cache avec les nouvelles données
        updateCache('currentPage', pagination.current_page);
        updateCache('totalPages', pagination.total_pages);
        updateCache('totalResults', pagination.total_count);
        updateCache('codePostal', currentCodePostal);
        
                        // Logs supprimés pour la production
        
        // Afficher la zone des résultats
        elements.searchResults.style.display = 'block';
        elements.searchError.style.display = 'none';
        
        // Mettre à jour le titre et les infos
        elements.resultsTitle.textContent = ` Résultats de recherche (${pagination.total_count} SCI trouvées)`;
        if (elements.paginationInfo) {
            elements.paginationInfo.textContent = `Page ${pagination.current_page} sur ${pagination.total_pages} - ${results.length} résultats affichés`;
            elements.paginationInfo.style.display = 'block';
        }
        
        // Vider le tableau
        elements.resultsTbody.innerHTML = '';
        
        // Remplir le tableau avec les résultats
        results.forEach((result, index) => {
            const row = createResultRow(result, index);
            elements.resultsTbody.appendChild(row);
        });
        
        // Mettre à jour les contrôles de pagination
        updatePaginationControls();
        
        // Réinitialiser les fonctionnalités JavaScript
        reinitializeJavaScriptFeatures();
    }
    
    function createResultRow(result, index) {
        const row = document.createElement('tr');
        
        // Préparer l'URL Google Maps
        const mapsQuery = encodeURIComponent(`${result.adresse} ${result.code_postal} ${result.ville}`);
        const mapsUrl = `https://www.google.com/maps/place/${mapsQuery}`;
        
        row.innerHTML = `
            <td>
                <button class="fav-btn" 
                        data-siren="${escapeHtml(result.siren)}"
                        data-denomination="${escapeHtml(result.denomination)}"
                        data-dirigeant="${escapeHtml(result.dirigeant)}"
                        data-adresse="${escapeHtml(result.adresse)}"
                        data-ville="${escapeHtml(result.ville)}"
                        data-code-postal="${escapeHtml(result.code_postal)}"
                        aria-label="Ajouter aux favoris">
                    <i class="fas fa-heart"></i>
                </button>
            </td>
            <td>
                <div style="font-weight: 600; font-size: 14px; color: #333; margin-bottom: 2px;">${escapeHtml(result.denomination)}</div>
                <div style="font-size: 11px; color: #666; font-style: italic;">ID: ${escapeHtml(result.siren)}</div>
            </td>
            <td>${escapeHtml(result.dirigeant)}</td>
            <td>${escapeHtml(result.adresse)} ${escapeHtml(result.ville)}</td>
            <td style="color: #0064A6 !important; text-align: center !important;">
                <a href="${mapsUrl}" 
                   target="_blank" 
                   class="maps-link"
                   title="Localiser ${escapeHtml(result.denomination)} sur Google Maps" style="font-size: 14px !important;">
                    <i class="fas fa-map-marker-alt"></i> Localiser
                </a>
            </td>
            <td style="text-align: center !important;">
                <input type="checkbox" class="select-sci-checkbox" data-siren="${escapeHtml(result.siren)}" data-denomination="${escapeHtml(result.denomination)}">
            </td>
            <td style="text-align: center !important;">
                <span class="contact-status" data-siren="${escapeHtml(result.siren)}">
                    <span class="contact-status-icon"></span>
                    <span class="contact-status-text"></span>
                </span>
            </td>
        `;
        
        return row;
    }
    
    function updatePaginationControls() {
        const elements = getElements();
        if (!elements) return;
        
        // Logs supprimés pour la production
        
        // Boutons précédent
        const shouldDisablePrev = cache.currentPage <= 1;
        if (elements.prevPageBtn) {
            elements.prevPageBtn.disabled = shouldDisablePrev;
            console.log(' Bouton précédent disabled:', shouldDisablePrev);
        }
        
        // Boutons suivant
        const shouldDisableNext = cache.currentPage >= cache.totalPages;
        if (elements.nextPageBtn) {
            elements.nextPageBtn.disabled = shouldDisableNext;
            console.log(' Bouton suivant disabled:', shouldDisableNext);
        }
        
        // Informations de page
        if (elements.pageInfo) {
            const pageText = `Page ${cache.currentPage} / ${cache.totalPages}`;
            elements.pageInfo.textContent = pageText;
            console.log(' Info page:', pageText);
        }
    }
    
    function reinitializeJavaScriptFeatures() {
        console.log(' Début reinitializeJavaScriptFeatures');
        
        setTimeout(() => {
            console.log('⏰ Timeout reinitializeJavaScriptFeatures exécuté');
            
            //  SIMPLIFIÉ : Avec la délégation d'événements, on n'a plus besoin d'attacher des listeners individuels
            if (typeof window.attachFavorisListeners === 'function') {
                window.attachFavorisListeners();
                console.log(' Délégation d\'événements favoris configurée');
            } else {
                console.warn(' Fonction attachFavorisListeners non disponible');
            }
            
            //  NOUVEAU : Configurer le MutationObserver pour détecter automatiquement les nouveaux boutons
            if (typeof window.setupFavorisObserver === 'function') {
                window.setupFavorisObserver();
                console.log(' MutationObserver favoris configuré');
            } else {
                console.warn(' Fonction setupFavorisObserver non disponible');
            }
            
            //  Mettre à jour l'affichage des boutons favoris
            if (typeof window.forceUpdateFavoris === 'function') {
                window.forceUpdateFavoris();
                console.log(' Mise à jour forcée des favoris lancée');
            } else if (typeof window.updateFavButtons === 'function') {
                window.updateFavButtons();
                console.log(' Boutons favoris mis à jour');
            } else {
                console.warn(' Fonction updateFavButtons non disponible');
            }
            
            if (typeof window.updateContactStatus === 'function') {
                window.updateContactStatus();
                console.log(' Statut de contact réinitialisé');
            } else {
                console.warn(' Fonction updateContactStatus non disponible');
            }
            
            if (typeof window.updateSelectedCount === 'function') {
                const newCheckboxes = document.querySelectorAll('.send-letter-checkbox');
                newCheckboxes.forEach(checkbox => {
                    checkbox.removeEventListener('change', window.updateSelectedCount);
                    checkbox.addEventListener('change', window.updateSelectedCount);
                });
                
                window.updateSelectedCount();
                console.log(' Checkboxes réinitialisées:', newCheckboxes.length, 'checkboxes trouvées');
            } else {
                console.warn(' Fonction updateSelectedCount non disponible');
            }
        }, 1000); //  AUGMENTÉ : Délai plus long pour s'assurer que les boutons sont créés
    }
    
    function displayError(message) {
        const elements = getElements();
        if (!elements) return;
        elements.searchResults.style.display = 'none';
        elements.searchError.style.display = 'block';
        elements.searchError.querySelector('#error-message').innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    function initialize() {
        const elements = getElements();
        if (!elements) {
            return;
        }
        
        function autoLoadFirstCodePostal() {
            console.log('🚀 Début autoLoadFirstCodePostal (admin)');
            console.log(' sciAutoSearch disponible:', typeof sciAutoSearch !== 'undefined');
            
            if (typeof sciAutoSearch !== 'undefined') {
                console.log(' sciAutoSearch.auto_search_enabled:', sciAutoSearch.auto_search_enabled);
                console.log(' sciAutoSearch.default_postal_code:', sciAutoSearch.default_postal_code);
            }
            
            //  AMÉLIORÉ : Vérifier si la recherche automatique est activée
            if (typeof sciAutoSearch !== 'undefined' && sciAutoSearch.auto_search_enabled && sciAutoSearch.default_postal_code) {
                // Utiliser le premier code postal de l'utilisateur
                const defaultCodePostal = sciAutoSearch.default_postal_code;
                
                // S'assurer que le premier code postal est sélectionné
                elements.codePostalSelect.value = defaultCodePostal;
                
                console.log(' Chargement automatique du premier code postal (admin):', defaultCodePostal);
                console.log(' elements.codePostalSelect.value après sélection:', elements.codePostalSelect.value);
                
                // Lancer automatiquement la recherche
                console.log('🚀 Lancement de performSearch (admin) avec:', defaultCodePostal, 1, cache.pageSize);
                performSearch(defaultCodePostal, 1, cache.pageSize);
            } else if (elements.codePostalSelect.options.length > 1) {
                // Fallback : sélectionner automatiquement le premier code postal disponible
                elements.codePostalSelect.selectedIndex = 1;
                const firstCodePostal = elements.codePostalSelect.value;
                
                console.log(' Chargement automatique du premier code postal disponible (admin):', firstCodePostal);
                
                // Lancer automatiquement la recherche
                performSearch(firstCodePostal, 1, cache.pageSize);
            } else {
                console.log(' Aucun code postal configuré pour le chargement automatique (admin)');
                console.log(' elements.codePostalSelect.options.length:', elements.codePostalSelect.options.length);
            }
        }
        
        autoLoadFirstCodePostal();
        
        // Mettre à jour l'UI au chargement initial
        setTimeout(() => {
            if (typeof window.updateSCISelectionUI === 'function') {
                window.updateSCISelectionUI();
            }
        }, 500);
        
        elements.searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const codePostal = elements.codePostalSelect.value;
            if (!codePostal) {
                alert('Veuillez sélectionner un code postal');
                return;
            }
            performSearch(codePostal, 1, cache.pageSize);
        });
        
        //  AMÉLIORÉ : Vérifier que les boutons de pagination existent
        console.log(' Éléments pagination admin trouvés:');
        console.log('- Bouton précédent:', elements.prevPageBtn ? '' : '');
        console.log('- Bouton suivant:', elements.nextPageBtn ? '' : '');
        console.log('- Info page:', elements.pageInfo ? '' : '');
        
        if (elements.prevPageBtn) {
            elements.prevPageBtn.addEventListener('click', function() {
                console.log(' Clic bouton précédent (admin)');
                console.log('📊 État du cache:', {
                    currentPage: cache.currentPage,
                    totalPages: cache.totalPages,
                    codePostal: cache.codePostal,
                    pageSize: cache.pageSize
                });
                console.log(' Bouton désactivé?', elements.prevPageBtn.disabled);
                
                const codePostal = elements.codePostalSelect ? elements.codePostalSelect.value : cache.codePostal;
                const prevPage = cache.currentPage - 1;
                
                console.log('🧮 Calcul: Page actuelle', cache.currentPage, '- 1 =', prevPage);
                console.log(' Condition prevPage >= 1:', prevPage >= 1);
                
                if (prevPage >= 1) {
                    console.log(' Navigation vers page:', prevPage, 'avec code postal:', codePostal);
                    performSearch(codePostal, prevPage, cache.pageSize);
                } else {
                    console.log(' Déjà sur la première page - navigation bloquée');
                }
            });
        }
        
        if (elements.nextPageBtn) {
            elements.nextPageBtn.addEventListener('click', function() {
                console.log(' Clic bouton suivant (admin) - Page actuelle:', cache.currentPage, 'Total pages:', cache.totalPages);
                const codePostal = elements.codePostalSelect ? elements.codePostalSelect.value : cache.codePostal;
                const nextPage = cache.currentPage + 1;
                
                if (nextPage <= cache.totalPages) {
                    console.log(' Navigation vers page:', nextPage);
                    performSearch(codePostal, nextPage, cache.pageSize);
                } else {
                    console.log(' Déjà sur la dernière page');
                }
            });
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        setTimeout(initialize, 0);
    }
    
    //  NOUVEAU : Exposer les fonctions de débogage (optionnel)
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        window.forceUpdatePagination = forceUpdatePagination;
        window.sciCache = cache;
    }
    
    window.sciAdminInitialized = true;
})(); 