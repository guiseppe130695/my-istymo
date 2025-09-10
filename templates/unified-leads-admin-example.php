<?php
if (!defined('ABSPATH')) exit;

/**
 * Exemple d'utilisation du composant de tableau unifié pour les leads
 * Montre comment configurer et utiliser le nouveau composant réutilisable
 */
function unified_leads_admin_example($context = array()) {
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'));
    }
    
    // Inclure le composant de tableau unifié
    require_once plugin_dir_path(__FILE__) . 'unified-table-component.php';
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $status_manager = Lead_Status_Manager::get_instance();
    
    // Récupérer les filtres
    $filters = array();
    if (!empty($_GET['lead_type'])) $filters['lead_type'] = sanitize_text_field($_GET['lead_type']);
    if (!empty($_GET['status'])) $filters['status'] = sanitize_text_field($_GET['status']);
    if (!empty($_GET['priorite'])) $filters['priorite'] = sanitize_text_field($_GET['priorite']);
    if (!empty($_GET['date_from'])) $filters['date_from'] = sanitize_text_field($_GET['date_from']);
    if (!empty($_GET['date_to'])) $filters['date_to'] = sanitize_text_field($_GET['date_to']);
    
    // Récupérer les leads
    $leads = $leads_manager->get_leads(null, $filters);
    
    // Configuration du tableau pour les leads
    $table_config = array(
        'title' => '📋 Gestion des Leads',
        'table_id' => 'leads-table',
        'show_filters' => true,
        'show_actions' => true,
        'show_checkboxes' => true,
        'per_page' => 20,
        'is_shortcode' => $context['is_shortcode'] ?? false,
        'empty_message' => 'Aucun lead trouvé avec les critères actuels.',
        'empty_action_text' => 'Voir tous les leads',
        'empty_action_url' => '?page=unified-leads',
        
        // Configuration des colonnes
        'columns' => array(
            'company' => array(
                'label' => 'Entreprise',
                'type' => 'company',
                'icon' => 'admin-home',
                'width' => '25%',
                'subtitle' => 'original_id'
            ),
            'category' => array(
                'label' => 'Catégorie',
                'type' => 'text',
                'icon' => 'category',
                'width' => '12%'
            ),
            'priority' => array(
                'label' => 'Priorité',
                'type' => 'priority',
                'icon' => 'flag',
                'width' => '10%',
                'priority_map' => array(
                    'haute' => array('class' => 'high', 'text' => 'Haute'),
                    'normale' => array('class' => 'normal', 'text' => 'Normale'),
                    'basse' => array('class' => 'low', 'text' => 'Basse')
                )
            ),
            'location' => array(
                'label' => 'Localisation',
                'type' => 'text',
                'icon' => 'location',
                'width' => '15%'
            ),
            'status' => array(
                'label' => 'Statut',
                'type' => 'status',
                'icon' => 'info',
                'width' => '12%',
                'status_map' => array(
                    'nouveau' => array('class' => 'pending', 'text' => 'Nouveau'),
                    'en_cours' => array('class' => 'progress', 'text' => 'En cours'),
                    'termine' => array('class' => 'completed', 'text' => 'Terminé'),
                    'qualifie' => array('class' => 'qualified', 'text' => 'Qualifié'),
                    'proposition' => array('class' => 'proposal', 'text' => 'Proposition'),
                    'negociation' => array('class' => 'negotiation', 'text' => 'Négociation'),
                    'gagne' => array('class' => 'won', 'text' => 'Gagné'),
                    'perdu' => array('class' => 'lost', 'text' => 'Perdu')
                )
            ),
            'date_creation' => array(
                'label' => 'Date création',
                'type' => 'date',
                'icon' => 'calendar',
                'width' => '12%',
                'format' => 'd/m/Y'
            )
        ),
        
        // Configuration des filtres
        'filters' => array(
            'lead_type' => array(
                'type' => 'select',
                'placeholder' => 'Tous les types',
                'options' => array(
                    'sci' => 'SCI',
                    'dpe' => 'DPE'
                )
            ),
            'status' => array(
                'type' => 'select',
                'placeholder' => 'Tous les statuts',
                'options' => array(
                    'nouveau' => 'Nouveau',
                    'en_cours' => 'En cours',
                    'termine' => 'Terminé',
                    'qualifie' => 'Qualifié',
                    'proposition' => 'Proposition',
                    'negociation' => 'Négociation',
                    'gagne' => 'Gagné',
                    'perdu' => 'Perdu'
                )
            ),
            'priorite' => array(
                'type' => 'select',
                'placeholder' => 'Toutes les priorités',
                'options' => array(
                    'haute' => 'Haute',
                    'normale' => 'Normale',
                    'basse' => 'Basse'
                )
            ),
            'date_from' => array(
                'type' => 'date',
                'placeholder' => 'Date de début'
            ),
            'date_to' => array(
                'type' => 'date',
                'placeholder' => 'Date de fin'
            )
        ),
        
        // Configuration des actions
        'actions' => array(
            'view' => array(
                'label' => 'Voir',
                'icon' => 'visibility',
                'onclick' => 'openLeadDetailModal($(this).data(\'item-id\'));'
            ),
            'edit' => array(
                'label' => 'Modifier',
                'icon' => 'edit',
                'onclick' => 'editLead($(this).data(\'item-id\'));'
            ),
            'delete' => array(
                'label' => 'Supprimer',
                'icon' => 'trash',
                'onclick' => 'if(confirm(\'Êtes-vous sûr de vouloir supprimer ce lead ?\')) { deleteLead($(this).data(\'item-id\')); }'
            )
        )
    );
    
    // Contexte pour le composant
    $component_context = array(
        'page_slug' => 'unified-leads',
        'shortcode_id' => $context['shortcode_id'] ?? '',
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_nonce')
    );
    
    // Préparer les données pour le tableau
    $table_data = array();
    foreach ($leads as $lead) {
        // Extraire les données selon le type de lead
        $company_name = '';
        $location = '';
        $category = '';
        
        if (!empty($lead->data_originale)) {
            if ($lead->lead_type === 'dpe') {
                $company_name = $lead->data_originale['adresse_ban'] ?? 'Bien immobilier';
                $ville = $lead->data_originale['nom_commune_ban'] ?? '';
                $code_postal = $lead->data_originale['code_postal_ban'] ?? '';
                $location = $ville . ($code_postal ? ' (' . $code_postal . ')' : '');
                $category = 'Immobilier';
            } elseif ($lead->lead_type === 'sci') {
                $company_name = $lead->data_originale['denomination'] ?? $lead->data_originale['raisonSociale'] ?? 'SCI';
                $ville = $lead->data_originale['ville'] ?? '';
                $code_postal = $lead->data_originale['code_postal'] ?? '';
                $location = $ville . ($code_postal ? ' (' . $code_postal . ')' : '');
                $category = 'Société Civile';
            }
        }
        
        // Créer l'objet de données pour le tableau
        $table_data[] = (object) array(
            'id' => $lead->id,
            'company' => $company_name ?: 'Lead #' . $lead->id,
            'original_id' => $lead->original_id,
            'category' => $category,
            'priority' => $lead->priorite,
            'location' => $location ?: '—',
            'status' => $lead->status,
            'date_creation' => $lead->date_creation,
            'lead_type' => $lead->lead_type,
            'data_originale' => $lead->data_originale
        );
    }
    
    // Utiliser le composant de tableau unifié
    unified_table_component($table_config, $table_data, $component_context);
    
    // Inclure les modals et scripts spécifiques aux leads
    include_once plugin_dir_path(__FILE__) . 'lead-detail-modal.php';
    
    // Scripts spécifiques aux leads
    ?>
    <script>
    // Fonctions spécifiques aux leads
    function openLeadDetailModal(leadId) {
        console.log('Opening modal for lead ID:', leadId);
        
        const modal = jQuery('#lead-detail-modal');
        modal.removeClass('my-istymo-hidden').addClass('my-istymo-show');
        modal.show();
        
        // Charger les détails via AJAX
        jQuery.ajax({
            url: unifiedTableAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'my_istymo_get_lead_details',
                lead_id: leadId,
                nonce: unifiedTableAjax.nonce
            },
            beforeSend: function() {
                jQuery('#lead-detail-content').html('<div style="text-align: center; padding: 20px;"><p><span class="dashicons dashicons-update" style="animation: spin 1s linear infinite; margin-right: 8px;"></span>Chargement des détails...</p></div>');
            },
            success: function(response) {
                if (response && response.success) {
                    jQuery('#lead-modal-title').text('Lead #' + leadId + ' - ' + (response.data.lead_type || 'Détails').toUpperCase());
                    var htmlContent = generateModernLeadDetailHTML(response.data);
                    jQuery('#lead-detail-content').html(htmlContent);
                    jQuery('#edit-lead-btn').show();
                    initLeadEditForm();
                } else {
                    jQuery('#lead-detail-content').html('<div class="my-istymo-error-state"><p>❌ Erreur: ' + (response && response.data ? response.data : 'Impossible de charger les détails') + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                jQuery('#lead-detail-content').html('<div style="color: red; padding: 20px;"><p>❌ Erreur de communication avec le serveur: ' + error + '</p></div>');
            }
        });
    }
    
    function closeLeadDetailModal() {
        jQuery('#lead-detail-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
        jQuery('#lead-detail-modal').hide();
    }
    
    function editLead(leadId) {
        // Rediriger vers la page d'édition ou ouvrir un modal d'édition
        console.log('Edit lead:', leadId);
        // Implémentation selon les besoins
    }
    
    function deleteLead(leadId) {
        jQuery.ajax({
            url: unifiedTableAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_unified_lead',
                lead_id: leadId,
                nonce: unifiedTableAjax.nonce
            },
            beforeSend: function() {
                jQuery('[data-item-id="' + leadId + '"]').prop('disabled', true);
            },
            success: function(response) {
                if (response && response.success) {
                    jQuery('[data-item-id="' + leadId + '"]').closest('tr').fadeOut(400, function() {
                        jQuery(this).remove();
                        if (jQuery('.my-istymo-table-row').length === 0) {
                            location.reload();
                        }
                    });
                    if (typeof UnifiedTableUtils !== 'undefined') {
                        UnifiedTableUtils.showNotification('Lead supprimé avec succès', 'success');
                    }
                } else {
                    alert('Erreur lors de la suppression du lead: ' + (response && response.data ? response.data : 'Erreur inconnue'));
                    jQuery('[data-item-id="' + leadId + '"]').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                alert('Erreur de communication avec le serveur: ' + error);
                jQuery('[data-item-id="' + leadId + '"]').prop('disabled', false);
            }
        });
    }
    
    function initLeadEditForm() {
        // Initialisation du formulaire d'édition des leads
        jQuery('#lead-edit-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            
            const formData = jQuery(this).serialize();
            const submitBtn = jQuery(this).find('button[type="submit"]');
            
            submitBtn.prop('disabled', true).text('Sauvegarde...');
            
            jQuery.ajax({
                url: unifiedTableAjax.ajaxurl,
                type: 'POST',
                data: formData + '&action=my_istymo_update_lead&nonce=' + unifiedTableAjax.nonce,
                success: function(response) {
                    if (response.success) {
                        jQuery('#lead-detail-content').prepend('<div class="my-istymo-success" style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><p>✅ Lead modifié avec succès!</p></div>');
                        
                        setTimeout(function() {
                            jQuery('.my-istymo-success').fadeOut();
                        }, 3000);
                        
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                        
                    } else {
                        alert('Erreur lors de la modification: ' + (response.data || 'Erreur inconnue'));
                    }
                },
                error: function() {
                    alert('Erreur de communication avec le serveur');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text('💾 Sauvegarder les modifications');
                }
            });
        });
    }
    
    function generateModernLeadDetailHTML(leadData) {
        // Fonction pour générer le HTML des détails du lead
        // (Code existant de la fonction generateModernLeadDetailHTML)
        var html = '';
        
        // Première ligne - Informations de base
        html += '<div class="my-istymo-lead-summary-row">';
        
        // Statut avec liste déroulante éditable
        html += '<div class="my-istymo-summary-item">';
        html += '<span class="my-istymo-summary-label">Statut :</span>';
        html += '<select class="my-istymo-edit-select" id="edit-status-' + leadData.id + '" data-field="status">';
        html += '<option value="nouveau"' + (leadData.status === 'nouveau' ? ' selected' : '') + '>Nouveau</option>';
        html += '<option value="en_cours"' + (leadData.status === 'en_cours' ? ' selected' : '') + '>En cours</option>';
        html += '<option value="qualifie"' + (leadData.status === 'qualifie' ? ' selected' : '') + '>Qualifié</option>';
        html += '<option value="proposition"' + (leadData.status === 'proposition' ? ' selected' : '') + '>Proposition</option>';
        html += '<option value="negociation"' + (leadData.status === 'negociation' ? ' selected' : '') + '>Négociation</option>';
        html += '<option value="gagne"' + (leadData.status === 'gagne' ? ' selected' : '') + '>Gagné</option>';
        html += '<option value="perdu"' + (leadData.status === 'perdu' ? ' selected' : '') + '>Perdu</option>';
        html += '</select>';
        html += '</div>';
        
        // Priorité avec liste déroulante éditable
        html += '<div class="my-istymo-summary-item">';
        html += '<span class="my-istymo-summary-label">Priorité :</span>';
        html += '<select class="my-istymo-edit-select" id="edit-priorite-' + leadData.id + '" data-field="priorite">';
        html += '<option value="basse"' + (leadData.priorite === 'basse' ? ' selected' : '') + '>Basse</option>';
        html += '<option value="normale"' + (leadData.priorite === 'normale' ? ' selected' : '') + '>Normale</option>';
        html += '<option value="haute"' + (leadData.priorite === 'haute' ? ' selected' : '') + '>Haute</option>';
        html += '</select>';
        html += '</div>';
        
        // Dates
        html += '<div class="my-istymo-summary-item">';
        html += '<span class="my-istymo-summary-label">Créé le :</span>';
        html += '<span class="my-istymo-summary-value">' + (leadData.date_creation ? new Date(leadData.date_creation).toLocaleDateString('fr-FR') : '—') + '</span>';
        html += '</div>';
        
        html += '<div class="my-istymo-summary-item">';
        html += '<span class="my-istymo-summary-label">Modifié le :</span>';
        html += '<span class="my-istymo-summary-value">' + (leadData.date_modification ? new Date(leadData.date_modification).toLocaleDateString('fr-FR') : '—') + '</span>';
        html += '</div>';
        
        html += '</div>'; // Fin summary-row
        
        // Container avec 2 colonnes
        html += '<div class="my-istymo-lead-detail-container">';
        
        // Colonne gauche - Informations du lead
        html += '<div class="my-istymo-lead-detail-left">';
        
        // Carte d'informations principales
        html += '<div class="my-istymo-info-card">';
        html += '<div class="my-istymo-card-header">';
        html += '<h4><span class="dashicons dashicons-info"></span> Informations ' + (leadData.lead_type === 'sci' ? 'SCI' : 'DPE') + '</h4>';
        html += '</div>';
        html += '<div class="my-istymo-card-content">';
        
        // Type de lead avec badge
        var typeIcon = leadData.lead_type === 'sci' ? '🏢' : '🏠';
        var typeText = leadData.lead_type === 'sci' ? 'Société Civile' : 'Bien Immobilier';
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Type :</span>';
        html += '<span class="my-istymo-info-value">' + typeIcon + ' ' + typeText + '</span>';
        html += '</div>';
        
        // ID original
        var idLabel = leadData.lead_type === 'sci' ? 'SIREN :' : 'DPE ID :';
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">' + idLabel + '</span>';
        html += '<span class="my-istymo-info-value">' + (leadData.original_id || '—') + '</span>';
        html += '</div>';
        
        // Informations spécifiques selon le type
        if (leadData.data_originale) {
            var data = leadData.data_originale;
            
            if (leadData.lead_type === 'sci') {
                // Informations SCI
                if (data.denomination || data.raisonSociale) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Dénomination :</span>';
                    html += '<span class="my-istymo-info-value">' + (data.denomination || data.raisonSociale || '—') + '</span>';
                    html += '</div>';
                }
                
                if (data.dirigeant) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Dirigeant :</span>';
                    html += '<span class="my-istymo-info-value">' + data.dirigeant + '</span>';
                    html += '</div>';
                }
                
                // Adresse
                var adresseComplete = '';
                var adresseParts = [];
                
                if (data.adresse) {
                    adresseParts.push(data.adresse);
                }
                
                if (data.code_postal && data.ville) {
                    adresseParts.push(data.code_postal + ' ' + data.ville);
                } else if (data.code_postal) {
                    adresseParts.push(data.code_postal);
                } else if (data.ville) {
                    adresseParts.push(data.ville);
                }
                
                adresseComplete = adresseParts.join(', ');
                
                if (adresseComplete) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Adresse :</span>';
                    html += '<span class="my-istymo-info-value">' + adresseComplete + '</span>';
                    html += '</div>';
                }
                
            } else if (leadData.lead_type === 'dpe') {
                // Informations DPE
                var adresseComplete = '';
                var adresseParts = [];
                
                if (data.adresse_ban) {
                    var adresseClean = data.adresse_ban;
                    if (data.code_postal_ban && data.nom_commune_ban) {
                        var pattern = new RegExp('\\s*' + data.code_postal_ban + '\\s*' + data.nom_commune_ban + '\\s*$', 'i');
                        adresseClean = adresseClean.replace(pattern, '').trim();
                    }
                    adresseParts.push(adresseClean || data.adresse_ban);
                }
                
                if (data.code_postal_ban && data.nom_commune_ban) {
                    adresseParts.push(data.code_postal_ban + ' ' + data.nom_commune_ban);
                } else if (data.code_postal_ban) {
                    adresseParts.push(data.code_postal_ban);
                } else if (data.nom_commune_ban) {
                    adresseParts.push(data.nom_commune_ban);
                }
                
                adresseComplete = adresseParts.join(', ');
                
                if (adresseComplete) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Adresse :</span>';
                    html += '<span class="my-istymo-info-value">' + adresseComplete + '</span>';
                    html += '</div>';
                }
                
                // Caractéristiques du bien
                if (data.surface_habitable_logement) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Surface :</span>';
                    html += '<span class="my-istymo-info-value">' + data.surface_habitable_logement + ' m²</span>';
                    html += '</div>';
                }
                
                if (data.etiquette_dpe) {
                    html += '<div class="my-istymo-info-row">';
                    html += '<span class="my-istymo-info-label">Étiquette DPE :</span>';
                    html += '<span class="my-istymo-info-value my-istymo-dpe-badge my-istymo-dpe-' + data.etiquette_dpe.toLowerCase() + '">';
                    html += '<span class="my-istymo-badge-dot"></span>';
                    html += data.etiquette_dpe;
                    html += '</span>';
                    html += '</div>';
                }
            }
        }
        
        html += '</div>'; // Fin card-content
        html += '</div>'; // Fin info-card
        html += '</div>'; // Fin colonne gauche
        
        // Colonne droite - Notes
        html += '<div class="my-istymo-lead-detail-right">';
        
        html += '<div class="my-istymo-info-card">';
        html += '<div class="my-istymo-card-header">';
        html += '<h4><span class="dashicons dashicons-edit"></span> Notes</h4>';
        html += '</div>';
        html += '<div class="my-istymo-card-content">';
        
        html += '<textarea class="my-istymo-edit-textarea" id="edit-notes-' + leadData.id + '" data-field="notes" placeholder="Ajouter des notes pour ce lead..." rows="6">' + (leadData.notes || '') + '</textarea>';
        
        html += '</div>'; // Fin card-content
        html += '</div>'; // Fin info-card
        html += '</div>'; // Fin colonne droite
        
        html += '</div>'; // Fin container principal
        
        // Boutons d'action
        html += '<div class="my-istymo-modal-actions" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e5e5e5; text-align: right;">';
        html += '<button type="button" class="my-istymo-btn my-istymo-btn-primary" onclick="saveLeadChanges(' + leadData.id + ');">';
        html += '<span class="dashicons dashicons-saved"></span> Sauvegarder';
        html += '</button>';
        html += '</div>';
        
        return html;
    }
    
    function saveLeadChanges(leadId) {
        var status = jQuery('#edit-status-' + leadId).val();
        var priorite = jQuery('#edit-priorite-' + leadId).val();
        var notes = jQuery('#edit-notes-' + leadId).val();
        
        if (!status || !priorite) {
            alert('❌ Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        var saveButton = jQuery('#lead-detail-modal .my-istymo-btn-primary');
        var originalText = saveButton.html();
        saveButton.html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> Sauvegarde...');
        saveButton.prop('disabled', true);
        
        jQuery.ajax({
            url: unifiedTableAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'my_istymo_update_lead_from_modal',
                lead_id: leadId,
                status: status,
                priorite: priorite,
                notes: notes,
                nonce: unifiedTableAjax.nonce
            },
            success: function(response) {
                saveButton.html(originalText);
                saveButton.prop('disabled', false);
                
                if (response && response.success) {
                    if (typeof UnifiedTableUtils !== 'undefined') {
                        UnifiedTableUtils.showNotification('Modifications sauvegardées avec succès !', 'success');
                    }
                    closeLeadDetailModal();
                    location.reload();
                } else {
                    if (typeof UnifiedTableUtils !== 'undefined') {
                        UnifiedTableUtils.showNotification('Erreur lors de la sauvegarde: ' + (response && response.data ? response.data : 'Erreur inconnue'), 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                saveButton.html(originalText);
                saveButton.prop('disabled', false);
                
                if (typeof UnifiedTableUtils !== 'undefined') {
                    UnifiedTableUtils.showNotification('Erreur de communication avec le serveur: ' + error, 'error');
                }
            }
        });
    }
    </script>
    <?php
}

