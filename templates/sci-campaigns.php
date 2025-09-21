<?php
/**
 * Template pour la page des campagnes SCI
 * Variables attendues dans $context :
 * - $campaigns : array des campagnes de l'utilisateur
 * - $campaign_details : array des détails d'une campagne (si en mode vue détaillée)
 * - $view_mode : boolean indiquant si on est en mode vue détaillée
 * - $title : titre de la page (optionnel)
 * - $show_empty_message : afficher le message si vide (optionnel)
 */

// Debug: Vérifier les variables reçues
// echo "<!-- DEBUG: view_mode = " . ($view_mode ? 'true' : 'false') . " -->";
// echo "<!-- DEBUG: campaign_details = " . (isset($campaign_details) ? 'set' : 'not set') . " -->";
?>

<?php if ($view_mode && $campaign_details): ?>
    <!-- Mode vue détaillée d'une campagne -->
    <!-- DEBUG: CSS Campaigns chargé -->
    <div class="sci-frontend-wrapper">
        <style>
.campaign-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 22px;
    margin-bottom: 32px;
    margin-top: 10px;
}

.campaign-stat-card {
    background: #fff;
    border-radius: 14px;
    /* box-shadow: 0 2px 12px rgba(0,0,0,0.07); */
    padding: 22px 18px 18px 18px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    border: none;
    position: relative;
    min-width: 0;
    transition: box-shadow 0.2s;
}
.campaign-stat-card.status    { border-left: 4px solid #0073aa; }
.campaign-stat-card.total     { border-left: 4px solid #28a745; }
.campaign-stat-card.sent      { border-left: 4px solid #1e7e34; }
.campaign-stat-card.errors    { border-left: 4px solid #dc3545; }

.campaign-stat-label {
    font-size: 13px;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 7px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.campaign-stat-value {
    font-size: 2.1em;
    font-weight: 700;
    color: #222;
    margin: 0;
    line-height: 1.1;
    letter-spacing: -1px;
}

@media (max-width: 700px) {
    .campaign-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .campaign-stat-card {
        padding: 14px 10px 12px 10px;
    }
}
@media (max-width: 480px) {
    .campaign-stats-grid {
        grid-template-columns: 1fr;
    }
    .campaign-stat-card {
        padding: 12px 6vw 10px 6vw;
    }
    .campaign-stat-value {
        font-size: 1.4em;
    }
}
</style>
        <!-- En-tête avec navigation -->
        <div class="campaign-page-header">
            <h1 class="campaign-page-title"><?php echo esc_html($campaign_details['title']); ?></h1>
            <a href="<?php echo esc_url(remove_query_arg('view')); ?>" class="campaign-back-link">
                Retour
            </a>
        </div>
        
        <!-- Section Résumé avec cartes -->
        <div class="campaign-stats-grid">
            <!-- Carte Statut -->
            <div class="campaign-stat-card status">
                <h4 class="campaign-stat-label">Statut</h4>
                <p class="campaign-stat-value">
                <?php
                $status_labels = [
                    'draft' => 'Brouillon',
                    'processing' => 'En cours',
                    'completed' => 'Terminée',
                    'completed_with_errors' => 'Terminée avec erreurs'
                ];
                echo $status_labels[$campaign_details['status']] ?? $campaign_details['status'];
                ?>
            </p>
            </div>
            
            <!-- Carte Total -->
            <div class="campaign-stat-card total">
                <h4 class="campaign-stat-label">Total lettres</h4>
                <p class="campaign-stat-value"><?php echo intval($campaign_details['total_letters']); ?></p>
            </div>
            
            <!-- Carte Envoyées -->
            <div class="campaign-stat-card sent">
                <h4 class="campaign-stat-label">Envoyées</h4>
                <p class="campaign-stat-value"><?php echo intval($campaign_details['sent_letters']); ?></p>
            </div>
            
            <!-- Carte Erreurs -->
            <div class="campaign-stat-card errors">
                <h4 class="campaign-stat-label">Erreurs</h4>
                <p class="campaign-stat-value"><?php echo intval($campaign_details['failed_letters']); ?></p>
            </div>
        </div>
        
        <!-- Section Contenu de la lettre avec popup -->
        <div class="campaign-letter-section">
            <div class="campaign-letter-header">
                <h3 class="campaign-letter-title">Contenu de la lettre</h3>
                <button id="show-letter-popup" class="letter-popup-trigger">
                    Voir le contenu
                </button>
            </div>
            <p class="campaign-letter-date">
                <strong>Date création :</strong> <?php echo date('d/m/Y H:i:s', strtotime($campaign_details['created_at'])); ?>
            </p>
        </div>

        <!-- Popup pour le contenu de la lettre (modale identique à sci-panel.php) -->
        <div id="letter-content-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6)!important; z-index:10000; justify-content:center; align-items:center;">
            <div style="background:#fff!important; padding:25px!important; width:700px; max-width:80vw; max-height:80vh; overflow-y:auto; border-radius:12px; display:flex; flex-direction:column;">
                <h3 style="margin:0 0 20px 0; color:#1e1e1e; font-size:1.3em;">Contenu de la lettre</h3>
                <div style="background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:6px; font-size:14px; line-height:1.7; color:#495057; white-space:pre-wrap; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; margin-bottom:20px;">
                    <?php echo esc_html($campaign_details['content']); ?>
                </div>
                <div style="text-align:center; margin-top:auto;">
                    <button id="close-letter-content-modal" style="background:linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color:white; border:none; padding:12px 24px; border-radius:6px; cursor:pointer; font-size:14px; font-weight:600; transition:all 0.2s;">
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const showButton = document.getElementById('show-letter-popup');
            const modal = document.getElementById('letter-content-modal');
            const closeBtn = document.getElementById('close-letter-content-modal');

            if (showButton && modal) {
                showButton.addEventListener('click', function() {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
                if (closeBtn) closeBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && modal.style.display === 'flex') {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
        });
        </script>
        
        <!-- Section Détail des envois -->
        <div class="campaign-details-section">
            <h3 class="campaign-details-title">Détail des envois</h3>
        <table class="sci-table">
            <thead>
                <tr>
                    <th>SCI</th>
                    <th>Dirigeant</th>
                    <th>SIREN</th>
                    <th>Adresse</th>
                    <th>Statut</th>
                    <th>UID La Poste</th>
                    <th>Date envoi</th>
                    <th>Erreur</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaign_details['letters'] as $letter): ?>
                    <tr>
                        <td><?php echo esc_html($letter['sci_denomination']); ?></td>
                        <td><?php echo esc_html($letter['sci_dirigeant']); ?></td>
                        <td><?php echo esc_html($letter['sci_siren']); ?></td>
                        <td><?php echo esc_html($letter['sci_adresse'] . ', ' . $letter['sci_code_postal'] . ' ' . $letter['sci_ville']); ?></td>
                        <td>
                            <?php
                            $status_icons = [
                                'pending' => 'En attente',
                                'sent' => 'Envoyée',
                                'failed' => 'Erreur'
                            ];
                            echo $status_icons[$letter['status']] ?? $letter['status'];
                            ?>
                        </td>
                        <td>
                            <?php if ($letter['laposte_uid']): ?>
                                <code><?php echo esc_html($letter['laposte_uid']); ?></code>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $letter['sent_at'] ? date('d/m/Y H:i', strtotime($letter['sent_at'])) : '-'; ?>
                        </td>
                        <td>
                            <?php if ($letter['error_message']): ?>
                                                        <span style="color: #dc3545; font-size: 11px; font-weight: 500;">
                                    <?php echo esc_html($letter['error_message']); ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

<?php else: ?>
    <!-- Mode liste des campagnes -->
    <div class="sci-frontend-wrapper">
        
        <?php if (empty($campaigns)): ?>
            <div class="sci-info" style="background: #e7f3ff; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #004085;">
                <?php if ($show_empty_message ?? true): ?>
                    <p style="margin: 0; font-size: 16px; line-height: 1.5;">Aucune campagne trouvée. Créez votre première campagne depuis la page principale SCI.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="sci-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Statut</th>
                        <th>Total</th>
                        <th>Envoyées</th>
                        <th>Erreurs</th>
                        <th>Date création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td><strong><?php echo esc_html($campaign['title']); ?></strong></td>
                            <td>
                                <?php
                                $status_labels = [
                                    'draft' => 'Brouillon',
                                    'processing' => 'En cours',
                                    'completed' => 'Terminée',
                                    'completed_with_errors' => 'Terminée avec erreurs'
                                ];
                                echo $status_labels[$campaign['status']] ?? $campaign['status'];
                                ?>
                            </td>
                            <td><?php echo intval($campaign['total_letters']); ?></td>
                            <td><?php echo intval($campaign['sent_letters']); ?></td>
                            <td><?php echo intval($campaign['failed_letters']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($campaign['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg('view', intval($campaign['id']))); ?>" 
                                   class="sci-button"
                                   style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; border: none !important; text-decoration: none !important; display: inline-block !important; padding: 8px 16px !important; border-radius: 6px !important;">
                                    Voir détails
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?> 