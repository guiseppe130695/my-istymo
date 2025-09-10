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

// Enqueue du design system CSS
wp_enqueue_style('my-istymo-global', plugin_dir_url(__FILE__) . '../assets/css/my-istymo-global.css', array(), '1.0.0');
wp_enqueue_style('dpe-design-system', plugin_dir_url(__FILE__) . '../assets/css/dpe-design-system.css', array('my-istymo-global'), '1.0.0');
wp_enqueue_style('dpe-style', plugin_dir_url(__FILE__) . '../assets/css/dpe-style.css', array('dpe-design-system'), '1.0.0');
?>

<div class="dpe-frontend-wrapper">
    <div class="">
        <div class="">
            <h1 class="dpe-title-1">SCI – Recherche et Contact</h1>
        </div>
        <div class="dpe-card__body">
            <!-- Information pour les utilisateurs -->
            <div class="dpe-alert dpe-alert--info dpe-mb-lg">
                <strong>Information :</strong> Prospectez directement les SCI. Vous avez également la possibilité de proposer vos services en envoyant un courrier.
            </div>
    
            <!-- Affichage du code postal par défaut -->
            <?php if (!empty($codesPostauxArray)): ?>
            <div class="dpe-alert dpe-alert--success dpe-mb-lg">
                <strong>Codes postaux disponibles :</strong> <?php echo esc_html(implode(', ', $codesPostauxArray)); ?>
                <em>(le premier sera sélectionné automatiquement)</em>
            </div>
            <?php endif; ?>
    
            <!-- Affichage des avertissements de configuration -->
            <?php
            // Vérifier si la configuration API est complète
            if (!$config_manager->is_configured()) {
                echo '<div class="dpe-alert dpe-alert--error dpe-mb-lg"><strong>Configuration manquante :</strong> Veuillez configurer vos tokens API dans l\'administration.</div>';
            }

            // Vérifier la configuration INPI
            $username = get_option('sci_inpi_username');
            $password = get_option('sci_inpi_password');
            
            if (!$username || !$password) {
                echo '<div class="dpe-alert dpe-alert--warning dpe-mb-lg"><strong>Identifiants INPI manquants :</strong> Veuillez configurer vos identifiants INPI pour la génération automatique de tokens.</div>';
            }

            // Vérifier WooCommerce
            if (!$woocommerce_integration->is_woocommerce_ready()) {
                echo '<div class="dpe-alert dpe-alert--warning dpe-mb-lg"><strong>WooCommerce requis :</strong> Veuillez installer et configurer WooCommerce pour utiliser le système de paiement.</div>';
            }

            // Vérifier la configuration des données expéditeur
            $expedition_data = $campaign_manager->get_user_expedition_data();
            $validation_errors = $campaign_manager->validate_expedition_data($expedition_data);
            
            if (!empty($validation_errors)) {
                echo '<div class="dpe-alert dpe-alert--warning dpe-mb-lg">';
                echo '<strong>Configuration expéditeur incomplète :</strong>';
                echo '<ul>';
                foreach ($validation_errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            ?>

            <!-- ✅ FORMULAIRE DE RECHERCHE AJAX -->
            <form id="sci-search-form" class="dpe-form">
                <div class="dpe-form-group dpe-mb-md">
                    <label for="codePostal" class="dpe-label">Sélectionnez votre code postal :</label>
                    <select name="codePostal" id="codePostal" class="dpe-select" required>
                        <option value="">— Choisir un code postal —</option>
                        <?php foreach ($codesPostauxArray as $index => $value): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php echo ($index === 0) ? 'selected' : ''; ?>>
                                <?php echo esc_html($value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="dpe-flex dpe-gap-md dpe-mb-lg">
                    <button type="submit" id="search-btn" class="dpe-btn dpe-btn--primary dpe-btn--large">
                        Rechercher les SCI
                    </button>
                    <button id="send-letters-btn" type="button" class="dpe-btn dpe-btn--success dpe-btn--large" disabled>
                        Créer une campagne d'envoi de courriers (<span id="selected-count">0</span>)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ✅ ZONE DE CHARGEMENT -->
    <div id="search-loading" class="dpe-loading dpe-text-center" style="display: none;">
        <div class="dpe-spinner dpe-spinner--large"></div>
        <p class="dpe-body">Recherche en cours...</p>
    </div>

    <!-- ✅ ZONE DES RÉSULTATS - STRUCTURE STABLE -->
    <div id="search-results" class="dpe-card dpe-card--elevated" style="display: none;">
        <div class="dpe-card__header">
            <h2 id="results-title" class="dpe-title-2">Résultats de recherche</h2>
            <div id="pagination-info" class="" style="display: none;"></div>
        </div>
        <div class="dpe-card__body">
            <!-- ✅ TABLEAU DES RÉSULTATS - STRUCTURE STABLE -->
            <div class="dpe-table-container">
                <table class="dpe-table" id="results-table">
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
        </div>
    </div>
    
    <!-- ✅ CONTRÔLES DE PAGINATION - HORS DE LA ZONE DES RÉSULTATS -->
    <div id="pagination-controls" class="dpe-card dpe-text-center" style="display: none;">
        <div class="dpe-card__body">
            <div class="dpe-flex dpe-justify-center dpe-items-center dpe-gap-md">
                <button id="prev-page" class="dpe-btn dpe-btn--secondary" disabled>Page précédente</button>
                <span id="page-info" class="dpe-badge dpe-badge--primary">1/1</span>
                <button id="next-page" class="dpe-btn dpe-btn--secondary" disabled>Page suivante</button>
            </div>
        </div>
    </div>
    
    <!-- ✅ CACHE DES DONNÉES - ÉVITE LES RECHARGEMENTS -->
    <div id="data-cache" style="display: none;">
        <span id="cached-title"></span>
        <span id="cached-page"></span>
        <span id="cached-total"></span>
    </div>

    <!-- ✅ ZONE D'ERREUR -->
    <div id="search-error" class="dpe-alert dpe-alert--error" style="display: none;">
        <p id="error-message" class="dpe-body"></p>
    </div>
</div>

<!-- ✅ POPUP LETTRE -->
<div id="letters-popup" class="dpe-modal" style="display:none;">
    <div class="dpe-modal__content">
        <div class="dpe-card">
            <!-- Étape 1 : Liste des SCI sélectionnées -->
            <div class="step" id="step-1">
                <div class="dpe-card__header">
                    <h2 class="dpe-title-2">SCI sélectionnées</h2>
                    <p class="dpe-subtitle">Vérifiez votre sélection avant de continuer</p>
                </div>
                <div class="dpe-card__body">
                    <div id="selected-sci-list" class="dpe-list dpe-mb-lg">
                        <!-- Les SCI sélectionnées seront ajoutées ici par JavaScript -->
                    </div>
                    <div class="dpe-text-center">
                        <button id="to-step-2" class="dpe-btn dpe-btn--success dpe-btn--large">
                            Rédiger le courrier →
                        </button>
                    </div>
                </div>
            </div>

            <!-- Étape 2 : Contenu dynamique -->
            <div class="step" id="step-2" style="display:none;">
                <!-- Le contenu sera généré par JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- ✅ Styles pour les boutons favoris gérés par le design system DPE -->

<!-- ✅ Le système de favoris SCI est géré par le fichier favoris.js amélioré --> 