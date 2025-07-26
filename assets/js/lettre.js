document.addEventListener('DOMContentLoaded', function() {
    const sendLettersBtn = document.getElementById('send-letters-btn');
    const selectedCountSpan = document.getElementById('selected-count');
    const lettersPopup = document.getElementById('letters-popup');
    const checkboxes = document.querySelectorAll('.send-letter-checkbox');
    
    // ✅ AMÉLIORÉ : Vérifier que les éléments nécessaires existent
    if (!sendLettersBtn || !selectedCountSpan) {
        console.log('🔍 lettre.js - Éléments de base non trouvés, arrêt du script');
        return;
    }
    
    // Éléments du popup
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const selectedSciList = document.getElementById('selected-sci-list') || document.getElementById('selected-dpe-list');
    
    // Boutons de navigation
    const toStep2Btn = document.getElementById('to-step-2');
    const closePopupBtns = document.querySelectorAll('#close-popup-1');
    
    // ✅ AMÉLIORÉ : Vérifier que tous les éléments du popup existent
    if (!lettersPopup || !step1 || !step2 || !selectedSciList || !toStep2Btn) {
        console.log('🔍 lettre.js - Éléments du popup non trouvés, arrêt du script');
        return;
    }
    
    // ✅ NOUVEAU : Détecter le contexte (SCI ou DPE)
    const isDPEContext = document.getElementById('selected-dpe-list') !== null;
    const contextType = isDPEContext ? 'DPE' : 'SCI';
    
    let selectedEntries = [];

    const defaultEmailContent = `(Votre prénom et nom)
(Statut : Mandataire Immobilier/Agent Immobilier)
(Votre adresse)
(Votre téléphone)
(Votre e-mail)
(Nom de l’agence ou réseau, si applicable)
(Date)


Objet : Proposition d’accompagnement pour la vente de biens immobiliers détenus par votre SCI

Madame, Monsieur [NOM],

Professionnel de l’immobilier au sein de (NOM de votre Agence ou Réseau, si applicable), je me permets de vous adresser la présente afin de vous proposer mes services pour la mise en vente ou l’optimisation de la valorisation des actifs immobiliers détenus par votre SCI.

Conscient des enjeux spécifiques liés à la gestion patrimoniale et fiscale des Sociétés Civiles Immobilières, je vous propose un accompagnement sur-mesure, fondé sur une parfaite connaissance du marché local, une stratégie de commercialisation efficace.

Mon approche se distingue par :
- Une estimation rigoureuse et objective de vos biens,
- La mise en place d’une communication ciblée auprès d’acquéreurs qualifiés,
- Un accompagnement administratif et juridique jusqu’à la signature définitive,
- La possibilité de travailler en toute confidentialité, selon vos contraintes et objectifs.

Je serai ravi d’échanger avec vous lors d’un rendez-vous à votre convenance, afin de mieux cerner vos besoins et vous exposer les solutions que je peux vous apporter.

Dans l’attente de votre retour, je vous remercie de l’attention portée à ma proposition et vous prie d’agréer, Madame, Monsieur, l’expression de mes salutations distinguées.

(Votre prénom et nom)
(Statut : Mandataire Immobilier/Agent Immobilier)
(Coordonnées)`;

    // ✅ NOUVEAU : Fonction pour obtenir le contenu d'email selon le contexte
    const getDefaultEmailContent = () => {
        if (isDPEContext) {
            return `(Votre prénom et nom)
(Statut : Mandataire Immobilier/Agent Immobilier)
(Votre adresse)
(Votre téléphone)
(Votre e-mail)
(Nom de l'agence ou réseau, si applicable)
(Date)


Objet : Proposition d'accompagnement pour la vente de votre bien immobilier

Madame, Monsieur,

Professionnel de l'immobilier au sein de (NOM de votre Agence ou Réseau, si applicable), je me permets de vous adresser la présente afin de vous proposer mes services pour la mise en vente de votre bien immobilier.

J'ai remarqué que votre bien situé à l'adresse suivante a fait l'objet d'un diagnostic de performance énergétique (DPE) récemment. Cette information m'indique que vous pourriez envisager une mise en vente dans un avenir proche.

Conscient des enjeux spécifiques liés à la vente immobilière, je vous propose un accompagnement sur-mesure, fondé sur une parfaite connaissance du marché local et une stratégie de commercialisation efficace.

Mon approche se distingue par :
- Une estimation rigoureuse et objective de votre bien,
- La mise en place d'une communication ciblée auprès d'acquéreurs qualifiés,
- Un accompagnement administratif et juridique jusqu'à la signature définitive,
- La possibilité de travailler en toute confidentialité, selon vos contraintes et objectifs.

Je serai ravi d'échanger avec vous lors d'un rendez-vous à votre convenance, afin de mieux cerner vos besoins et vous exposer les solutions que je peux vous apporter.

Dans l'attente de votre retour, je vous remercie de l'attention portée à ma proposition et vous prie d'agréer, Madame, Monsieur, l'expression de mes salutations distinguées.

(Votre prénom et nom)
(Statut : Mandataire Immobilier/Agent Immobilier)
(Coordonnées)`;
        } else {
            return defaultEmailContent;
        }
    };

    // Système de sélection simple avec un seul tableau
    const SCISelection = {
        storageKey: 'sci_selected_data',
        expiryKey: 'sci_selection_expiry',
        expiryHours: 24,
        selectedSCIs: [], // Un seul tableau avec toutes les données

        // Initialiser le système
        init() {
            this.loadFromStorage();
            this.attachEvents();
            this.updateUI();
        },

        // Charger les sélections depuis localStorage
        loadFromStorage() {
            try {
                const expiryTime = localStorage.getItem(this.expiryKey);
                if (expiryTime && Date.now() > parseInt(expiryTime)) {
                    this.clearStorage();
                    return;
                }

                const stored = localStorage.getItem(this.storageKey);
                if (stored) {
                    this.selectedSCIs = JSON.parse(stored);
                }
            } catch (error) {
                this.clearStorage();
            }
        },

        // Sauvegarder dans localStorage
        saveToStorage() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.selectedSCIs));
                const expiryTime = Date.now() + (this.expiryHours * 60 * 60 * 1000);
                localStorage.setItem(this.expiryKey, expiryTime.toString());
            } catch (error) {
                console.warn('Impossible de sauvegarder les sélections:', error);
            }
        },

        // Nettoyer le stockage
        clearStorage() {
            localStorage.removeItem(this.storageKey);
            localStorage.removeItem(this.expiryKey);
        },

        // Ajouter/retirer une sélection
        toggle(id) {
            const existingIndex = this.selectedSCIs.findIndex(sci => sci.siren === id);
            
            if (existingIndex !== -1) {
                // Supprimer la SCI
                this.selectedSCIs.splice(existingIndex, 1);
            } else {
                // Ajouter la SCI
                const checkbox = document.querySelector(`.send-letter-checkbox[data-siren="${id}"]`);
                if (checkbox) {
                    const data = {
                        siren: id,
                        denomination: checkbox.getAttribute('data-denomination') || '',
                        dirigeant: checkbox.getAttribute('data-dirigeant') || '',
                        adresse: checkbox.getAttribute('data-adresse') || '',
                        ville: checkbox.getAttribute('data-ville') || '',
                        code_postal: checkbox.getAttribute('data-code-postal') || ''
                    };
                    this.selectedSCIs.push(data);
                }
            }
            this.saveToStorage();
            this.updateUI();
        },

        // Vérifier si un ID est sélectionné
        isSelected(id) {
            return this.selectedSCIs.some(sci => sci.siren === id);
        },

        // Obtenir toutes les sélections (même tableau pour tout)
        getSelectedData() {
            return this.selectedSCIs;
        },

        // Compter les sélections
        getCount() {
            return this.selectedSCIs.length;
        },

        // Effacer toutes les sélections
        clearAll() {
            this.selectedSCIs = [];
            this.clearStorage();
            this.updateUI();
            this.updateCheckboxes();
        },

        // Attacher les événements aux checkboxes
        attachEvents() {
            document.addEventListener('change', (e) => {
                if (e.target.classList.contains('send-letter-checkbox')) {
                    const id = e.target.getAttribute('data-siren');
                    if (id) {
                        this.toggle(id);
                    }
                }
            });
        },

        // Mettre à jour l'interface utilisateur
        updateUI() {
            const count = this.getCount();
            
            // Mettre à jour le compteur du bouton
            if (selectedCountSpan) {
                selectedCountSpan.textContent = count;
            }
            
            // Activer/désactiver le bouton campagne
            if (sendLettersBtn) {
                sendLettersBtn.disabled = count === 0;
            }



            // Mettre à jour selectedEntries pour compatibilité
            selectedEntries = this.selectedSCIs;
        },

        // Mettre à jour les checkboxes selon les sélections
        updateCheckboxes() {
            const checkboxes = document.querySelectorAll('.send-letter-checkbox');
            checkboxes.forEach(checkbox => {
                const id = checkbox.getAttribute('data-siren');
                checkbox.checked = this.isSelected(id);
            });
        },

        // Restaurer les sélections sur une nouvelle page
        restoreSelections() {
            this.updateCheckboxes();
            this.updateUI();
        }
    };

    // Fonction de compatibilité pour updateSelectedCount
    function updateSelectedCount() {
        SCISelection.updateUI();
    }

    // Ouvrir le popup
    if (sendLettersBtn) {
        sendLettersBtn.addEventListener('click', function() {
            // ✅ NOUVEAU : Récupérer les données selon le contexte (DPE ou SCI)
            let selectedData = [];
            
            if (isDPEContext) {
                // Utiliser les données DPE
                if (window.getSelectedDPEEntries) {
                    selectedData = window.getSelectedDPEEntries();
                } else if (window.getSelectedEntries) {
                    selectedData = window.getSelectedEntries();
                }
                
                if (selectedData.length === 0) {
                    // Alerte désactivée - ne rien faire
                    return;
                }
            } else {
                // Utiliser les données SCI
                selectedData = SCISelection.selectedSCIs;
                
                if (selectedData.length === 0) {
                    // Alerte désactivée - ne rien faire
                    return;
                }
            }
            
            // Remplir la liste selon le contexte
            if (selectedSciList) {
                selectedSciList.innerHTML = '';
                selectedData.forEach(item => {
                    const li = document.createElement('li');
                    
                    if (isDPEContext) {
                        // Format DPE
                        li.innerHTML = `
                            <strong>${item.adresse}</strong><br>
                            <small>Commune: ${item.commune}</small><br>
                            <small>DPE: ${item.etiquette_dpe} | GES: ${item.etiquette_ges}</small><br>
                            <small>Surface: ${item.surface} | Date: ${item.date_dpe}</small>
                        `;
                    } else {
                        // Format SCI
                        li.innerHTML = `
                            <strong>${item.denomination}</strong><br>
                            <small>Dirigeant: ${item.dirigeant}</small><br>
                            <small>SIREN: ${item.siren}</small><br>
                            <small>${item.adresse}, ${item.ville}</small>
                        `;
                    }
                    selectedSciList.appendChild(li);
                });
            }
            
            // Mettre à jour selectedEntries pour compatibilité avec payment.js
            selectedEntries = selectedData;
            
            // Afficher le popup
            if (lettersPopup) {
                lettersPopup.style.display = 'flex';
            }
            if (step1) {
                step1.style.display = 'block';
            }
            if (step2) {
                step2.style.display = 'none';
            }
        });
    }

    // Navigation vers l'étape 2
    if (toStep2Btn) {
        toStep2Btn.addEventListener('click', function() {
            if (step1) step1.style.display = 'none';
            if (step2) step2.style.display = 'block';
            
            // ✅ NOUVEAU : S'assurer que le contenu de l'étape 2 est généré
            resetStep2Content();
        });
    }

    // Fermer le popup
    if (closePopupBtns && closePopupBtns.length > 0) {
        closePopupBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                if (lettersPopup) {
                    lettersPopup.style.display = 'none';
                }
                resetPopup();
            });
        });
    }

    // Fermer le popup en cliquant sur l'arrière-plan
    if (lettersPopup) {
        lettersPopup.addEventListener('click', function(e) {
            if (e.target === lettersPopup) {
                lettersPopup.style.display = 'none';
                resetPopup();
            }
        });
    }

    function resetPopup() {
        // Réinitialiser les champs
        const campaignTitle = document.getElementById('campaign-title');
        const campaignContent = document.getElementById('campaign-content');
        if (campaignTitle) campaignTitle.value = '';
        if (campaignContent) campaignContent.value = '';
        
        // Revenir à l'étape 1
        if (step1) step1.style.display = 'block';
        if (step2) step2.style.display = 'none';
        
        // Réinitialiser le contenu de l'étape 2 au contenu original
        resetStep2Content();
    }

    function resetStep2Content() {
        if (step2) {
            const campaignTitle = isDPEContext ? 'Campagne DPE 01' : 'Campagne SCI 01';
            const placeholderText = isDPEContext ? 'Ex: Proposition d\'acquisition maison' : 'Ex: Proposition d\'acquisition SCI';
            
            step2.innerHTML = `
                <h2>✍️ Contenu du courriel</h2>
                <p style="color: #666; margin-bottom: 20px;">Rédigez le titre et le contenu de votre courriel</p>
                
                <label for="campaign-title"><strong>Titre de la campagne :</strong></label><br>
                <input type="text" id="campaign-title" style="width:100%; margin-bottom:20px; padding:10px; border:1px solid #ddd; border-radius:4px;" required placeholder="${placeholderText}" value="${campaignTitle}"><br>

                <label for="campaign-content"><strong>Contenu du courriel :</strong></label><br>
                <textarea id="campaign-content" style="width:100%; height:200px; margin-bottom:20px; padding:10px; border:1px solid #ddd; border-radius:4px;" required placeholder="Rédigez votre message...">${getDefaultEmailContent()}</textarea>

                <div style="background: #e7f3ff; padding: 20px; border-radius: 6px; margin-bottom: 25px;">
                    <h4 style="margin-top: 0; color: #0056b3;">💡 Conseils pour votre courriel :</h4>
                    <ul style="margin-bottom: 0; font-size: 14px; color: #495057;">
                        <li> Pour afficher le nom du destinataire sur le courriel, tapez l'index <code style="background:#f8f9fa; padding:2px 4px; border-radius:3px;">[NOM]</code></li>
                        <li>Soyez professionnel et courtois dans votre approche</li>
                        <li>Précisez clairement l'objet de votre demande</li>
                        <li>N'oubliez pas d'ajouter vos coordonnées de contact dans le contenu</li>
                    </ul>
                </div>

                <div style="display: flex; justify-content: center; align-items: flex-start; gap: 15px;">
                    <button id="send-campaign" class="button button-primary button-large">
                        📋 Voir le récapitulatif →
                    </button>
                    <button id="back-to-step-1" class="button" style="background:#FFF!important;  color: #000064!important;">← Précédent</button>
                </div>
            `;
        }
        
        // Réattacher les event listeners
        attachStep2Listeners();
    }

    function attachStep2Listeners() {
        const backToStep1Btn = document.getElementById('back-to-step-1');
        
        if (backToStep1Btn) {
            backToStep1Btn.addEventListener('click', function() {
                if (step2) step2.style.display = 'none';
                if (step1) step1.style.display = 'block';
            });
        }
    }

    // Initialiser le contenu de l'étape 2 seulement si step2 existe
    if (step2) {
        resetStep2Content();
    }

    // Initialiser le système de sélection seulement si les éléments nécessaires existent
    if (typeof SCISelection !== 'undefined') {
        SCISelection.init();
        
        // Restaurer les sélections après un délai pour s'assurer que le DOM est prêt
        setTimeout(() => {
            if (typeof SCISelection !== 'undefined' && SCISelection.restoreSelections) {
                SCISelection.restoreSelections();
            }
        }, 100);
    }

    // ✅ NOUVEAU : Fonction utilitaire pour obtenir les entrées sélectionnées selon le contexte
    window.getSelectedEntries = function() {
        if (isDPEContext) {
            // Retourner les données DPE
            if (window.getSelectedDPEEntries) {
                return window.getSelectedDPEEntries();
            }
            return [];
        } else {
            // Retourner les données SCI
            return SCISelection.selectedSCIs;
        }
    };

    window.resetSciPopup = function() {
        resetPopup();
        SCISelection.clearAll();
    };

    // Fonction exposée pour restaurer les sélections après chargement AJAX
    window.restoreSCISelections = function() {
        setTimeout(() => {
            SCISelection.restoreSelections();
        }, 50);
    };

    // Fonction exposée pour forcer la mise à jour de l'UI
    window.updateSCISelectionUI = function() {
        SCISelection.updateUI();
    };

    // Fonction exposée pour obtenir les données sélectionnées
    window.getSCISelections = function() {
        return SCISelection.selectedSCIs;
    };

    // Fonction exposée pour effacer toutes les sélections
    window.clearSCISelections = function() {
        SCISelection.clearAll();
    };

    // Fonctions de débogage (disponibles en console)
    window.debugSCISelection = {
        getAll: () => SCISelection.selectedSCIs,
        getCount: () => SCISelection.getCount(),
        clear: () => SCISelection.clearAll(),
        add: (id) => SCISelection.toggle(id),
        isSelected: (id) => SCISelection.isSelected(id),
        showStorage: () => {
                    // localStorage data supprimé pour la production
        }
    };
});