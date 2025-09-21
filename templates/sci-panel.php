<?php
/**
 * Template pour le panneau principal SCI
 * Variables attendues dans $context :
 * - $codesPostauxArray : array des codes postaux de l'utilisateur
 * - $config_manager : instance du gestionnaire de configuration
 * - $inpi_token_manager : instance du gestionnaire de tokens INPI
 * - $woocommerce_integration : instance de l'intégration WooCommerce
 * - $campaign_manager : instance du gestionnaire de campagnes
 */
?>

<div class="frontend-wrapper">
    <h1>SCI - Recherche et Contact</h1>

    <!-- INFORMATION POUR LES UTILISATEURS -->
    <div class="info-message">
        <p>
            Prospectez directement les SCI. Vous avez également la possibilité de proposer vos services en envoyant un courrier.
        </p>
    </div>
    
    <!-- NOUVEAU : Affichage du code postal par défaut -->
    <?php if (!empty($codesPostauxArray)): ?>
    <div class="default-status">
        <p>
            <strong>Codes postaux disponibles :</strong> <?php echo esc_html(implode(', ', $codesPostauxArray)); ?>
            <span class="status-note">(le premier sera sélectionné automatiquement)</span>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- AFFICHAGE DES AVERTISSEMENTS DE CONFIGURATION -->
    <?php
    // Vérifier si la configuration API est complète
    if (!$config_manager->is_configured()) {
        echo '<div class="sci-error"><strong>Configuration manquante :</strong> Veuillez configurer vos tokens API dans l\'administration.</div>';
    }

    // Vérifier la configuration INPI
    $username = get_option('sci_inpi_username');
    $password = get_option('sci_inpi_password');
    
    if (!$username || !$password) {
        echo '<div class="sci-warning"><strong>Identifiants INPI manquants :</strong> Veuillez configurer vos identifiants INPI pour la génération automatique de tokens.</div>';
    }

    // Vérifier WooCommerce
    if (!$woocommerce_integration->is_woocommerce_ready()) {
        echo '<div class="sci-warning"><strong>WooCommerce requis :</strong> Veuillez installer et configurer WooCommerce pour utiliser le système de paiement.</div>';
    }

    // Vérifier la configuration des données expéditeur
    $expedition_data = $campaign_manager->get_user_expedition_data();
    $validation_errors = $campaign_manager->validate_expedition_data($expedition_data);
    
    if (!empty($validation_errors)) {
        echo '<div class="sci-warning">';
        echo '<strong>Configuration expéditeur incomplète :</strong>';
        echo '<ul>';
        foreach ($validation_errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    ?>

    <!-- FORMULAIRE DE RECHERCHE AJAX -->
    <form id="sci-search-form" class="search-form">
        <div class="form-row">
            <div class="form-field">
                <label for="codePostal">Sélectionnez votre code postal :</label>
                <select name="codePostal" id="codePostal" required>
                    <option value="">— Choisir un code postal —</option>
                    <?php foreach ($codesPostauxArray as $index => $value): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php echo ($index === 0) ? 'selected' : ''; ?>>
                            <?php echo esc_html($value); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" id="search-btn" class="btn btn-primary">
                <i class="fas fa-search"></i> Rechercher les SCI
            </button>
        </div>

        <div class="form-row mt-3">
            <button id="send-letters-btn" type="button" class="btn btn-success" disabled
                    data-tooltip="Prospectez directement les SCI. Vous avez également la possibilité de proposer vos services en envoyant un courrier">
                <i class="fas fa-envelope"></i> Créez une campagne d'envoi de courriers (<span id="selected-count">0</span>)
            </button>
        </div>
    </form>

    <!-- ZONE DE CHARGEMENT -->
    <div id="search-loading" style="display: none;">
        <div class="loading-spinner"></div>
        <span>Recherche en cours...</span>
    </div>

    <!-- ZONE DES RÉSULTATS - STRUCTURE STABLE -->
    <div id="search-results" style="display: none;">
        <div id="results-header">
            <h2 id="results-title">Résultats de recherche</h2>
            <div id="pagination-info" style="display: none;"></div>
        </div>
        
        <!-- TABLEAU DES RÉSULTATS - STRUCTURE STABLE -->
        <table class="sci-table" id="results-table">
            <thead>
                <tr>
                    <th>Favoris</th>
                    <th>Dénomination</th>
                    <th>Dirigeant</th>
                    <th>SIREN</th>
                    <th>Adresse</th>
                    <th>Ville</th>
                    <th>Déjà contacté ?</th>
                    <th>Géolocalisation</th>
                    <th>Envoi courrier</th>
                </tr>
            </thead>
            <tbody id="results-tbody">
                <!-- Les résultats seront insérés ici par JavaScript -->
            </tbody>
        </table>
    </div>
    
    <!-- CONTRÔLES DE PAGINATION - HORS DE LA ZONE DES RÉSULTATS -->
    <div id="pagination-controls" class="pagination-controls" style="display: none;">
        <div class="pagination-main">
            <button id="prev-page" class="pagination-btn" disabled>
                <i class="fas fa-chevron-left"></i> Page précédente
            </button>
            <span id="page-info" class="page-info">1/1</span>
            <button id="next-page" class="pagination-btn" disabled>
                Page suivante <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
    <!-- CACHE DES DONNÉES - ÉVITE LES RECHARGEMENTS -->
    <div id="data-cache" style="display: none;">
        <span id="cached-title"></span>
        <span id="cached-page"></span>
        <span id="cached-total"></span>
    </div>

    <!-- ZONE D'ERREUR -->
    <div id="search-error" style="display: none;" class="sci-error">
        <p id="error-message"></p>
    </div>
</div>

<!-- POPUP LETTRE -->
<div id="letters-popup" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:10000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:25px; width:700px; max-width:95vw; max-height:95vh; overflow-y:auto; border-radius:12px;">
        <!-- Étape 1 : Liste des SCI sélectionnées -->
        <div class="step" id="step-1">
            <h2>SCI sélectionnées</h2>
            <p style="color: #666; margin-bottom: 20px;">Vérifiez votre sélection avant de continuer</p>
            <ul id="selected-sci-list" style="max-height:350px; overflow-y:auto; border:1px solid #ddd; padding:15px; margin-bottom:25px; border-radius:6px; background-color: #f9f9f9; list-style: none;">
                <!-- Les SCI sélectionnées seront ajoutées ici par JavaScript -->
            </ul>
            <div style="text-align: center;">
                <button id="to-step-2" class="sci-button" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; border: none !important; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px;">
                    Rédiger le courrier →
                </button>
            </div>
        </div>

        <!-- Étape 2 : Contenu dynamique -->
        <div class="step" id="step-2" style="display:none;">
            <!-- Le contenu sera généré par JavaScript -->
        </div>
    </div>
</div>

<style>
/* NOUVEAU : Styles pour les boutons favoris SCI (inspirés du système DPE) */
.favorite-btn, .fav-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    color: #ccc;
    transition: color 0.3s ease;
    padding: 5px;
    border-radius: 4px;
}

.favorite-btn.active, .fav-btn.favori {
    color: #ff6b6b;
}

.favorite-btn:hover, .fav-btn:hover {
    color: #ff6b6b;
    background: #f0f0f0;
}

/* NOUVEAU : Styles pour les boutons favoris dans le tableau */
.sci-table .favorite-btn, .sci-table .fav-btn {
    font-size: 16px;
    padding: 3px;
}

.sci-table .favorite-btn.active, .sci-table .fav-btn.favori {
    color: #ffd700;
}

.sci-table .favorite-btn:not(.active), .sci-table .fav-btn:not(.favori) {
    color: #ccc;
}

/* NOUVEAU : Responsive pour les boutons favoris */
@media (max-width: 768px) {
    .favorite-btn, .fav-btn {
        font-size: 16px;
    }
    
    .sci-table .favorite-btn, .sci-table .fav-btn {
        font-size: 14px;
    }
}
</style>

<!-- Le système de favoris SCI est géré par le fichier favoris.js amélioré --> 