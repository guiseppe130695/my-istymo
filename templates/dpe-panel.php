<?php
/**
 * Template pour le panneau principal DPE
 * Variables attendues dans $context :
 * - $codesPostauxArray : array des codes postaux de l'utilisateur
 * - $config_manager : instance du gestionnaire de configuration
 * - $favoris_handler : instance du gestionnaire de favoris
 * - $dpe_handler : instance du gestionnaire DPE
 */
?>

<div class="my-istymo dpe-panel">
    <div class="frontend-wrapper">
    <h1><i class="fas fa-search"></i> DPE – Recherche de Diagnostics</h1>

    <!-- Information pour les utilisateurs -->
    <div class="info-message">
        <p>
            <i class="fas fa-info-circle"></i> Recherchez les diagnostics de performance énergétique (DPE) par code postal. Consultez les étiquettes énergétiques et les informations détaillées.
        </p>
    </div>
    
    <!-- Affichage du code postal par défaut -->
    <?php if (!empty($codesPostauxArray)): ?>
    <div class="default-status">
        <p>
            <i class="fas fa-map-marker-alt"></i> <strong>Codes postaux disponibles :</strong> <?php echo esc_html(implode(', ', $codesPostauxArray)); ?>
            <span class="status-note">(le premier sera sélectionné automatiquement)</span>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- Affichage des avertissements de configuration -->
    <?php
    // Vérifier si la configuration API est complète
    if (!$config_manager->is_configured()) {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <strong>Configuration manquante :</strong> Veuillez configurer vos tokens API dans l\'administration.</div>';
    }
    ?>

    <!-- ✅ FORMULAIRE DE RECHERCHE AJAX -->
    <form id="dpe-search-form" class="search-form">
        <div class="form-row">
            <div class="form-field">
                <label for="codePostal"><i class="fas fa-map-marker-alt"></i> Votre code postal :</label>
                <select name="codePostal" id="codePostal" required>
                    <option value="">— Choisir un code postal —</option>
                    <?php foreach ($codesPostauxArray as $index => $value): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php echo ($index === 0) ? 'selected' : ''; ?>>
                            <?php echo esc_html($value); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="buildingType"><i class="fas fa-building"></i> Type de bâtiment :</label>
                <select name="buildingType" id="buildingType">
                    <option value="">— Tous les types —</option>
                    <option value="Maison"><i class="fas fa-home"></i> Maison</option>
                    <option value="Appartement"><i class="fas fa-building"></i> Appartement</option>
                    <option value="Immeuble"><i class="fas fa-city"></i> Immeuble</option>
                </select>
            </div>
            <button type="submit" id="search-btn" class="btn btn-primary">
                <i class="fas fa-search"></i> Rechercher les DPE
            </button>
        </div>
    </form>

    <!-- ✅ ZONE DE CHARGEMENT -->
    <div id="search-loading" class="d-none text-center" style="padding: 40px 20px; font-size: 16px; color: #666;">
        <span><i class="fas fa-spinner fa-spin"></i> Recherche en cours...</span>
    </div>

    <!-- ✅ AFFICHAGE DE L'URL DE LA REQUÊTE -->
    <div id="api-url-display" class="alert alert-info d-none">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong><i class="fas fa-link"></i> URL de la requête API :</strong>
            <button type="button" onclick="document.getElementById('api-url-display').classList.add('d-none')" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Masquer</button>
        </div>
        <span id="current-api-url" style="font-family: monospace; word-break: break-all; font-size: 12px;"></span>
    </div>

    <!-- ✅ ZONE DES RÉSULTATS - STRUCTURE STABLE -->
    <div id="search-results" class="search-results">
        <div id="results-header">
            <h2 id="results-title"> Résultats de recherche</h2>
            <div id="pagination-info" class="d-none"></div>
        </div>
        
        <!-- ✅ TABLEAU DES RÉSULTATS - STRUCTURE STABLE -->
        <table class="data-table dpe-results-table" id="results-table">
            <thead>
                <tr>
                    <th class="col-favoris"><i class="fas fa-heart" title="Favoris - Enregistrez les DPE pour les traiter dans la gestion des leads"></i></th>
                    <th class="col-type"><i class="fas fa-building"></i> Type</th>
                    <th class="col-date"><i class="fas fa-calendar"></i> Date</th>
                    <th class="col-adresse"><i class="fas fa-map-marker-alt"></i> Adresse</th>
                    <th class="col-surface"><i class="fas fa-expand-arrows-alt"></i> Surface</th>
                    <th class="col-etiquette"><i class="fas fa-certificate" title="Étiquette DPE - Classe énergétique du bien (A à G)"></i></th>
                    <th class="col-complement"><i class="fas fa-plus"></i> Complément adresse</th>
                    <th class="col-geolocalisation"><i class="fas fa-map"></i> Géolocalisation</th>
                </tr>
            </thead>
            <tbody id="results-tbody">
                <!-- Les résultats seront insérés ici par JavaScript -->
            </tbody>
        </table>
    </div>
    
    <!-- ✅ CONTRÔLES DE PAGINATION - HORS DE LA ZONE DES RÉSULTATS -->
    <div id="pagination-controls" class="pagination-controls">
        <div class="pagination-main">
            <button id="prev-page" class="pagination-btn" disabled><i class="fas fa-chevron-left"></i> Page précédente</button>
            <span id="page-info" class="page-info">1/1</span>
            <button id="next-page" class="pagination-btn" disabled>Page suivante <i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
    
    <!-- ✅ CACHE DES DONNÉES - ÉVITE LES RECHARGEMENTS -->
    <div id="data-cache" class="d-none">
        <span id="cached-title"></span>
        <span id="cached-page"></span>
        <span id="cached-total"></span>
    </div>

    <!-- ✅ ZONE D'ERREUR -->
    <div id="search-error" class="alert alert-danger d-none">
        <p id="error-message"><i class="fas fa-exclamation-circle"></i> <span id="error-text"></span></p>
    </div>
</div>
</div>



<script>
// Variables globales
var currentPage = 1;
var totalPages = 1;
var totalResults = 0;
var currentSearchParams = {
    codePostal: '<?php echo esc_js(!empty($codesPostauxArray) ? reset($codesPostauxArray) : ""); ?>'
};

// Variables pour la pagination
var nextPageUrl = null;
var previousPageUrls = [];
var currentPageUrl = null;

// Fonction pour construire l'URL de l'API
function buildApiUrl(page = 1) {
    var baseUrl = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines?size=50&sort=-date_reception_dpe&q_mode=complete&q_fields=code_postal_ban,type_batiment';
    
    // Construire la requête structurée
    var queryString = 'code_postal_ban:"' + currentSearchParams.codePostal + '"';
    
    // Ajouter le type de bâtiment si sélectionné
    if (currentSearchParams.buildingType) {
        queryString += ' AND type_batiment:"' + currentSearchParams.buildingType.toLowerCase() + '"';
    }
    
    baseUrl += '&qs=' + encodeURIComponent(queryString);



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
            favBtn.innerHTML = '<i class="far fa-heart"></i>';
            favBtn.setAttribute('data-numero-dpe', result.numero_dpe || '');
            favBtn.setAttribute('data-type-batiment', result.type_batiment || '');
            favBtn.setAttribute('data-adresse', result.adresse_ban || result.adresse_brut || '');
            favBtn.setAttribute('data-commune', result.nom_commune_ban || result.nom_commune_brut || '');
            favBtn.setAttribute('data-code-postal', result.code_postal_ban || result.code_postal_brut || '');
            favBtn.setAttribute('data-surface', result.surface_habitable_logement || '');
            favBtn.setAttribute('data-etiquette-dpe', result.etiquette_dpe || '');
            favBtn.setAttribute('data-etiquette-ges', result.etiquette_ges || '');
            favBtn.setAttribute('data-date-dpe', result.date_etablissement_dpe || result.date_reception_dpe || '');
            favBtn.title = 'Ajouter aux favoris - Enregistrez ce DPE pour le traiter dans la gestion des leads';
            favCell.appendChild(favBtn);
            row.appendChild(favCell);

            // Type bâtiment
            row.appendChild(createCell(result.type_batiment || 'Non spécifié'));
            
            // Date DPE
            row.appendChild(createCell(formatDate(result.date_etablissement_dpe || result.date_reception_dpe)));
            
            // Adresse complète (adresse + ville)
            var adresseComplete = cleanAddress(result.adresse_ban || result.adresse_brut);
            var commune = result.nom_commune_ban || result.nom_commune_brut || '';
            var codePostal = result.code_postal_ban || result.code_postal_brut || '';
            
            if (commune) {
                if (codePostal) {
                    adresseComplete += ', ' + codePostal + ' ' + commune;
                } else {
                    adresseComplete += ', ' + commune;
                }
            } else if (codePostal) {
                adresseComplete += ', ' + codePostal;
            }
            
            row.appendChild(createCell(adresseComplete || 'Non spécifié'));
            
            // Surface
            row.appendChild(createCell(result.surface_habitable_logement ? result.surface_habitable_logement + ' m²' : 'Non spécifié'));
            
            // Étiquette DPE
            var dpeCell = document.createElement('td');
            var dpeLabel = document.createElement('span');
            dpeLabel.className = 'label ' + (result.etiquette_dpe || '');
            dpeLabel.textContent = result.etiquette_dpe || 'Non spécifié';
            dpeCell.appendChild(dpeLabel);
            row.appendChild(dpeCell);
            
            // Complément adresse
            var complementCell = document.createElement('td');
            var complementText = result.complement_adresse_logement || '';
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
                geoLink.innerHTML = '<i class="fas fa-map-marker-alt"></i> Localiser';
                geoLink.title = 'Localiser sur Google Maps';
                geoCell.appendChild(geoLink);
            } else {
                geoCell.innerHTML = '<i class="fas fa-ban"></i> Non disponible';
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
    pageInfo.textContent = currentPage + '/' + totalPages;
    
    var paginationInfo = document.getElementById('pagination-info');
    paginationInfo.textContent = totalResults + ' résultat(s) trouvé(s)';
    paginationInfo.classList.remove('d-none');
}

// Fonction pour afficher les contrôles de pagination
function showPaginationControls() {
    var controls = document.getElementById('pagination-controls');
    controls.classList.remove('d-none');
    
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
    controls.classList.add('d-none');
}

// Fonction pour effectuer une recherche
function performSearch() {
    var codePostal = document.getElementById('codePostal').value;
    var buildingType = document.getElementById('buildingType').value;
    

    
    if (!codePostal) {
        alert('Veuillez sélectionner un code postal');
        return;
    }
    
    currentSearchParams.codePostal = codePostal;
    currentSearchParams.buildingType = buildingType;
    currentPage = 1;
    
    // Réinitialiser la pagination
    nextPageUrl = null;
    previousPageUrls = [];
    currentPageUrl = null;
    
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
    document.getElementById('search-loading').classList.remove('d-none');
}

function hideLoading() {
    document.getElementById('search-loading').classList.add('d-none');
}

function showResults() {
    document.getElementById('search-results').classList.remove('d-none');
}

function hideResults() {
    document.getElementById('search-results').classList.add('d-none');
}

function showError(message) {
    var errorDiv = document.getElementById('search-error');
    document.getElementById('error-text').textContent = message;
    errorDiv.classList.remove('d-none');
}

function hideError() {
    document.getElementById('search-error').classList.add('d-none');
}

// Gestionnaires d'événements
document.getElementById('dpe-search-form').addEventListener('submit', function(e) {
    e.preventDefault();
    performSearch();
});

document.getElementById('prev-page').addEventListener('click', function() {
    if (previousPageUrls.length > 0) {
        // Récupérer l'URL précédente
        var previousUrl = previousPageUrls.pop();
        
        // Sauvegarder l'URL actuelle comme "next" pour pouvoir revenir
        if (currentPageUrl) {
            nextPageUrl = currentPageUrl;
        }
        
        currentPage--;
        
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

document.getElementById('next-page').addEventListener('click', function() {
    if (nextPageUrl) {
        // Sauvegarder l'URL actuelle pour pouvoir revenir
        if (currentPageUrl) {
            previousPageUrls.push(currentPageUrl);
        }
        
        currentPage++;
        
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

// Chargement initial
window.onload = function () {
    if (document.getElementById('codePostal').value) {
        performSearch();
    }
};
</script> 