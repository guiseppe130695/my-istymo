/**
 * JavaScript pour la Gestion du Workflow des Leads
 * 
 * Ce fichier gère les transitions de statuts, les validations de workflow
 * et les actions contextuelles selon l'état du lead.
 * 
 * @package My_Istymo
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Classe principale pour la gestion du workflow
     */
    class LeadWorkflowManager {
        
        constructor() {
            this.currentLeadId = null;
            this.currentStatus = null;
            this.allowedTransitions = [];
            this.suggestedActions = [];
            this.init();
        }
        
        /**
         * Initialisation
         */
        init() {
            this.bindEvents();
            this.initWorkflowVisualization();
        }
        
        /**
         * Liaison des événements
         */
        bindEvents() {
            // Transitions de workflow
            $(document).on('click', '.my-istymo-workflow-transition', this.handleWorkflowTransition.bind(this));
            $(document).on('click', '.my-istymo-status-change', this.handleStatusChange.bind(this));
            
            // Actions contextuelles
            $(document).on('click', '.my-istymo-contextual-action', this.handleContextualAction.bind(this));
            $(document).on('click', '.my-istymo-suggested-action', this.handleSuggestedAction.bind(this));
            
            // Validation de workflow
            $(document).on('change', '.my-istymo-status-select', this.validateStatusChange.bind(this));
            
            // Workflow visuel
            $(document).on('click', '.my-istymo-workflow-step', this.handleWorkflowStepClick.bind(this));
            
            // Raccourcis clavier pour le workflow
            $(document).on('keydown', this.handleWorkflowKeyboardShortcuts.bind(this));
        }
        
        /**
         * Initialisation de la visualisation du workflow
         */
        initWorkflowVisualization() {
            this.renderWorkflowSteps();
            this.updateWorkflowProgress();
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
            
            // Validation de la transition
            this.validateWorkflowTransition(leadId, fromStatus, toStatus, (validation) => {
                if (validation.valid) {
                    this.executeWorkflowTransition(leadId, fromStatus, toStatus, validation.suggestedActions);
                } else {
                    this.showWorkflowError(validation.message, validation.requiredActions);
                }
            });
        }
        
        /**
         * Gestion du changement de statut
         */
        handleStatusChange(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const newStatus = $button.data('status');
            const leadId = $button.data('lead-id');
            
            this.changeLeadStatus(leadId, newStatus, (response) => {
                if (response.success) {
                    this.showWorkflowSuccess('Statut modifié avec succès');
                    this.updateWorkflowDisplay(leadId, newStatus);
                    this.refreshLeadDetails();
                } else {
                    this.showWorkflowError(response.data || 'Erreur lors du changement de statut');
                }
            });
        }
        
        /**
         * Gestion des actions contextuelles
         */
        handleContextualAction(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const actionType = $button.data('action-type');
            const leadId = $button.data('lead-id');
            const context = $button.data('context');
            
            // Déclencher l'action appropriée selon le contexte
            switch (context) {
                case 'qualification':
                    this.handleQualificationAction(leadId, actionType);
                    break;
                case 'proposition':
                    this.handlePropositionAction(leadId, actionType);
                    break;
                case 'negotiation':
                    this.handleNegotiationAction(leadId, actionType);
                    break;
                case 'closing':
                    this.handleClosingAction(leadId, actionType);
                    break;
                default:
                    this.handleGenericAction(leadId, actionType);
            }
        }
        
        /**
         * Gestion des actions suggérées
         */
        handleSuggestedAction(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const actionType = $button.data('action-type');
            const leadId = $button.data('lead-id');
            
            // Pré-remplir le formulaire d'action avec les suggestions
            this.prepopulateActionForm(leadId, actionType);
            
            // Afficher le modal d'ajout d'action
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        /**
         * Validation du changement de statut
         */
        validateStatusChange(e) {
            const $select = $(e.currentTarget);
            const newStatus = $select.val();
            const leadId = $select.data('lead-id');
            
            if (!newStatus) return;
            
            // Charger les validations pour ce changement
            this.loadStatusChangeValidation(leadId, newStatus, (validation) => {
                this.displayStatusChangeValidation(validation);
            });
        }
        
        /**
         * Gestion du clic sur une étape du workflow
         */
        handleWorkflowStepClick(e) {
            e.preventDefault();
            
            const $step = $(e.currentTarget);
            const stepStatus = $step.data('status');
            const leadId = $step.data('lead-id');
            
            // Afficher les informations de l'étape
            this.showWorkflowStepInfo(leadId, stepStatus);
        }
        
        /**
         * Gestion des raccourcis clavier pour le workflow
         */
        handleWorkflowKeyboardShortcuts(e) {
            // Ctrl+Shift+N pour nouveau statut
            if (e.ctrlKey && e.shiftKey && e.keyCode === 78) {
                e.preventDefault();
                this.showStatusChangeModal();
            }
            
            // Ctrl+Shift+A pour action suggérée
            if (e.ctrlKey && e.shiftKey && e.keyCode === 65) {
                e.preventDefault();
                this.showSuggestedActionsModal();
            }
        }
        
        /**
         * Validation d'une transition de workflow
         */
        validateWorkflowTransition(leadId, fromStatus, toStatus, callback) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_validate_workflow_transition',
                    lead_id: leadId,
                    from_status: fromStatus,
                    to_status: toStatus,
                    nonce: myIstymoAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        callback({
                            valid: true,
                            suggestedActions: response.data.suggested_actions || []
                        });
                    } else {
                        callback({
                            valid: false,
                            message: response.data.message,
                            requiredActions: response.data.required_actions || []
                        });
                    }
                }
            });
        }
        
        /**
         * Exécuter une transition de workflow
         */
        executeWorkflowTransition(leadId, fromStatus, toStatus, suggestedActions) {
            // Changer le statut
            this.changeLeadStatus(leadId, toStatus, (response) => {
                if (response.success) {
                    this.showWorkflowSuccess('Transition effectuée avec succès');
                    
                    // Suggérer des actions si disponibles
                    if (suggestedActions && suggestedActions.length > 0) {
                        this.showSuggestedActionsAfterTransition(suggestedActions, leadId);
                    }
                    
                    // Mettre à jour l'affichage
                    this.updateWorkflowDisplay(leadId, toStatus);
                    this.refreshLeadDetails();
                } else {
                    this.showWorkflowError(response.data || 'Erreur lors de la transition');
                }
            });
        }
        
        /**
         * Changer le statut d'un lead
         */
        changeLeadStatus(leadId, newStatus, callback) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_change_lead_status',
                    lead_id: leadId,
                    new_status: newStatus,
                    nonce: myIstymoAjax.nonce
                },
                success: callback
            });
        }
        
        /**
         * Charger la validation d'un changement de statut
         */
        loadStatusChangeValidation(leadId, newStatus, callback) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_get_status_change_validation',
                    lead_id: leadId,
                    new_status: newStatus,
                    nonce: myIstymoAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        callback(response.data);
                    }
                }
            });
        }
        
        /**
         * Afficher la validation d'un changement de statut
         */
        displayStatusChangeValidation(validation) {
            const $validationContainer = $('.my-istymo-status-validation');
            
            if (validation.valid) {
                $validationContainer.html(`
                    <div class="my-istymo-validation-success">
                        <span class="dashicons dashicons-yes"></span>
                        Transition autorisée
                    </div>
                `);
            } else {
                let html = `
                    <div class="my-istymo-validation-error">
                        <span class="dashicons dashicons-no"></span>
                        ${validation.message}
                    </div>
                `;
                
                if (validation.required_actions && validation.required_actions.length > 0) {
                    html += '<div class="my-istymo-required-actions">';
                    html += '<strong>Actions requises :</strong><ul>';
                    validation.required_actions.forEach(action => {
                        html += `<li>${action}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                $validationContainer.html(html);
            }
        }
        
        /**
         * Afficher les informations d'une étape du workflow
         */
        showWorkflowStepInfo(leadId, stepStatus) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'my_istymo_get_workflow_step_info',
                    lead_id: leadId,
                    step_status: stepStatus,
                    nonce: myIstymoAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Afficher les informations dans un modal ou tooltip
                        LeadWorkflowManager.showStepInfoModal(response.data);
                    }
                }
            });
        }
        
        /**
         * Afficher un modal d'informations d'étape
         */
        static showStepInfoModal(stepInfo) {
            const modal = $(`
                <div class="my-istymo-modal my-istymo-step-info-modal">
                    <div class="my-istymo-modal-content">
                        <div class="my-istymo-modal-header">
                            <h3>${stepInfo.title}</h3>
                            <button type="button" class="my-istymo-modal-close">&times;</button>
                        </div>
                        <div class="my-istymo-modal-body">
                            <div class="my-istymo-step-description">
                                ${stepInfo.description}
                            </div>
                            <div class="my-istymo-step-actions">
                                <h4>Actions recommandées :</h4>
                                <ul>
                                    ${stepInfo.recommended_actions.map(action => 
                                        `<li>${action.icon} ${action.label}</li>`
                                    ).join('')}
                                </ul>
                            </div>
                            <div class="my-istymo-step-criteria">
                                <h4>Critères de passage :</h4>
                                <ul>
                                    ${stepInfo.criteria.map(criterion => 
                                        `<li>${criterion}</li>`
                                    ).join('')}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            modal.addClass('show');
            
            // Fermeture du modal
            modal.find('.my-istymo-modal-close, .my-istymo-modal-overlay').on('click', () => {
                modal.removeClass('show');
                setTimeout(() => modal.remove(), 300);
            });
        }
        
        /**
         * Afficher les actions suggérées après une transition
         */
        showSuggestedActionsAfterTransition(suggestedActions, leadId) {
            const modal = $(`
                <div class="my-istymo-modal my-istymo-suggested-actions-modal">
                    <div class="my-istymo-modal-content">
                        <div class="my-istymo-modal-header">
                            <h3>Actions suggérées</h3>
                            <button type="button" class="my-istymo-modal-close">&times;</button>
                        </div>
                        <div class="my-istymo-modal-body">
                            <p>Voici les actions recommandées pour ce nouveau statut :</p>
                            <div class="my-istymo-suggested-actions-list">
                                ${suggestedActions.map(action => `
                                    <div class="my-istymo-suggested-action-item">
                                        <span class="my-istymo-action-icon">${action.icon}</span>
                                        <span class="my-istymo-action-label">${action.label}</span>
                                        <button type="button" class="my-istymo-btn my-istymo-btn-small" 
                                                onclick="quickAddAction('${action.type}', ${leadId})">
                                            Ajouter
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="my-istymo-modal-footer">
                            <button type="button" class="my-istymo-btn my-istymo-btn-secondary" onclick="closeSuggestedActionsModal()">
                                Plus tard
                            </button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            modal.addClass('show');
            
            // Fermeture du modal
            modal.find('.my-istymo-modal-close, .my-istymo-btn-secondary').on('click', () => {
                modal.removeClass('show');
                setTimeout(() => modal.remove(), 300);
            });
        }
        
        /**
         * Pré-remplir le formulaire d'action
         */
        prepopulateActionForm(leadId, actionType) {
            // Pré-remplir le type d'action
            $('#action-type').val(actionType);
            $('#action-lead-id').val(leadId);
            
            // Pré-remplir la description selon le type
            const descriptions = {
                'appel': 'Appel téléphonique effectué',
                'email': 'Email envoyé',
                'sms': 'SMS envoyé',
                'rdv': 'Rendez-vous programmé',
                'note': 'Note ajoutée'
            };
            
            if (descriptions[actionType]) {
                $('#action-description').val(descriptions[actionType]);
            }
        }
        
        /**
         * Mettre à jour l'affichage du workflow
         */
        updateWorkflowDisplay(leadId, newStatus) {
            this.currentLeadId = leadId;
            this.currentStatus = newStatus;
            
            // Mettre à jour les étapes du workflow
            this.updateWorkflowSteps(newStatus);
            
            // Mettre à jour les actions contextuelles
            this.updateContextualActions(leadId, newStatus);
            
            // Mettre à jour les suggestions d'actions
            this.updateSuggestedActions(leadId, newStatus);
        }
        
        /**
         * Mettre à jour les étapes du workflow
         */
        updateWorkflowSteps(currentStatus) {
            const workflowSteps = [
                { status: 'nouveau', label: 'Nouveau', icon: '🆕' },
                { status: 'en_cours', label: 'En cours', icon: 'En cours' },
                { status: 'qualifie', label: 'Qualifié', icon: 'Qualifié' },
                { status: 'proposition', label: 'Proposition', icon: 'Proposition' },
                { status: 'negocie', label: 'Négocié', icon: '🤝' },
                { status: 'gagne', label: 'Gagné', icon: '🏆' }
            ];
            
            let html = '';
            workflowSteps.forEach((step, index) => {
                const isActive = step.status === currentStatus;
                const isCompleted = this.isStepCompleted(step.status, currentStatus);
                const isAccessible = this.isStepAccessible(step.status, currentStatus);
                
                html += `
                    <div class="my-istymo-workflow-step ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''} ${isAccessible ? 'accessible' : ''}"
                         data-status="${step.status}" data-lead-id="${this.currentLeadId}">
                        <div class="my-istymo-step-icon">${step.icon}</div>
                        <div class="my-istymo-step-label">${step.label}</div>
                        ${index < workflowSteps.length - 1 ? '<div class="my-istymo-step-connector"></div>' : ''}
                    </div>
                `;
            });
            
            $('.my-istymo-workflow-steps').html(html);
        }
        
        /**
         * Vérifier si une étape est complétée
         */
        isStepCompleted(stepStatus, currentStatus) {
            const stepOrder = ['nouveau', 'en_cours', 'qualifie', 'proposition', 'negocie', 'gagne'];
            const stepIndex = stepOrder.indexOf(stepStatus);
            const currentIndex = stepOrder.indexOf(currentStatus);
            
            return stepIndex < currentIndex;
        }
        
        /**
         * Vérifier si une étape est accessible
         */
        isStepAccessible(stepStatus, currentStatus) {
            const allowedTransitions = this.getAllowedTransitions(currentStatus);
            return allowedTransitions.includes(stepStatus);
        }
        
        /**
         * Obtenir les transitions autorisées
         */
        getAllowedTransitions(currentStatus) {
            const transitions = {
                'nouveau': ['en_cours', 'qualifie', 'perdu'],
                'en_cours': ['qualifie', 'perdu', 'en_attente'],
                'qualifie': ['proposition', 'gagne', 'perdu'],
                'proposition': ['gagne', 'perdu', 'negocie'],
                'negocie': ['gagne', 'perdu'],
                'gagne': [],
                'perdu': ['nouveau'],
                'en_attente': ['en_cours', 'perdu']
            };
            
            return transitions[currentStatus] || [];
        }
        
        /**
         * Mettre à jour les actions contextuelles
         */
        updateContextualActions(leadId, status) {
            const contextualActions = this.getContextualActions(status);
            
            let html = '';
            contextualActions.forEach(action => {
                html += `
                    <button type="button" class="my-istymo-btn my-istymo-contextual-action"
                            data-action-type="${action.type}" data-lead-id="${leadId}" data-context="${action.context}">
                        ${action.icon} ${action.label}
                    </button>
                `;
            });
            
            $('.my-istymo-contextual-actions').html(html);
        }
        
        /**
         * Obtenir les actions contextuelles selon le statut
         */
        getContextualActions(status) {
            const actions = {
                'nouveau': [
                    { type: 'appel', label: 'Premier appel', icon: '📞', context: 'qualification' },
                    { type: 'email', label: 'Email de présentation', icon: '📧', context: 'qualification' }
                ],
                'en_cours': [
                    { type: 'appel', label: 'Suivi téléphonique', icon: '📞', context: 'qualification' },
                    { type: 'rdv', label: 'Programmer RDV', icon: '📅', context: 'qualification' }
                ],
                'qualifie': [
                    { type: 'email', label: 'Envoi proposition', icon: '📧', context: 'proposition' },
                    { type: 'rdv', label: 'RDV présentation', icon: '📅', context: 'proposition' }
                ],
                'proposition': [
                    { type: 'appel', label: 'Suivi proposition', icon: '📞', context: 'negotiation' },
                    { type: 'email', label: 'Relance', icon: '📧', context: 'negotiation' }
                ],
                'negocie': [
                    { type: 'appel', label: 'Négociation', icon: '📞', context: 'negotiation' },
                    { type: 'rdv', label: 'RDV clôture', icon: '📅', context: 'closing' }
                ]
            };
            
            return actions[status] || [];
        }
        
        /**
         * Mettre à jour les suggestions d'actions
         */
        updateSuggestedActions(leadId, status) {
            const suggestedActions = this.getSuggestedActions(status);
            
            let html = '';
            suggestedActions.forEach(action => {
                html += `
                    <div class="my-istymo-suggested-action">
                        <span class="my-istymo-action-icon">${action.icon}</span>
                        <span class="my-istymo-action-label">${action.label}</span>
                        <button type="button" class="my-istymo-btn my-istymo-btn-small my-istymo-suggested-action"
                                data-action-type="${action.type}" data-lead-id="${leadId}">
                            Ajouter
                        </button>
                    </div>
                `;
            });
            
            $('.my-istymo-suggested-actions').html(html);
        }
        
        /**
         * Obtenir les actions suggérées selon le statut
         */
        getSuggestedActions(status) {
            const suggestions = {
                'nouveau': [
                    { type: 'appel', label: 'Premier contact téléphonique', icon: '📞' },
                    { type: 'email', label: 'Email de présentation', icon: '📧' },
                    { type: 'note', label: 'Notes de qualification', icon: '📝' }
                ],
                'en_cours': [
                    { type: 'appel', label: 'Suivi téléphonique', icon: '📞' },
                    { type: 'email', label: 'Envoi de documentation', icon: '📧' },
                    { type: 'rdv', label: 'Programmer un rendez-vous', icon: '📅' }
                ],
                'qualifie': [
                    { type: 'appel', label: 'Appel de qualification', icon: '📞' },
                    { type: 'email', label: 'Envoi de proposition', icon: '📧' },
                    { type: 'rdv', label: 'Rendez-vous de présentation', icon: '📅' }
                ],
                'proposition': [
                    { type: 'appel', label: 'Suivi de proposition', icon: '📞' },
                    { type: 'email', label: 'Relance de proposition', icon: '📧' },
                    { type: 'rdv', label: 'Rendez-vous de négociation', icon: '📅' }
                ],
                'negocie': [
                    { type: 'appel', label: 'Négociation téléphonique', icon: '📞' },
                    { type: 'email', label: 'Contre-proposition', icon: '📧' },
                    { type: 'rdv', label: 'Rendez-vous de clôture', icon: '📅' }
                ]
            };
            
            return suggestions[status] || [];
        }
        
        /**
         * Gestion des actions de qualification
         */
        handleQualificationAction(leadId, actionType) {
            // Actions spécifiques à la qualification
            const qualificationActions = {
                'appel': () => this.handleQualificationCall(leadId),
                'email': () => this.handleQualificationEmail(leadId),
                'rdv': () => this.handleQualificationMeeting(leadId)
            };
            
            if (qualificationActions[actionType]) {
                qualificationActions[actionType]();
            }
        }
        
        /**
         * Gestion des actions de proposition
         */
        handlePropositionAction(leadId, actionType) {
            // Actions spécifiques à la proposition
            const propositionActions = {
                'email': () => this.handlePropositionEmail(leadId),
                'rdv': () => this.handlePropositionMeeting(leadId)
            };
            
            if (propositionActions[actionType]) {
                propositionActions[actionType]();
            }
        }
        
        /**
         * Gestion des actions de négociation
         */
        handleNegotiationAction(leadId, actionType) {
            // Actions spécifiques à la négociation
            const negotiationActions = {
                'appel': () => this.handleNegotiationCall(leadId),
                'email': () => this.handleNegotiationEmail(leadId),
                'rdv': () => this.handleNegotiationMeeting(leadId)
            };
            
            if (negotiationActions[actionType]) {
                negotiationActions[actionType]();
            }
        }
        
        /**
         * Gestion des actions de clôture
         */
        handleClosingAction(leadId, actionType) {
            // Actions spécifiques à la clôture
            const closingActions = {
                'appel': () => this.handleClosingCall(leadId),
                'rdv': () => this.handleClosingMeeting(leadId),
                'note': () => this.handleClosingNote(leadId)
            };
            
            if (closingActions[actionType]) {
                closingActions[actionType]();
            }
        }
        
        /**
         * Gestion des actions génériques
         */
        handleGenericAction(leadId, actionType) {
            // Action générique - ouvrir le formulaire d'action
            this.prepopulateActionForm(leadId, actionType);
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        /**
         * Actions spécifiques de qualification
         */
        handleQualificationCall(leadId) {
            this.prepopulateActionForm(leadId, 'appel');
            $('#action-description').val('Appel de qualification - Vérification des besoins et budget');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        handleQualificationEmail(leadId) {
            this.prepopulateActionForm(leadId, 'email');
            $('#action-description').val('Email de présentation - Envoi de documentation et présentation de nos services');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        handleQualificationMeeting(leadId) {
            this.prepopulateActionForm(leadId, 'rdv');
            $('#action-description').val('Rendez-vous de qualification - Présentation détaillée et évaluation des besoins');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        /**
         * Actions spécifiques de proposition
         */
        handlePropositionEmail(leadId) {
            this.prepopulateActionForm(leadId, 'email');
            $('#action-description').val('Envoi de proposition commerciale - Devis détaillé et conditions');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        handlePropositionMeeting(leadId) {
            this.prepopulateActionForm(leadId, 'rdv');
            $('#action-description').val('Rendez-vous de présentation - Présentation de la proposition et discussion');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        /**
         * Actions spécifiques de négociation
         */
        handleNegotiationCall(leadId) {
            this.prepopulateActionForm(leadId, 'appel');
            $('#action-description').val('Appel de négociation - Discussion des conditions et ajustements');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        handleNegotiationEmail(leadId) {
            this.prepopulateActionForm(leadId, 'email');
            $('#action-description').val('Contre-proposition - Ajustement des conditions selon les retours');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        handleNegotiationMeeting(leadId) {
            this.prepopulateActionForm(leadId, 'rdv');
            $('#action-description').val('Rendez-vous de négociation - Discussion approfondie des conditions');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        /**
         * Actions spécifiques de clôture
         */
        handleClosingCall(leadId) {
            this.prepopulateActionForm(leadId, 'appel');
            $('#action-description').val('Appel de finalisation - Confirmation des conditions et prochaines étapes');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        handleClosingMeeting(leadId) {
            this.prepopulateActionForm(leadId, 'rdv');
            $('#action-description').val('Rendez-vous de clôture - Signature du contrat et finalisation');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        handleClosingNote(leadId) {
            this.prepopulateActionForm(leadId, 'note');
            $('#action-description').val('Notes de clôture - Résumé de la transaction et conditions finales');
            
            if (window.leadActionsManager) {
                window.leadActionsManager.showAddActionModal(leadId);
            }
        }
        
        /**
         * Afficher un message de succès du workflow
         */
        showWorkflowSuccess(message) {
            this.showWorkflowNotification(message, 'success');
        }
        
        /**
         * Afficher un message d'erreur du workflow
         */
        showWorkflowError(message, requiredActions = []) {
            this.showWorkflowNotification(message, 'error', requiredActions);
        }
        
        /**
         * Afficher une notification du workflow
         */
        showWorkflowNotification(message, type = 'info', requiredActions = []) {
            let html = `
                <div class="my-istymo-workflow-notification my-istymo-notification-${type}">
                    <div class="my-istymo-notification-content">
                        <span class="my-istymo-notification-message">${message}</span>
                        <button type="button" class="my-istymo-notification-close">&times;</button>
                    </div>
            `;
            
            if (requiredActions.length > 0) {
                html += `
                    <div class="my-istymo-required-actions">
                        <strong>Actions requises :</strong>
                        <ul>
                            ${requiredActions.map(action => `<li>${action}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            html += '</div>';
            
            const notification = $(html);
            $('body').append(notification);
            
            // Animation d'apparition
            notification.addClass('show');
            
            // Auto-fermeture après 8 secondes
            setTimeout(() => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            }, 8000);
            
            // Fermeture manuelle
            notification.find('.my-istymo-notification-close').on('click', () => {
                notification.removeClass('show');
                setTimeout(() => notification.remove(), 300);
            });
        }
        
        /**
         * Rafraîchir les détails du lead
         */
        refreshLeadDetails() {
            if (this.currentLeadId && window.leadActionsManager) {
                window.leadActionsManager.refreshLeadDetails();
            }
        }
        
        /**
         * Rendu des étapes du workflow
         */
        renderWorkflowSteps() {
            // Cette méthode est appelée lors de l'initialisation
            // L'affichage sera mis à jour dynamiquement selon le statut
        }
        
        /**
         * Mettre à jour la progression du workflow
         */
        updateWorkflowProgress() {
            // Calculer la progression selon le statut actuel
            const progress = this.calculateWorkflowProgress();
            
            $('.my-istymo-workflow-progress-bar .my-istymo-progress-fill').css('width', progress + '%');
            $('.my-istymo-workflow-progress-text').text(progress + '%');
        }
        
        /**
         * Calculer la progression du workflow
         */
        calculateWorkflowProgress() {
            const stepOrder = ['nouveau', 'en_cours', 'qualifie', 'proposition', 'negocie', 'gagne'];
            const currentIndex = stepOrder.indexOf(this.currentStatus);
            
            if (currentIndex === -1) return 0;
            
            return Math.round(((currentIndex + 1) / stepOrder.length) * 100);
        }
    }
    
    // Initialisation quand le DOM est prêt
    $(document).ready(function() {
        window.leadWorkflowManager = new LeadWorkflowManager();
    });
    
    // Fonctions globales pour compatibilité
    window.closeSuggestedActionsModal = function() {
        $('.my-istymo-suggested-actions-modal').removeClass('show');
        setTimeout(() => $('.my-istymo-suggested-actions-modal').remove(), 300);
    };
    
    window.quickAddAction = function(actionType, leadId) {
        if (window.leadActionsManager) {
            window.leadActionsManager.prepopulateActionForm(leadId, actionType);
            window.leadActionsManager.showAddActionModal(leadId);
        }
    };
    
})(jQuery);
