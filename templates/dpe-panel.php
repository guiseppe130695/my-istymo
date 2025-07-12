<?php
/**
 * Template pour le panneau de recherche DPE
 * Même design que le SCI
 */
if (!defined('ABSPATH')) exit;

$codesPostauxArray = $context['codesPostauxArray'] ?? [];
$config_manager = $context['config_manager'];
$favoris_handler = $context['favoris_handler'];
$dpe_handler = $context['dpe_handler'];
$atts = $context['atts'] ?? [];
?>

<div class="sci-frontend-wrapper">
    <h1>🏠 DPE – Recherche de Diagnostics</h1>

    <!-- ✅ INFORMATION POUR LES UTILISATEURS -->
    <div class="sci-info" style="background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #004085;">
        <p style="margin: 0; font-size: 16px; line-height: 1.5;">
            💡 Recherchez les diagnostics de performance énergétique par adresse ou code postal.
        </p>
    </div>
    
    <!-- ✅ NOUVEAU : Affichage du code postal par défaut -->
    <?php if (!empty($codesPostauxArray)): ?>
    <div class="sci-default-postal" style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 12px; margin-bottom: 15px; color: #155724;">
        <p style="margin: 0; font-size: 14px; line-height: 1.4;">
            📍 <strong>Codes postaux disponibles :</strong> <?php echo esc_html(implode(', ', $codesPostauxArray)); ?>
            <span style="color: #0c5460; font-style: italic;">(le premier sera sélectionné automatiquement)</span>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- ✅ AFFICHAGE DES AVERTISSEMENTS DE CONFIGURATION -->
    <?php
    // Vérifier si la configuration API est complète
    if (!$config_manager->is_configured()) {
        echo '<div class="sci-error"><strong>⚠️ Configuration manquante :</strong> Veuillez configurer l\'URL de l\'API DPE dans l\'administration.</div>';
    }
    ?>

    <!-- ✅ FORMULAIRE DE RECHERCHE AJAX -->
    <form id="dpe-search-form" class="sci-form">
        <div class="form-group-left">
            <div class="form-group">
                <label for="dpe-code-postal">Sélectionnez votre code postal :</label>
                <select name="dpe-code-postal" id="dpe-code-postal" required>
                    <option value="">— Choisir un code postal —</option>
                    <?php foreach ($codesPostauxArray as $index => $value): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php echo ($index === 0) ? 'selected' : ''; ?>>
                            <?php echo esc_html($value); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="dpe-adresse">Adresse (optionnel) :</label>
                <input type="text" id="dpe-adresse" name="dpe-adresse" placeholder="Ex: 50 Rue du Disque">
            </div>
            
            <div class="form-group">
                <label for="dpe-page-size">Résultats par page :</label>
                <select id="dpe-page-size" name="dpe-page-size">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                </select>
            </div>
            
            <button type="submit" id="dpe-search-btn" class="sci-button">
                🔍 Rechercher les DPE
            </button>
        </div>
    </form>

    <!-- ✅ ZONE DE CHARGEMENT -->
    <div id="dpe-loading" style="display: none;">
        <div class="loading-spinner"></div>
        <span>Recherche en cours...</span>
    </div>

    <!-- ✅ ZONE DES RÉSULTATS - STRUCTURE STABLE -->
    <div id="dpe-results" style="display: none;">
        <div id="dpe-results-header">
            <h2 id="dpe-results-title">📋 Résultats de recherche DPE</h2>
            <div id="dpe-pagination-info" style="display: none;"></div>
        </div>
        
        <!-- ✅ TABLEAU DES RÉSULTATS - STRUCTURE STABLE -->
        <table class="sci-table" id="dpe-results-table">
            <thead>
                <tr>
                    <th>Favoris</th>
                    <th>Adresse</th>
                    <th>Commune</th>
                    <th>Type d'habitation</th>
                    <th>Surface</th>
                    <th>Étiquette DPE</th>
                    <th>Étiquette GES</th>
                    <th>Date DPE</th>
                    <th>Géolocalisation</th>
                </tr>
            </thead>
            <tbody id="dpe-results-tbody">
                <!-- Les résultats seront insérés ici par JavaScript -->
            </tbody>
        </table>
    </div>
    
    <!-- ✅ CONTRÔLES DE PAGINATION - HORS DE LA ZONE DES RÉSULTATS -->
    <div id="dpe-pagination-controls" style="display: none; margin-top: 20px; text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
        <div class="pagination-main" style="display: flex; align-items: center; justify-content: center; gap: 15px;">
            <button id="dpe-prev-page" disabled style="padding: 10px 20px; font-size: 14px; font-weight: 500; border: none; border-radius: 0; background: #fff; color: #333; cursor: pointer; transition: all 0.2s ease; box-shadow: none;">⬅️ Page précédente</button>
            <span id="dpe-page-info" style="background: #0073aa; color: white; padding: 8px 15px; border-radius: 4px; font-size: 14px; font-weight: 500;">1/1</span>
            <button id="dpe-next-page" disabled style="padding: 10px 20px; font-size: 14px; font-weight: 500; border: none; border-radius: 0; background: #fff; color: #333; cursor: pointer; transition: all 0.2s ease; box-shadow: none;">Page suivante ➡️</button>
        </div>
    </div>
    
    <!-- ✅ CACHE DES DONNÉES - ÉVITE LES RECHARGEMENTS -->
    <div id="dpe-data-cache" style="display: none;">
        <span id="dpe-cached-title"></span>
        <span id="dpe-cached-page"></span>
        <span id="dpe-cached-total"></span>
    </div>

    <!-- ✅ ZONE D'ERREUR -->
    <div id="dpe-search-error" style="display: none;" class="sci-error">
        <p id="dpe-error-message"></p>
    </div>
</div>

<!-- Template pour un résultat DPE (format tableau) -->
<template id="dpe-result-template">
    <tr data-dpe-id="">
        <td>
            <button type="button" class="btn-favori" data-dpe-id="" title="Ajouter aux favoris">
                <span class="favori-icon">⭐</span>
            </button>
        </td>
        <td class="adresse"></td>
        <td class="commune"></td>
        <td class="type-batiment"></td>
        <td class="surface"></td>
        <td>
            <span class="dpe-label"></span>
        </td>
        <td>
            <span class="ges-label"></span>
        </td>
        <td class="date-dpe"></td>
        <td class="geolocalisation"></td>
    </tr>
</template>

<style>
/* Styles pour les étiquettes DPE et GES */
.dpe-label, .ges-label {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 12px;
    text-align: center;
    min-width: 30px;
    display: inline-block;
}

/* Étiquettes DPE */
.dpe-label.A { background-color: #009639; color: white; }
.dpe-label.B { background-color: #85bb2f; color: white; }
.dpe-label.C { background-color: #ffcc02; color: black; }
.dpe-label.D { background-color: #f68b1f; color: white; }
.dpe-label.E { background-color: #e30613; color: white; }
.dpe-label.F { background-color: #8b0000; color: white; }
.dpe-label.G { background-color: #4a4a4a; color: white; }

/* Étiquettes GES */
.ges-label.A { background-color: #009639; color: white; }
.ges-label.B { background-color: #85bb2f; color: white; }
.ges-label.C { background-color: #ffcc02; color: black; }
.ges-label.D { background-color: #f68b1f; color: white; }
.ges-label.E { background-color: #e30613; color: white; }
.ges-label.F { background-color: #8b0000; color: white; }
.ges-label.G { background-color: #4a4a4a; color: white; }

/* Styles pour les boutons favoris */
.btn-favori {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.2s ease;
    padding: 5px;
    border-radius: 4px;
}

.btn-favori:hover {
    background: #f0f0f0;
}

.btn-favori.is-favori {
    color: #ffd700;
}

.btn-favori:not(.is-favori) {
    color: #ccc;
}

/* Responsive pour le tableau */
@media (max-width: 768px) {
    .sci-table {
        font-size: 12px;
    }
    
    .sci-table th,
    .sci-table td {
        padding: 6px 4px;
    }
    
    .dpe-label, .ges-label {
        font-size: 10px;
        padding: 2px 4px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dpePanel = {
        currentPage: 1,
        currentPageSize: 50,
        currentResults: [],
        
        init: function() {
            this.bindEvents();
            this.setupNonce();
        },
        
        bindEvents: function() {
            document.getElementById('dpe-search-form').addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
            
            document.getElementById('dpe-page-size').addEventListener('change', (e) => {
                this.currentPageSize = parseInt(e.target.value);
                this.currentPage = 1;
                if (this.currentResults.length > 0) {
                    this.performSearch();
                }
            });
            
            // Pagination
            document.getElementById('dpe-prev-page').addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.performSearch();
                }
            });
            
            document.getElementById('dpe-next-page').addEventListener('click', () => {
                this.currentPage++;
                this.performSearch();
            });
        },
        
        setupNonce: function() {
            this.nonce = '<?php echo wp_create_nonce("dpe_search_nonce"); ?>';
            this.favorisNonce = '<?php echo wp_create_nonce("dpe_favoris_nonce"); ?>';
        },
        
        performSearch: function() {
            const codePostal = document.getElementById('dpe-code-postal').value;
            const adresse = document.getElementById('dpe-adresse').value;
            
            if (!codePostal && !adresse) {
                this.showError('Veuillez saisir un code postal ou une adresse');
                return;
            }
            
            this.showLoading();
            
            const formData = new FormData();
            formData.append('action', 'dpe_search_ajax');
            formData.append('nonce', this.nonce);
            formData.append('code_postal', codePostal);
            formData.append('adresse', adresse);
            formData.append('page', this.currentPage);
            formData.append('page_size', this.currentPageSize);
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.hideLoading();
                if (data.success) {
                    // ✅ ADAPTÉ : Utiliser la nouvelle structure de réponse
                    console.log('Réponse DPE brute:', data.data.raw_json_response);
                    this.displayResults(data.data.raw_json_response.results, data.data.pagination);
                } else {
                    this.showError(data.data || 'Erreur lors de la recherche');
                }
            })
            .catch(error => {
                this.hideLoading();
                this.showError('Erreur de connexion: ' + error.message);
            });
        },
        
        displayResults: function(results, pagination) {
            this.currentResults = results;
            
            const tbody = document.getElementById('dpe-results-tbody');
            const title = document.getElementById('dpe-results-title');
            const paginationInfo = document.getElementById('dpe-pagination-info');
            
            // Vider le tableau
            tbody.innerHTML = '';
            
            if (results.length === 0) {
                title.textContent = '📋 Aucun résultat trouvé';
                document.getElementById('dpe-results').style.display = 'block';
                document.getElementById('dpe-pagination-controls').style.display = 'none';
                return;
            }
            
            // Mettre à jour le titre
            title.textContent = `📋 Résultats de recherche DPE (${pagination.total} résultat${pagination.total > 1 ? 's' : ''})`;
            
            // Afficher chaque résultat
            results.forEach(result => {
                const resultElement = this.createResultElement(result);
                tbody.appendChild(resultElement);
            });
            
            // Afficher la pagination
            this.displayPagination(pagination);
            
            // Afficher les résultats
            document.getElementById('dpe-results').style.display = 'block';
        },
        
        createResultElement: function(result) {
            const template = document.getElementById('dpe-result-template');
            const clone = template.content.cloneNode(true);
            
            // ✅ ADAPTÉ : Utiliser les champs corrects de l'API DPE
            clone.querySelector('tr').dataset.dpeId = result._id || result.id;
            clone.querySelector('.adresse').textContent = result.adresse_ban || result.adresse || 'Non spécifié';
            clone.querySelector('.commune').textContent = result.nom_commune_ban || result.commune || 'Non spécifié';
            clone.querySelector('.type-batiment').textContent = result.type_batiment || 'Non spécifié';
            clone.querySelector('.surface').textContent = result.surface_habitable_logement ? `${result.surface_habitable_logement} m²` : 'Non spécifié';
            clone.querySelector('.date-dpe').textContent = result.date_etablissement_dpe || result.date_dpe || 'Non spécifié';
            
            // Étiquettes DPE et GES
            const dpeLabel = clone.querySelector('.dpe-label');
            const gesLabel = clone.querySelector('.ges-label');
            
            dpeLabel.textContent = result.etiquette_dpe;
            dpeLabel.className = `dpe-label ${result.etiquette_dpe}`;
            
            gesLabel.textContent = result.etiquette_ges;
            gesLabel.className = `ges-label ${result.etiquette_ges}`;
            
            // Géolocalisation
            const geoText = result.coordonnee_cartographique_x_ban && result.coordonnee_cartographique_y_ban 
                ? `${parseFloat(result.coordonnee_cartographique_x_ban).toFixed(6)}, ${parseFloat(result.coordonnee_cartographique_y_ban).toFixed(6)}`
                : 'Non disponible';
            clone.querySelector('.geolocalisation').textContent = geoText;
            
            // Gestion des favoris
            const favoriBtn = clone.querySelector('.btn-favori');
            favoriBtn.dataset.dpeId = result._id || result.id;
            
            // Ajouter l'événement click pour les favoris
            favoriBtn.addEventListener('click', () => {
                this.toggleFavori(result);
            });
            
            // Vérifier si c'est déjà un favori (à implémenter avec une requête AJAX)
            // Pour l'instant, on affiche toujours comme non-favori
            favoriBtn.classList.remove('is-favori');
            favoriBtn.title = 'Ajouter aux favoris';
            
            return clone;
        },
        
        toggleFavori: function(result) {
            const isCurrentlyFavori = result.is_favori || false;
            const action = isCurrentlyFavori ? 'dpe_remove_favori' : 'dpe_add_favori';
            const formData = new FormData();
            
            formData.append('action', 'dpe_manage_favoris');
            formData.append('operation', isCurrentlyFavori ? 'remove' : 'add');
            formData.append('nonce', this.favorisNonce);
            
            if (isCurrentlyFavori) {
                formData.append('dpe_data', JSON.stringify({numero_dpe: result._id || result.id}));
            } else {
                formData.append('dpe_data', JSON.stringify(result));
            }
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'interface
                    this.updateFavoriButton(result._id || result.id, !isCurrentlyFavori);
                    result.is_favori = !isCurrentlyFavori;
                } else {
                    alert('Erreur: ' + (data.data || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                alert('Erreur de connexion: ' + error.message);
            });
        },
        
        updateFavoriButton: function(dpeId, isFavori) {
            const btn = document.querySelector(`.btn-favori[data-dpe-id="${dpeId}"]`);
            if (btn) {
                if (isFavori) {
                    btn.classList.add('is-favori');
                    btn.title = 'Retirer des favoris';
                    btn.querySelector('.favori-icon').textContent = '⭐';
                } else {
                    btn.classList.remove('is-favori');
                    btn.title = 'Ajouter aux favoris';
                    btn.querySelector('.favori-icon').textContent = '⭐';
                }
            }
        },
        
        displayPagination: function(pagination) {
            const controls = document.getElementById('dpe-pagination-controls');
            const pageInfo = document.getElementById('dpe-page-info');
            const prevBtn = document.getElementById('dpe-prev-page');
            const nextBtn = document.getElementById('dpe-next-page');
            
            if (pagination.total <= pagination.page_size) {
                controls.style.display = 'none';
                return;
            }
            
            // Mettre à jour les informations de page
            const totalPages = Math.ceil(pagination.total / pagination.page_size);
            pageInfo.textContent = `${pagination.current_page}/${totalPages}`;
            
            // Activer/désactiver les boutons
            prevBtn.disabled = pagination.current_page <= 1;
            nextBtn.disabled = !pagination.has_next;
            
            controls.style.display = 'block';
        },
        
        showLoading: function() {
            document.getElementById('dpe-loading').style.display = 'block';
            document.getElementById('dpe-results').style.display = 'none';
            document.getElementById('dpe-search-error').style.display = 'none';
        },
        
        hideLoading: function() {
            document.getElementById('dpe-loading').style.display = 'none';
        },
        
        showError: function(message) {
            const errorContainer = document.getElementById('dpe-search-error');
            const errorMessage = document.getElementById('dpe-error-message');
            errorMessage.textContent = message;
            errorContainer.style.display = 'block';
            document.getElementById('dpe-results').style.display = 'none';
        }
    };
    
    dpePanel.init();
});
</script> 