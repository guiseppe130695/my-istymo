<?php
/**
 * Template pour la page des campagnes SCI
 * Variables attendues dans $context :
 * - $campaigns : array des campagnes de l'utilisateur
 * - $campaign_details : array des détails d'une campagne (si en mode vue détaillée)
 * - $view_mode : boolean indiquant si on est en mode vue détaillée
 */
?>

<?php if ($view_mode && $campaign_details): ?>
    <!-- Mode vue détaillée d'une campagne -->
    <div class="wrap">
        <h1>📬 Détails de la campagne : <?php echo esc_html($campaign_details['title']); ?></h1>
        
        <a href="<?php echo admin_url('admin.php?page=sci-campaigns'); ?>" class="button">
            ← Retour aux campagnes
        </a>
        
        <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc; border-radius: 5px;">
            <h3>📊 Résumé</h3>
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
            
            <h4>📝 Contenu de la lettre :</h4>
            <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa;">
                <?php echo nl2br(esc_html($campaign_details['content'])); ?>
            </div>
        </div>
        
        <h3>📋 Détail des envois</h3>
        <table class="widefat fixed striped">
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
                                <span style="color: red; font-size: 12px;">
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
    <div class="wrap">
        <h1>📬 Mes Campagnes de Lettres</h1>
        
        <?php if (empty($campaigns)): ?>
            <div class="notice notice-info">
                <p>Aucune campagne trouvée. Créez votre première campagne depuis la page principale SCI.</p>
            </div>
        <?php else: ?>
            <table class="widefat fixed striped">
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
                                <a href="<?php echo admin_url('admin.php?page=sci-campaigns&view=' . $campaign['id']); ?>" 
                                   class="button button-small">
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