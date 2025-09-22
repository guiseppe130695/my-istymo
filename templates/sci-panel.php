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

<div class="my-istymo sci-panel">
    <div class="frontend-wrapper">
        <h1><i class="fas fa-building"></i> SCI – Recherche et Contact</h1>

        <!-- Information pour les utilisateurs -->
        <div class="info-message">
            <p>
                <i class="fas fa-info-circle"></i> <strong>Prospection SCI</strong><br><br>
                Prospectez directement les SCI. Vous avez également la possibilité de proposer vos services en envoyant un courrier.
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

        // Vérifier la configuration INPI
        $username = get_option('sci_inpi_username');
        $password = get_option('sci_inpi_password');
        
        if (!$username || !$password) {
            echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Identifiants INPI manquants :</strong> Veuillez configurer vos identifiants INPI pour la génération automatique de tokens.</div>';
        }

        // Vérifier WooCommerce
        if (!$woocommerce_integration->is_woocommerce_ready()) {
            echo '<div class="alert alert-warning"><i class="fas fa-shopping-cart"></i> <strong>WooCommerce requis :</strong> Veuillez installer et configurer WooCommerce pour utiliser le système de paiement.</div>';
        }

        // Vérifier la configuration des données expéditeur
        $expedition_data = $campaign_manager->get_user_expedition_data();
        $validation_errors = $campaign_manager->validate_expedition_data($expedition_data);
        
        if (!empty($validation_errors)) {
            echo '<div class="alert alert-warning">';
            echo '<i class="fas fa-exclamation-triangle"></i> <strong>Configuration expéditeur incomplète :</strong>';
            echo '<ul>';
            foreach ($validation_errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <!-- ✅ FORMULAIRE DE RECHERCHE AJAX -->
        <form id="sci-search-form" class="search-form">
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
                <button type="submit" id="search-btn" class="btn btn-primary">
                    <i class="fas fa-search"></i> Rechercher les SCI
                </button>
            </div>

            <div class="form-row">
                <button id="send-letters-btn" type="button" class="btn btn-success" disabled
                        data-tooltip="Prospectez directement les SCI. Vous avez également la possibilité de proposer vos services en envoyant un courrier">
                    <i class="fas fa-envelope"></i> Créez une campagne d'envoi de courriers (<span id="selected-count">0</span>)
                </button>
            </div>
        </form>

        <!-- ✅ ZONE DE CHARGEMENT -->
        <div id="search-loading" class="d-none text-center" style="padding: 40px 20px; font-size: 16px; color: #666;">
            <span><i class="fas fa-spinner fa-spin"></i> Recherche en cours...</span>
        </div>

        <!-- ✅ ZONE DES RÉSULTATS - STRUCTURE STABLE -->
        <div id="search-results" class="search-results">
            <div id="results-header">
                <h2 id="results-title"><i class="fas fa-list"></i> Résultats de recherche</h2>
                <div id="pagination-info" class="d-none"></div>
            </div>
            
            <!-- ✅ TABLEAU DES RÉSULTATS - STRUCTURE STABLE -->
            <table class="data-table sci-results-table" id="results-table">
                <thead>
                    <tr>
                        <th class="col-favoris"><i class="fas fa-heart" title="Favoris - Enregistrez les SCI pour les traiter dans la gestion des leads"></i></th>
                        <th class="col-entreprise"><i class="fas fa-building"></i> Entreprise</th>
                        <th class="col-dirigeant"><i class="fas fa-user-tie"></i> Dirigeant</th>
                        <th class="col-adresse"><i class="fas fa-map-marker-alt"></i> Adresse</th>
                        <th class="col-geolocalisation"><i class="fas fa-map"></i> Géolocalisation</th>
                        <th class="col-envoi-courrier"><i class="fas fa-envelope"></i> Envoi courrier</th>
                        <th class="col-deja-contacte"><i class="fas fa-phone"></i> Déjà contacté ?</th>
                    </tr>
                </thead>
                <tbody id="results-tbody">
                    <!-- Les résultats seront insérés ici par JavaScript -->
                </tbody>
            </table>
        </div>
    
        <!-- ✅ CONTRÔLES DE PAGINATION - HORS DE LA ZONE DES RÉSULTATS -->
        <div id="pagination-controls" class="pagination-controls d-none">
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

<!-- POPUP LETTRE -->
<div id="letters-popup" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:10000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:25px; width:700px; max-width:95vw; max-height:95vh; overflow-y:auto; border-radius:12px;">
        <!-- Étape 1 : Liste des SCI sélectionnées -->
        <div class="step" id="step-1">
            <h2><i class="fas fa-list-check"></i> SCI sélectionnées</h2>
            <p style="color: #666; margin-bottom: 20px;">Vérifiez votre sélection avant de continuer</p>
            <ul id="selected-sci-list" style="max-height:350px; overflow-y:auto; border:1px solid #ddd; padding:15px; margin-bottom:25px; border-radius:6px; background-color: #f9f9f9; list-style: none;">
                <!-- Les SCI sélectionnées seront ajoutées ici par JavaScript -->
            </ul>
            <div style="text-align: center;">
                <button id="to-step-2" class="btn btn-success">
                    <i class="fas fa-edit"></i> Rédiger le courrier →
                </button>
            </div>
        </div>

        <!-- Étape 2 : Contenu dynamique -->
        <div class="step" id="step-2" style="display:none;">
            <!-- Le contenu sera généré par JavaScript -->
        </div>
    </div>
</div>

<!-- Le système de favoris SCI est géré par le fichier favoris.js amélioré --> 