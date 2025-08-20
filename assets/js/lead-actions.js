/**
 * JavaScript pour la Gestion des Actions et Workflow des Leads
 * 
 * Ce fichier gère toutes les interactions liées aux actions sur les leads,
 * incluant l'ajout, modification, suppression d'actions et les transitions de workflow.
 * 
 * @package My_Istymo
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    // Variables globales
    let currentLeadId = null;
    let currentActionId = null;
    
    /**
     * Classe principale pour la gestion des actions
     */
    class LeadActionsManager {
        
        constructor() {
            this.init();
        }
        
        /**
         * Initialisation
         */
        init() {
            this.bindEvents();
            this.initModals();
            this.initTooltips();
        }
        
        /**
         * Liaison des événements
         */
        bindEvents() {
            // Actions rapides
            $(document).on('click', '.my-istymo-quick-action', this.handleQuickAction.bind(this));
            
            // Modals
            $(document).on('click', '.my-istymo-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.my-istymo-modal-overlay', this.closeModal.bind(this));
            
            // Formulaires
            $(document).on('submit', '#add-action-form', this.handleAddAction.bind(this));
            $(document).on('submit', '#edit-action-form', this.handleEditAction.bind(this));
            $(document).on('submit', '#change-status-form', this.handleStatusChangeForm.bind(this));
            
            // Actions sur les leads
            $(document).on('click', '.my-istymo-change-status', this.handleStatusChange.bind(this));
            $(document).on('click', '.my-istymo-add-action', this.showAddActionModal.bind(this));
            $(document).on('click', '.my-istymo-edit-action', this.showEditActionModal.bind(this));
            $(document).on('click', '.my-istymo-delete-action', this.handleDeleteAction.bind(this));
            
            // Workflow
            $(document).on('click', '.my-istymo-workflow-transition', this.handleWorkflowTransition.bind(this));
            $(document).on('click', '.my-istymo-suggested-action', this.handleSuggestedAction.bind(this));
            
            // Raccourcis clavier
            $(document).on('keydown', this.handleKeyboardShortcuts.bind(this));
            
            // Gestionnaires pour les actions du modal de détail des leads
            $(document).on('click', '[data-action="close-lead-detail"]', this.closeLeadDetailModal.bind(this));
            $(document).on('click', '[data-action="show-add-action"]', this.showAddActionModal.bind(this));
            $(document).on('click', '[data-action="close-add-action"]', this.closeAddActionModal.bind(this));
            $(document).on('click', '[data-action="close-edit-action"]', this.closeEditActionModal.bind(this));
            $(document).on('click', '[data-action="change-status"]', this.handleStatusChangeFromModal.bind(this));
            $(document).on('click', '[data-action="edit-action"]', this.handleEditAction.bind(this));
            $(document).on('click', '[data-action="delete-action"]', this.handleDeleteAction.bind(this));
            $(document).on('click', '[data-action="quick-add-action"]', this.handleQuickAddAction.bind(this));
            
                         // Gestionnaires pour les formulaires d'actions
             $(document).on('submit', '#add-action-form', this.handleAddActionForm.bind(this));
             $(document).on('submit', '#edit-action-form', this.handleEditActionForm.bind(this));
             
             // Gestionnaire pour l'édition du lead
             $(document).on('submit', '#lead-edit-form', this.handleLeadEditForm.bind(this));
             $(document).on('click', '#reset-lead-form', this.resetLeadForm.bind(this));
             
             // Gestionnaire pour le formulaire d'ajout d'action intégré
             $(document).on('click', '#toggle-add-action-form', this.toggleAddActionForm.bind(this));
             $(document).on('click', '#cancel-add-action', this.hideAddActionForm.bind(this));
             $(document).on('submit', '#add-action-form', this.handleAddActionForm.bind(this));
             
             // Gestionnaire spécifique pour le formulaire d'ajout d'action dans le modal de détail
             $(document).on('submit', '.my-istymo-lead-detail-modal #add-action-form', this.handleAddActionForm.bind(this));
        }
        
        /**
         * Initialisation des modals
         */
        initModals() {
            // Modal d'ajout d'action
            this.addActionModal = new Modal('#add-action-modal', {
                onOpen: () => this.resetAddActionForm(),
                onClose: () => this.resetAddActionForm()
            });
            
            // Modal d'édition d'action
            this.editActionModal = new Modal('#edit-action-modal', {
                onOpen: () => this.loadActionData(),
                onClose: () => this.resetEditActionForm()
            });
            
            // Modal de vue détaillée
            this.leadDetailModal = new Modal('.my-istymo-lead-detail-modal', {
                onOpen: () => this.loadLeadDetails(),
                onClose: () => this.refreshLeadsList()
            });
        }
        
        /**
         * Initialisation des tooltips
         */
        initTooltips() {
            // Vérifier si jQuery UI Tooltip est disponible
            if (typeof $.fn.tooltip === 'function') {
                $('[data-tooltip]').tooltip({
                    position: 'top',
                    delay: 200
                });
            } else {
                // Fallback : utiliser les attributs title natifs
                $('[data-tooltip]').each(function() {
                    const $element = $(this);
                    const tooltipText = $element.data('tooltip');
                    if (tooltipText && !$element.attr('title')) {
                        $element.attr('title', tooltipText);
                    }
                });
            }
        }
        
        /**
         * Gestion des actions rapides
         */
        handleQuickAction(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const actionType = $button.data('action-type');
            const leadId = $button.data('lead-id');
            
            if (actionType === 'add-action') {
                this.showAddActionModal(leadId);
            } else if (actionType === 'change-status') {
                this.showStatusChangeModal(leadId);
            } else if (actionType === 'view-details') {
                this.showLeadDetailModal(leadId);
            }
        }
        
        /**
         * Afficher le modal d'ajout d'action
         */
        showAddActionModal(leadId = null) {
            currentLeadId = leadId || currentLeadId;
            
            if (!currentLeadId) {
                this.showError('Aucun lead sélectionné');
                return;
            }
            
            $('#action-lead-id').val(currentLeadId);
            this.addActionModal.open();
        }
        
        /**
         * Afficher le modal d'édition d'action
         */
        showEditActionModal(actionId) {
            currentActionId = actionId;
            this.editActionModal.open();
        }
        
        /**
         * Afficher le modal de changement de statut
         */
        showStatusChangeModal(leadId) {
            currentLeadId = leadId;
            
            // Charger les transitions autorisées
            this.loadAllowedTransitions(leadId, (transitions) => {
                this.populateStatusSelect(transitions);
                $('#status-change-modal').modal('show');
            });
        }
        
        /**
         * Afficher le modal de vue détaillée
         */
        showLeadDetailModal(leadId) {
            currentLeadId = leadId;
            
            // Charger les détails du lead via AJAX
            this.loadLeadDetailContent(leadId, (content) => {
                $('#lead-detail-container').html(content);
                this.leadDetailModal.open();
            });
        }
        
        /**
         * Gestion de l'ajout d'action
         */
        handleAddAction(e) {
            e.preventDefault();
            
            const formData = this.serializeForm('#add-action-form');
            
            if (!this.validateActionForm(formData)) {
                return;
            }
            
            this.addAction(formData, (response) => {
                if (response.success) {
                    this.showSuccess('Action ajoutée avec succès');
                    this.addActionModal.close();
                    this.refreshLeadDetails();
                } else {
                    this.showError(response.data || 'Erreur lors de l\'ajout de l\'action');
                }
            });
        }
        
        /**
         * Gestion de l'édition d'action
         */
        handleEditAction(e) {
            e.preventDefault();
            
            const formData = this.serializeForm('#edit-action-form');
            
            if (!this.validateActionForm(formData)) {
                return;
            }
            
            this.updateAction(formData, (response) => {
                if (response.success) {
                    this.showSuccess('Action modifiée avec succès');
                    this.editActionModal.close();
                    this.refreshLeadDetails();
                } else {
                    this.showError(response.data || 'Erreur lors de la modification de l\'action');
                }
            });
        }
        
        /**
         * Gestion de la suppression d'action
         */
        handleDeleteAction(e) {
            e.preventDefault();
            
            const actionId = $(e.currentTarget).data('action-id');
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette action ?')) {
                return;
            }
            
            this.deleteAction(actionId, (response) => {
                if (response.success) {
                    this.showSuccess('Action supprimée avec succès');
                    this.refreshLeadDetails();
                } else {
                    this.showError(response.data || 'Erreur lors de la suppression de l\'action');
                }
            });
        }
        
        /**
         * Gestion du changement de statut
         */
        handleStatusChange(e) {
            e.preventDefault();
            
            const newStatus = $(e.currentTarget).data('status');
            const leadId = $(e.currentTarget).data('lead-id');
            
            this.changeLeadStatus(leadId, newStatus, (response) => {
                if (response.success) {
                    this.showSuccess('Statut modifié avec succès');
                    this.refreshLeadDetails();
                    this.refreshLeadsList();
                } else {
                    this.showError(response.data || 'Erreur lors du changement de statut');
                }
            });
        }
        
        /**
         * Gestion du formulaire de changement de statut
         */
        handleStatusChangeForm(e) {
            e.preventDefault();
            
            const formData = this.serializeForm('#change-status-form');
            
            if (!formData.lead_id || !formData.new_status) {
                this.showError('Veuillez remplir tous les champs obligatoires');
                return;
            }
            
            this.changeLeadStatus(formData.lead_id, formData.new_status, (response) => {
                if (response.success) {
                    this.showSuccess('Statut modifié avec succès');
                    this.closeAllModals();
                    this.refreshLeadsList();
                } else {
                    this.showError(response.data || 'Erreur lors du changement de statut');
                }
            });
        }
        
        /**
         * Gestion des transitions de workflow
         */
        handleWorkflowTransition(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const fromStatus = $button.data('from-status');
            const toStatus = $button.data('to-status');
            const leadId = $button.data('lead-id');
            
            // Valider la transition
            this.validateWorkflowTransition(leadId, fromStatus, toStatus, (validation) => {
                if (validation.valid) {
                    this.changeLeadStatus(leadId, toStatus, (response) => {
                        if (response.success) {
                            this.showSuccess('Transition effectuée avec succès');
                            this.refreshLeadDetails();
                            this.refreshLeadsList();
                        } else {
                            this.showError(response.data || 'Erreur lors de la transition');
                        }
                    });
                } else {
                    this.showError(validation.message);
                }
            });
        }
        
        /**
         * Gestion des actions suggérées
         */
        handleSuggestedAction(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const actionType = $button.data('action-type');
            const leadId = $button.data('lead-id');
            
            // Pré-remplir le formulaire d'action
            $('#action-type').val(actionType);
            $('#action-lead-id').val(leadId);
            
            this.showAddActionModal(leadId);
        }
        
        /**
         * Gestion des raccourcis clavier
         */
        handleKeyboardShortcuts(e) {
            // Échap pour fermer les modals
            if (e.keyCode === 27) {
                this.closeAllModals();
            }
            
            // Ctrl+N pour nouvelle action
            if (e.ctrlKey && e.keyCode === 78) {
                e.preventDefault();
                this.showAddActionModal();
            }
            
            // Ctrl+S pour sauvegarder
            if (e.ctrlKey && e.keyCode === 83) {
                e.preventDefault();
                this.saveCurrentForm();
            }
        }
        
        /**
         * Fermer tous les modals
         */
        closeAllModals() {
            this.addActionModal.close();
            this.editActionModal.close();
            this.leadDetailModal.close();
            $('.modal').modal('hide');
        }
        
        /**
         * Fermer un modal
         */
        closeModal(e) {
            const $modal = $(e.currentTarget).closest('.modal, .my-istymo-modal');
            $modal.removeClass('show').addClass('hidden');
        }
        
        /**
         * Valider le formulaire d'action
         */
        validateActionForm(formData) {
            if (!formData.action_type) {
                this.showError('Veuillez sélectionner un type d\'action');
                return false;
            }
            
            return true;
        }
        
        /**
         * Sérialiser un formulaire
         */
        serializeForm(selector) {
            const form = $(selector);
            const formData = {};
            
            form.find('input, select, textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                const value = $field.val();
                
                if (name) {
                    formData[name] = value;
                }
            });
            
            return formData;
        }
        
        /**
         * Réinitialiser le formulaire d'ajout
         */
        resetAddActionForm() {
            $('#add-action-form')[0].reset();
            $('#action-lead-id').val(currentLeadId);
        }
        
        /**
         * Réinitialiser le formulaire d'édition
         */
        resetEditActionForm() {
            $('#edit-action-form')[0].reset();
            currentActionId = null;
        }
        
        /**
         * Charger les données d'une action
         */
        loadActionData() {
            if (!currentActionId) return;
            
            this.getAction(currentActionId, (response) => {
                if (response.success) {
                    const action = response.data;
                    $('#edit-action-id').val(action.id);
                    $('#edit-action-description').val(action.description);
                    $('#edit-action-result').val(action.result);
                }
            });
        }
        
        /**
         * Charger les détails d'un lead
         */
        loadLeadDetails() {
            if (!currentLeadId) return;
            
            this.getLeadDetails(currentLeadId, (response) => {
                if (response.success) {
                    // Les détails sont déjà chargés dans le modal
                    this.updateLeadDetailDisplay(response.data);
                }
            });
        }
        
        /**
         * Charger les transitions autorisées
         */
        loadAllowedTransitions(leadId, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_get_workflow_transitions',
                    lead_id: leadId,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        callback(response.data);
                    }
                }
            });
        }
        
        /**
         * Charger le contenu détaillé d'un lead
         */
        loadLeadDetailContent(leadId, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_get_lead_detail_content',
                    lead_id: leadId,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        callback(response.data);
                    }
                }
            });
        }
        
        /**
         * Valider une transition de workflow
         */
        validateWorkflowTransition(leadId, fromStatus, toStatus, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_validate_workflow_transition',
                    lead_id: leadId,
                    from_status: fromStatus,
                    to_status: toStatus,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        callback({ valid: true });
                    } else {
                        callback({ valid: false, message: response.data });
                    }
                }
            });
        }
        
        /**
         * Ajouter une action
         */
        addAction(formData, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_add_lead_action',
                    ...formData,
                    nonce: leadActionsAjax.nonce
                },
                success: callback
            });
        }
        
        /**
         * Mettre à jour une action
         */
        updateAction(formData, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_update_lead_action',
                    ...formData,
                    nonce: leadActionsAjax.nonce
                },
                success: callback
            });
        }
        
        /**
         * Supprimer une action
         */
        deleteAction(actionId, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_delete_lead_action',
                    action_id: actionId,
                    nonce: leadActionsAjax.nonce
                },
                success: callback
            });
        }
        
        /**
         * Obtenir une action
         */
        getAction(actionId, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_get_lead_action',
                    action_id: actionId,
                    nonce: leadActionsAjax.nonce
                },
                success: callback
            });
        }
        
        /**
         * Changer le statut d'un lead
         */
        changeLeadStatus(leadId, newStatus, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_change_lead_status',
                    lead_id: leadId,
                    new_status: newStatus,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    callback(response);
                },
                error: function(xhr, status, error) {
                    callback({
                        success: false,
                        data: 'Erreur lors de la communication avec le serveur'
                    });
                }
            });
        }
        
        /**
         * Obtenir les détails d'un lead
         */
        getLeadDetails(leadId, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_get_lead_detail_content',
                    lead_id: leadId,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Afficher le modal avec le contenu
                        this.showLeadDetailModal(response.data, leadId);
                    } else {
                        console.error('❌ Erreur lors du chargement des détails:', response.data);
                        this.showNotification('Erreur lors du chargement des détails : ' + (response.data || 'Erreur inconnue'), 'error');
                    }
                }.bind(this),
                error: function(xhr, status, error) {
                    console.error('❌ Erreur AJAX:', {xhr: xhr, status: status, error: error});
                    this.showNotification('Erreur lors de la communication avec le serveur', 'error');
                }.bind(this)
            });
        }
        
        /**
         * Rafraîchir les détails du lead
         */
        refreshLeadDetails() {
            if (currentLeadId) {
                this.loadLeadDetails();
            }
        }
        
        /**
         * Rafraîchir la liste des leads
         */
        refreshLeadsList() {
            if (typeof window.refreshLeadsList === 'function') {
                window.refreshLeadsList();
            }
        }
        
        /**
         * Mettre à jour l'affichage des détails du lead
         */
        updateLeadDetailDisplay(data) {
            // Mettre à jour les statistiques
            if (data.stats) {
                this.updateStatsDisplay(data.stats);
            }
            
            // Mettre à jour l'historique
            if (data.history) {
                this.updateHistoryDisplay(data.history);
            }
            
            // Mettre à jour les recommandations
            if (data.recommendations) {
                this.updateRecommendationsDisplay(data.recommendations);
            }
        }
        
        /**
         * Mettre à jour l'affichage des statistiques
         */
        updateStatsDisplay(stats) {
            const $statsContainer = $('.my-istymo-lead-stats-section');
            if ($statsContainer.length) {
                // Mettre à jour le contenu des statistiques
                $statsContainer.find('.my-istymo-stats-grid').html(this.renderStats(stats));
            }
        }
        
        /**
         * Mettre à jour l'affichage de l'historique
         */
        updateHistoryDisplay(history) {
            const $historyContainer = $('.my-istymo-lead-history-section');
            if ($historyContainer.length) {
                // Mettre à jour le contenu de l'historique
                $historyContainer.find('.my-istymo-actions-timeline').html(this.renderHistory(history));
            }
        }
        
        /**
         * Mettre à jour l'affichage des recommandations
         */
        updateRecommendationsDisplay(recommendations) {
            const $recommendationsContainer = $('.my-istymo-lead-recommendations-section');
            if ($recommendationsContainer.length) {
                // Mettre à jour le contenu des recommandations
                $recommendationsContainer.find('.my-istymo-recommendations-grid').html(this.renderRecommendations(recommendations));
            }
        }
        
        /**
         * Rendu des statistiques
         */
        renderStats(stats) {
            let html = '';
            
            Object.keys(stats).forEach(typeKey => {
                const typeStats = stats[typeKey];
                html += `
                    <div class="my-istymo-stat-card">
                        <div class="my-istymo-stat-header">
                            <span class="my-istymo-stat-icon">${typeStats.icon}</span>
                            <span class="my-istymo-stat-title">${typeStats.label}</span>
                        </div>
                        <div class="my-istymo-stat-content">
                            <div class="my-istymo-stat-total">Total : ${typeStats.total}</div>
                            ${this.renderStatResults(typeStats.results)}
                        </div>
                    </div>
                `;
            });
            
            return html;
        }
        
        /**
         * Rendu des résultats de statistiques
         */
        renderStatResults(results) {
            let html = '';
            
            Object.keys(results).forEach(resultKey => {
                const resultInfo = results[resultKey];
                html += `
                    <div class="my-istymo-stat-result">
                        <span class="my-istymo-result-badge my-istymo-result-${resultKey}">
                            ${resultInfo.label}
                        </span>
                        <span class="my-istymo-result-count">${resultInfo.count}</span>
                    </div>
                `;
            });
            
            return html;
        }
        
        /**
         * Rendu de l'historique
         */
        renderHistory(history) {
            let html = '';
            
            history.forEach(action => {
                html += `
                    <div class="my-istymo-action-item" data-action-id="${action.id}">
                        <div class="my-istymo-action-icon">
                            ${action.action_type_icon}
                        </div>
                        <div class="my-istymo-action-content">
                            <div class="my-istymo-action-header">
                                <span class="my-istymo-action-type">${action.action_type_label}</span>
                                <span class="my-istymo-action-result my-istymo-result-${action.result}">
                                    ${action.result_label}
                                </span>
                            </div>
                            <div class="my-istymo-action-details">
                                ${action.description ? `<div class="my-istymo-action-description">${action.description}</div>` : ''}
                                <div class="my-istymo-action-meta">
                                    <span class="my-istymo-action-date">${action.date_formatted}</span>
                                    ${action.user_name ? `<span class="my-istymo-action-user">par ${action.user_name}</span>` : ''}
                                </div>
                            </div>
                            <div class="my-istymo-action-actions">
                                <button type="button" class="my-istymo-btn my-istymo-btn-small" onclick="editAction(${action.id})">
                                    Modifier
                                </button>
                                <button type="button" class="my-istymo-btn my-istymo-btn-small my-istymo-btn-danger" onclick="deleteAction(${action.id})">
                                    Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            return html;
        }
        
        /**
         * Rendu des recommandations
         */
        renderRecommendations(recommendations) {
            let html = '';
            
            Object.keys(recommendations).forEach(actionKey => {
                const actionLabel = recommendations[actionKey];
                html += `
                    <div class="my-istymo-recommendation-item">
                        <span class="my-istymo-recommendation-icon">
                            ${this.getActionIcon(actionKey)}
                        </span>
                        <span class="my-istymo-recommendation-label">
                            ${actionLabel}
                        </span>
                        <button type="button" class="my-istymo-btn my-istymo-btn-small" onclick="quickAddAction('${actionKey}')">
                            Ajouter
                        </button>
                    </div>
                `;
            });
            
            return html;
        }
        
        /**
         * Obtenir l'icône d'une action
         */
        getActionIcon(actionType) {
            const icons = {
                'appel': '📞',
                'email': '📧',
                'sms': '💬',
                'rdv': '📅',
                'note': '📝'
            };
            
            return icons[actionType] || '📋';
        }
        
        /**
         * Afficher un message de succès
         */
        showSuccess(message) {
            this.showNotification(message, 'success');
        }
        
        /**
         * Afficher un message d'erreur
         */
        showError(message) {
            this.showNotification(message, 'error');
        }
        
        /**
         * Afficher une notification
         */
        showNotification(message, type = 'info') {
            const notification = $(`
                <div class="my-istymo-notification my-istymo-notification-${type}">
                    <span class="my-istymo-notification-message">${message}</span>
                    <button type="button" class="my-istymo-notification-close">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Animation d'apparition
            setTimeout(() => {
                notification.addClass('show');
            }, 100);
            
            // Auto-fermeture après 5 secondes
            setTimeout(() => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
            
            // Fermeture manuelle
            notification.find('.my-istymo-notification-close').on('click', () => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            });
        }
        
        /**
         * Sauvegarder le formulaire actuel
         */
        saveCurrentForm() {
            const $activeForm = $('.my-istymo-modal.show form');
            
            if ($activeForm.length) {
                $activeForm.submit();
            }
        }
        
        /**
         * Méthodes AJAX pour les actions
         */
        
        /**
         * Ajouter une action
         */
        addAction(formData, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_add_lead_action',
                    ...formData,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    callback(response);
                },
                error: function(xhr, status, error) {
                    callback({
                        success: false,
                        data: 'Erreur lors de la communication avec le serveur'
                    });
                }
            });
        }
        
        /**
         * Mettre à jour une action
         */
        updateAction(formData, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_update_lead_action',
                    ...formData,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    callback(response);
                },
                error: function(xhr, status, error) {
                    callback({
                        success: false,
                        data: 'Erreur lors de la communication avec le serveur'
                    });
                }
            });
        }
        
        /**
         * Supprimer une action
         */
        deleteAction(actionId, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_delete_lead_action',
                    action_id: actionId,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    callback(response);
                },
                error: function(xhr, status, error) {
                    callback({
                        success: false,
                        data: 'Erreur lors de la communication avec le serveur'
                    });
                }
            });
        }
        
        /**
         * Changer le statut d'un lead
         */
        changeLeadStatus(leadId, newStatus, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_change_lead_status',
                    lead_id: leadId,
                    new_status: newStatus,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    callback(response);
                },
                error: function(xhr, status, error) {
                    callback({
                        success: false,
                        data: 'Erreur lors de la communication avec le serveur'
                    });
                }
            });
        }
        
        /**
         * Mettre à jour un lead
         */
        updateLead(formData, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_update_lead',
                    ...formData,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    callback(response);
                },
                error: function(xhr, status, error) {
                    callback({
                        success: false,
                        data: 'Erreur lors de la communication avec le serveur'
                    });
                }
            });
        }
        

        
        /**
         * Obtenir les détails d'une action
         */
        getAction(actionId, callback) {
            $.ajax({
                url: leadActionsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_get_lead_action',
                    action_id: actionId,
                    nonce: leadActionsAjax.nonce
                },
                success: function(response) {
                    callback(response);
                },
                error: function(xhr, status, error) {
                    callback({
                        success: false,
                        data: 'Erreur lors de la communication avec le serveur'
                    });
                }
            });
        }
        
        /**
         * Rafraîchir la liste des leads
         */
        refreshLeadsList() {
            location.reload();
        }
        
        /**
         * Rafraîchir les détails du lead
         */
        refreshLeadDetails() {
            if (currentLeadId) {
                this.loadLeadDetails();
            }
        }
        
        /**
         * Fermer tous les modals
         */
        closeAllModals() {
            $('.my-istymo-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
        }
        
        /**
         * Afficher le modal de détail des leads
         */
        showLeadDetailModal(content, leadId) {
            // Supprimer les modals existants
            $('.my-istymo-lead-detail-modal').closest('.my-istymo-modal').remove();
            
            // Créer le modal avec le contenu
            const modalHtml = `
                <div class="my-istymo-modal my-istymo-show">
                    <div class="my-istymo-modal-overlay"></div>
                    <div class="my-istymo-modal-content my-istymo-lead-detail-modal" data-lead-id="${leadId}">
                        ${content}
                    </div>
                </div>
            `;
            
            // Ajouter le modal au body
            $('body').append(modalHtml);
            
            // Initialiser les tooltips
            this.initTooltips();
        }
        
        /**
         * Fermer le modal de détail des leads
         */
        closeLeadDetailModal() {
            const modal = $('.my-istymo-lead-detail-modal').closest('.my-istymo-modal');
            if (modal.length) {
                modal.remove();
            }
            this.refreshLeadsList();
        }
        
        /**
         * Fermer le modal d'ajout d'action
         */
        closeAddActionModal() {
            $('#add-action-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
            $('#add-action-form')[0].reset();
        }
        
        /**
         * Fermer le modal d'édition d'action
         */
        closeEditActionModal() {
            $('#edit-action-modal').removeClass('my-istymo-show').addClass('my-istymo-hidden');
            $('#edit-action-form')[0].reset();
            currentActionId = null;
        }
        
        /**
         * Gérer le changement de statut depuis le modal
         */
        handleStatusChangeFromModal() {
            const newStatus = $('#lead-status-change').val();
            
            if (!newStatus) {
                this.showError('Veuillez sélectionner un statut');
                return;
            }
            
            // Récupérer l'ID du lead depuis le modal
            const leadId = $('.my-istymo-lead-detail-modal').data('lead-id');
            
            if (!leadId) {
                this.showError('ID du lead introuvable. Veuillez rafraîchir la page et réessayer.');
                return;
            }
            
            this.changeLeadStatus(leadId, newStatus, (response) => {
                if (response.success) {
                    this.showSuccess('Statut modifié avec succès');
                    // Rafraîchir les détails du lead
                    this.refreshLeadDetails();
                    // Rafraîchir la liste des leads
                    this.refreshLeadsList();
                    // Réinitialiser le select
                    $('#lead-status-change').val('');
                } else {
                    this.showError(response.data || 'Erreur lors de la modification du statut');
                }
            });
        }
        
        /**
         * Gérer l'édition d'une action
         */
        handleEditAction(e) {
            const actionId = $(e.currentTarget).data('action-id');
            this.showEditActionModal(actionId);
        }
        
        /**
         * Gérer la suppression d'une action
         */
        handleDeleteAction(e) {
            const actionId = $(e.currentTarget).data('action-id');
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette action ?')) {
                return;
            }
            
            this.deleteAction(actionId, (response) => {
                if (response.success) {
                    this.showSuccess('Action supprimée avec succès');
                    this.closeLeadDetailModal();
                } else {
                    this.showError(response.data || 'Erreur lors de la suppression de l\'action');
                }
            });
        }
        
        /**
         * Gérer l'ajout rapide d'une action
         */
        handleQuickAddAction(e) {
            const actionType = $(e.currentTarget).data('action-type');
            $('#action-type').val(actionType);
            this.showAddActionModal();
        }
        
                 /**
          * Gérer le formulaire d'ajout d'action
          */
         handleAddActionForm(e) {
             e.preventDefault();
             
             // Récupérer l'ID du lead depuis le modal de détail ou le formulaire d'édition
             let leadId = $('.my-istymo-lead-detail-modal').data('lead-id');
             
             // Si pas trouvé dans le modal, essayer depuis le formulaire d'édition
             if (!leadId) {
                 leadId = $('#lead-edit-form input[name="lead_id"]').val();
             }
             
             if (!leadId) {
                 this.showError('ID du lead introuvable. Veuillez rafraîchir la page et réessayer.');
                 return;
             }
             
             // Récupérer les données du formulaire depuis le modal de détail
             const $form = $('.my-istymo-lead-detail-modal #add-action-form');
             
             const formData = {
                 lead_id: leadId,
                 action_type: $form.find('#action-type').val(),
                 description: $form.find('#action-description').val(),
                 result: $form.find('#action-result').val(),
                 scheduled_date: $form.find('#action-scheduled-date').val()
             };
             
             // Valider le formulaire avant envoi
             if (!this.validateActionForm(formData)) {
                 return;
             }
             
             this.addAction(formData, (response) => {
                 if (response.success) {
                     this.showSuccess('Action ajoutée avec succès');
                     this.hideAddActionForm();
                     this.refreshLeadDetails();
                 } else {
                     this.showError(response.data || 'Erreur lors de l\'ajout de l\'action');
                 }
             });
         }
        
                 /**
          * Gérer le formulaire d'édition d'action
          */
         handleEditActionForm(e) {
             e.preventDefault();
             
             const formData = {
                 action_id: $('#edit-action-id').val(),
                 description: $('#edit-action-description').val(),
                 result: $('#edit-action-result').val()
             };
             
             this.updateAction(formData, (response) => {
                 if (response.success) {
                     this.showSuccess('Action modifiée avec succès');
                     this.closeEditActionModal();
                     this.closeLeadDetailModal();
                 } else {
                     this.showError(response.data || 'Erreur lors de la modification de l\'action');
                 }
             });
         }
         
                   /**
           * Gérer l'édition du lead
           */
          handleLeadEditForm(e) {
              e.preventDefault();
              
              const formData = {
                  lead_id: $('#lead-edit-form input[name="lead_id"]').val(),
                  status: $('#lead-edit-form select[name="status"]').val(),
                  priorite: $('#lead-edit-form select[name="priorite"]').val(),
                  notes: $('#lead-edit-form textarea[name="notes"]').val()
              };
             
             this.updateLead(formData, (response) => {
                 if (response.success) {
                     this.showSuccess('Lead modifié avec succès');
                     // Rafraîchir les détails du lead
                     this.refreshLeadDetails();
                     // Rafraîchir la liste des leads
                     this.refreshLeadsList();
                 } else {
                     this.showError(response.data || 'Erreur lors de la modification du lead');
                 }
             });
         }
         
                   /**
           * Réinitialiser le formulaire d'édition du lead
           */
          resetLeadForm() {
              $('#lead-edit-form')[0].reset();
              this.showSuccess('Formulaire réinitialisé');
          }
          
          /**
           * Afficher/masquer le formulaire d'ajout d'action
           */
          toggleAddActionForm() {
              const $formContainer = $('#add-action-form-container');
              const $button = $('#toggle-add-action-form');
              
              if ($formContainer.is(':visible')) {
                  this.hideAddActionForm();
              } else {
                  this.showAddActionForm();
              }
          }
          
          /**
           * Afficher le formulaire d'ajout d'action
           */
          showAddActionForm() {
              $('#add-action-form-container').show();
              $('#toggle-add-action-form').html('<span class="dashicons dashicons-minus"></span>Masquer le formulaire');
          }
          
                     /**
            * Masquer le formulaire d'ajout d'action
            */
           hideAddActionForm() {
               $('#add-action-form-container').hide();
               $('.my-istymo-lead-detail-modal #add-action-form')[0].reset();
               $('#toggle-add-action-form').html('<span class="dashicons dashicons-plus-alt"></span>Ajouter une action');
           }
    }
    
    /**
     * Classe Modal simplifiée
     */
    class Modal {
        constructor(selector, options = {}) {
            this.selector = selector;
            this.options = options;
            this.$modal = $(selector);
        }
        
        open() {
            this.$modal.removeClass('my-istymo-hidden').addClass('my-istymo-show');
            if (this.options.onOpen) {
                this.options.onOpen();
            }
        }
        
        close() {
            this.$modal.removeClass('my-istymo-show').addClass('my-istymo-hidden');
            if (this.options.onClose) {
                this.options.onClose();
            }
        }
    }
    
    // Initialisation quand le DOM est prêt
    $(document).ready(function() {
        window.leadActionsManager = new LeadActionsManager();
    });
    
    // Fonctions globales pour compatibilité
    window.showAddActionModal = function(leadId) {
        window.leadActionsManager.showAddActionModal(leadId);
    };
    
    window.showEditActionModal = function(actionId) {
        window.leadActionsManager.showEditActionModal(actionId);
    };
    
    window.showLeadDetailModal = function(leadId) {
        window.leadActionsManager.showLeadDetailModal(leadId);
    };
    
    window.closeLeadDetailModal = function() {
        window.leadActionsManager.leadDetailModal.close();
    };
    
    window.changeLeadStatus = function() {
        const newStatus = $('#lead-status-change').val();
        if (newStatus) {
            window.leadActionsManager.changeLeadStatus(currentLeadId, newStatus, (response) => {
                if (response.success) {
                    window.leadActionsManager.showSuccess('Statut modifié avec succès');
                    window.leadActionsManager.closeAllModals();
                } else {
                    window.leadActionsManager.showError(response.data || 'Erreur lors de la modification du statut');
                }
            });
        }
    };
    
    window.quickAddAction = function(actionType) {
        $('#action-type').val(actionType);
        window.leadActionsManager.showAddActionModal();
    };
    
    window.editAction = function(actionId) {
        window.leadActionsManager.showEditActionModal(actionId);
    };
    
    window.deleteAction = function(actionId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette action ?')) {
            window.leadActionsManager.deleteAction(actionId, (response) => {
                if (response.success) {
                    window.leadActionsManager.showSuccess('Action supprimée avec succès');
                    window.leadActionsManager.refreshLeadDetails();
                } else {
                    window.leadActionsManager.showError(response.data || 'Erreur lors de la suppression de l\'action');
                }
            });
        }
    };
    
})(jQuery);
