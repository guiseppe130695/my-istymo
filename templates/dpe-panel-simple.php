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

<div class="dpe-frontend-wrapper">
    <h1>🏠 DPE – Recherche de Diagnostics</h1>

    <!-- ✅ INFORMATION POUR LES UTILISATEURS -->
    <div class="dpe-info">
        <p>
            💡 Recherchez les diagnostics de performance énergétique (DPE) par code postal. Consultez les étiquettes énergétiques et les informations détaillées. 
            <strong>Prospectez directement les propriétaires de maisons en envoyant un courrier.</strong>
        </p>
    </div>
    
    <!-- ✅ NOUVEAU : Affichage du code postal par défaut -->
    <?php if (!empty($codesPostauxArray)): ?>
    <div class="dpe-default-postal">
        <p>
            📍 <strong>Codes postaux disponibles :</strong> <?php echo esc_html(implode(', ', $codesPostauxArray)); ?>
            <span class="postal-note">(le premier sera sélectionné automatiquement)</span>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- ✅ AFFICHAGE DES AVERTISSEMENTS DE CONFIGURATION -->
    <?php
    // Vérifier si la configuration API est complète
    if (!$config_manager->is_configured()) {
        echo '<div class="dpe-error"><strong>⚠️ Configuration manquante :</strong> Veuillez configurer vos tokens API dans l\'administration.</div>';
    }

    // Vérifier la configuration des données expéditeur
    $campaign_manager = dpe_campaign_manager();
    if ($campaign_manager) {
        $expedition_data = $campaign_manager->get_user_expedition_data();
        $validation_errors = $campaign_manager->validate_expedition_data($expedition_data);
        
        if (!empty($validation_errors)) {
            echo '<div class="dpe-warning">';
            echo '<strong>⚠️ Configuration expéditeur incomplète :</strong>';
            echo '<ul>';
            foreach ($validation_errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }
    ?>

    <!-- ✅ FORMULAIRE DE RECHERCHE AJAX -->
    <form id="dpe-search-form" class="dpe-form">
        <div class="form-group-left">
            <div class="form-group">
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
            <div class="form-group">
                <label for="buildingType">Type de bâtiment :</label>
                <select name="buildingType" id="buildingType">
                    <option value="">— Tous les types —</option>
                    <option value="Maison" selected>🏠 Maison</option>
                    <option value="Appartement">🏢 Appartement</option>
                    <option value="Immeuble">🏗️ Immeuble</option>
                </select>
            </div>
            <button type="submit" id="search-btn" class="dpe-button">
                🔍 Rechercher les DPE
            </button>
        </div>

            <!-- ✅ NOUVEAU : Bouton d'envoi de courriers pour les maisons -->
    <button id="send-letters-btn" type="button" class="dpe-button secondary" disabled
            data-tooltip="Prospectez directement les propriétaires de maisons en envoyant un courrier"
            style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; border: none !important;">
        📬 Créez une campagne d'envoi de courriers (<span id="selected-count">0</span>)
    </button>
    </form>

    <!-- ✅ ZONE DE CHARGEMENT -->
    <div id="search-loading">
        <div class="loading-spinner"></div>
        <span>Recherche en cours...</span>
    </div>

    <!-- ✅ AFFICHAGE DE L'URL DE LA REQUÊTE -->
    <div id="api-url-display">
        <div class="api-url-header">
            <strong>URL de la requête API :</strong>
            <button type="button" class="api-url-close-btn" onclick="document.getElementById('api-url-display').style.display='none'">Masquer</button>
        </div>
        <span id="current-api-url"></span>
    </div>

    <!-- ✅ ZONE DES RÉSULTATS - STRUCTURE STABLE -->
    <div id="search-results">
        <div id="results-header">
            <h2 id="results-title">📋 Résultats de recherche</h2>
            <div id="pagination-info"></div>
        </div>
        
        <!-- ✅ TABLEAU DES RÉSULTATS - STRUCTURE STABLE -->
        <table class="dpe-table" id="results-table">
            <thead>
                <tr>
                    <th>Favoris</th>
                    <th>Type bâtiment</th>
                    <th>Date DPE</th>
                    <th>Adresse</th>
                    <th>Commune</th>
                    <th>Surface</th>
                    <th>Étiquette DPE</th>
                    <th>Étiquette GES</th>
                    <th>Géolocalisation</th>
                    <th class="letter-column">
                        📬 Envoi courrier<br>
                        <small>(Maisons uniquement)</small>
                    </th>
                </tr>
            </thead>
            <tbody id="results-tbody">
                <!-- Les résultats seront insérés ici par JavaScript -->
            </tbody>
        </table>
    </div>
    
    <!-- ✅ CONTRÔLES DE PAGINATION - HORS DE LA ZONE DES RÉSULTATS -->
    <div id="pagination-controls">
        <div class="pagination-main">
            <button id="prev-page" class="pagination-btn" disabled>⬅️ Page précédente</button>
            <span id="page-info">1/1</span>
            <button id="next-page" class="pagination-btn" disabled>Page suivante ➡️</button>
        </div>
    </div>
    
    <!-- ✅ CACHE DES DONNÉES - ÉVITE LES RECHARGEMENTS -->
    <div id="data-cache">
        <span id="cached-title"></span>
        <span id="cached-page"></span>
        <span id="cached-total"></span>
    </div>

    <!-- ✅ ZONE D'ERREUR -->
    <div id="search-error" class="dpe-error">
        <p id="error-message"></p>
    </div>
</div>

<!-- ✅ POPUP LETTRE DPE (identique au système SCI) -->
<div id="letters-popup" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:10000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:25px; width:700px; max-width:95vw; max-height:95vh; overflow-y:auto; border-radius:12px;">
        <!-- Étape 1 : Liste des DPE sélectionnées -->
        <div class="step" id="step-1">
            <h2>📋 DPE sélectionnées</h2>
            <p style="color: #666; margin-bottom: 20px;">Vérifiez votre sélection avant de continuer</p>
            <ul id="selected-dpe-list" style="max-height:350px; overflow-y:auto; border:1px solid #ddd; padding:15px; margin-bottom:25px; border-radius:6px; background-color: #f9f9f9; list-style: none;">
                <!-- Les DPE sélectionnées seront ajoutées ici par JavaScript -->
            </ul>
            <div style="text-align: center;">
                <button id="to-step-2" class="dpe-button" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; border: none !important; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px;">
                    ✍️ Rédiger le courriel →
                </button>
            </div>
        </div>

        <!-- Étape 2 : Contenu dynamique -->
        <div class="step" id="step-2" style="display:none;">
            <!-- Le contenu sera généré par JavaScript -->
        </div>
    </div>
</div>

<!-- ✅ Variables JavaScript pour le système DPE (compatible avec le popup SCI) -->
<script>
// Variables AJAX pour DPE
window.dpe_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('dpe_campaign_nonce'); ?>'
};

// Variable ajaxurl pour compatibilité
window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Variables de paiement DPE (compatibles avec payment.js)
window.dpePaymentData = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('dpe_campaign_nonce'); ?>',
    unit_price: 5.00,
    woocommerce_ready: <?php echo class_exists('WooCommerce') ? 'true' : 'false'; ?>,
    campaigns_url: '<?php echo admin_url('admin.php?page=dpe-campaigns'); ?>'
};

// Alias pour compatibilité avec le système SCI
window.sciPaymentData = window.dpePaymentData;
</script>

<!-- Inclusion du CSS séparé -->
<link rel="stylesheet" href="<?php echo plugins_url('assets/css/dpe-panel-simple.css', dirname(__FILE__)); ?>" type="text/css" media="all" />

<!-- Inclusion du JS séparé (système identique au SCI) -->
<script src="<?php echo plugins_url('assets/js/dpe-frontend.js', dirname(__FILE__)); ?>"></script>
<script src="<?php echo plugins_url('assets/js/dpe-selection-system.js', dirname(__FILE__)); ?>"></script>
<script src="<?php echo plugins_url('assets/js/lettre.js', dirname(__FILE__)); ?>"></script>
<script src="<?php echo plugins_url('assets/js/payment.js', dirname(__FILE__)); ?>"></script> 