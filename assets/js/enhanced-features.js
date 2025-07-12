/**
 * ✅ NOUVEAU : Fonctionnalités avancées pour le plugin SCI
 * - Filtres et recherche avancée
 * - Statistiques en temps réel
 * - Notifications toast
 * - Mode sombre
 * - Export de données
 */

class SCIEnhancedFeatures {
    constructor() {
        this.stats = {
            totalResults: 0,
            selectedCount: 0,
            favorisCount: 0,
            contactedCount: 0
        };
        this.filters = {
            status: 'all',
            ville: '',
            codePostal: '',
            denomination: ''
        };
        this.init();
    }

    init() {
        this.initFilters();
        this.initStats();
        this.initNotifications();
        this.initDarkMode();
        this.initExport();
        this.updateStats();
        this.initAutoSearch(); // ✅ NOUVEAU : Initialisation de la recherche automatique
    }

    // ✅ Filtres et recherche avancée
    initFilters() {
        const filterPanel = document.querySelector('.filters-panel');
        if (!filterPanel) return;

        // Créer le panel de filtres
        filterPanel.innerHTML = `
            <h4>🔍 Filtres avancés</h4>
            <div class="filter-group">
                <label for="filter-status">Statut :</label>
                <select id="filter-status">
                    <option value="all">Tous</option>
                    <option value="new">Nouveaux</option>
                    <option value="contacted">Déjà contactés</option>
                    <option value="favoris">Mes favoris</option>
                </select>

                <label for="filter-ville">Ville :</label>
                <input type="text" id="filter-ville" placeholder="Filtrer par ville">

                <label for="filter-denomination">Dénomination :</label>
                <input type="text" id="filter-denomination" placeholder="Rechercher dans le nom">

                <button class="sci-button secondary" id="clear-filters">🗑️ Effacer</button>
            </div>
        `;

        // Event listeners pour les filtres
        document.getElementById('filter-status').addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.applyFilters();
        });

        document.getElementById('filter-ville').addEventListener('input', (e) => {
            this.filters.ville = e.target.value.toLowerCase();
            this.applyFilters();
        });

        document.getElementById('filter-denomination').addEventListener('input', (e) => {
            this.filters.denomination = e.target.value.toLowerCase();
            this.applyFilters();
        });

        document.getElementById('clear-filters').addEventListener('click', () => {
            this.clearFilters();
        });
    }

    applyFilters() {
        const rows = document.querySelectorAll('#results-tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            const denomination = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
            const ville = row.querySelector('td:nth-child(6)')?.textContent.toLowerCase() || '';
            const siren = row.querySelector('td:nth-child(4)')?.textContent || '';
            const isFavori = row.querySelector('.fav-btn.favori') !== null;
            const isContacted = row.querySelector('.contact-status.contacted') !== null;

            let shouldShow = true;

            // Filtre par statut
            if (this.filters.status === 'new' && (isContacted || isFavori)) shouldShow = false;
            if (this.filters.status === 'contacted' && !isContacted) shouldShow = false;
            if (this.filters.status === 'favoris' && !isFavori) shouldShow = false;

            // Filtre par ville
            if (this.filters.ville && !ville.includes(this.filters.ville)) shouldShow = false;

            // Filtre par dénomination
            if (this.filters.denomination && !denomination.includes(this.filters.denomination)) shouldShow = false;

            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });

        this.showNotification(`Filtres appliqués : ${visibleCount} résultats affichés`, 'success');
        this.updateStats();
    }

    clearFilters() {
        this.filters = {
            status: 'all',
            ville: '',
            codePostal: '',
            denomination: ''
        };

        // Réinitialiser les champs
        document.getElementById('filter-status').value = 'all';
        document.getElementById('filter-ville').value = '';
        document.getElementById('filter-denomination').value = '';

        // Afficher toutes les lignes
        const rows = document.querySelectorAll('#results-tbody tr');
        rows.forEach(row => row.style.display = '');

        this.showNotification('Filtres effacés', 'success');
        this.updateStats();
    }

    // ✅ Statistiques en temps réel
    initStats() {
        const statsContainer = document.querySelector('.stats-panel');
        if (!statsContainer) return;

        statsContainer.innerHTML = `
            <div class="stat-card">
                <div class="stat-number" id="stat-total">0</div>
                <div class="stat-label">Total SCI</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-selected">0</div>
                <div class="stat-label">Sélectionnés</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-favoris">0</div>
                <div class="stat-label">Favoris</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="stat-contacted">0</div>
                <div class="stat-label">Contactés</div>
            </div>
        `;
    }

    updateStats() {
        const rows = document.querySelectorAll('#results-tbody tr');
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');

        this.stats.totalResults = visibleRows.length;
        this.stats.selectedCount = visibleRows.filter(row => 
            row.querySelector('.send-letter-checkbox:checked')
        ).length;
        this.stats.favorisCount = visibleRows.filter(row => 
            row.querySelector('.fav-btn.favori')
        ).length;
        this.stats.contactedCount = visibleRows.filter(row => 
            row.querySelector('.contact-status.contacted')
        ).length;

        // Mettre à jour l'affichage
        document.getElementById('stat-total')?.textContent = this.stats.totalResults;
        document.getElementById('stat-selected')?.textContent = this.stats.selectedCount;
        document.getElementById('stat-favoris')?.textContent = this.stats.favorisCount;
        document.getElementById('stat-contacted')?.textContent = this.stats.contactedCount;
    }

    // ✅ Système de notifications toast
    initNotifications() {
        // Créer le conteneur de notifications
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'sci-frontend-wrapper';
        document.body.appendChild(toastContainer);
    }

    showNotification(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span>${this.getNotificationIcon(type)}</span>
                <span>${message}</span>
            </div>
        `;

        document.getElementById('toast-container').appendChild(toast);

        // Animation d'entrée
        setTimeout(() => toast.classList.add('show'), 100);

        // Auto-suppression
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    getNotificationIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }

    // ✅ Mode sombre
    initDarkMode() {
        const darkModeToggle = document.createElement('button');
        darkModeToggle.className = 'sci-button secondary';
        darkModeToggle.innerHTML = '🌙 Mode sombre';
        darkModeToggle.style.position = 'fixed';
        darkModeToggle.style.top = '20px';
        darkModeToggle.style.left = '20px';
        darkModeToggle.style.zIndex = '9999';

        darkModeToggle.addEventListener('click', () => {
            const wrapper = document.querySelector('.sci-frontend-wrapper');
            wrapper.classList.toggle('dark-mode');
            
            const isDark = wrapper.classList.contains('dark-mode');
            darkModeToggle.innerHTML = isDark ? '☀️ Mode clair' : '🌙 Mode sombre';
            
            // Sauvegarder la préférence
            localStorage.setItem('sci_dark_mode', isDark);
        });

        // Restaurer la préférence
        const savedMode = localStorage.getItem('sci_dark_mode') === 'true';
        if (savedMode) {
            darkModeToggle.click();
        }

        document.body.appendChild(darkModeToggle);
    }

    // ✅ Export de données
    initExport() {
        const exportButton = document.createElement('button');
        exportButton.className = 'sci-button secondary';
        exportButton.innerHTML = '📊 Exporter';
        exportButton.style.position = 'fixed';
        exportButton.style.top = '20px';
        exportButton.style.right = '20px';
        exportButton.style.zIndex = '9999';

        exportButton.addEventListener('click', () => {
            this.exportData();
        });

        document.body.appendChild(exportButton);
    }

    exportData() {
        const rows = document.querySelectorAll('#results-tbody tr');
        const data = [];

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 7) {
                data.push({
                    denomination: cells[1]?.textContent || '',
                    dirigeant: cells[2]?.textContent || '',
                    siren: cells[3]?.textContent || '',
                    adresse: cells[4]?.textContent || '',
                    ville: cells[5]?.textContent || '',
                    code_postal: cells[6]?.textContent || '',
                    favori: row.querySelector('.fav-btn.favori') ? 'Oui' : 'Non',
                    contacte: row.querySelector('.contact-status.contacted') ? 'Oui' : 'Non',
                    selectionne: row.querySelector('.send-letter-checkbox:checked') ? 'Oui' : 'Non'
                });
            }
        });

        if (data.length === 0) {
            this.showNotification('Aucune donnée à exporter', 'warning');
            return;
        }

        // Créer le CSV
        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(','),
            ...data.map(row => headers.map(header => `"${row[header]}"`).join(','))
        ].join('\n');

        // Télécharger le fichier
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `sci_export_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        this.showNotification(`Export réussi : ${data.length} SCI exportées`, 'success');
    }

    // ✅ NOUVEAU : Recherche automatique au chargement de la page
    initAutoSearch() {
        // Vérifier si la recherche automatique est activée
        if (typeof sciAutoSearch !== 'undefined' && sciAutoSearch.auto_search_enabled) {
            // ✅ AMÉLIORÉ : Ne pas lancer de recherche automatique si elle est déjà gérée par le script principal
            // Le script principal (my-istymo.php) gère déjà la recherche automatique
            console.log('✅ Recherche automatique gérée par le script principal');
            
            // Afficher une notification pour informer l'utilisateur
            setTimeout(() => {
                this.showNotification(`Recherche automatique activée pour le code postal ${sciAutoSearch.default_postal_code}`, 'info', 3000);
            }, 1000);
        }
    }


}

// ✅ Initialisation des fonctionnalités avancées
document.addEventListener('DOMContentLoaded', function() {
    window.sciEnhancedFeatures = new SCIEnhancedFeatures();
    
    // ✅ Exposer les fonctions globalement
    window.showNotification = (message, type, duration) => {
        window.sciEnhancedFeatures.showNotification(message, type, duration);
    };
    
    window.updateStats = () => {
        window.sciEnhancedFeatures.updateStats();
    };
    
    console.log('✅ Fonctionnalités avancées SCI initialisées');
});

// Fonctionnalités avancées pour le plugin SCI
console.log('Chargement des fonctionnalités avancées SCI...'); 