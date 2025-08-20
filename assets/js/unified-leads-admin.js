/**
 * JavaScript pour l'interface d'administration des leads unifiés
 * Gère les interactions, sélections multiples et modals
 */
jQuery(document).ready(function($) {
    
    // Variables globales
    let selectedLeads = [];
    
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
        
        // Voir un lead
        $(document).on('click', '.view-lead', function(e) {
            e.preventDefault();
            const leadId = $(this).data('lead-id');
            viewLead(leadId);
        });
        
        // Supprimer un lead
        $(document).on('click', '.delete-lead', function(e) {
            e.preventDefault();
            const leadId = $(this).data('lead-id');
            deleteLead(leadId);
        });
        
        // ✅ PHASE 3 : Ajouter une action - Utiliser le système de lead-actions.js
        $(document).on('click', '.my-istymo-add-action', function(e) {
            e.preventDefault();
            const leadId = $(this).data('lead-id');
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            } else {
                addAction(leadId); // Fallback
            }
        });
        
        // ✅ PHASE 3 : Changer le statut - Utiliser le système de lead-actions.js
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
        console.log('✏️ Édition du lead:', leadId);
        
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
                console.log('📡 Réponse des détails:', response);
                
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
                    console.error('❌ Erreur lors du chargement des détails:', response.data);
                    alert('Erreur lors du chargement des détails : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Erreur AJAX:', {xhr: xhr, status: status, error: error});
                alert('Erreur lors de la communication avec le serveur');
            }
        });
    }
    
    /**
     * Affiche les détails d'un lead
     */
    function viewLead(leadId) {
        console.log('👁️ Affichage des détails du lead:', leadId);
        
        // Utiliser le nouveau système de modal si disponible
        if (window.leadActionsManager && typeof window.leadActionsManager.getLeadDetails === 'function') {
            window.leadActionsManager.getLeadDetails(leadId);
            return;
        }
        
        // Fallback vers l'ancien système si le nouveau n'est pas disponible
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'my_istymo_get_lead_detail_content',
                lead_id: leadId,
                nonce: unifiedLeadsAjax.nonce
            },
            success: function(response) {
                console.log('📡 Réponse des détails:', response);
                
                if (response.success) {
                    // Créer un modal temporaire pour afficher les détails
                    const modalHtml = `
                        <div id="lead-detail-modal" class="my-istymo-modal my-istymo-show">
                            <div class="my-istymo-modal-content" style="max-width: 800px; max-height: 80vh; overflow-y: auto;">
                                <div class="my-istymo-modal-header">
                                    <h3>Détails du Lead #${leadId}</h3>
                                    <button type="button" class="my-istymo-modal-close" onclick="closeLeadDetailModal()">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </div>
                                <div class="my-istymo-modal-body">
                                    ${response.data}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Ajouter le modal au body
                    $('body').append(modalHtml);
                } else {
                    console.error('❌ Erreur lors du chargement des détails:', response.data);
                    alert('Erreur lors du chargement des détails : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Erreur AJAX:', {xhr: xhr, status: status, error: error});
                alert('Erreur lors de la communication avec le serveur');
            }
        });
    }
    
    /**
     * ✅ PHASE 3 : Ajoute une action à un lead
     */
    function addAction(leadId) {
        console.log('📝 Ajout d\'action pour le lead:', leadId);
        
        // Remplir l'ID du lead dans le formulaire
        $('#action-lead-id').val(leadId);
        
        // Afficher le modal d'ajout d'action
        $('#add-action-modal').show();
    }
    
    /**
     * ✅ PHASE 3 : Change le statut d'un lead
     */
    function changeStatus(leadId, currentStatus) {
        console.log('🔄 Changement de statut pour le lead:', leadId, 'Statut actuel:', currentStatus);
        
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
            console.log('🗑️ Suppression du lead:', leadId);
            
            $.ajax({
                url: unifiedLeadsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_unified_lead',
                    lead_id: leadId,
                    nonce: unifiedLeadsAjax.nonce
                },
                success: function(response) {
                    console.log('📡 Réponse de suppression:', response);
                    
                    if (response.success) {
                        console.log('✅ Lead supprimé avec succès');
                        // Recharger la page pour mettre à jour la liste
                        location.reload();
                    } else {
                        console.error('❌ Erreur lors de la suppression:', response.data);
                        alert('Erreur lors de la suppression : ' + (response.data || 'Erreur inconnue'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Erreur AJAX:', {xhr: xhr, status: status, error: error});
                    console.error('❌ Réponse du serveur:', xhr.responseText);
                    
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
        
        console.log('📤 Envoi des données d\'édition:', formData);
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('📡 Réponse de mise à jour:', response);
                
                if (response.success) {
                    console.log('✅ Lead mis à jour avec succès');
                    alert('Lead mis à jour avec succès');
                    
                    // Fermer le modal
                    $('#edit-lead-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
                    
                    // Recharger la page pour mettre à jour la liste
                    location.reload();
                } else {
                    console.error('❌ Erreur lors de la mise à jour:', response.data);
                    alert('Erreur lors de la mise à jour : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Erreur AJAX:', {xhr: xhr, status: status, error: error});
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
    
    // ✅ PHASE 3 : Gestionnaires pour les modals d'actions et workflow
    
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
                console.log('📡 Réponse ajout action:', response);
                
                if (response.success) {
                    console.log('✅ Action ajoutée avec succès');
                    $('#add-action-modal').hide();
                    $('#add-action-form')[0].reset();
                    showNotification('Action ajoutée avec succès', 'success');
                    // Recharger la page pour mettre à jour l'affichage
                    setTimeout(() => location.reload(), 1000);
                } else {
                    console.error('❌ Erreur lors de l\'ajout de l\'action:', response.data);
                    alert('Erreur lors de l\'ajout de l\'action : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Erreur AJAX ajout action:', {xhr: xhr, status: status, error: error});
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
        
        console.log('🔄 Soumission changement statut:', Object.fromEntries(formData));
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('📡 Réponse changement statut:', response);
                
                if (response.success) {
                    console.log('✅ Statut changé avec succès');
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
                    console.error('❌ Erreur lors du changement de statut:', response.data);
                    alert('Erreur lors du changement de statut : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Erreur AJAX changement statut:', {xhr: xhr, status: status, error: error});
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
        // Utiliser le nouveau système si disponible
        if (window.leadActionsManager && typeof window.leadActionsManager.closeLeadDetailModal === 'function') {
            window.leadActionsManager.closeLeadDetailModal();
        } else {
            // Fallback vers l'ancien système
            $('#lead-detail-modal').remove();
        }
    };
    
    /**
     * Gestionnaire pour le formulaire d'édition
     */
    $(document).on('submit', '#edit-lead-form', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'my_istymo_update_lead');
        formData.append('nonce', unifiedLeadsAjax.nonce);
        
        console.log('✏️ Soumission édition lead:', Object.fromEntries(formData));
        
        $.ajax({
            url: unifiedLeadsAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('📡 Réponse édition lead:', response);
                
                if (response.success) {
                    console.log('✅ Lead modifié avec succès');
                    closeEditLeadModal();
                    showNotification('Lead modifié avec succès', 'success');
                    // Recharger la page pour mettre à jour l'affichage
                    setTimeout(() => location.reload(), 1000);
                } else {
                    console.error('❌ Erreur lors de la modification:', response.data);
                    alert('Erreur lors de la modification : ' + (response.data || 'Erreur inconnue'));
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Erreur AJAX édition lead:', {xhr: xhr, status: status, error: error});
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
    
});
