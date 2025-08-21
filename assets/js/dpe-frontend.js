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

    // Afficher l'URL dans la console pour debug
    console.log('URL de recherche DPE:', baseUrl);
    console.log('Paramètres de recherche:', currentSearchParams);

    return baseUrl;
}

// Fonction pour récupérer les données de l'API
function fetchDataFromApi(url, successCallback, errorCallback) {
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
            
            // Adresse (nettoyée)
            row.appendChild(createCell(cleanAddress(result.adresse_ban || result.adresse_brut)));
            
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
            
            // Complément adresse
            var complementCell = document.createElement('td');
            var complementText = result.complement_adresse_logement || result.complement_adresse_batiment || 'Non spécifié';
            complementCell.textContent = complementText;
            row.appendChild(complementCell);
            
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

            tbody.appendChild(row);
        });

        // Mettre à jour les informations de pagination
        updatePaginationInfo();
        showPaginationControls();
        
        // Réinitialiser les favoris après affichage des résultats
        if (typeof window.refreshFavorisAfterPageChange === 'function') {
            window.refreshFavorisAfterPageChange();
        } else if (typeof window.updateFavButtons === 'function') {
            window.updateFavButtons();
            if (typeof window.attachFavorisListeners === 'function') {
                window.attachFavorisListeners();
            }
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px; color: #666;">Aucun résultat trouvé</td></tr>';
        hidePaginationControls();
    }
}

// Fonction pour nettoyer l'adresse (enlever code postal et commune)
function cleanAddress(address) {
    if (!address) return 'Non spécifié';
    
    // Supprimer le code postal (5 chiffres) et la commune qui suivent
    var cleaned = address.replace(/\s+\d{5}\s+[A-Za-zÀ-ÿ\s-]+$/, '');
    
    // Si l'adresse est vide après nettoyage, retourner l'original
    return cleaned.trim() || address.trim();
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
    // Calculer la page actuelle basée sur l'historique plutôt que sur l'incrémentation manuelle
    var actualPage = pageHistory.length + 1;
    pageInfo.textContent = actualPage + '/' + totalPages;
    
    var paginationInfo = document.getElementById('pagination-info');
    paginationInfo.textContent = totalResults + ' résultat(s) trouvé(s)';
    paginationInfo.style.display = 'block';
}

// Fonction pour afficher les contrôles de pagination
function showPaginationControls() {
    var controls = document.getElementById('pagination-controls');
    controls.style.display = 'block';
    
    var prevBtn = document.getElementById('prev-page');
    var nextBtn = document.getElementById('next-page');
    
    // Activer/désactiver le bouton précédent
    prevBtn.disabled = previousPageUrls.length === 0;
    
    // Activer/désactiver le bouton suivant
    nextBtn.disabled = !nextPageUrl;
}

// Fonction pour masquer les contrôles de pagination
function hidePaginationControls() {
    var controls = document.getElementById('pagination-controls');
    controls.style.display = 'none';
}

// Fonction pour effectuer une recherche
function performSearch() {
    var codePostal = document.getElementById('codePostal').value;
    var buildingType = document.getElementById('buildingType').value;
    var keywordSearch = document.getElementById('keywordSearch').value;
    
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
    document.getElementById('search-loading').style.display = 'block';
}

function hideLoading() {
    document.getElementById('search-loading').style.display = 'none';
}

function showResults() {
    document.getElementById('search-results').style.display = 'block';
}

function hideResults() {
    document.getElementById('search-results').style.display = 'none';
}

function showError(message) {
    var errorDiv = document.getElementById('search-error');
    document.getElementById('error-message').textContent = message;
    errorDiv.style.display = 'block';
}

function hideError() {
    document.getElementById('search-error').style.display = 'none';
}

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
}); 