<?php
/**
 * Template DPE avec Design System
 * Exemple d'utilisation du design system pour le panneau DPE
 */

// Vérifier si l'utilisateur est connecté
if (!is_user_logged_in()) {
    echo '<div class="dpe-container"><div class="dpe-alert dpe-alert--error">Vous devez être connecté pour accéder à cette page.</div></div>';
    return;
}

// Enqueue du design system CSS
wp_enqueue_style('dpe-design-system', plugin_dir_url(__FILE__) . '../assets/css/dpe-design-system.css', array(), '1.0.0');
?>

<div class="dpe-container">
    <!-- En-tête principal -->
    <div class="dpe-card dpe-card--elevated dpe-mb-lg">
        <div class="dpe-card__header">
            <h1 class="dpe-title-1">Diagnostic de Performance Énergétique</h1>
            <p class="dpe-subtitle">Recherchez et analysez les diagnostics énergétiques des logements</p>
        </div>
    </div>

    <!-- Formulaire de recherche -->
    <div class="dpe-search-form">
        <h2 class="dpe-title-2">Rechercher un DPE</h2>
        
        <div class="dpe-form-group">
            <label class="dpe-label" for="dpe-address">Adresse du logement</label>
            <input type="text" id="dpe-address" class="dpe-input" placeholder="Ex: 123 Rue de la Paix, Paris">
            <span class="dpe-caption">Saisissez l'adresse complète du logement à analyser</span>
        </div>
        
        <div class="dpe-flex dpe-gap-md">
            <button class="dpe-btn dpe-btn--primary dpe-btn--large" id="search-dpe-btn">
                Rechercher les DPE
            </button>
            <button class="dpe-btn dpe-btn--secondary dpe-btn--large" id="clear-search-btn">
                Effacer
            </button>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="dpe-stats-grid" id="dpe-stats" style="display: none;">
        <div class="dpe-stat-card">
            <div class="dpe-stat-value" id="total-dpe">0</div>
            <div class="dpe-stat-label">DPE trouvés</div>
        </div>
        <div class="dpe-stat-card">
            <div class="dpe-stat-value" id="avg-class">-</div>
            <div class="dpe-stat-label">Classe moyenne</div>
        </div>
        <div class="dpe-stat-card">
            <div class="dpe-stat-value" id="avg-consumption">0</div>
            <div class="dpe-stat-label">kWh/m²/an</div>
        </div>
        <div class="dpe-stat-card">
            <div class="dpe-stat-value" id="avg-emissions">0</div>
            <div class="dpe-stat-label">kg CO₂/m²/an</div>
        </div>
    </div>

    <!-- Zone de résultats -->
    <div id="dpe-results" style="display: none;">
        <div class="dpe-flex dpe-justify-between dpe-items-center dpe-mb-lg">
            <h2 class="dpe-title-2">Résultats de la recherche</h2>
            <div class="dpe-flex dpe-gap-sm">
                <button class="dpe-btn dpe-btn--small dpe-btn--secondary" id="export-results-btn">
                    Exporter
                </button>
                <button class="dpe-btn dpe-btn--small dpe-btn--primary" id="add-all-favorites-btn">
                    Tout ajouter aux favoris
                </button>
            </div>
        </div>
        
        <div class="dpe-results-grid" id="results-grid">
            <!-- Les résultats seront injectés ici par JavaScript -->
        </div>
    </div>

    <!-- Zone de favoris -->
    <div id="dpe-favorites" style="display: none;">
        <div class="dpe-flex dpe-justify-between dpe-items-center dpe-mb-lg">
            <h2 class="dpe-title-2">⭐ Mes DPE Favoris</h2>
            <button class="dpe-btn dpe-btn--small dpe-btn--secondary" id="clear-favorites-btn">
                <span class="dashicons dashicons-trash"></span>
                Vider les favoris
            </button>
        </div>
        
        <div class="dpe-results-grid" id="favorites-grid">
            <!-- Les favoris seront injectés ici -->
        </div>
    </div>

    <!-- Messages d'état -->
    <div id="dpe-messages"></div>
</div>

<!-- Template pour une carte de résultat DPE -->
<template id="dpe-result-template">
    <div class="dpe-result-card" data-dpe-id="">
        <div class="dpe-energy-class dpe-badge--class-" data-energy-class="">
            <span class="energy-letter"></span>
        </div>
        
        <h3 class="dpe-title-4 dpe-mb-sm address-title"></h3>
        <p class="dpe-body dpe-mb-sm property-type"></p>
        
        <!-- Indicateur de performance énergétique -->
        <div class="dpe-energy-meter dpe-mb-md">
            <div class="dpe-energy-meter__scale">
                <div class="dpe-energy-meter__bar" data-energy-class="" style="width: 0%"></div>
            </div>
            <span class="dpe-energy-meter__label energy-letter"></span>
        </div>
        
        <!-- Informations détaillées -->
        <div class="dpe-flex dpe-justify-between dpe-mb-md">
            <div>
                <span class="dpe-caption">Consommation</span>
                <div class="dpe-body consumption-value"></div>
            </div>
            <div>
                <span class="dpe-caption">Émissions GES</span>
                <div class="dpe-body emissions-value"></div>
            </div>
        </div>
        
        <div class="dpe-flex dpe-justify-between dpe-mb-md">
            <div>
                <span class="dpe-caption">Surface</span>
                <div class="dpe-body surface-value"></div>
            </div>
            <div>
                <span class="dpe-caption">Date DPE</span>
                <div class="dpe-body dpe-date"></div>
            </div>
        </div>
        
        <!-- Complément d'adresse si disponible -->
        <div class="dpe-mb-md" id="address-complement" style="display: none;">
            <span class="dpe-caption">Complément d'adresse</span>
            <div class="dpe-body address-complement"></div>
        </div>
        
        <!-- Actions -->
        <div class="dpe-flex dpe-gap-sm">
            <button class="dpe-btn dpe-btn--primary dpe-btn--small view-details-btn">
                <span class="dashicons dashicons-visibility"></span>
                Voir détails
            </button>
            <button class="dpe-btn dpe-btn--secondary dpe-btn--small favorite-btn">
                <span class="dashicons dashicons-star-empty"></span>
                Favoris
            </button>
        </div>
    </div>
</template>

<!-- Template pour les alertes -->
<template id="dpe-alert-template">
    <div class="dpe-alert dpe-alert--" data-alert-type="">
        <strong class="alert-title"></strong>
        <span class="alert-message"></span>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let searchResults = [];
    let favorites = JSON.parse(localStorage.getItem('dpe-favorites') || '[]');
    
    // Éléments DOM
    const searchBtn = document.getElementById('search-dpe-btn');
    const clearBtn = document.getElementById('clear-search-btn');
    const addressInput = document.getElementById('dpe-address');
    const resultsContainer = document.getElementById('dpe-results');
    const favoritesContainer = document.getElementById('dpe-favorites');
    const statsContainer = document.getElementById('dpe-stats');
    const messagesContainer = document.getElementById('dpe-messages');
    
    // Templates
    const resultTemplate = document.getElementById('dpe-result-template');
    const alertTemplate = document.getElementById('dpe-alert-template');
    
    // Initialisation
    init();
    
    function init() {
        // Event listeners
        searchBtn.addEventListener('click', handleSearch);
        clearBtn.addEventListener('click', handleClear);
        addressInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') handleSearch();
        });
        
        // Afficher les favoris s'il y en a
        if (favorites.length > 0) {
            displayFavorites();
        }
    }
    
    function handleSearch() {
        const address = addressInput.value.trim();
        
        if (!address) {
            showAlert('warning', 'Attention', 'Veuillez saisir une adresse');
            return;
        }
        
        // Simulation de recherche (remplacer par votre logique API)
        showAlert('info', 'Recherche en cours', 'Recherche des DPE pour : ' + address);
        
        // Simuler des résultats après 2 secondes
        setTimeout(() => {
            const mockResults = generateMockResults(address);
            displayResults(mockResults);
            updateStats(mockResults);
        }, 2000);
    }
    
    function handleClear() {
        addressInput.value = '';
        resultsContainer.style.display = 'none';
        statsContainer.style.display = 'none';
        clearMessages();
    }
    
    function generateMockResults(address) {
        // Générer des résultats fictifs pour la démonstration
        const classes = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        const results = [];
        
        for (let i = 0; i < Math.floor(Math.random() * 5) + 3; i++) {
            const energyClass = classes[Math.floor(Math.random() * classes.length)];
            const consumption = Math.floor(Math.random() * 300) + 50;
            const emissions = Math.floor(consumption * 0.2);
            const surface = Math.floor(Math.random() * 200) + 50;
            
            results.push({
                id: 'dpe_' + Date.now() + '_' + i,
                address: address + (i > 0 ? ' - Logement ' + (i + 1) : ''),
                complement: i === 0 ? 'Appartement 3 pièces' : null,
                energyClass: energyClass,
                consumption: consumption,
                emissions: emissions,
                surface: surface,
                date: '2023-' + String(Math.floor(Math.random() * 12) + 1).padStart(2, '0') + '-15',
                propertyType: ['Appartement', 'Maison', 'Studio'][Math.floor(Math.random() * 3)]
            });
        }
        
        return results;
    }
    
    function displayResults(results) {
        searchResults = results;
        const grid = document.getElementById('results-grid');
        grid.innerHTML = '';
        
        results.forEach(result => {
            const card = createResultCard(result);
            grid.appendChild(card);
        });
        
        resultsContainer.style.display = 'block';
        resultsContainer.scrollIntoView({ behavior: 'smooth' });
    }
    
    function createResultCard(result) {
        const template = resultTemplate.content.cloneNode(true);
        const card = template.querySelector('.dpe-result-card');
        
        // Données de base
        card.setAttribute('data-dpe-id', result.id);
        card.querySelector('.address-title').textContent = result.address;
        card.querySelector('.property-type').textContent = result.propertyType;
        card.querySelector('.consumption-value').textContent = result.consumption + ' kWh/m²/an';
        card.querySelector('.emissions-value').textContent = result.emissions + ' kg CO₂/m²/an';
        card.querySelector('.surface-value').textContent = result.surface + ' m²';
        card.querySelector('.dpe-date').textContent = result.date;
        
        // Classe énergétique
        const energyClass = result.energyClass.toLowerCase();
        card.querySelector('.dpe-energy-class').className = 'dpe-energy-class dpe-badge--class-' + energyClass;
        card.querySelectorAll('.energy-letter').forEach(el => el.textContent = result.energyClass);
        card.querySelector('.dpe-energy-meter__bar').className = 'dpe-energy-meter__bar dpe-energy-meter__bar--' + energyClass;
        
        // Barre de progression (simulation)
        const progress = (7 - ['a', 'b', 'c', 'd', 'e', 'f', 'g'].indexOf(energyClass)) * 14.28;
        card.querySelector('.dpe-energy-meter__bar').style.width = progress + '%';
        
        // Complément d'adresse
        if (result.complement) {
            card.querySelector('#address-complement').style.display = 'block';
            card.querySelector('.address-complement').textContent = result.complement;
        }
        
        // État du favori
        const favoriteBtn = card.querySelector('.favorite-btn');
        const isFavorite = favorites.some(fav => fav.id === result.id);
        if (isFavorite) {
            favoriteBtn.innerHTML = '<span class="dashicons dashicons-star-filled"></span> Favori';
            favoriteBtn.classList.add('dpe-btn--success');
        }
        
        // Event listeners
        card.querySelector('.view-details-btn').addEventListener('click', () => viewDetails(result));
        favoriteBtn.addEventListener('click', () => toggleFavorite(result, favoriteBtn));
        
        return card;
    }
    
    function updateStats(results) {
        if (results.length === 0) return;
        
        const totalDpe = results.length;
        const avgConsumption = Math.round(results.reduce((sum, r) => sum + r.consumption, 0) / totalDpe);
        const avgEmissions = Math.round(results.reduce((sum, r) => sum + r.emissions, 0) / totalDpe);
        
        // Calculer la classe moyenne (simplifié)
        const classValues = results.map(r => ['a', 'b', 'c', 'd', 'e', 'f', 'g'].indexOf(r.energyClass.toLowerCase()));
        const avgClassValue = Math.round(classValues.reduce((sum, val) => sum + val, 0) / totalDpe);
        const avgClass = ['A', 'B', 'C', 'D', 'E', 'F', 'G'][avgClassValue];
        
        document.getElementById('total-dpe').textContent = totalDpe;
        document.getElementById('avg-class').textContent = avgClass;
        document.getElementById('avg-consumption').textContent = avgConsumption;
        document.getElementById('avg-emissions').textContent = avgEmissions;
        
        statsContainer.style.display = 'grid';
    }
    
    function toggleFavorite(result, button) {
        const index = favorites.findIndex(fav => fav.id === result.id);
        
        if (index > -1) {
            // Retirer des favoris
            favorites.splice(index, 1);
            button.innerHTML = '<span class="dashicons dashicons-star-empty"></span> Favoris';
            button.classList.remove('dpe-btn--success');
            showAlert('info', 'Favori retiré', result.address + ' a été retiré de vos favoris');
        } else {
            // Ajouter aux favoris
            favorites.push(result);
            button.innerHTML = '<span class="dashicons dashicons-star-filled"></span> Favori';
            button.classList.add('dpe-btn--success');
            showAlert('success', 'Favori ajouté', result.address + ' a été ajouté à vos favoris');
        }
        
        localStorage.setItem('dpe-favorites', JSON.stringify(favorites));
        displayFavorites();
    }
    
    function displayFavorites() {
        if (favorites.length === 0) {
            favoritesContainer.style.display = 'none';
            return;
        }
        
        const grid = document.getElementById('favorites-grid');
        grid.innerHTML = '';
        
        favorites.forEach(favorite => {
            const card = createResultCard(favorite);
            grid.appendChild(card);
        });
        
        favoritesContainer.style.display = 'block';
    }
    
    function viewDetails(result) {
        // Ici vous pouvez ouvrir un modal ou rediriger vers une page de détails
        showAlert('info', 'Détails DPE', 'Affichage des détails pour : ' + result.address);
    }
    
    function showAlert(type, title, message) {
        const template = alertTemplate.content.cloneNode(true);
        const alert = template.querySelector('.dpe-alert');
        
        alert.className = 'dpe-alert dpe-alert--' + type;
        alert.querySelector('.alert-title').textContent = title + ' : ';
        alert.querySelector('.alert-message').textContent = message;
        
        messagesContainer.appendChild(alert);
        
        // Auto-supprimer après 5 secondes
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 5000);
    }
    
    function clearMessages() {
        messagesContainer.innerHTML = '';
    }
    
    // Event listeners pour les boutons d'action
    document.getElementById('export-results-btn')?.addEventListener('click', () => {
        showAlert('info', 'Export', 'Export des résultats en cours...');
    });
    
    document.getElementById('add-all-favorites-btn')?.addEventListener('click', () => {
        searchResults.forEach(result => {
            if (!favorites.some(fav => fav.id === result.id)) {
                favorites.push(result);
            }
        });
        localStorage.setItem('dpe-favorites', JSON.stringify(favorites));
        displayFavorites();
        showAlert('success', 'Favoris', 'Tous les résultats ont été ajoutés aux favoris');
    });
    
    document.getElementById('clear-favorites-btn')?.addEventListener('click', () => {
        if (confirm('Êtes-vous sûr de vouloir vider tous vos favoris ?')) {
            favorites = [];
            localStorage.removeItem('dpe-favorites');
            displayFavorites();
            showAlert('info', 'Favoris', 'Tous vos favoris ont été supprimés');
        }
    });
});
</script>
