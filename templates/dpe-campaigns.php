<?php
/**
 * Template pour afficher les campagnes DPE dans le dashboard WordPress
 * Variables attendues dans $context :
 * - $campaigns : array des campagnes de l'utilisateur
 * - $campaign_details : array des détails d'une campagne (mode vue)
 * - $view_mode : boolean pour indiquer le mode d'affichage
 * - $title : string titre de la page
 * - $show_empty_message : boolean pour afficher un message si aucune campagne
 */
?>

<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    
    <?php if ($view_mode && isset($campaign_details)): ?>
        <!-- ✅ MODE VUE DÉTAILLÉE D'UNE CAMPAGNE -->
        <div class="campaign-details">
            <div class="campaign-header">
                <h2><?php echo esc_html($campaign_details['title']); ?></h2>
                <div class="campaign-meta">
                    <span class="campaign-status status-<?php echo esc_attr($campaign_details['status']); ?>">
                        <?php echo esc_html(ucfirst($campaign_details['status'])); ?>
                    </span>
                    <span class="campaign-date">
                        Créée le <?php echo esc_html(date('d/m/Y à H:i', strtotime($campaign_details['created_at']))); ?>
                    </span>
                </div>
            </div>
            
            <div class="campaign-content">
                <h3>📧 Contenu du courriel</h3>
                <div class="email-content">
                    <?php echo nl2br(esc_html($campaign_details['content'])); ?>
                </div>
            </div>
            
            <div class="campaign-entries">
                <h3>🏠 DPE incluses (<?php echo count($campaign_details['entries']); ?>)</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Numéro DPE</th>
                            <th>Type bâtiment</th>
                            <th>Adresse</th>
                            <th>Commune</th>
                            <th>Étiquette DPE</th>
                            <th>Statut envoi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaign_details['entries'] as $entry): ?>
                            <tr>
                                <td><?php echo esc_html($entry['numero_dpe']); ?></td>
                                <td><?php echo esc_html($entry['type_batiment']); ?></td>
                                <td><?php echo esc_html($entry['adresse']); ?></td>
                                <td><?php echo esc_html($entry['commune']); ?></td>
                                <td>
                                    <span class="dpe-label dpe-<?php echo strtolower($entry['etiquette_dpe']); ?>">
                                        <?php echo esc_html($entry['etiquette_dpe']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($entry['sent_at']) && $entry['sent_at']): ?>
                                        <span class="status-sent">✅ Envoyé le <?php echo esc_html(date('d/m/Y', strtotime($entry['sent_at']))); ?></span>
                                    <?php else: ?>
                                        <span class="status-pending">⏳ En attente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="campaign-actions">
                <a href="<?php echo admin_url('admin.php?page=dpe-campaigns'); ?>" class="button">
                    ← Retour à la liste
                </a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- ✅ MODE LISTE DES CAMPAGNES -->
        
        <?php if (empty($campaigns)): ?>
            <div class="no-campaigns">
                <div class="notice notice-info">
                    <p>
                        <strong>📬 Aucune campagne DPE trouvée</strong><br>
                        Vous n'avez pas encore créé de campagnes d'envoi de courriers DPE.
                    </p>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=dpe-panel'); ?>" class="button button-primary">
                            🏠 Aller au panneau DPE
                        </a>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="campaigns-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Titre de la campagne</th>
                            <th>Statut</th>
                            <th>DPE incluses</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($campaign['title']); ?></strong>
                                    <div class="campaign-preview">
                                        <?php echo esc_html(substr($campaign['content'], 0, 100)) . '...'; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="campaign-status status-<?php echo esc_attr($campaign['status']); ?>">
                                        <?php 
                                        $status_labels = [
                                            'draft' => '📝 Brouillon',
                                            'processing' => '⚙️ En cours',
                                            'completed' => '✅ Terminée',
                                            'failed' => '❌ Échec'
                                        ];
                                        echo esc_html($status_labels[$campaign['status']] ?? ucfirst($campaign['status']));
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html($campaign['entries_count']); ?> DPE
                                </td>
                                <td>
                                    <?php echo esc_html(date('d/m/Y à H:i', strtotime($campaign['created_at']))); ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=dpe-campaigns&view=' . $campaign['id']); ?>" 
                                       class="button button-small">
                                        👁️ Voir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

 