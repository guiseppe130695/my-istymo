<?php
/**
 * Exemple de page utilisant le shortcode [sci_panel_admin]
 * 
 * Ce fichier montre comment intégrer le panneau admin SCI
 * dans une page WordPress personnalisée.
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier que l'utilisateur est connecté
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header(); ?>

<div class="container">
    <div class="row">
        <div class="col-12">
            
            <!-- En-tête de la page -->
            <div class="page-header">
                <h1 class="page-title">Prospection SCI</h1>
                <p class="page-description">
                    Recherchez et contactez les SCI de votre secteur d'activité.
                </p>
            </div>
            
            <!-- Panneau SCI embarqué -->
            <div class="sci-panel-container">
                <?php echo do_shortcode('[sci_panel_admin title="Recherche et Contact SCI"]'); ?>
            </div>
            
            <!-- Informations supplémentaires -->
            <div class="additional-info">
                <div class="info-card">
                    <h3>💡 Conseils d'utilisation</h3>
                    <ul>
                        <li>Sélectionnez votre code postal pour commencer</li>
                        <li>Utilisez les favoris pour marquer les SCI intéressantes</li>
                        <li>Créez des campagnes pour envoyer des courriers</li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3>📊 Statistiques</h3>
                    <p>Accédez à vos statistiques de prospection dans votre tableau de bord.</p>
                </div>
            </div>
            
        </div>
    </div>
</div>

<style>
/* Styles pour la page d'exemple */
.page-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.page-description {
    font-size: 1.2rem;
    opacity: 0.9;
    margin: 0;
}

.sci-panel-container {
    margin: 2rem 0;
}

.additional-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 3rem;
}

.info-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #007cba;
}

.info-card h3 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.info-card ul {
    margin: 0;
    padding-left: 1.5rem;
}

.info-card li {
    margin-bottom: 0.5rem;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: 2rem;
    }
    
    .page-description {
        font-size: 1rem;
    }
    
    .additional-info {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>
