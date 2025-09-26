/**
 * JavaScript pour l'interface d'administration des leads unifiés
 * Gère les interactions, sélections multiples et modals
 */
jQuery(document).ready(function($) {
    
    // Variables globales
    let selectedLeads = [];
    let modalClosing = false; // Protection contre les fermetures multiples
    
    // ===== DÉFINITION DES FONCTIONS GLOBALES =====
    
    /**
     * Fonction globale pour ouvrir le modal de détail d'un lead
     * Cette fonction est appelée depuis les boutons HTML
     */
    function openLeadDetailModal(leadId) {
        console.log('=== OUVERTURE MODAL LEAD ===');
        console.log('Lead ID:', leadId);
        console.log('Type de leadId:', typeof leadId);
        
        // Vérifications préliminaires
        console.log('jQuery disponible:', typeof $ !== 'undefined');
        console.log('unifiedLeadsAjax disponible:', typeof unifiedLeadsAjax !== 'undefined');
        
        if (typeof unifiedLeadsAjax !== 'undefined') {
            console.log('AJAX URL:', unifiedLeadsAjax.ajaxurl);
            console.log('Nonce:', unifiedLeadsAjax.nonce);
        } else {
            console.error('unifiedLeadsAjax non défini!');
            console.log('Variables globales disponibles:', Object.keys(window).filter(k => k.includes('Ajax') || k.includes('ajax')));
            alert('Erreur: Variables AJAX non disponibles. Vérifiez que le script est bien chargé.');
            return;
        }
        
        // Vérifier le modal
        const modal = $('#lead-detail-modal');
        console.log('Modal trouvé:', modal.length > 0);
        
        if (modal.length === 0) {
            console.error('Modal #lead-detail-modal non trouvé dans le DOM');
            console.log('Modals disponibles:', $('[id*="modal"]').map(function() { return this.id; }).get());
            alert('Erreur: Modal non trouvé. Le template modal n\'est peut-être pas chargé.');
            return;
        }
        
        // Afficher le modal
        modal.removeClass('my-istymo-hidden').addClass('my-istymo-show');
        modal.show();
        
        // Ajouter un gestionnaire d'événement pour l'overlay (une seule fois)
        modal.off('click.lead-detail').on('click.lead-detail', '.my-istymo-modal-overlay', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Clic sur overlay - fermeture du modal');
            if (!modalClosing) {
                closeLeadDetailModal();
            }
        });
        
        // Empêcher la fermeture du modal quand on clique sur le contenu
        modal.off('click.lead-content').on('click.lead-content', '.my-istymo-modal-content', function(e) {
            e.stopPropagation();
        });
        
        // Gestionnaire d'événements clavier pour fermer avec Escape
        $(document).off('keydown.lead-detail').on('keydown.lead-detail', function(e) {
            if (e.key === 'Escape' && modal.hasClass('my-istymo-show')) {
                e.preventDefault();
                console.log('Touche Escape - fermeture du modal');
                if (!modalClosing) {
                    closeLeadDetailModal();
                }
            }
        });
        
        console.log('Modal affiché');
        
        // Test AJAX simple d'abord
        console.log('=== DÉBUT REQUÊTE AJAX ===');
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'my_istymo_get_lead_details',
                lead_id: leadId,
                nonce: unifiedLeadsAjax.nonce
            },
            beforeSend: function(xhr) {
                console.log('Envoi de la requête...');
                console.log('URL:', unifiedLeadsAjax.ajaxurl);
                console.log('Data:', {
                    action: 'my_istymo_get_lead_details',
                    lead_id: leadId,
                    nonce: unifiedLeadsAjax.nonce
                });
                
                $('#lead-detail-content').html('<div style="text-align: center; padding: 20px;"><p><span class="dashicons dashicons-update" style="animation: spin 1s linear infinite; margin-right: 8px;"></span>Chargement des détails...</p></div>');
            },
            success: function(response, textStatus, xhr) {
                console.log('=== RÉPONSE AJAX REÇUE ===');
                console.log('Status:', textStatus);
                console.log('Response:', response);
                console.log('Type de response:', typeof response);
                
                if (response && response.success) {
                    console.log('✅ Succès - Données reçues:', response.data);
                    
                    // Mettre à jour le titre du modal
                    var leadType = response.data.lead_type || 'lead';
                    var typeIcon = leadType === 'sci' ? '<i class="fas fa-building"></i>' : '<i class="fas fa-home"></i>';
                    $('#lead-modal-title').html(typeIcon + ' Lead #' + leadId + ' - ' + leadType.toUpperCase());
                    
                    // Mettre à jour la date de création dans l'en-tête
                    var creationDate = response.data.date_creation || response.data.created_at || response.data.date_ajout || response.data.timestamp;
                    if (creationDate) {
                        $('#lead-creation-date').text('Créé le ' + formatDate(creationDate));
                    } else {
                        $('#lead-creation-date').text('Date de création non disponible');
                    }
                    
                    // Générer le contenu HTML moderne
                    var htmlContent = generateModernLeadHTML(response.data);
                    
                    // Charger le contenu
                    $('#lead-detail-content').html(htmlContent);
                    
                } else {
                    console.log('❌ Échec - Message:', response ? response.data : 'Pas de réponse');
                    $('#lead-detail-content').html('<div style="color: red; padding: 20px;"><p>❌ Erreur: ' + (response && response.data ? response.data : 'Impossible de charger les détails') + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                console.log('=== ERREUR AJAX ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('XHR Status:', xhr.status);
                console.error('XHR StatusText:', xhr.statusText);
                console.error('Response Text:', xhr.responseText);
                console.error('Content-Type:', xhr.getResponseHeader('Content-Type'));
                
                var errorMsg = '<div style="color: red; padding: 20px; font-family: monospace;">';
                errorMsg += '<h4>❌ Erreur de communication avec le serveur</h4>';
                errorMsg += '<p><strong>Status HTTP:</strong> ' + xhr.status + ' ' + xhr.statusText + '</p>';
                errorMsg += '<p><strong>Status AJAX:</strong> ' + status + '</p>';
                errorMsg += '<p><strong>Error:</strong> ' + error + '</p>';
                errorMsg += '<p><strong>URL:</strong> ' + unifiedLeadsAjax.ajaxurl + '</p>';
                
                if (xhr.responseText) {
                    errorMsg += '<details style="margin-top: 10px;"><summary>Réponse serveur complète</summary>';
                    errorMsg += '<pre style="background: #f0f0f0; padding: 10px; white-space: pre-wrap; max-height: 300px; overflow: auto;">' + xhr.responseText + '</pre>';
                    errorMsg += '</details>';
                }
                
                errorMsg += '</div>';
                
                $('#lead-detail-content').html(errorMsg);
            }
        });
    }
    
    /**
     * Fonction globale pour fermer le modal de détail
     */
    function closeLeadDetailModal() {
        console.log('Fermeture du modal');
        const modal = $('#lead-detail-modal');
        modal.removeClass('my-istymo-show').addClass('my-istymo-hidden');
        modal.hide();
    }
    
    /**
     * Fonction globale pour supprimer un lead
     */
    function deleteLead(leadId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce lead ?')) {
            console.log('Deleting lead ID:', leadId);
            
            // Vérifier que les variables AJAX sont disponibles
            if (typeof unifiedLeadsAjax === 'undefined') {
                console.error('unifiedLeadsAjax not defined');
                alert('Erreur: Variables AJAX non disponibles');
                return;
            }
            
            $.ajax({
                url: unifiedLeadsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_unified_lead',
                    lead_id: leadId,
                    nonce: unifiedLeadsAjax.nonce
                },
                beforeSend: function() {
                    // Désactiver le bouton pendant la suppression
                    $('.delete-lead[data-lead-id="' + leadId + '"]').prop('disabled', true);
                },
                success: function(response) {
                    console.log('Delete Response:', response);
                    if (response && response.success) {
                        // Supprimer la ligne du tableau
                        $('tr[data-lead-id="' + leadId + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                        
                        // Afficher un message de succès
                        showNotification('Lead supprimé avec succès', 'success');
                    } else {
                        alert('Erreur lors de la suppression: ' + (response && response.data ? response.data : 'Erreur inconnue'));
                        $('.delete-lead[data-lead-id="' + leadId + '"]').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete Error:', xhr, status, error);
                    alert('Erreur de communication avec le serveur: ' + error);
                    $('.delete-lead[data-lead-id="' + leadId + '"]').prop('disabled', false);
                }
            });
        }
    }
    
    /**
     * Fonction de test de connexion AJAX
     */
    function testAjaxConnection() {
        console.log('=== TEST CONNEXION AJAX ===');
        
        if (typeof unifiedLeadsAjax === 'undefined') {
            console.error('unifiedLeadsAjax non disponible');
            alert('Variables AJAX non disponibles');
            return;
        }
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'my_istymo_test_ajax',
                nonce: unifiedLeadsAjax.nonce
            },
            success: function(response) {
                console.log('✅ Test AJAX réussi:', response);
                alert('Test AJAX réussi! Vérifiez la console pour les détails.');
            },
            error: function(xhr, status, error) {
                console.error('❌ Test AJAX échoué:', status, error);
                console.error('Response:', xhr.responseText);
                alert('Test AJAX échoué. Vérifiez la console pour les détails.');
            }
        });
    }
    
    /**
     * Génère le HTML moderne pour l'affichage des détails d'un lead
     */
    function generateModernLeadHTML(leadData) {
        
        var html = '';
        
        // En-tête avec informations principales
        html += '<div class="my-istymo-lead-header">';
        html += '<div class="my-istymo-lead-status-row">';
        
        // Statut avec édition inline
        html += '<div class="my-istymo-status-item">';
        html += '<label>Statut</label>';
        html += '<select class="my-istymo-status-select" data-field="status" data-lead-id="' + leadData.id + '">';
        var statuses = ['nouveau', 'en_cours', 'qualifie', 'proposition', 'negociation', 'gagne', 'perdu'];
        var statusLabels = {
            'nouveau': '<i class="fas fa-plus-circle"></i> Nouveau',
            'en_cours': '<i class="fas fa-spinner"></i> En cours',
            'qualifie': '<i class="fas fa-check-circle"></i> Qualifié',
            'proposition': '<i class="fas fa-file-alt"></i> Proposition',
            'negociation': '<i class="fas fa-handshake"></i> Négociation',
            'gagne': '<i class="fas fa-trophy"></i> Gagné',
            'perdu': '<i class="fas fa-times-circle"></i> Perdu'
        };
        statuses.forEach(function(status) {
            html += '<option value="' + status + '"' + (leadData.status === status ? ' selected' : '') + '>';
            html += statusLabels[status] || status;
            html += '</option>';
        });
        html += '</select>';
        html += '</div>';
        
        // Priorité avec édition inline
        html += '<div class="my-istymo-status-item">';
        html += '<label>Priorité</label>';
        html += '<select class="my-istymo-priority-select" data-field="priorite" data-lead-id="' + leadData.id + '">';
        var priorities = ['basse', 'normale', 'haute'];
        var priorityLabels = {
            'basse': '<i class="fas fa-arrow-down text-info"></i> Basse',
            'normale': '<i class="fas fa-minus text-warning"></i> Normale',
            'haute': '<i class="fas fa-arrow-up text-danger"></i> Haute'
        };
        priorities.forEach(function(priority) {
            html += '<option value="' + priority + '"' + (leadData.priorite === priority ? ' selected' : '') + '>';
            html += priorityLabels[priority] || priority;
            html += '</option>';
        });
        html += '</select>';
        html += '</div>';
        
        
        html += '</div>'; // Fin status-row
        html += '</div>'; // Fin header
        
        // Corps principal avec deux colonnes alignées
        html += '<div class="my-istymo-lead-body">';
        
        // Colonne gauche - Informations du lead
        html += '<div class="my-istymo-lead-left">';
        html += '<div class="my-istymo-info-card">';
        html += '<h4 class="my-istymo-card-title">';
        html += '<i class="fas fa-info-circle"></i> ';
        html += 'Informations ' + (leadData.lead_type === 'sci' ? 'SCI' : 'DPE');
        html += '</h4>';
        
        // Afficher les données originales selon le type
        if (leadData.data_originale) {
            var originalData = typeof leadData.data_originale === 'string' ? 
                JSON.parse(leadData.data_originale) : leadData.data_originale;
            
            if (leadData.lead_type === 'sci') {
                html += generateSCIInfo(originalData);
            } else {
                html += generateDPEInfo(originalData);
            }
        }
        
        html += '</div>'; // Fin info-card
        html += '</div>'; // Fin colonne gauche
        
        // Colonne droite - Notes et actions
        html += '<div class="my-istymo-lead-right">';
        
        // Notes éditables
        html += '<div class="my-istymo-notes-card">';
        html += '<h4 class="my-istymo-card-title">';
        html += '<i class="fas fa-sticky-note"></i> Notes';
        html += '</h4>';
        html += '<textarea class="my-istymo-notes-textarea" data-lead-id="' + leadData.id + '" ';
        html += 'placeholder="Ajouter des notes sur ce lead..." rows="8">';
        html += (leadData.notes || '');
        html += '</textarea>';
        html += '</div>';
        
        html += '</div>'; // Fin colonne droite
        html += '</div>'; // Fin corps
        
        
        return html;
    }
    
    /**
     * Génère les informations SCI
     */
    function generateSCIInfo(data) {
        var html = '<div class="my-istymo-sci-info">';
        
        // Dénomination
        if (data.denomination || data.nom_entreprise) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-building"></i> Dénomination</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(data.denomination || data.nom_entreprise) + '</span>';
            html += '</div>';
        }
        
        // SIREN
        if (data.siren) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-hashtag"></i> SIREN</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(data.siren) + '</span>';
            html += '</div>';
        }
        
        // Dirigeant
        if (data.dirigeant || data.representant) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-user-tie"></i> Dirigeant</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(data.dirigeant || data.representant) + '</span>';
            html += '</div>';
        }
        
        // Adresse complète
        if (data.adresse || data.adresse_complete) {
            var adresse = data.adresse || data.adresse_complete;
            var ville = '';
            if (data.ville) {
                ville = ', ' + data.ville;
            }
            if (data.code_postal) {
                ville += ' ' + data.code_postal;
            }
            
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-map-marker-alt"></i> Adresse</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(adresse + ville) + '</span>';
            html += '</div>';
        }
        
        // Statut juridique
        if (data.forme_juridique || data.statut) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-gavel"></i> Statut Juridique</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(data.forme_juridique || data.statut) + '</span>';
            html += '</div>';
        }
        
        // Capital social
        if (data.capital_social) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-euro-sign"></i> Capital Social</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(data.capital_social) + ' €</span>';
            html += '</div>';
        }
        
        // Date de création
        if (data.date_creation_entreprise || data.date_immatriculation) {
            var dateCreation = data.date_creation_entreprise || data.date_immatriculation;
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-calendar-alt"></i> Date de Création</span>';
            html += '<span class="my-istymo-info-value">' + formatDate(dateCreation) + '</span>';
            html += '</div>';
        }
        
        // Activité principale
        if (data.activite_principale || data.secteur_activite) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-briefcase"></i> Activité</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(data.activite_principale || data.secteur_activite) + '</span>';
            html += '</div>';
        }
        
        html += '</div>';
        return html;
    }
    
    /**
     * Génère les informations DPE
     */
    function generateDPEInfo(data) {
        var html = '<div class="my-istymo-dpe-info">';
        
        // DPE ID
        if (data.dpe_id || data.numero_dpe || data.id_dpe) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-hashtag"></i> DPE ID</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(data.dpe_id || data.numero_dpe || data.id_dpe) + '</span>';
            html += '</div>';
        }
        
        // Adresse
        if (data.adresse_ban || data.adresse) {
            var adresse = data.adresse_ban || data.adresse;
            var ville = '';
            if (data.nom_commune_ban || data.ville) {
                ville = ', ' + (data.nom_commune_ban || data.ville);
            }
            if (data.code_postal_ban || data.code_postal) {
                ville += ' ' + (data.code_postal_ban || data.code_postal);
            }
            
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-map-marker-alt"></i> Adresse</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(adresse + ville) + '</span>';
            html += '</div>';
        }
        
        // Surface
        if (data.surface_habitable_logement) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-ruler-combined"></i> Surface</span>';
            html += '<span class="my-istymo-info-value">' + data.surface_habitable_logement + ' m²</span>';
            html += '</div>';
        }
        
        // Type Bâtiment
        if (data.type_batiment) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-building"></i> Type Bâtiment</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(data.type_batiment) + '</span>';
            html += '</div>';
        }
        
        // Étiquette DPE
        if (data.etiquette_dpe) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-bolt"></i> Étiquette DPE</span>';
            html += '<span class="my-istymo-dpe-badge dpe-' + data.etiquette_dpe.toLowerCase() + '">';
            html += escapeHtml(data.etiquette_dpe);
            html += '</span>';
            html += '</div>';
        }
        
        // Date DPE
        if (data.date_etablissement_dpe || data.date_dpe) {
            var dateDpe = data.date_etablissement_dpe || data.date_dpe;
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-calendar-alt"></i> Date DPE</span>';
            html += '<span class="my-istymo-info-value">' + formatDate(dateDpe) + '</span>';
            html += '</div>';
        }
        
        // Année de construction
        if (data.annee_construction && data.annee_construction !== '0') {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-calendar-alt"></i> Année Construction</span>';
            html += '<span class="my-istymo-info-value">' + escapeHtml(data.annee_construction) + '</span>';
            html += '</div>';
        }
        
        // Consommation énergétique
        if (data.conso_energie_primaire_logement) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-fire"></i> Consommation</span>';
            html += '<span class="my-istymo-info-value">' + data.conso_energie_primaire_logement + ' kWh/m²/an</span>';
            html += '</div>';
        }
        
        // Émissions GES
        if (data.emission_ges_logement) {
            html += '<div class="my-istymo-info-row">';
            html += '<span class="my-istymo-info-label"><i class="fas fa-leaf"></i> Émissions GES</span>';
            html += '<span class="my-istymo-info-value">' + data.emission_ges_logement + ' kg CO2/m²/an</span>';
            html += '</div>';
        }
        
        // Bouton pour voir les détails du DPE
        html += '<div class="my-istymo-info-row my-istymo-dpe-action">';
        html += '<button type="button" class="my-istymo-btn-dpe-details" onclick="viewDPEDetails(\'' + (data.dpe_id || data.numero_dpe || data.id_dpe || '') + '\')">';
        html += '<i class="fas fa-eye"></i> Voir les détails du DPE';
        html += '</button>';
        html += '</div>';
        
        html += '</div>';
        return html;
    }
    
    /**
     * Fonctions utilitaires
     */
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            var date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateString;
        }
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Sauvegarde les modifications depuis l'en-tête
     */
    window.saveLeadChangesFromHeader = function() {
        // Récupérer l'ID du lead depuis le titre du modal ou depuis les selects
        var modalTitle = $('#lead-modal-title').text();
        var leadIdFromTitle = modalTitle.match(/Lead #(\d+)/);
        
        var leadId = null;
        if (leadIdFromTitle && leadIdFromTitle[1]) {
            leadId = leadIdFromTitle[1];
        } else {
            // Fallback : récupérer depuis les data-lead-id des selects
            var statusSelect = $('.my-istymo-status-select[data-lead-id]');
            if (statusSelect.length > 0) {
                leadId = statusSelect.attr('data-lead-id');
            }
        }
        
        if (leadId) {
            saveLeadChanges(leadId);
        } else {
            showNotification('Impossible de déterminer l\'ID du lead', 'error');
        }
    };
    
    /**
     * Sauvegarde les modifications du lead
     */
    window.saveLeadChanges = function(leadId) {
        var status = $('.my-istymo-status-select[data-lead-id="' + leadId + '"]').val();
        var priorite = $('.my-istymo-priority-select[data-lead-id="' + leadId + '"]').val();
        var notes = $('.my-istymo-notes-textarea[data-lead-id="' + leadId + '"]').val();
        
        
        var ajaxData = {
            action: 'my_istymo_update_lead_from_modal',
            lead_id: leadId,
            status: status,
            priorite: priorite,
            notes: notes,
            nonce: unifiedLeadsAjax.nonce
        };
        
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: ajaxData,
            beforeSend: function() {
                // Désactiver le bouton de sauvegarde dans l'en-tête
                $('#save-lead-header-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sauvegarde...');
            },
            success: function(response) {
                if (response && response.success) {
                    // Fermer le modal
                    closeLeadDetailModal();
                    
                    // Une seule notification combinée
                    showNotification('Modifications sauvegardées avec succès !', 'success');
                    
                    // Recharger immédiatement avec un paramètre pour éviter le cache
                    setTimeout(function() {
                        // Ajouter un paramètre timestamp pour éviter le cache
                        var currentUrl = window.location.href;
                        var separator = currentUrl.includes('?') ? '&' : '?';
                        var newUrl = currentUrl + separator + 'refresh=' + Date.now();
                        window.location.href = newUrl;
                    }, 1000);
                } else {
                    showNotification('Erreur lors de la sauvegarde: ' + (response.data || 'Erreur inconnue'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Erreur de communication avec le serveur', 'error');
            },
            complete: function() {
                // Réactiver le bouton de sauvegarde dans l'en-tête
                $('#save-lead-header-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Sauvegarder');
            }
        });
    };
    
    /**
     * Affiche les détails du DPE dans un nouvel onglet
     */
    window.viewDPEDetails = function(dpeId) {
        if (!dpeId || dpeId === 'null' || dpeId === '') {
            showNotification('ID DPE non disponible', 'error');
            return;
        }
        
        
        // URL vers l'observatoire DPE ADEME
        var dpeUrl = 'https://observatoire-dpe-audit.ademe.fr/afficher-dpe/' + encodeURIComponent(dpeId);
        
        // Ouvrir dans un nouvel onglet
        window.open(dpeUrl, '_blank');
        
        showNotification('Ouverture des détails DPE dans un nouvel onglet', 'info');
    };
    
    /**
     * Met à jour le lead dans le tableau après sauvegarde
     */
    function updateLeadInTable(leadId, status, priorite, notes) {
        // Fonction désactivée - le rechargement automatique gère la mise à jour
        return;
    }
    
    /**
     * Affiche une notification ultra simple
     */
    function showNotification(message, type) {
        // Supprimer les notifications existantes
        $('.my-istymo-notification').remove();
        
        // Créer et afficher la notification
        var notification = $('<div class="my-istymo-notification ' + type + '">' + message + '</div>');
        $('body').append(notification);
        notification.show();
        
        // Supprimer après 2 secondes
        setTimeout(function() {
            notification.remove();
        }, 2000);
    }
    
    
    // Assigner les fonctions aux variables globales
    window.openLeadDetailModal = openLeadDetailModal;
    window.closeLeadDetailModal = closeLeadDetailModal;
    window.deleteLead = deleteLead;
    window.testAjaxConnection = testAjaxConnection;
    
    // Initialisation
    initLeadManagement();
    
    /**
     * Initialise la gestion des leads
     */
    function initLeadManagement() {
        initBulkSelection();
        initBulkActions();
        initModals();
        initRowActions();
    }
    
    /**
     * Initialise la sélection multiple
     */
    function initBulkSelection() {
        // Sélectionner tout
        $('#select-all, #select-all-table').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.lead-checkbox').prop('checked', isChecked);
            updateSelectedCount();
        });
        
        // Sélection individuelle
        $(document).on('change', '.lead-checkbox', function() {
            updateSelectedCount();
            updateSelectAllState();
        });
    }
    
    /**
     * Met à jour le compteur de sélection
     */
    function updateSelectedCount() {
        const count = $('.lead-checkbox:checked').length;
        $('.selected-count').text(count + ' lead' + (count > 1 ? 's' : '') + ' sélectionné' + (count > 1 ? 's' : ''));
        
        // Activer/désactiver le bouton d'action en lot
        $('#apply-bulk-action').prop('disabled', count === 0);
    }
    
    /**
     * Met à jour l'état de "Sélectionner tout"
     */
    function updateSelectAllState() {
        const totalCheckboxes = $('.lead-checkbox').length;
        const checkedCheckboxes = $('.lead-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#select-all, #select-all-table').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all, #select-all-table').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#select-all, #select-all-table').prop('indeterminate', true);
        }
    }
    
    /**
     * Initialise les actions en lot
     */
    function initBulkActions() {
        $('#bulk-action').on('change', function() {
            const action = $(this).val();
            
            if (action === 'update_status') {
                showModal('#bulk-status-modal');
            } else if (action === 'update_priority') {
                showModal('#bulk-priority-modal');
            } else if (action === 'add_note') {
                showModal('#bulk-note-modal');
            } else if (action === 'delete') {
                if (confirm('Êtes-vous sûr de vouloir supprimer les leads sélectionnés ?')) {
                    submitBulkAction();
                } else {
                    $(this).val('');
                }
            } else {
                hideAllModals();
            }
        });
        
        // Soumission du formulaire d'action en lot
        $('#bulk-actions-form').on('submit', function(e) {
            const action = $('#bulk-action').val();
            
            if (!action) {
                e.preventDefault();
                alert('Veuillez sélectionner une action.');
                return false;
            }
            
            const selectedCount = $('.lead-checkbox:checked').length;
            if (selectedCount === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins un lead.');
                return false;
            }
            
            // Pour les actions avec modal, ne pas soumettre automatiquement
            if (['update_status', 'update_priority', 'add_note'].includes(action)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Initialise les modals
     */
    function initModals() {
        // Fermer les modals
        $('.modal-close').on('click', function() {
            hideAllModals();
            resetBulkAction();
        });
        
        // Fermer en cliquant à l'extérieur
        $('.modal').on('click', function(e) {
            if (e.target === this) {
                hideAllModals();
                resetBulkAction();
            }
        });
        
        // Soumission des modals
        $('#bulk-status-modal form, #bulk-priority-modal form, #bulk-note-modal form').on('submit', function(e) {
            e.preventDefault();
            submitBulkAction();
        });
    }
    
    /**
     * Affiche un modal
     */
    function showModal(modalId) {
        hideAllModals();
        $(modalId).show();
    }
    
    /**
     * Cache tous les modals
     */
    function hideAllModals() {
        $('.modal').hide();
    }
    
    /**
     * Réinitialise l'action en lot
     */
    function resetBulkAction() {
        $('#bulk-action').val('');
    }
    
    /**
     * Soumet l'action en lot
     */
    function submitBulkAction() {
        const form = $('#bulk-actions-form')[0];
        const formData = new FormData(form);
        
        // Ajouter les leads sélectionnés
        $('.lead-checkbox:checked').each(function() {
            formData.append('selected_leads[]', $(this).val());
        });
        
        // Soumettre le formulaire
        form.submit();
    }
    
    /**
     * Initialise les actions sur les lignes
     */
    function initRowActions() {
        // Modifier un lead
        $(document).on('click', '.edit-lead', function(e) {
            e.preventDefault();
            const leadId = $(this).data('lead-id');
            editLead(leadId);
        });
        
        // Voir un lead - utiliser directement openLeadDetailModal
        $(document).on('click', '.view-lead', function(e) {
            e.preventDefault();
            const leadId = $(this).data('lead-id');
            openLeadDetailModal(leadId);
        });
        
        // Supprimer un lead
        $(document).on('click', '.delete-lead', function(e) {
            e.preventDefault();
            const leadId = $(this).data('lead-id');
            deleteLead(leadId);
        });
        
        // PHASE 3 : Ajouter une action - Utiliser le système de lead-actions.js
        $(document).on('click', '.my-istymo-add-action', function(e) {
            e.preventDefault();
            const leadId = $(this).data('lead-id');
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            } else {
                addAction(leadId); // Fallback
            }
        });
        
        // PHASE 3 : Changer le statut - Utiliser le système de lead-actions.js
        $(document).on('click', '.my-istymo-change-status', function(e) {
            e.preventDefault();
            const leadId = $(this).data('lead-id');
            const currentStatus = $(this).data('current-status');
            if (window.leadActionsManager) {
                window.leadActionsManager.showStatusChangeModal(leadId);
            } else {
                changeStatus(leadId, currentStatus); // Fallback
            }
        });
    }
    
    /**
     * Modifie un lead
     */
    function editLead(leadId) {
        console.log('Édition du lead:', leadId);
        
        // Charger les détails du lead
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'my_istymo_get_lead_details',
                lead_id: leadId,
                nonce: unifiedLeadsAjax.nonce
            },
            success: function(response) {
                console.log('Réponse des détails:', response);
                
                if (response.success) {
                    const lead = response.data;
                    
                    // Remplir le formulaire d'édition
                    $('#edit-lead-id').val(lead.id);
                    $('#edit-lead-type').val(lead.lead_type);
                    $('#edit-lead-status').val(lead.status);
                    $('#edit-lead-priority').val(lead.priorite);
                    $('#edit-lead-notes').val(lead.notes);
                    
                    // Afficher le modal
                    $('#edit-lead-modal').removeClass('my-istymo-hidden').addClass('my-istymo-show');
                    $('#edit-lead-modal').show();
                } else {
                    console.error(' Erreur lors du chargement des détails:', response.data);
                    alert('Erreur lors du chargement des détails : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error(' Erreur AJAX:', {xhr: xhr, status: status, error: error});
                alert('Erreur lors de la communication avec le serveur');
            }
        });
    }
    
    // Ancienne fonction viewLead supprimée - remplacée par openLeadDetailModal
    
    /**
     *  PHASE 3 : Ajoute une action à un lead
     */
    function addAction(leadId) {
        console.log('📝 Ajout d\'action pour le lead:', leadId);
        
        // Remplir l'ID du lead dans le formulaire
        $('#action-lead-id').val(leadId);
        
        // Afficher le modal d'ajout d'action
        $('#add-action-modal').show();
    }
    
    /**
     *  PHASE 3 : Change le statut d'un lead
     */
    function changeStatus(leadId, currentStatus) {
        console.log(' Changement de statut pour le lead:', leadId, 'Statut actuel:', currentStatus);
        
        // Remplir les champs du formulaire
        $('#status-lead-id').val(leadId);
        $('#current-status').val(currentStatus);
        
        // Afficher le modal de changement de statut
        $('#change-status-modal').show();
    }
    
    /**
     * Supprime un lead
     */
    function deleteLead(leadId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce lead ?')) {
            console.log(' Suppression du lead:', leadId);
            
            $.ajax({
                url: unifiedLeadsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_unified_lead',
                    lead_id: leadId,
                    nonce: unifiedLeadsAjax.nonce
                },
                success: function(response) {
                    console.log(' Réponse de suppression:', response);
                    
                    if (response.success) {
                        console.log(' Lead supprimé avec succès');
                        // Recharger la page pour mettre à jour la liste
                        location.reload();
                    } else {
                        console.error(' Erreur lors de la suppression:', response.data);
                        alert('Erreur lors de la suppression : ' + (response.data || 'Erreur inconnue'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error(' Erreur AJAX:', {xhr: xhr, status: status, error: error});
                    console.error(' Réponse du serveur:', xhr.responseText);
                    
                    // Essayer de parser la réponse pour voir s'il y a des détails
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data) {
                            alert('Erreur lors de la suppression : ' + response.data);
                        } else {
                            alert('Erreur lors de la communication avec le serveur. Vérifiez la console pour plus de détails.');
                        }
                    } catch (e) {
                        alert('Erreur lors de la communication avec le serveur. Vérifiez la console pour plus de détails.');
                    }
                }
            });
        }
    }
    
    /**
     * Gestion des filtres
     */
    $('.leads-filters select, .leads-filters input').on('change', function() {
        // Auto-submit du formulaire de filtres
        $('.leads-filters').submit();
    });
    
    /**
     * Gestion du modal d'édition
     */
    $(document).on('click', '.my-istymo-modal-close', function() {
        $(this).closest('.my-istymo-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
    });
    
    /**
     * Fermer le modal d'édition (fonction globale)
     */
    window.closeEditLeadModal = function() {
        $('#edit-lead-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
    };
    
    /**
     * Gestion du formulaire d'édition
     */
    $('#edit-lead-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'my_istymo_update_lead',
            lead_id: $('#edit-lead-id').val(),
            lead_type: $('#edit-lead-type').val(),
            status: $('#edit-lead-status').val(),
            priorite: $('#edit-lead-priority').val(),
            notes: $('#edit-lead-notes').val(),
            nonce: unifiedLeadsAjax.nonce
        };
        
        console.log(' Envoi des données d\'édition:', formData);
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log(' Réponse de mise à jour:', response);
                
                if (response.success) {
                    console.log(' Lead mis à jour avec succès');
                    alert('Lead mis à jour avec succès');
                    
                    // Fermer le modal
                    $('#edit-lead-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
                    
                    // Recharger la page pour mettre à jour la liste
                    location.reload();
                } else {
                    console.error(' Erreur lors de la mise à jour:', response.data);
                    alert('Erreur lors de la mise à jour : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error(' Erreur AJAX:', {xhr: xhr, status: status, error: error});
                alert('Erreur lors de la communication avec le serveur');
            }
        });
    });
    
    /**
     * Amélioration de l'UX pour les filtres de date
     */
    $('#date_from, #date_to').on('change', function() {
        const dateFrom = $('#date_from').val();
        const dateTo = $('#date_to').val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            alert('La date de début ne peut pas être postérieure à la date de fin.');
            $(this).val('');
            return;
        }
    });
    
    /**
     * Gestion responsive
     */
    function handleResponsive() {
        const windowWidth = $(window).width();
        
        if (windowWidth < 768) {
            // Mode mobile
            $('.leads-table').addClass('mobile-table');
        } else {
            // Mode desktop
            $('.leads-table').removeClass('mobile-table');
        }
    }
    
    // Gestion du redimensionnement
    $(window).on('resize', handleResponsive);
    handleResponsive();
    
    /**
     * Amélioration de l'accessibilité
     */
    // Navigation au clavier dans le tableau
    $('.leads-table tbody tr').on('keydown', function(e) {
        const currentRow = $(this);
        
        switch(e.keyCode) {
            case 38: // Flèche haut
                e.preventDefault();
                currentRow.prev().focus();
                break;
            case 40: // Flèche bas
                e.preventDefault();
                currentRow.next().focus();
                break;
            case 32: // Espace
                e.preventDefault();
                currentRow.find('.lead-checkbox').click();
                break;
        }
    });
    
    // Rendre les lignes focusables
    $('.leads-table tbody tr').attr('tabindex', '0');
    
    /**
     * Notifications toast (optionnel)
     */
    function showNotification(message, type = 'success') {
        const notification = $('<div class="notification notification-' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        // Styles pour les notifications
        notification.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '12px 20px',
            borderRadius: '6px',
            color: 'white',
            fontWeight: '500',
            zIndex: '9999',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease'
        });
        
        if (type === 'success') {
            notification.css('background', '#28a745');
        } else if (type === 'error') {
            notification.css('background', '#dc3545');
        } else {
            notification.css('background', '#0073aa');
        }
        
        // Animation d'entrée
        setTimeout(() => {
            notification.css('transform', 'translateX(0)');
        }, 100);
        
        setTimeout(function() {
            notification.css('transform', 'translateX(100%)');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    /**
     * Export des données (optionnel)
     */
    function exportLeads(format = 'csv') {
        const filters = $('.leads-filters').serialize();
        const url = '?page=unified-leads&action=export&format=' + format + '&' + filters;
        window.open(url, '_blank');
    }
    
    // Ajouter des raccourcis clavier
    $(document).on('keydown', function(e) {
        // Ctrl+A pour sélectionner tout
        if (e.ctrlKey && e.keyCode === 65) {
            e.preventDefault();
            $('#select-all').click();
        }
        
        // Échap pour fermer les modals
        if (e.keyCode === 27) {
            hideAllModals();
            resetBulkAction();
            closeLeadDetailModal();
        }
    });
    
    //  PHASE 3 : Gestionnaires pour les modals d'actions et workflow
    
    /**
     * Gestionnaire pour le formulaire d'ajout d'action
     */
    $(document).on('submit', '#add-action-form', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'my_istymo_add_lead_action');
        formData.append('nonce', unifiedLeadsAjax.nonce);
        
        console.log('📝 Soumission d\'action:', Object.fromEntries(formData));
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log(' Réponse ajout action:', response);
                
                if (response.success) {
                    console.log(' Action ajoutée avec succès');
                    $('#add-action-modal').hide();
                    $('#add-action-form')[0].reset();
                    showNotification('Action ajoutée avec succès', 'success');
                    // Recharger la page pour mettre à jour l'affichage
                    setTimeout(() => location.reload(), 1000);
                } else {
                    console.error(' Erreur lors de l\'ajout de l\'action:', response.data);
                    alert('Erreur lors de l\'ajout de l\'action : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error(' Erreur AJAX ajout action:', {xhr: xhr, status: status, error: error});
                alert('Erreur lors de la communication avec le serveur');
            }
        });
    });
    
    /**
     * Gestionnaire pour le formulaire de changement de statut
     */
    $(document).on('submit', '#change-status-form', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'my_istymo_change_lead_status');
        formData.append('nonce', unifiedLeadsAjax.nonce);
        
        console.log(' Soumission changement statut:', Object.fromEntries(formData));
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log(' Réponse changement statut:', response);
                
                if (response.success) {
                    console.log(' Statut changé avec succès');
                    if (window.leadActionsManager) {
                        window.leadActionsManager.closeAllModals();
                    } else {
                        $('#change-status-modal').hide();
                    }
                    $('#change-status-form')[0].reset();
                    showNotification('Statut changé avec succès', 'success');
                    // Recharger la page pour mettre à jour l'affichage
                    setTimeout(() => location.reload(), 1000);
                } else {
                    console.error(' Erreur lors du changement de statut:', response.data);
                    alert('Erreur lors du changement de statut : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error(' Erreur AJAX changement statut:', {xhr: xhr, status: status, error: error});
                alert('Erreur lors de la communication avec le serveur');
            }
        });
    });
    
    /**
     * Fermer les modals d'actions et workflow
     */
    $(document).on('click', '.modal-close', function() {
        if (window.leadActionsManager) {
            window.leadActionsManager.closeAllModals();
        } else {
            $('#add-action-modal').hide();
            $('#change-status-modal').hide();
        }
        $('#add-action-form')[0].reset();
        $('#change-status-form')[0].reset();
    });
    
    /**
     * Fermer le modal de détails du lead
     */
    window.closeLeadDetailModal = function() {
        console.log('=== FERMETURE MODAL LEAD ===');
        
        // Protection contre les fermetures multiples
        if (modalClosing) {
            console.log('Modal déjà en cours de fermeture, ignoré');
            return;
        }
        
        modalClosing = true;
        
        // Utiliser le nouveau système si disponible
        if (window.leadActionsManager && typeof window.leadActionsManager.closeLeadDetailModal === 'function') {
            window.leadActionsManager.closeLeadDetailModal();
        } else {
            // Fallback vers l'ancien système
            const modal = $('#lead-detail-modal');
            if (modal.length > 0) {
                modal.removeClass('my-istymo-show').addClass('my-istymo-hidden');
                modal.hide();
                console.log('Modal fermé avec fallback');
            }
        }
        
        // Nettoyer les événements
        $(document).off('keydown.lead-detail');
        const modal = $('#lead-detail-modal');
        modal.off('click.lead-detail click.lead-content');
        
        // Réinitialiser le flag après un délai
        setTimeout(() => {
            modalClosing = false;
        }, 500);
    };
    
    /**
     * Gestionnaire pour le formulaire d'édition
     */
    $(document).on('submit', '#edit-lead-form', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'my_istymo_update_lead');
        formData.append('nonce', unifiedLeadsAjax.nonce);
        
        console.log(' Soumission édition lead:', Object.fromEntries(formData));
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log(' Réponse édition lead:', response);
                
                if (response.success) {
                    console.log(' Lead modifié avec succès');
                    closeEditLeadModal();
                    showNotification('Lead modifié avec succès', 'success');
                    // Recharger la page pour mettre à jour l'affichage
                    setTimeout(() => location.reload(), 1000);
                } else {
                    console.error(' Erreur lors de la modification:', response.data);
                    alert('Erreur lors de la modification : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error(' Erreur AJAX édition lead:', {xhr: xhr, status: status, error: error});
                alert('Erreur lors de la communication avec le serveur');
            }
        });
    });
    
    /**
     * Fermer le modal d'édition
     */
    window.closeEditLeadModal = function() {
        $('#edit-lead-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
        $('#edit-lead-form')[0].reset();
    };
    
    /**
     * Amélioration des performances
     */
    // Debounce pour les filtres
    let filterTimeout;
    $('.leads-filters input[type="text"]').on('input', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            $('.leads-filters').submit();
        }, 500);
    });
    
    // Lazy loading pour les grandes listes (optionnel)
    function initLazyLoading() {
        if ($('.leads-table tbody tr').length > 100) {
            // Implémenter le lazy loading si nécessaire
            console.log('Beaucoup de leads détectés, lazy loading recommandé');
        }
    }
    
    initLazyLoading();
    
    /**
     * Fonction de test de connexion AJAX
     */
    function testAjaxConnection() {
        console.log('=== TEST CONNEXION AJAX ===');
        
        if (typeof unifiedLeadsAjax === 'undefined') {
            console.error('unifiedLeadsAjax non disponible');
            alert('Variables AJAX non disponibles');
            return;
        }
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'my_istymo_test_ajax',
                nonce: unifiedLeadsAjax.nonce
            },
            success: function(response) {
                console.log('✅ Test AJAX réussi:', response);
                alert('Test AJAX réussi! Vérifiez la console pour les détails.');
            },
            error: function(xhr, status, error) {
                console.error('❌ Test AJAX échoué:', status, error);
                console.error('Response:', xhr.responseText);
                alert('Test AJAX échoué. Vérifiez la console pour les détails.');
            }
        });
    }
    
    // Fonctions supprimées - déjà définies plus haut
    
});
