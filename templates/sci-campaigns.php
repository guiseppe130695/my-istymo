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
?>

<?php if ($view_mode && $campaign_details): ?>
    <!-- Mode vue détaillée d'une campagne -->
    <div class="sci-frontend-wrapper">
        <h1>📬 Détails de la campagne : <?php echo esc_html($campaign_details['title']); ?></h1>
        
        <a href="?view=" class="sci-button"
           style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important; color: white !important; border: none !important; text-decoration: none !important; display: inline-block !important; padding: 8px 16px !important; border-radius: 6px !important; margin-bottom: 20px !important;">
            ← Retour aux campagnes
        </a>
        
        <div style="background: #fff; padding: 25px; margin: 20px 0; border: 1px solid #e9ecef; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            <h3 style="color: #1e1e1e; font-size: 1.3em; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef; display: flex; align-items: center; gap: 8px;">📊 Résumé</h3>
            <p><strong>Statut :</strong> 
                <?php
                $status_labels = [
                    'draft' => '📝 Brouillon',
                    'processing' => '⏳ En cours',
                    'completed' => '✅ Terminée',
                    'completed_with_errors' => '⚠️ Terminée avec erreurs'
                ];
                echo $status_labels[$campaign_details['status']] ?? $campaign_details['status'];
                ?>
            </p>
            <p><strong>Total lettres :</strong> <?php echo intval($campaign_details['total_letters']); ?></p>
            <p><strong>Envoyées :</strong> <?php echo intval($campaign_details['sent_letters']); ?></p>
            <p><strong>Erreurs :</strong> <?php echo intval($campaign_details['failed_letters']); ?></p>
            <p><strong>Date création :</strong> <?php echo date('d/m/Y H:i:s', strtotime($campaign_details['created_at'])); ?></p>
            
            <h4 style="color: #495057; font-size: 1.1em; margin: 20px 0 10px 0; display: flex; align-items: center; gap: 8px;">📝 Contenu de la lettre :</h4>
            <div style="background: #f8f9fa; padding: 20px; border-left: 4px solid #0073aa; border-radius: 4px; margin: 15px 0; font-size: 14px; line-height: 1.6; color: #495057;">
                <?php echo nl2br(esc_html($campaign_details['content'])); ?>
            </div>
        </div>
        
        <h3>📋 Détail des envois</h3>
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
                                'pending' => '⏳ En attente',
                                'sent' => '✅ Envoyée',
                                'failed' => '❌ Erreur'
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

<?php else: ?>
    <!-- Mode liste des campagnes -->
    <div class="sci-frontend-wrapper">
        <h1><?php echo esc_html($title ?? '📬 Mes Campagnes de Lettres'); ?></h1>
        
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
                                    'draft' => '📝 Brouillon',
                                    'processing' => '⏳ En cours',
                                    'completed' => '✅ Terminée',
                                    'completed_with_errors' => '⚠️ Terminée avec erreurs'
                                ];
                                echo $status_labels[$campaign['status']] ?? $campaign['status'];
                                ?>
                            </td>
                            <td><?php echo intval($campaign['total_letters']); ?></td>
                            <td><?php echo intval($campaign['sent_letters']); ?></td>
                            <td><?php echo intval($campaign['failed_letters']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($campaign['created_at'])); ?></td>
                            <td>
                                <a href="?view=<?php echo intval($campaign['id']); ?>" 
                                   class="sci-button"
                                   style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; color: white !important; border: none !important; text-decoration: none !important; display: inline-block !important; padding: 8px 16px !important; border-radius: 6px !important;">
                                    👁️ Voir détails
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?> 