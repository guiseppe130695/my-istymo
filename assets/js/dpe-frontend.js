// Script pour les fonctionnalités DPE frontend

// Variables globales
var currentPage = 1;
var totalPages = 1;
var totalResults = 0;
var currentSearchParams = {
    codePostal: '',
    buildingType: '',
    keywordSearch: ''
};

// Variables pour la pagination
var nextPageUrl = null;
var previousPageUrls = [];
var currentPageUrl = null;
var pageHistory = []; // Historique des pages pour un calcul plus précis

// Fonction pour construire l'URL de l'API
function buildApiUrl(page = 1) {
    var baseUrl = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines?size=50&sort=-date_reception_dpe&q_mode=complete&q_fields=code_postal_ban,type_batiment,adresse_ban,adresse_brut';
    
    // Construire la requête structurée pour les filtres
    var queryString = 'code_postal_ban:"' + currentSearchParams.codePostal + '"';
    
    // Ajouter le type de bâtiment si sélectionné
    if (currentSearchParams.buildingType) {
        queryString += ' AND type_batiment:"' + currentSearchParams.buildingType.toLowerCase() + '"';
    }
    
    // Ajouter le paramètre qs pour les filtres structurés
    baseUrl += '&qs=' + encodeURIComponent(queryString);
    
    // Ajouter la recherche textuelle avec le paramètre q si un mot-clé est spécifié
    if (currentSearchParams.keywordSearch && currentSearchParams.keywordSearch.trim()) {
        var keyword = currentSearchParams.keywordSearch.trim();
        baseUrl += '&q=' + encodeURIComponent(keyword);
    }



    return baseUrl;
}

// Fonction pour récupérer les données de l'API
function fetchDataFromApi(url, successCallback, errorCallback) {
    // Afficher l'URL de la requête
    var urlDisplay = document.getElementById('api-url-display');
    var urlSpan = document.getElementById('current-api-url');
    if (urlDisplay && urlSpan) {
        urlSpan.textContent = url;
        urlDisplay.style.display = 'block';
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);

    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            var parsedResponse = JSON.parse(xhr.responseText);
            
            // Gérer la pagination
            nextPageUrl = parsedResponse.next || null;
            currentPageUrl = url;
            
            successCallback(parsedResponse);
        } else {
            errorCallback();
        }
    };

    xhr.onerror = function() {
        errorCallback();
    };

    xhr.send();
}

// Fonction pour afficher les résultats
function displayResults(data) {
    var tbody = document.getElementById('results-tbody');
    if (!tbody) {
        console.warn('⚠️ Élément results-tbody non trouvé');
        return;
    }
    
    tbody.innerHTML = '';

    if (data.results && data.results.length > 0) {
        totalResults = data.total;
        
        // Calculer le nombre total de pages (approximatif)
        totalPages = Math.ceil(totalResults / 50);

        data.results.forEach(function (result) {
            var row = document.createElement('tr');
            
            // Bouton favoris
            var favCell = document.createElement('td');
            var favBtn = document.createElement('button');
            favBtn.className = 'favorite-btn';
            favBtn.innerHTML = '☆';
            favBtn.setAttribute('data-numero-dpe', result.numero_dpe || '');
            favBtn.setAttribute('data-type-batiment', result.type_batiment || '');
            favBtn.setAttribute('data-adresse', result.adresse_ban || result.adresse_brut || '');
            favBtn.setAttribute('data-commune', result.nom_commune_ban || result.nom_commune_brut || '');
            favBtn.setAttribute('data-code-postal', result.code_postal_ban || result.code_postal_brut || '');
            favBtn.setAttribute('data-surface', result.surface_habitable_logement || '');
            favBtn.setAttribute('data-etiquette-dpe', result.etiquette_dpe || '');
            favBtn.setAttribute('data-etiquette-ges', result.etiquette_ges || '');
            favBtn.setAttribute('data-date-dpe', result.date_etablissement_dpe || result.date_reception_dpe || '');
            favBtn.title = 'Ajouter aux favoris';
            favCell.appendChild(favBtn);
            row.appendChild(favCell);

            // Type bâtiment
            row.appendChild(createCell(result.type_batiment || 'Non spécifié'));
            
            // Date DPE
            row.appendChild(createCell(formatDate(result.date_etablissement_dpe || result.date_reception_dpe)));
            
            // Adresse
            row.appendChild(createCell(result.adresse_ban || result.adresse_brut || 'Non spécifié'));
            
            // Commune
            row.appendChild(createCell(result.nom_commune_ban || result.nom_commune_brut || 'Non spécifié'));
            
            // Surface
            row.appendChild(createCell(result.surface_habitable_logement ? result.surface_habitable_logement + ' m²' : 'Non spécifié'));
            
            // Étiquette DPE
            var dpeCell = document.createElement('td');
            var dpeLabel = document.createElement('span');
            dpeLabel.className = 'dpe-label ' + (result.etiquette_dpe || '');
            dpeLabel.textContent = result.etiquette_dpe || 'Non spécifié';
            dpeCell.appendChild(dpeLabel);
            row.appendChild(dpeCell);
            
            // Étiquette GES
            var gesCell = document.createElement('td');
            var gesLabel = document.createElement('span');
            gesLabel.className = 'dpe-label ' + (result.etiquette_ges || '');
            gesLabel.textContent = result.etiquette_ges || 'Non spécifié';
            gesCell.appendChild(gesLabel);
            row.appendChild(gesCell);
            
            // Géolocalisation avec adresse simple
            var geoCell = document.createElement('td');
            
            if (result.adresse_ban && result.adresse_ban.trim()) {
                var geoLink = document.createElement('a');
                geoLink.className = 'maps-link';
                geoLink.href = 'https://www.google.com/maps/place/' + encodeURIComponent(result.adresse_ban.trim());
                geoLink.target = '_blank';
                geoLink.rel = 'noopener noreferrer';
                geoLink.innerHTML = 'Localiser';
                geoLink.title = 'Localiser sur Google Maps';
                geoCell.appendChild(geoLink);
            } else {
                geoCell.textContent = 'Non disponible';
            }
            row.appendChild(geoCell);
            
            // ✅ NOUVEAU : Checkbox pour l'envoi de courrier (maisons uniquement)
            var letterCell = document.createElement('td');
            
            // Créer un conteneur pour la checkbox et le label
            var checkboxContainer = document.createElement('div');
            checkboxContainer.className = 'checkbox-container';
            
            var letterCheckbox = document.createElement('input');
            letterCheckbox.type = 'checkbox';
            letterCheckbox.className = 'send-letter-checkbox';
            letterCheckbox.setAttribute('data-numero-dpe', result.numero_dpe || '');
            letterCheckbox.setAttribute('data-type-batiment', result.type_batiment || '');
            letterCheckbox.setAttribute('data-adresse', result.adresse_ban || result.adresse_brut || '');
            letterCheckbox.setAttribute('data-commune', result.nom_commune_ban || result.nom_commune_brut || '');
            letterCheckbox.setAttribute('data-code-postal', result.code_postal_ban || result.code_postal_brut || '');
            letterCheckbox.setAttribute('data-surface', result.surface_habitable_logement || '');
            letterCheckbox.setAttribute('data-etiquette-dpe', result.etiquette_dpe || '');
            letterCheckbox.setAttribute('data-etiquette-ges', result.etiquette_ges || '');
            letterCheckbox.setAttribute('data-date-dpe', result.date_etablissement_dpe || result.date_reception_dpe || '');
            
            // Créer un label pour la checkbox
            var checkboxLabel = document.createElement('span');
            checkboxLabel.className = 'checkbox-label';
            
            // Désactiver si ce n'est pas une maison
            if (result.type_batiment && result.type_batiment.toLowerCase() !== 'maison') {
                letterCheckbox.disabled = true;
                letterCheckbox.title = 'Envoi de courrier disponible uniquement pour les maisons';
                checkboxLabel.textContent = 'Non disponible';
                checkboxLabel.classList.add('disabled');
            } else {
                letterCheckbox.title = 'Sélectionner pour l\'envoi de courrier';
                checkboxLabel.textContent = 'Sélectionner';
                checkboxLabel.classList.add('enabled');
            }
            
            checkboxContainer.appendChild(letterCheckbox);
            checkboxContainer.appendChild(checkboxLabel);
            letterCell.appendChild(checkboxContainer);
            row.appendChild(letterCell);

            tbody.appendChild(row);
        });

        // Mettre à jour les informations de pagination
        updatePaginationInfo();
        showPaginationControls();
        
        // ✅ NOUVEAU : Réinitialiser les favoris après affichage des résultats
        if (typeof window.refreshFavorisAfterPageChange === 'function') {
            window.refreshFavorisAfterPageChange();
        } else if (typeof window.updateFavButtons === 'function') {
            window.updateFavButtons();
            if (typeof window.attachFavorisListeners === 'function') {
                window.attachFavorisListeners();
            }
        }
        
        // ✅ NOUVEAU : Initialiser le système de sélection DPE APRÈS création des checkboxes
        if (typeof window.DPESelectionSystem !== 'undefined') {
            console.log('🔄 DPE Panel - Réinitialisation du système de sélection externe');
            window.DPESelectionSystem.reinitialize();
        } else {
            console.log('⚠️ DPE Panel - Aucun système de sélection disponible');
        }
        
        // ✅ NOUVEAU : S'assurer que le bouton d'envoi est correctement configuré
        const sendLettersBtn = document.getElementById('send-letters-btn');
        if (sendLettersBtn) {
            let count = 0;
            
            if (typeof window.DPESelectionSystem !== 'undefined') {
                count = window.DPESelectionSystem.getCount();
            }
            
            console.log('📊 DPE Panel - Nombre de sélections après affichage:', count);
            if (count === 0) {
                if (typeof window.forceDisableSendButton === 'function') {
                    window.forceDisableSendButton();
                }
            } else {
                if (typeof window.enableSendButton === 'function') {
                    window.enableSendButton();
                }
            }
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 20px; color: #666;">Aucun résultat trouvé</td></tr>';
        hidePaginationControls();
    }
}

// Fonction pour créer une cellule
function createCell(content) {
    var cell = document.createElement('td');
    cell.textContent = content;
    return cell;
}

// Fonction pour formater la date en format dd/MM/YY
function formatDate(dateString) {
    if (!dateString) return 'Non spécifié';
    
    // Essayer de parser la date dans différents formats
    var dateObj = new Date(dateString);
    if (isNaN(dateObj.getTime())) {
        // Essayer le format YYYY-MM-DD
        var parts = dateString.split('-');
        if (parts.length === 3) {
            dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
        } else {
            return dateString; // Retourner la chaîne originale si pas de date valide
        }
    }
    
    if (isNaN(dateObj.getTime()) || dateObj.getFullYear() < 1900) {
        return dateString;
    }
    
    // Formater en dd/MM/YY
    var day = String(dateObj.getDate()).padStart(2, '0');
    var month = String(dateObj.getMonth() + 1).padStart(2, '0');
    var year = String(dateObj.getFullYear()).slice(-2); // Prendre seulement les 2 derniers chiffres
    
    return day + '/' + month + '/' + year;
}

// Fonction pour mettre à jour les informations de pagination
function updatePaginationInfo() {
    var pageInfo = document.getElementById('page-info');
    var paginationInfo = document.getElementById('pagination-info');
    
    if (pageInfo) {
        pageInfo.textContent = currentPage + '/' + totalPages;
    }
    
    if (paginationInfo) {
        paginationInfo.textContent = totalResults + ' résultat(s) trouvé(s)';
        paginationInfo.style.display = 'block';
    }
}

// Fonction pour afficher les contrôles de pagination
function showPaginationControls() {
    var controls = document.getElementById('pagination-controls');
    if (!controls) return;
    
    controls.style.display = 'block';
    
    var prevBtn = document.getElementById('prev-page');
    var nextBtn = document.getElementById('next-page');
    
    // Activer/désactiver le bouton précédent
    if (prevBtn) {
        prevBtn.disabled = previousPageUrls.length === 0;
    }
    
    // Activer/désactiver le bouton suivant
    if (nextBtn) {
        nextBtn.disabled = !nextPageUrl;
    }
}

// Fonction pour masquer les contrôles de pagination
function hidePaginationControls() {
    var controls = document.getElementById('pagination-controls');
    if (controls) {
        controls.style.display = 'none';
    }
}

// Fonction pour effectuer une recherche
function performSearch() {
    var codePostalElement = document.getElementById('codePostal');
    var buildingTypeElement = document.getElementById('buildingType');
    var keywordSearchElement = document.getElementById('keywordSearch');
    
    // Vérifier que les éléments existent
    if (!codePostalElement) {
        console.warn('⚠️ Élément codePostal non trouvé');
        return;
    }
    
    var codePostal = codePostalElement.value;
    var buildingType = buildingTypeElement ? buildingTypeElement.value : '';
    var keywordSearch = keywordSearchElement ? keywordSearchElement.value : '';
    
    if (!codePostal) {
        alert('Veuillez sélectionner un code postal');
        return;
    }
    
    currentSearchParams.codePostal = codePostal;
    currentSearchParams.buildingType = buildingType;
    currentSearchParams.keywordSearch = keywordSearch;
    
    // Réinitialiser la pagination
    nextPageUrl = null;
    previousPageUrls = [];
    currentPageUrl = null;
    pageHistory = []; // Réinitialiser l'historique des pages
    
    showLoading();
    hideError();
    
    var url = buildApiUrl();
    fetchDataFromApi(url, function(data) {
        hideLoading();
        displayResults(data);
        showResults();
    }, function() {
        hideLoading();
        showError('Erreur lors de la récupération des données depuis l\'API');
    });
}

// Fonctions d'affichage/masquage
function showLoading() {
    var loadingElement = document.getElementById('search-loading');
    if (loadingElement) {
        loadingElement.style.display = 'block';
    }
}

function hideLoading() {
    var loadingElement = document.getElementById('search-loading');
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }
}

function showResults() {
    var resultsElement = document.getElementById('search-results');
    if (resultsElement) {
        resultsElement.style.display = 'block';
    }
}

function hideResults() {
    var resultsElement = document.getElementById('search-results');
    if (resultsElement) {
        resultsElement.style.display = 'none';
    }
}

function showError(message) {
    var errorDiv = document.getElementById('search-error');
    var errorMessage = document.getElementById('error-message');
    if (errorDiv && errorMessage) {
        errorMessage.textContent = message;
        errorDiv.style.display = 'block';
    }
}

function hideError() {
    var errorDiv = document.getElementById('search-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

// ✅ NOUVEAU : Fonction pour forcer la désactivation du bouton
function forceDisableSendButton() {
    const sendLettersBtn = document.getElementById('send-letters-btn');
    if (sendLettersBtn) {
        sendLettersBtn.disabled = true;
        sendLettersBtn.setAttribute('disabled', 'disabled');
        sendLettersBtn.classList.add('disabled');
        
        console.log('🔒 Bouton d\'envoi forcé à désactivé');
    }
}

// ✅ NOUVEAU : Fonction pour activer le bouton
function enableSendButton() {
    const sendLettersBtn = document.getElementById('send-letters-btn');
    if (sendLettersBtn) {
        sendLettersBtn.disabled = false;
        sendLettersBtn.removeAttribute('disabled');
        sendLettersBtn.classList.remove('disabled');
        
        console.log('🔓 Bouton d\'envoi activé');
    }
}

// ✅ NOUVEAU : Fonction pour ouvrir le popup de campagne
function openCampaignPopup() {
    let selectedDPEs = [];
    
    // Récupérer les données selon le système disponible
    if (typeof window.DPESelectionSystem !== 'undefined') {
        selectedDPEs = window.DPESelectionSystem.getSelectedData();
    }
    
    // SUPPRIMER l'alerte contextuelle si aucune sélection
    if (selectedDPEs.length === 0) {
        // Ne rien faire
        return;
    }
    
    console.log('📬 Ouverture du popup avec', selectedDPEs.length, 'DPE sélectionnées');
    
    // Remplir la liste des DPE sélectionnées
    const selectedDpeList = document.getElementById('selected-dpe-list');
    if (selectedDpeList) {
        selectedDpeList.innerHTML = '';
        selectedDPEs.forEach(dpe => {
            const li = document.createElement('li');
            li.className = 'selected-dpe-item';
            li.innerHTML = `
                <strong>${dpe.adresse}</strong><br>
                <small>Commune: ${dpe.commune}</small><br>
                <small>DPE: ${dpe.etiquette_dpe} | GES: ${dpe.etiquette_ges}</small><br>
                <small>Surface: ${dpe.surface} | Date: ${dpe.date_dpe}</small>
            `;
            selectedDpeList.appendChild(li);
        });
    }
    
    // Afficher le popup
    const lettersPopup = document.getElementById('dpe-letters-popup');
    const step1 = document.getElementById('dpe-step-1');
    const step2 = document.getElementById('dpe-step-2');
    
    if (lettersPopup) {
        lettersPopup.style.display = 'flex';
        if (step1) {
            step1.classList.remove('hidden');
            step1.style.display = 'block';
        }
        if (step2) {
            step2.classList.add('hidden');
            step2.style.display = 'none';
        }
    }
}

// ✅ NOUVEAU : Rendre les fonctions disponibles globalement
window.forceDisableSendButton = forceDisableSendButton;
window.enableSendButton = enableSendButton;
window.openCampaignPopup = openCampaignPopup;

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les codes postaux par défaut
    var codePostalSelect = document.getElementById('codePostal');
    if (codePostalSelect && codePostalSelect.value) {
        currentSearchParams.codePostal = codePostalSelect.value;
    }
    
    // Gestionnaire de soumission du formulaire
    var searchForm = document.getElementById('dpe-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
    
    // Gestionnaire de changement de code postal
    if (codePostalSelect) {
        codePostalSelect.addEventListener('change', function() {
            if (this.value) {
                performSearch();
            }
        });
    }
    
    // Gestionnaires de pagination
    var prevBtn = document.getElementById('prev-page');
    var nextBtn = document.getElementById('next-page');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            if (previousPageUrls.length > 0) {
                // Récupérer l'URL précédente
                var previousUrl = previousPageUrls.pop();
                
                // Sauvegarder l'URL actuelle comme "next" pour pouvoir revenir
                if (currentPageUrl) {
                    nextPageUrl = currentPageUrl;
                }

                // Retirer la page actuelle de l'historique
                pageHistory.pop();
                
                showLoading();
                fetchDataFromApi(previousUrl, function(data) {
                    hideLoading();
                    displayResults(data);
                }, function() {
                    hideLoading();
                    showError('Erreur lors de la récupération de la page précédente');
                });
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            if (nextPageUrl) {
                // Sauvegarder l'URL actuelle pour pouvoir revenir
                if (currentPageUrl) {
                    previousPageUrls.push(currentPageUrl);
                }
                
                // Ajouter la page actuelle à l'historique
                pageHistory.push(currentPageUrl);
                
                showLoading();
                fetchDataFromApi(nextPageUrl, function(data) {
                    hideLoading();
                    displayResults(data);
                }, function() {
                    hideLoading();
                    showError('Erreur lors de la récupération de la page suivante');
                });
            }
        });
    }
    
    // Chargement initial si un code postal est sélectionné
    if (codePostalSelect && codePostalSelect.value) {
        performSearch();
    }
    
    // ✅ NOUVEAU : Désactiver le bouton dès que le DOM est prêt
    forceDisableSendButton();
    
    // Attacher l'événement au bouton d'envoi
    const sendLettersBtn = document.getElementById('send-letters-btn');
    if (sendLettersBtn) {
        sendLettersBtn.addEventListener('click', openCampaignPopup);
        console.log('🔗 Événement de clic attaché au bouton d\'envoi');
    }
    
    // Navigation entre les étapes du popup DPE
    const toStep2Btn = document.getElementById('dpe-to-step-2');
    if (toStep2Btn) {
        toStep2Btn.addEventListener('click', function() {
            const step1 = document.getElementById('dpe-step-1');
            const step2 = document.getElementById('dpe-step-2');
            if (step1 && step2) {
                step1.classList.add('hidden');
                step1.style.display = 'none';
                step2.classList.remove('hidden');
                step2.style.display = 'block';
            }
        });
    }
    
    // Optionnel : bouton retour étape 1
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'back-to-step-1') {
            const step1 = document.getElementById('dpe-step-1');
            const step2 = document.getElementById('dpe-step-2');
            if (step1 && step2) {
                step2.classList.add('hidden');
                step2.style.display = 'none';
                step1.classList.remove('hidden');
                step1.style.display = 'block';
            }
        }
    });
});

// ✅ NOUVEAU : Initialisation du système de sélection au chargement
window.onload = function () {
    // ✅ NOUVEAU : Forcer la désactivation du bouton immédiatement
    forceDisableSendButton();
    
    // Vérifier que l'élément codePostal existe avant d'accéder à sa valeur
    var codePostalElement = document.getElementById('codePostal');
    if (codePostalElement && codePostalElement.value) {
        performSearch();
    }
    
    // ✅ NOUVEAU : Initialiser le système de sélection DPE
    if (typeof window.DPESelectionSystem !== 'undefined') {
        console.log('🔧 Initialisation du système de sélection externe');
        window.DPESelectionSystem.init();
    } else {
        console.log('⚠️ Aucun système de sélection disponible');
        // Fallback : initialisation manuelle du bouton
        const sendLettersBtn = document.getElementById('send-letters-btn');
        if (sendLettersBtn) {
            sendLettersBtn.disabled = true;
        }
    }
    
    // ✅ NOUVEAU : S'assurer que le bouton est correctement configuré au chargement initial
    setTimeout(() => {
        const sendLettersBtn = document.getElementById('send-letters-btn');
        if (sendLettersBtn) {
            let count = 0;
            
            if (typeof window.DPESelectionSystem !== 'undefined') {
                count = window.DPESelectionSystem.getCount();
            }
            
            if (count === 0) {
                forceDisableSendButton();
            } else {
                enableSendButton();
            }
        }
    }, 100);
    
    // ✅ NOUVEAU : Vérifier les systèmes de sélection disponibles
    console.log('🔍 Vérification des systèmes de sélection:');
    console.log('- DPESelectionSystem:', typeof window.DPESelectionSystem !== 'undefined');
}; 