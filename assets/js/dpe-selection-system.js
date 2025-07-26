/**
 * Système unique de sélection DPE
 * Utilisable dans dpe-panel-simple.php et dpe-shortcodes.php
 */

(function() {
    'use strict';



    // Configuration du système
    const DPESelectionConfig = {
        storageKey: 'dpe_selected_data',
        expiryKey: 'dpe_selection_expiry',
        expiryHours: 24,
        debug: true
    };

    // Système principal de sélection DPE
    const DPESelectionSystem = {
        selectedDPEs: [],
        isInitialized: false,

        // Initialiser le système
        init() {
            if (this.isInitialized) {
                return;
            }

            this.loadFromStorage();
            this.attachGlobalEvents();
            this.updateUI();
            this.isInitialized = true;
        },

        // Charger les sélections depuis localStorage
        loadFromStorage() {
            try {
                const expiryTime = localStorage.getItem(DPESelectionConfig.expiryKey);
                
                if (expiryTime && Date.now() > parseInt(expiryTime)) {
                    this.clearStorage();
                    return;
                }

                const stored = localStorage.getItem(DPESelectionConfig.storageKey);
                if (stored) {
                    this.selectedDPEs = JSON.parse(stored);
                }
            } catch (error) {
                console.warn('⚠️ DPE Selection System - Erreur chargement:', error);
                this.clearStorage();
            }
        },

        // Sauvegarder dans localStorage
        saveToStorage() {
            try {
                localStorage.setItem(DPESelectionConfig.storageKey, JSON.stringify(this.selectedDPEs));
                const expiryTime = Date.now() + (DPESelectionConfig.expiryHours * 60 * 60 * 1000);
                localStorage.setItem(DPESelectionConfig.expiryKey, expiryTime.toString());
            } catch (error) {
                console.warn('⚠️ DPE Selection System - Erreur sauvegarde:', error);
            }
        },

        // Nettoyer le stockage
        clearStorage() {
            localStorage.removeItem(DPESelectionConfig.storageKey);
            localStorage.removeItem(DPESelectionConfig.expiryKey);
        },

        // Ajouter/retirer une sélection
        toggle(id) {
            const existingIndex = this.selectedDPEs.findIndex(dpe => dpe.numero_dpe === id);
            
            if (existingIndex !== -1) {
                // Supprimer la DPE
                this.selectedDPEs.splice(existingIndex, 1);
            } else {
                // Ajouter la DPE
                const checkbox = document.querySelector(`.send-letter-checkbox[data-numero-dpe="${id}"]`);
                
                if (checkbox) {
                    const data = {
                        numero_dpe: id,
                        type_batiment: checkbox.getAttribute('data-type-batiment') || '',
                        adresse: checkbox.getAttribute('data-adresse') || '',
                        commune: checkbox.getAttribute('data-commune') || '',
                        code_postal: checkbox.getAttribute('data-code-postal') || '',
                        surface: checkbox.getAttribute('data-surface') || '',
                        etiquette_dpe: checkbox.getAttribute('data-etiquette-dpe') || '',
                        etiquette_ges: checkbox.getAttribute('data-etiquette-ges') || '',
                        date_dpe: checkbox.getAttribute('data-date-dpe') || ''
                    };
                    this.selectedDPEs.push(data);
                } else {
                    console.warn('⚠️ DPE Selection System - Checkbox non trouvée pour ID:', id);
                }
            }
            
            this.saveToStorage();
            this.updateUI();
        },

        // Vérifier si un ID est sélectionné
        isSelected(id) {
            return this.selectedDPEs.some(dpe => dpe.numero_dpe === id);
        },

        // Obtenir les données sélectionnées
        getSelectedData() {
            return this.selectedDPEs;
        },

        // Obtenir le nombre de sélections
        getCount() {
            return this.selectedDPEs.length;
        },

        // Effacer toutes les sélections
        clearAll() {
            this.selectedDPEs = [];
            this.saveToStorage();
            this.updateUI();
            this.updateCheckboxes();
        },

        // Attacher les événements globaux
        attachGlobalEvents() {
            // Événements pour les checkboxes existantes
            this.attachToExistingCheckboxes();

            // Événement pour le bouton d'envoi
            const sendLettersBtn = document.getElementById('send-letters-btn');
            if (sendLettersBtn) {
                sendLettersBtn.addEventListener('click', () => this.openCampaignPopup());
            }

            // Événements pour le popup
            this.attachPopupEvents();
        },

        // Attacher les événements aux checkboxes existantes
        attachToExistingCheckboxes() {
            const checkboxes = document.querySelectorAll('.send-letter-checkbox');

            checkboxes.forEach(checkbox => {
                // Supprimer les anciens listeners pour éviter les doublons
                checkbox.removeEventListener('change', this.handleCheckboxChange);
                
                // Ajouter le nouveau listener
                checkbox.addEventListener('change', this.handleCheckboxChange.bind(this));
                
                // Mettre à jour l'état de la checkbox
                const id = checkbox.getAttribute('data-numero-dpe');
                if (id) {
                    checkbox.checked = this.isSelected(id);
                }
            });
        },

        // Gérer le changement de checkbox
        handleCheckboxChange(event) {
            const checkbox = event.target;
            const id = checkbox.getAttribute('data-numero-dpe');
            
            if (id) {
                this.toggle(id);
            }
        },

        // Mettre à jour l'interface utilisateur
        updateUI() {
            const selectedCountSpan = document.getElementById('selected-count');
            const sendLettersBtn = document.getElementById('send-letters-btn');
            
            if (selectedCountSpan) {
                selectedCountSpan.textContent = this.getCount();
            }
            
            if (sendLettersBtn) {
                // ✅ NOUVEAU : Utiliser les fonctions personnalisées pour une meilleure gestion
                if (this.getCount() === 0) {
                    // Utiliser la fonction globale si disponible, sinon méthode standard
                    if (typeof window.forceDisableSendButton === 'function') {
                        window.forceDisableSendButton();
                    } else {
                        sendLettersBtn.disabled = true;
                        sendLettersBtn.classList.add('disabled');
                    }
                } else {
                    // Utiliser la fonction globale si disponible, sinon méthode standard
                    if (typeof window.enableSendButton === 'function') {
                        window.enableSendButton();
                    } else {
                        sendLettersBtn.disabled = false;
                        sendLettersBtn.classList.remove('disabled');
                    }
                }
            }
        },

        // Mettre à jour l'état des checkboxes
        updateCheckboxes() {
            const checkboxes = document.querySelectorAll('.send-letter-checkbox');
            
            checkboxes.forEach(checkbox => {
                const id = checkbox.getAttribute('data-numero-dpe');
                if (id) {
                    checkbox.checked = this.isSelected(id);
                }
            });
        },

        // Restaurer les sélections après changement de page
        restoreSelections() {
            this.updateCheckboxes();
            this.updateUI();
        },

        // Réinitialiser le système après création de nouvelles checkboxes
        reinitialize() {
            this.attachToExistingCheckboxes();
            this.restoreSelections();
            this.updateUI();
        },

        // Ouvrir le popup de campagne
        openCampaignPopup() {
            const selectedDPEs = this.getSelectedData();
            
            if (selectedDPEs.length === 0) {
                // Alerte désactivée - ne rien faire
                return;
            }
            
            // Remplir la liste des DPE sélectionnées
            const selectedDpeList = document.getElementById('selected-dpe-list');
            if (selectedDpeList) {
                selectedDpeList.innerHTML = '';
                selectedDPEs.forEach(dpe => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <strong>${dpe.adresse}</strong><br>
                        <small>Commune: ${dpe.commune}</small><br>
                        <small>DPE: ${dpe.etiquette_dpe} | GES: ${dpe.etiquette_ges}</small><br>
                        <small>Surface: ${dpe.surface} | Date: ${dpe.date_dpe}</small>
                    `;
                    selectedDpeList.appendChild(li);
                });
            }
            
            // Afficher le popup
            const lettersPopup = document.getElementById('letters-popup');
            const step1 = document.getElementById('step-1');
            const step2 = document.getElementById('step-2');
            
            if (lettersPopup) {
                lettersPopup.style.display = 'flex';
                if (step1) step1.style.display = 'block';
                if (step2) step2.style.display = 'none';
            }
        },

        // Attacher les événements du popup
        attachPopupEvents() {
            // Navigation vers l'étape 2
            const toStep2Btn = document.getElementById('to-step-2');
            if (toStep2Btn) {
                toStep2Btn.addEventListener('click', () => {
                    const step1 = document.getElementById('step-1');
                    const step2 = document.getElementById('step-2');
                    if (step1) step1.style.display = 'none';
                    if (step2) step2.style.display = 'block';
                });
            }

            // Fermer le popup (compatible avec le système SCI)
            const closePopupBtns = document.querySelectorAll('.popup-close-btn');
            closePopupBtns.forEach(btn => {
                btn.addEventListener('click', () => this.closeCampaignPopup());
            });

            // Fermer en cliquant sur l'arrière-plan
            const lettersPopup = document.getElementById('letters-popup');
            if (lettersPopup) {
                lettersPopup.addEventListener('click', (e) => {
                    if (e.target === lettersPopup) {
                        this.closeCampaignPopup();
                    }
                });
            }
        },

        // Fermer le popup de campagne
        closeCampaignPopup() {
            const lettersPopup = document.getElementById('letters-popup');
            if (lettersPopup) {
                lettersPopup.style.display = 'none';
            }
        },

        // Debug du système
        debug() {
            // Debug silencieux
        }
    };

    // Fonctions globales pour compatibilité
    window.DPESelectionSystem = DPESelectionSystem;

    // Fonctions de compatibilité avec l'ancien système
    window.restoreDPESelections = function() {
        DPESelectionSystem.restoreSelections();
    };

    window.updateDPESelectionUI = function() {
        DPESelectionSystem.updateUI();
    };

    window.reinitializeDPESelection = function() {
        DPESelectionSystem.reinitialize();
    };

    window.getSelectedDPEEntries = function() {
        return DPESelectionSystem.getSelectedData();
    };

    window.clearDPESelections = function() {
        DPESelectionSystem.clearAll();
    };

    window.debugDPESelection = function() {
        DPESelectionSystem.debug();
    };
    
    // ✅ NOUVEAU : Compatibilité avec le système SCI (payment.js)
    window.getSelectedEntries = function() {
        return DPESelectionSystem.getSelectedDPEs();
    };
    
    // ✅ NOUVEAU : Fonction pour fermer le popup (compatibilité SCI)
    window.resetSciPopup = function() {
        DPESelectionSystem.closeCampaignPopup();
    };

    // Initialisation automatique
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            DPESelectionSystem.init();
        });
    } else {
        DPESelectionSystem.init();
    }

    // Initialisation alternative pour les pages avec AJAX
    window.addEventListener('load', () => {
        if (!DPESelectionSystem.isInitialized) {
            DPESelectionSystem.init();
        }
    });



})(); 