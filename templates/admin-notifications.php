<?php
/**
 * Template pour les notifications d'administration
 * Variables attendues dans $context :
 * - $config_manager : instance du gestionnaire de configuration
 * - $inpi_token_manager : instance du gestionnaire de tokens INPI
 * - $woocommerce_integration : instance de l'intégration WooCommerce
 * - $campaign_manager : instance du gestionnaire de campagnes
 */
?>

<?php
// Vérifier si la configuration API est complète
if (!$config_manager->is_configured()) {
    echo '<div class="notice notice-error"><p><strong>Configuration manquante :</strong> Veuillez configurer vos tokens API dans <a href="' . admin_url('admin.php?page=sci-config') . '">Configuration</a>.</p></div>';
}

// Vérifier la configuration INPI
$username = get_option('sci_inpi_username');
$password = get_option('sci_inpi_password');

if (!$username || !$password) {
    echo '<div class="notice notice-warning"><p><strong>Identifiants INPI manquants :</strong> Veuillez configurer vos identifiants INPI dans <a href="' . admin_url('admin.php?page=sci-inpi-credentials') . '">Identifiants INPI</a> pour la génération automatique de tokens.</p></div>';
} else {
    // Vérifier le statut du token
    $token_valid = $inpi_token_manager->check_token_validity(false);
    if (!$token_valid) {
        echo '<div class="notice notice-info"><p><strong>Token INPI :</strong> Le token sera généré automatiquement lors de votre première recherche. <a href="' . admin_url('admin.php?page=sci-inpi-credentials') . '">Gérer les tokens</a></p></div>';
    } else {
        echo '<div class="notice notice-success"><p><strong>Token INPI :</strong> Token valide et prêt à l\'utilisation. <a href="' . admin_url('admin.php?page=sci-inpi-credentials') . '">Gérer les tokens</a></p></div>';
    }
}

// Vérifier WooCommerce
if (!$woocommerce_integration->is_woocommerce_ready()) {
    echo '<div class="notice notice-warning"><p><strong>WooCommerce requis :</strong> Veuillez installer et configurer WooCommerce pour utiliser le système de paiement. <br><small>En attendant, vous pouvez utiliser le mode envoi direct (sans paiement).</small></p></div>';
}

// Vérifier la configuration des données expéditeur
$expedition_data = $campaign_manager->get_user_expedition_data();
$validation_errors = $campaign_manager->validate_expedition_data($expedition_data);

if (!empty($validation_errors)) {
    echo '<div class="notice notice-warning">';
    echo '<p><strong>Configuration expéditeur incomplète :</strong></p>';
    echo '<ul>';
    foreach ($validation_errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul>';
    echo $campaign_manager->get_configuration_help();
    echo '</div>';
}
?>

<!-- Affichage des shortcodes disponibles avec URLs configurées -->
<div class="notice notice-info">
    <h4>Shortcodes disponibles pour vos pages/articles :</h4>
    <ul>
        <li><code>[sci_panel]</code> - Panneau de recherche SCI complet
            <?php if ($config_manager->get_sci_panel_page_url()): ?>
                <small>(<a href="<?php echo esc_url($config_manager->get_sci_panel_page_url()); ?>" target="_blank">Voir la page</a>)</small>
            <?php endif; ?>
        </li>
        <li><code>[sci_favoris]</code> - Liste des SCI favoris
            <?php if ($config_manager->get_sci_favoris_page_url()): ?>
                <small>(<a href="<?php echo esc_url($config_manager->get_sci_favoris_page_url()); ?>" target="_blank">Voir la page</a>)</small>
            <?php endif; ?>
        </li>
        <li><code>[sci_campaigns]</code> - Liste des campagnes de lettres
            <?php if ($config_manager->get_sci_campaigns_page_url()): ?>
                <small>(<a href="<?php echo esc_url($config_manager->get_sci_campaigns_page_url()); ?>" target="_blank">Voir la page</a>)</small>
            <?php endif; ?>
        </li>
    </ul>
    <p><small>Copiez-collez ces shortcodes dans vos pages ou articles pour afficher les fonctionnalités SCI sur votre site.</small></p>
    <p><small><strong>Astuce :</strong> Configurez les URLs de vos pages dans <a href="<?php echo admin_url('admin.php?page=sci-config'); ?>">Configuration</a> pour des redirections automatiques.</small></p>
</div> 