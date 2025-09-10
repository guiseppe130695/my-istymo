document.addEventListener('DOMContentLoaded', function() {
    // Attendre que lettre.js soit chargé
    setTimeout(function() {
        attachPaymentHandlers();
    }, 100);
    
    function attachPaymentHandlers() {
        // Remplacer la fonction d'envoi de campagne existante
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'send-campaign') {
                e.preventDefault();
                e.stopPropagation();
                handleCampaignValidation();
            }
        });
    }
    
    function handleCampaignValidation() {
        const campaignTitle = document.getElementById('campaign-title');
        const campaignContent = document.getElementById('campaign-content');
        
        if (!campaignTitle || !campaignContent) {
            showValidationError('Éléments de formulaire introuvables');
            return;
        }
        
        if (!campaignTitle.value.trim() || !campaignContent.value.trim()) {
            showValidationError('Veuillez remplir le titre et le contenu de la campagne');
            return;
        }
        
        const selectedEntries = window.getSelectedEntries ? window.getSelectedEntries() : [];
        
        if (selectedEntries.length === 0) {
            showValidationError('Aucune SCI sélectionnée');
            return;
        }
        
        // Vérifier si WooCommerce est disponible
        if (!sciPaymentData.woocommerce_ready) {
            // Fallback vers l'ancien système d'envoi direct
            handleDirectSending(selectedEntries, campaignTitle.value, campaignContent.value);
            return;
        }
        
        // Passer à l'étape 3 (récapitulatif simplifié)
        showRecapStep(selectedEntries, campaignTitle.value, campaignContent.value);
    }
    
    function handleDirectSending(entries, title, content) {
        if (!confirm('WooCommerce n\'est pas disponible. Voulez-vous envoyer directement les lettres (sans paiement) ?')) {
            return;
        }
        
        const step2 = document.getElementById('step-2');
        
        // Afficher le processus d'envoi direct
        step2.innerHTML = `
            <h2>📬 Envoi en cours</h2>
            <div class="processing-container">
                <div class="processing-icon">⏳</div>
                <div class="processing-text">Génération des PDFs...</div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" id="direct-progress"></div>
                </div>
                <div class="processing-subtext">Préparation des lettres...</div>
            </div>
        `;
        
        // Désactiver le bouton pendant l'envoi
        const sendBtn = document.getElementById('send-letters-btn');
        if (sendBtn) {
            sendBtn.disabled = true;
            sendBtn.textContent = 'Envoi en cours...';
        }
        
        // Préparer les données pour l'envoi
        const campaignData = {
            title: title,
            content: content,
            entries: entries
        };
        
        // Étape 1: Générer les PDFs et créer la campagne en BDD
        const formData = new FormData();
        formData.append('action', 'sci_generer_pdfs');
        formData.append('data', JSON.stringify(campaignData));
        formData.append('nonce', sciPaymentData.nonce);
        
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.files && data.data.campaign_id) {
                // Étape 2: Envoyer chaque lettre via l'API La Poste
                updateDirectProgress(30, 'Envoi des lettres...');
                
                return sendLettersSequentially(data.data.files, entries, campaignData, data.data.campaign_id);
            } else {
                throw new Error('Erreur lors de la génération des PDFs: ' + (data.data || 'Erreur inconnue'));
            }
        })
        .then(results => {
            // Afficher les résultats
            const successCount = results.filter(r => r.success).length;
            const errorCount = results.length - successCount;
            
            updateDirectProgress(100, 'Envoi terminé !');
            
            setTimeout(() => {
                let message = `✅ Campagne terminée !\n\n`;
                message += `📊 Résultats :\n`;
                message += `• ${successCount} lettres envoyées avec succès\n`;
                if (errorCount > 0) {
                    message += `• ${errorCount} erreurs d'envoi\n`;
                }
                message += `\n📋 Consultez le détail dans "SCI > Mes Campagnes"`;
                
                alert(message);
                
                // Fermer le popup et réinitialiser
                document.getElementById('letters-popup').style.display = 'none';
                if (window.resetSciPopup) window.resetSciPopup();
            }, 1000);
            
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'envoi : ' + error.message);
        })
        .finally(() => {
            // Réactiver le bouton
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.textContent = '📬 Créer une campagne (0)';
            }
        });
    }
    
    function updateDirectProgress(percent, text) {
        const progressBar = document.getElementById('direct-progress');
        const statusText = document.querySelector('.processing-text');
        
        if (progressBar) {
            animateProgress(progressBar, progressBar.style.width.replace('%', '') || 0, percent, 500);
        }
        
        if (statusText) {
            statusText.textContent = text;
        }
    }
    
    // Fonction pour envoyer les lettres séquentiellement (reprise de l'ancien code)
    async function sendLettersSequentially(pdfFiles, entries, campaignData, campaignId) {
        const results = [];
        
        for (let i = 0; i < entries.length; i++) {
            const entry = entries[i];
            const pdfFile = pdfFiles[i];
            
            updateDirectProgress(30 + (i / entries.length) * 60, `Envoi vers ${entry.denomination}...`);
            
            try {
                // Télécharger le PDF généré
                const pdfResponse = await fetch(pdfFile.url);
                const pdfBlob = await pdfResponse.blob();
                const pdfBase64 = await blobToBase64(pdfBlob);
                
                // Préparer les données pour l'API La Poste
                const letterData = new FormData();
                letterData.append('action', 'sci_envoyer_lettre_laposte');
                letterData.append('entry', JSON.stringify(entry));
                letterData.append('pdf_base64', pdfBase64);
                letterData.append('campaign_title', campaignData.title);
                letterData.append('campaign_id', campaignId);
                
                // Envoyer via AJAX
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    body: letterData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    results.push({
                        success: true,
                        denomination: entry.denomination,
                        uid: result.data.uid
                    });
                } else {
                    results.push({
                        success: false,
                        denomination: entry.denomination,
                        error: result.data || 'Erreur inconnue'
                    });
                }
                
            } catch (error) {
                results.push({
                    success: false,
                    denomination: entry.denomination,
                    error: error.message
                });
            }
            
            // Petite pause entre les envois
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
        
        return results;
    }
    
    // Fonction utilitaire pour convertir un blob en base64
    function blobToBase64(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
                const base64 = reader.result.split(',')[1];
                resolve(base64);
            };
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }
    
    function showRecapStep(entries, title, content) {
        const step2 = document.getElementById('step-2');
        const sciCount = entries.length;
        
        // Récupérer le prix unitaire depuis PHP
        const unitPrice = parseFloat(sciPaymentData.unit_price || 5.00);
        const totalPrice = (sciCount * unitPrice).toFixed(2);
        
        // Créer l'interface de récapitulatif simplifiée (étape 3)
        const recapHtml = `
            <h2>📋 Récapitulatif de votre campagne</h2>
            
            <div class="campaign-recap">
                <div class="recap-section">
                    <h3>💰 Tarification</h3>
                    <div class="pricing-table">
                        <div class="pricing-row">
                            <span>Nombre de courriers :</span>
                            <span>${sciCount}</span>
                        </div>
                        <div class="pricing-row total-row">
                            <span><strong>Total TTC :</strong></span>
                            <span><strong>${totalPrice}€</strong></span>
                        </div>
                    </div>
                </div>
                
                <div class="recap-section">
                    <h3>📦 Services inclus</h3>
                    <div class="services-list">
                        <div class="service-item">✅ Accompagnement</div>
                        <div class="service-item">✅ Historique complet de vos campagnes</div>
                        <div class="service-item">✅ Support technique inclus</div>
                    </div>
                </div>
            </div>
            
            <div class="recap-buttons">
                <button id="proceed-to-payment" class="button button-primary button-large">
                    💳 Procéder au paiement (${totalPrice}€)
                </button>
            </div>
        `;
        
        step2.innerHTML = recapHtml;
        
        // Event listener pour le bouton de paiement
        document.getElementById('proceed-to-payment').addEventListener('click', function() {
            // Afficher le checkout embarqué dans le popup
            showEmbeddedCheckout(entries, title, content);
        });
    }
    
    function showEmbeddedCheckout(entries, title, content) {
        const step2 = document.getElementById('step-2');
        const sciCount = entries.length;
        const unitPrice = parseFloat(sciPaymentData.unit_price || 5.00);
        const totalPrice = (sciCount * unitPrice).toFixed(2);
        
        // Afficher le loader pendant la création de commande
        step2.innerHTML = `
            <h2>💳 Paiement sécurisé</h2>
            
            <div class="payment-header">
                <div class="payment-summary-compact">
                    <div class="summary-item">
                        <span>Campagne :</span>
                        <span><strong>${escapeHtml(title)}</strong></span>
                    </div>
                    <div class="summary-item">
                        <span>${sciCount} lettre${sciCount > 1 ? 's' : ''} :</span>
                        <span><strong>${totalPrice}€</strong></span>
                    </div>
                </div>
            </div>
            
            <div id="payment-processing">
                <div class="processing-container">
                    <div class="processing-icon">⏳</div>
                    <div class="processing-text">Création de la commande...</div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="payment-progress"></div>
                    </div>
                    <div class="processing-subtext">Préparation du paiement sécurisé...</div>
                </div>
            </div>
            
            <div id="checkout-container" style="display: none;">
            <style>
                            div#payment .form-row .woocommerce #payment #place_order {
                    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
                    color: white !important;
                    border: none !important;
                }
            </style>
                <div id="embedded-checkout">
                    <!-- Le checkout WooCommerce sera chargé ici -->
                </div>
            </div>
            
            <div id="payment-success" style="display: none;">
                <div class="success-container">
                    <div class="success-icon">✅</div>
                    <h3>Paiement confirmé !</h3>
                    <p>Votre campagne est en cours de traitement.</p>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="sending-progress"></div>
                    </div>
                    <div id="sending-status">Préparation de l'envoi...</div>
                    <div class="success-actions">
                        <button id="view-campaigns" class="button button-primary">
                            📋 Voir mes campagnes
                        </button>
                    </div>
                </div>
            </div>
            

        `;
        
        // Animation de la barre de progression
        const progressBar = document.getElementById('payment-progress');
        animateProgress(progressBar, 0, 90, 1500);
        

        
        // Préparer les données
        const campaignData = {
            title: title,
            content: content,
            entries: entries
        };
        
        const formData = new FormData();
        formData.append('action', 'sci_create_order');
        formData.append('campaign_data', JSON.stringify(campaignData));
        formData.append('nonce', sciPaymentData.nonce);
        
        // Créer la commande
        fetch(sciPaymentData.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            animateProgress(progressBar, 90, 100, 300);
            
            if (data.success) {
                setTimeout(() => {
                    // Masquer le processing et afficher le checkout embarqué
                    document.getElementById('payment-processing').style.display = 'none';
                    document.getElementById('checkout-container').style.display = 'block';
                    
                    // Charger le checkout dans l'iframe
                    loadEmbeddedCheckout(data.data.order_id, data.data.checkout_url);
                }, 500);
            } else {
                showPaymentError(data.data || 'Erreur lors de la création de la commande');
                // Retour au récapitulatif en cas d'erreur
                showRecapStep(entries, title, content);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showPaymentError('Erreur réseau lors de la création de la commande');
            // Retour au récapitulatif en cas d'erreur
            showRecapStep(entries, title, content);
        });
    }
    
    function loadEmbeddedCheckout(orderId, checkoutUrl) {
        const checkoutDiv = document.getElementById('embedded-checkout');
        
        // Créer un iframe optimisé pour le checkout
        const iframe = document.createElement('iframe');
        iframe.src = checkoutUrl + '&embedded=1&hide_admin_bar=1';
        iframe.style.width = '100%';
        iframe.style.height = '600px';
        iframe.style.border = 'none';
        iframe.style.borderRadius = '8px';
        iframe.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
        iframe.name = 'checkout-frame';
        iframe.id = 'checkout-iframe';
        
        // Message de chargement avec style amélioré
        checkoutDiv.innerHTML = `
            <div class="checkout-loading">
                <div class="loading-spinner"></div>
                <div class="loading-text">🔒 Chargement du paiement sécurisé...</div>
                <div class="loading-subtext">Connexion sécurisée en cours</div>
            </div>
        `;
        
        // Charger l'iframe après un court délai
        setTimeout(() => {
            checkoutDiv.innerHTML = '';
            checkoutDiv.appendChild(iframe);
            
            // Ajouter les boutons de navigation

            checkoutDiv.appendChild(navigationDiv);
            
            // Event listener pour actualiser
            document.getElementById('refresh-checkout').onclick = function() {
                iframe.src = iframe.src;
            };
        }, 1000);
        
        // Écouter les messages de l'iframe pour détecter le succès du paiement
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'woocommerce_checkout_success') {
                handlePaymentSuccess(orderId);
            } else if (event.data && event.data.type === 'woocommerce_checkout_error') {
                showPaymentError(event.data.message || 'Erreur lors du paiement');
            }
        });
        
        // Polling pour vérifier le statut de la commande
        startPaymentStatusPolling(orderId);
    }
    
    function startPaymentStatusPolling(orderId) {
        const pollInterval = setInterval(() => {
            const formData = new FormData();
            formData.append('action', 'sci_check_order_status');
            formData.append('order_id', orderId);
            formData.append('nonce', sciPaymentData.nonce);
            
            fetch(sciPaymentData.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.status === 'paid') {
                    clearInterval(pollInterval);
                    handlePaymentSuccess(orderId);
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification du statut:', error);
            });
        }, 3000);
        
        // Arrêter le polling après 15 minutes
        setTimeout(() => {
            clearInterval(pollInterval);
        }, 900000);
    }
    
    // ✅ VARIABLE GLOBALE POUR ÉVITER LES DOUBLONS
    let paymentProcessed = false;
    
    function handlePaymentSuccess(orderId) {
        // ✅ VÉRIFICATION ANTI-DOUBLON
        if (paymentProcessed) {
            // Paiement déjà traité, ignoré
            return;
        }
        paymentProcessed = true;
        
        const checkoutContainer = document.getElementById('checkout-container');
        const successDiv = document.getElementById('payment-success');
        
        // Masquer le checkout et afficher le succès
        if (checkoutContainer) checkoutContainer.style.display = 'none';
        if (successDiv) successDiv.style.display = 'block';
        
        // ✅ DÉSACTIVER LE MENU CONTEXTUEL SUR TOUTE LA PAGE
        disableContextMenu();
        
        // ✅ DÉMARRER LA PROGRESSION RÉALISTE
        startRealisticSendingProgress(orderId);
        
        // Event listener pour le bouton "Voir mes campagnes"
        const viewCampaignsBtn = document.getElementById('view-campaigns');
        if (viewCampaignsBtn) {
            viewCampaignsBtn.addEventListener('click', function() {
                // ✅ RÉACTIVER LE MENU CONTEXTUEL AVANT DE QUITTER
                enableContextMenu();
                paymentProcessed = false; // Reset pour la prochaine fois
                
                document.getElementById('letters-popup').style.display = 'none';
                if (window.resetSciPopup) window.resetSciPopup();
                
                window.location.href = sciPaymentData.campaigns_url || (window.location.origin + '/wp-admin/admin.php?page=sci-campaigns');
            });
        }
        
        // ✅ PROGRAMMER LA RÉACTIVATION AUTOMATIQUE APRÈS 60 SECONDES
        setTimeout(() => {
            enableContextMenu();
            paymentProcessed = false; // Reset
        }, 60000);
    }
    
    // ✅ NOUVELLE FONCTION : PROGRESSION RÉALISTE BASÉE SUR LE NOMBRE DE LETTRES
    function startRealisticSendingProgress(orderId) {
        const progressBar = document.getElementById('sending-progress');
        const statusDiv = document.getElementById('sending-status');
        
        if (!progressBar || !statusDiv) {
            console.warn('Éléments de progression non trouvés');
            return;
        }
        
        // Récupérer le nombre de lettres depuis les données de campagne
        const selectedEntries = window.getSelectedEntries ? window.getSelectedEntries() : [];
        const totalLetters = selectedEntries.length;
        
        if (totalLetters === 0) {
            statusDiv.textContent = 'Erreur : Aucune lettre à envoyer';
            return;
        }
        
        let currentLetter = 0;
        let currentProgress = 0;
        
        // ✅ ÉTAPES INITIALES (20% du temps total)
        const initialSteps = [
            { progress: 5, text: 'Validation du paiement...', duration: 1000 },
            { progress: 15, text: 'Génération des PDFs personnalisés...', duration: 2000 },
            { progress: 25, text: 'Préparation des adresses destinataires...', duration: 1500 },
            { progress: 35, text: 'Connexion à l\'API La Poste...', duration: 1000 }
        ];
        
        let stepIndex = 0;
        
        function executeInitialSteps() {
            if (stepIndex < initialSteps.length) {
                const step = initialSteps[stepIndex];
                animateProgress(progressBar, currentProgress, step.progress, 800);
                statusDiv.textContent = step.text;
                currentProgress = step.progress;
                stepIndex++;
                
                setTimeout(executeInitialSteps, step.duration);
            } else {
                // Commencer l'envoi des lettres individuelles
                startLetterSending();
            }
        }
        
        function startLetterSending() {
            const letterProgressRange = 60; // 60% pour l'envoi des lettres (35% à 95%)
            const progressPerLetter = letterProgressRange / totalLetters;
            
            function sendNextLetter() {
                if (currentLetter < totalLetters) {
                    currentLetter++;
                    const letterProgress = 35 + (currentLetter * progressPerLetter);
                    
                    // Afficher le nom de la SCI si disponible
                    let letterName = `lettre ${currentLetter}`;
                    if (selectedEntries[currentLetter - 1] && selectedEntries[currentLetter - 1].denomination) {
                        letterName = selectedEntries[currentLetter - 1].denomination;
                    }
                    
                    animateProgress(progressBar, currentProgress, letterProgress, 1200);
                    statusDiv.textContent = `📤 Envoi ${currentLetter}/${totalLetters} : ${letterName}`;
                    currentProgress = letterProgress;
                    
                    // Temps d'attente réaliste entre les lettres (2-4 secondes)
                    const waitTime = 2000 + Math.random() * 2000;
                    setTimeout(sendNextLetter, waitTime);
                } else {
                    // Finalisation
                    finalizeSending();
                }
            }
            
            sendNextLetter();
        }
        
        function finalizeSending() {
            // Étapes finales
            setTimeout(() => {
                animateProgress(progressBar, currentProgress, 98, 1000);
                statusDiv.textContent = 'Finalisation de l\'envoi...';
            }, 500);
            
            setTimeout(() => {
                animateProgress(progressBar, 98, 100, 800);
                statusDiv.textContent = `${totalLetters} lettre${totalLetters > 1 ? 's' : ''} envoyée${totalLetters > 1 ? 's' : ''} avec succès !`;
            }, 1500);
        }
        
        // Démarrer le processus
        executeInitialSteps();
    }
    
    // ✅ NOUVELLE FONCTION : DÉSACTIVER LE MENU CONTEXTUEL
    function disableContextMenu() {
        // Vérifier si déjà désactivé pour éviter les doublons
        if (document.body.hasAttribute('data-context-menu-disabled')) {
            return;
        }
        
        // Marquer comme désactivé
        document.body.setAttribute('data-context-menu-disabled', 'true');
        
        // Désactiver le clic droit
        document.addEventListener('contextmenu', preventContextMenu, true);
        
        // Désactiver les raccourcis clavier
        document.addEventListener('keydown', preventKeyboardShortcuts, true);
        
        // Désactiver la sélection de texte
        document.body.style.userSelect = 'none';
        document.body.style.webkitUserSelect = 'none';
        document.body.style.mozUserSelect = 'none';
        document.body.style.msUserSelect = 'none';
        
        // Désactiver le glisser-déposer
        document.addEventListener('dragstart', preventDragDrop, true);
        
        // Ajouter un style CSS pour désactiver la sélection
        const style = document.createElement('style');
        style.id = 'disable-context-menu-style';
        style.textContent = `
            * {
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
                -webkit-touch-callout: none !important;
                -webkit-tap-highlight-color: transparent !important;
            }
            
            /* Permettre la sélection uniquement pour les champs de saisie */
            input, textarea, [contenteditable="true"] {
                -webkit-user-select: text !important;
                -moz-user-select: text !important;
                -ms-user-select: text !important;
                user-select: text !important;
            }
        `;
        document.head.appendChild(style);
        
        // Menu contextuel désactivé
    }
    
    // ✅ NOUVELLE FONCTION : RÉACTIVER LE MENU CONTEXTUEL
    function enableContextMenu() {
        // Vérifier si déjà réactivé
        if (!document.body.hasAttribute('data-context-menu-disabled')) {
            return;
        }
        
        // Supprimer le marqueur
        document.body.removeAttribute('data-context-menu-disabled');
        
        // Réactiver le clic droit
        document.removeEventListener('contextmenu', preventContextMenu, true);
        
        // Réactiver les raccourcis clavier
        document.removeEventListener('keydown', preventKeyboardShortcuts, true);
        
        // Réactiver la sélection de texte
        document.body.style.userSelect = '';
        document.body.style.webkitUserSelect = '';
        document.body.style.mozUserSelect = '';
        document.body.style.msUserSelect = '';
        
        // Réactiver le glisser-déposer
        document.removeEventListener('dragstart', preventDragDrop, true);
        
        // Supprimer le style CSS de désactivation
        const style = document.getElementById('disable-context-menu-style');
        if (style) {
            style.remove();
        }
        
        // Menu contextuel réactivé
    }
    
    // ✅ FONCTIONS DE PRÉVENTION
    function preventContextMenu(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }
    
    function preventKeyboardShortcuts(e) {
        // Désactiver F12, Ctrl+Shift+I, Ctrl+U, Ctrl+S, etc.
        if (
            e.key === 'F12' ||
            (e.ctrlKey && e.shiftKey && e.key === 'I') ||
            (e.ctrlKey && e.shiftKey && e.key === 'C') ||
            (e.ctrlKey && e.shiftKey && e.key === 'J') ||
            (e.ctrlKey && e.key === 'u') ||
            (e.ctrlKey && e.key === 's') ||
            (e.ctrlKey && e.key === 'a') ||
            (e.ctrlKey && e.key === 'p')
        ) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }
    
    function preventDragDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    }
    
    function showContentStep(title, content) {
        const step2 = document.getElementById('step-2');
        
        const contentHtml = `
            <h2>✍️ Contenu du courrier</h2>
            <p style="color: #666; margin-bottom: 20px;">Rédigez le titre et le contenu de votre courrier</p>
            
            <label for="campaign-title"><strong>Titre de la campagne :</strong></label><br>
            <input type="text" id="campaign-title" style="width:100%; margin-bottom:20px; padding:10px; border:1px solid #ddd; border-radius:4px;" required placeholder="Ex: Proposition d'acquisition SCI" value="${escapeHtml(title)}"><br>

            <label for="campaign-content"><strong>Contenu du courrier :</strong></label><br>
            <textarea id="campaign-content" style="width:100%; height:200px; margin-bottom:20px; padding:10px; border:1px solid #ddd; border-radius:4px;" required placeholder="Rédigez votre message...">${escapeHtml(content)}</textarea>

            <div style="background: #e7f3ff; padding: 20px; border-radius: 6px; margin-bottom: 25px;">
                <h4 style="margin-top: 0; color: #0056b3;">💡 Conseils pour votre courrier :</h4>
                <ul style="margin-bottom: 0; font-size: 14px; color: #495057;">
                    <li>Pour afficher le nom du destinataire sur le couriel tapez l'index <code style="background:#f8f9fa; padding:2px 4px; border-radius:3px;">[NOM]</code></li>
                    <li>Soyez professionnel et courtois dans votre approche</li>
                    <li>Précisez clairement l'objet de votre demande</li>
                    <li>N'oubliez pas d'ajouter vos coordonnées de contact</li>
                </ul>
            </div>

            <div style="text-align: center;">
                <button id="send-campaign" class="button button-primary button-large">
                    📋 Voir le récapitulatif →
                </button>
            </div>
        `;
        
        step2.innerHTML = contentHtml;
        

    }
    
    function animateProgress(element, from, to, duration) {
        if (!element) return;
        
        const start = parseFloat(from) || 0;
        const end = parseFloat(to);
        const startTime = Date.now();
        
        function update() {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = start + (end - start) * progress;
            
            element.style.width = current + '%';
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    }
    
    function showValidationError(message) {
        // Supprimer les anciennes erreurs
        const existingErrors = document.querySelectorAll('.validation-error');
        existingErrors.forEach(error => error.remove());
        
        // Créer la nouvelle erreur
        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error';
        errorDiv.textContent = message;
        
        // L'insérer avant les boutons
        const step2 = document.getElementById('step-2');
        if (step2) {
            const buttons = step2.querySelector('#send-campaign')?.parentNode || 
                           step2.querySelector('#proceed-to-payment')?.parentNode ||
                           step2.querySelector('#create-order-btn')?.parentNode;
            if (buttons) {
                step2.insertBefore(errorDiv, buttons);
            } else {
                step2.appendChild(errorDiv);
            }
        }
        
        // La supprimer après 5 secondes
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }
    
    function showPaymentError(message) {
        alert('Erreur de paiement : ' + message);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});