/**
 * JavaScript pour la gestion des leads vendeur
 */

jQuery(document).ready(function($) {
    
    // Toggle favori
    $(document).on('click', '.favorite-btn', function(e) {
        e.preventDefault();
        
        var $toggle = $(this);
        var entryId = $toggle.data('entry-id');
        var $row = $toggle.closest('.lead-vendeur-row');
        
        if ($toggle.hasClass('loading')) {
            return; // Éviter les clics multiples
        }
        
        $toggle.addClass('loading');
        
        $.ajax({
            url: simpleFavoritesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'simple_favorites_toggle',
                entry_id: entryId,
                form_id: leadVendeurAjax.form_id,
                nonce: simpleFavoritesAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.action === 'added') {
                        $toggle.addClass('favori-active');
                        $toggle.html('★'); // Étoile pleine
                        $row.addClass('favori-row');
                        showMessage('Lead ajouté aux favoris', 'success');
                    } else {
                        $toggle.removeClass('favori-active');
                        $toggle.html('☆'); // Étoile vide
                        $row.removeClass('favori-row');
                        showMessage('Lead retiré des favoris', 'success');
                    }
                } else {
                    showMessage('Erreur: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Erreur de connexion', 'error');
            },
            complete: function() {
                $toggle.removeClass('loading');
            }
        });
    });
    
    // Voir les détails d'un lead
    $(document).on('click', '.view-lead-details', function(e) {
        e.preventDefault();
        
        var entryId = $(this).data('entry-id');
        
        $.ajax({
            url: leadVendeurAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'lead_vendeur_get_entry_details',
                entry_id: entryId,
                nonce: leadVendeurAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showLeadDetailsModal(response.data);
                } else {
                    showMessage('Erreur: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Erreur de connexion', 'error');
            }
        });
    });
    
    // Fermer le modal
    $(document).on('click', '.lead-details-modal-close, .lead-details-modal', function(e) {
        if (e.target === this) {
            closeLeadDetailsModal();
        }
    });
    
    // Échapper pour fermer le modal
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27) { // Échap
            closeLeadDetailsModal();
        }
    });
    
    /**
     * Extraire l'ID du lead depuis les données Gravity Form
     */
    function extractLeadId(data) {
        // Essayer différentes sources pour l'ID
        if (data.entry_id) {
            return data.entry_id;
        }
        if (data.id) {
            return data.id;
        }
        if (data.entry && data.entry.id) {
            return data.entry.id;
        }
        if (data.entry && data.entry.entry_id) {
            return data.entry.entry_id;
        }
        
        // Chercher dans les données formatées
        if (data.formatted_data) {
            for (var i = 0; i < data.formatted_data.length; i++) {
                var item = data.formatted_data[i];
                if (item.label && item.label.toLowerCase().indexOf('id') !== -1) {
                    return item.value;
                }
            }
        }
        
        // Si rien n'est trouvé, essayer d'extraire depuis l'URL ou d'autres sources
        var currentUrl = window.location.href;
        var idMatch = currentUrl.match(/[?&]id=(\d+)/);
        if (idMatch) {
            return idMatch[1];
        }
        
        // Par défaut, retourner un ID généré
        return 'GF-' + Date.now();
    }
    
    /**
     * Afficher le modal avec les détails du lead (version qui fonctionne)
     */
    function showLeadDetailsModal(data) {
        // Debug: Afficher la structure des données pour trouver l'ID
        console.log('🔍 Structure des données:', data);
        console.log('🔍 data.entry_id:', data.entry_id);
        console.log('🔍 data.id:', data.id);
        console.log('🔍 data.entry:', data.entry);
        
        // Extraire l'ID du lead
        var leadId = extractLeadId(data);
        console.log('🔍 ID extrait:', leadId);
        
        // Supprimer l'ancien modal s'il existe
        $('#lead-details-modal').remove();
        
        // HTML avec le design existant restauré
        var modalHtml = '<div class="lead-details-modal" id="lead-details-modal">' +
            '<div class="lead-details-modal-content">' +
            '<div class="lead-details-modal-header">' +
            '<div class="lead-details-header-left">' +
            '<div class="lead-details-icon">' +
            '<i class="fas fa-users"></i>' +
            '</div>' +
            '<div class="lead-details-title-section">' +
            '<h2>Lead #' + leadId + '</h2>' +
            '<p class="lead-details-subtitle">Informations complètes et actions</p>' +
            '<p class="lead-details-date">Créé le ' + formatDate(data.date_created) + '</p>' +
            '</div>' +
            '</div>' +
            '<div class="lead-details-header-right">' +
            '<span class="lead-details-modal-close">&times;</span>' +
            '</div>' +
            '</div>' +
            '<div class="lead-details-main-content">' +
            '<div class="lead-details-left-column">' +
            '<div id="property-data"></div>' +
            '</div>' +
            '<div class="lead-details-right-column">' +
            '<div id="client-data"></div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
        
        // HTML avec le design existant (commenté pour test)
        /*
        var modalHtml = '<div class="lead-details-modal" id="lead-details-modal">' +
            '<div class="lead-details-modal-content">' +
            '<div class="lead-details-modal-header">' +
            '<div class="lead-details-header-left">' +
            '<div class="lead-details-icon">' +
            '<i class="fas fa-users"></i>' +
            '</div>' +
            '<div class="lead-details-title-section">' +
            '<h2>Lead #' + data.entry_id + '</h2>' +
            '<p class="lead-details-subtitle">Informations complètes et actions</p>' +
            '<p class="lead-details-date">Créé le ' + formatDate(data.date_created) + '</p>' +
            '</div>' +
            '</div>' +
            '<div class="lead-details-header-right">' +
            '<span class="lead-details-modal-close">&times;</span>' +
            '</div>' +
            '</div>' +
            '<div class="lead-details-main-content">' +
            '<div class="lead-details-left-column">' +
            '<div class="lead-details-info-section">' +
            '<div class="lead-details-section-header">' +
            '<i class="fas fa-home"></i>' +
            '<h3>Informations sur le bien</h3>' +
            '</div>' +
            '<div class="lead-details-info-grid" id="property-data"></div>' +
            '</div>' +
            '</div>' +
            '<div class="lead-details-right-column">' +
            '<div class="lead-details-info-section">' +
            '<div class="lead-details-section-header">' +
            '<i class="fas fa-info-circle"></i>' +
            '<h3>Informations sur le lead</h3>' +
            '</div>' +
            '<div class="lead-details-info-grid" id="client-data"></div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
        */
        
        // Ajouter au DOM
        $('body').append(modalHtml);
        
        // Afficher le modal
        $('#lead-details-modal').fadeIn(200);
        
        // Remplir les données avec un délai pour s'assurer que le DOM est prêt
        setTimeout(function() {
            fillDataBasic(data);
        }, 100);
        
        // Gestion de la fermeture
        $('.lead-details-modal-close').click(function() {
            $('#lead-details-modal').fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // Fermer en cliquant sur le fond
        $('#lead-details-modal').click(function(e) {
            if (e.target === this) {
                $('#lead-details-modal').fadeOut(200, function() {
                    $(this).remove();
                });
            }
        });
    }
    
    /**
     * Remplir les données organisées par catégories
     */
    function fillDataBasic(data) {
        console.log('🔍 fillDataBasic appelée avec:', data);
        
        var propertyHtml = '';
        var clientHtml = '';
        
        if (data.formatted_data && data.formatted_data.length > 0) {
            console.log('✅ Données réelles trouvées:', data.formatted_data.length, 'éléments');
            
            // Organiser les données par catégories
            var categories = organizeDataByCategories(data.formatted_data);
            
            // Colonne gauche - Informations sur le bien (2 sections)
            propertyHtml = buildPropertySections(categories, data);
            
            // Colonne droite - Informations sur le lead
            clientHtml = buildClientSection(categories);
            
        } else {
            console.log('❌ Aucune donnée réelle, utilisation des données de test');
            
            // Données de test organisées
            propertyHtml = buildTestPropertySections();
            clientHtml = buildTestClientSection();
        }
        
        // Mettre à jour le DOM
        console.log('🔍 HTML bien généré:', propertyHtml.length, 'caractères');
        console.log('🔍 HTML client généré:', clientHtml.length, 'caractères');
        
        // Vérifier que les sélecteurs existent
        console.log('🔍 Sélecteur #property-data trouvé:', $('#property-data').length);
        console.log('🔍 Sélecteur #client-data trouvé:', $('#client-data').length);
        
        // Forcer l'affichage
        $('#property-data').html(propertyHtml);
        $('#client-data').html(clientHtml);
        
        // Vérifier que le contenu a été ajouté
        console.log('🔍 Contenu property-data après injection:', $('#property-data').html().length, 'caractères');
        console.log('🔍 Contenu client-data après injection:', $('#client-data').html().length, 'caractères');
        
        console.log('✅ DOM mis à jour avec données organisées');
    }
    
    /**
     * Organiser les données par catégories selon les IDs de champs
     */
    function organizeDataByCategories(formattedData) {
        var categories = {
            general: [],      // Informations générales sur le bien
            technical: [],    // Détails techniques et état du bien
            lead: []         // Informations sur le lead
        };
        
        // IDs des champs par catégorie
        var generalFieldIds = [6, 50, 4, 10, 62, 24, 29, 30, 52, 57, 63, 9, 18, 61, 12, 13, 20, 40, 59, 38, 39];
        var technicalFieldIds = [11, 58, 23, 32, 25, 31, 26, 33, 53, 55];
        
        console.log('🔍 Début de l\'organisation des données:', formattedData.length, 'éléments');
        
        for (var i = 0; i < formattedData.length; i++) {
            var item = formattedData[i];
            if (!item.value || !item.value.trim()) {
                console.log('🔍 Champ vide ignoré:', item.label);
                continue;
            }
            
            // Filtrer le champ "Site Web" avec l'URL spécifique
            if (isSiteWebField(item)) {
                console.log('🔍 Champ "Site Web" filtré:', item.label, '=', item.value);
                continue; // Ignorer ce champ
            }
            
            // Filtrer les champs indésirables
            if (isUnwantedField(item)) {
                console.log('🔍 Champ indésirable filtré:', item.label, '=', item.value);
                continue; // Ignorer ce champ
            }
            
            var fieldId = item.field_id || item.id;
            var label = item.label.toLowerCase();
            
            console.log('🔍 Traitement du champ:', item.label, 'ID:', fieldId);
            
            // Classification par ID de champ
            if (generalFieldIds.indexOf(parseInt(fieldId)) !== -1) {
                categories.general.push(item);
                console.log('✅ Ajouté aux informations générales:', item.label);
            } else if (technicalFieldIds.indexOf(parseInt(fieldId)) !== -1) {
                categories.technical.push(item);
                console.log('✅ Ajouté aux détails techniques:', item.label);
            } else {
                // Classification par mots-clés pour les champs sans ID ou non reconnus
                if (isGeneralPropertyField(label, fieldId)) {
                    categories.general.push(item);
                    console.log('✅ Ajouté aux informations générales (par mots-clés):', item.label);
                } else if (isTechnicalPropertyField(label, fieldId)) {
                    categories.technical.push(item);
                    console.log('✅ Ajouté aux détails techniques (par mots-clés):', item.label);
                } else {
                    categories.lead.push(item);
                    console.log('✅ Ajouté aux informations lead:', item.label);
                }
            }
        }
        
        console.log('🔍 Résultat de la classification:');
        console.log('- Informations générales:', categories.general.length);
        console.log('- Détails techniques:', categories.technical.length);
        console.log('- Informations lead:', categories.lead.length);
        
        return categories;
    }
    
    /**
     * Vérifier si un champ est le champ "Site Web" à filtrer
     */
    function isSiteWebField(item) {
        var label = item.label.toLowerCase();
        var value = item.value.toLowerCase();
        
        // Vérifier si c'est un champ "Site Web" ou similaire
        var siteWebKeywords = ['site web', 'site', 'website', 'url', 'lien', 'link'];
        var isSiteWebLabel = false;
        
        for (var i = 0; i < siteWebKeywords.length; i++) {
            if (label.indexOf(siteWebKeywords[i]) !== -1) {
                isSiteWebLabel = true;
                break;
            }
        }
        
        // Vérifier si la valeur contient l'URL spécifique (plus flexible)
        var isSpecificUrl = value.indexOf('immo-data.fr') !== -1 || 
                           value.indexOf('rapport') !== -1 || 
                           value.indexOf('5c46d089-a7c4-478d-9927-2ef5c29630f2') !== -1;
        
        // Debug pour voir ce qui est filtré
        if (isSiteWebLabel) {
            console.log('🔍 Champ Site Web détecté:', item.label, '=', item.value);
            console.log('🔍 isSpecificUrl:', isSpecificUrl);
        }
        
        return isSiteWebLabel && isSpecificUrl;
    }
    
    /**
     * Vérifier si un champ est indésirable et doit être filtré
     */
    function isUnwantedField(item) {
        var label = item.label.toLowerCase();
        var value = item.value.toLowerCase();
        
        // Champs à filtrer par label
        var unwantedLabels = [
            'sans titre',
            'en cochant la case, vous acceptez nos conditions générales d\'utilisation',
            'conditions générales',
            'acceptez nos conditions',
            'checkbox',
            'case à cocher'
        ];
        
        // Vérifier par label
        for (var i = 0; i < unwantedLabels.length; i++) {
            if (label.indexOf(unwantedLabels[i]) !== -1) {
                return true;
            }
        }
        
        // Vérifier par valeur (pour les champs avec des valeurs spécifiques)
        var unwantedValues = [
            'en cochant la case, vous acceptez nos conditions générales d\'utilisation',
            'conditions générales d\'utilisation',
            'j\'accepte les conditions'
        ];
        
        for (var j = 0; j < unwantedValues.length; j++) {
            if (value.indexOf(unwantedValues[j]) !== -1) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extraire l'URL d'analyse depuis les données du formulaire
     */
    function getAnalysisUrlFromData(data) {
        if (!data.formatted_data || data.formatted_data.length === 0) {
            return null;
        }
        
        for (var i = 0; i < data.formatted_data.length; i++) {
            var item = data.formatted_data[i];
            if (!item.value || !item.value.trim()) continue;
            
            var label = item.label.toLowerCase();
            var value = item.value;
            
            // Chercher un champ "Site Web" ou similaire
            var siteWebKeywords = ['site web', 'site', 'website', 'url', 'lien', 'link', 'rapport', 'analyse'];
            var isSiteWebField = false;
            
            for (var j = 0; j < siteWebKeywords.length; j++) {
                if (label.indexOf(siteWebKeywords[j]) !== -1) {
                    isSiteWebField = true;
                    break;
                }
            }
            
            // Si c'est un champ de type site web et qu'il contient une URL valide
            if (isSiteWebField && isValidUrl(value)) {
                console.log('🔍 URL d\'analyse trouvée:', value);
                return value;
            }
        }
        
        console.log('❌ Aucune URL d\'analyse trouvée');
        return null;
    }
    
    /**
     * Vérifier si une chaîne est une URL valide
     */
    function isValidUrl(string) {
        try {
            // Vérifier si c'est une URL valide
            var url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            // Si ce n'est pas une URL valide, vérifier si ça ressemble à une URL
            return string.indexOf('http') === 0 || string.indexOf('www.') === 0 || string.indexOf('.fr') !== -1 || string.indexOf('.com') !== -1;
        }
    }
    
    /**
     * Construire les sections pour les informations sur le bien
     */
    function buildPropertySections(categories, data) {
        var html = '';
        
        // Section 1: Informations générales sur le bien
        html += '<div class="lead-details-info-section">';
        html += '<div class="lead-details-section-header">';
        html += '<i class="fas fa-home"></i>';
        html += '<h3>Informations générales sur le bien</h3>';
        html += '</div>';
        
        if (categories.general.length > 0) {
            for (var i = 0; i < categories.general.length; i++) {
                var item = categories.general[i];
                html += buildFieldItem(item, '#007cba');
            }
        } else {
            html += '<div class="no-data-message">Aucune information générale disponible</div>';
        }
        html += '</div>';
        
        // Section 2: Détails techniques et état du bien
        html += '<div class="lead-details-info-section">';
        html += '<div class="lead-details-section-header">';
        html += '<i class="fas fa-cogs"></i>';
        html += '<h3>Détails techniques et état du bien</h3>';
        html += '</div>';
        
        if (categories.technical.length > 0) {
            for (var j = 0; j < categories.technical.length; j++) {
                var item2 = categories.technical[j];
                html += buildFieldItem(item2, '#28a745');
            }
        }
        
        // ✅ NOUVEAU : Section "Analyser le bien" avec le style demandé
        var analysisUrl = getAnalysisUrlFromData(data);
        if (analysisUrl) {
            html += '<div class="my-istymo-section my-istymo-analyze-section">';
            html += '<h5 class="my-istymo-section-title"><i class="fas fa-chart-line"></i> Analyser le bien</h5>';
            html += '<div class="my-istymo-analyze-actions">';
            html += '<button type="button" class="my-istymo-btn-analyze" onclick="openPropertyReport(\'' + escapeHtml(analysisUrl) + '\')">';
            html += '<i class="fas fa-external-link-alt"></i> Ouvrir le rapport';
            html += '</button>';
            html += '</div>';
            html += '</div>';
        } else {
            // Afficher "Aucune donnée disponible" si pas d'URL d'analyse
            if (categories.technical.length === 0) {
                html += '<div class="no-data-message">Aucune donnée disponible</div>';
            }
        }
        
        html += '</div>';
        
        return html;
    }
    
    /**
     * Construire la section pour les informations sur le lead
     */
    function buildClientSection(categories) {
        var html = '';
        
        html += '<div class="lead-details-info-section">';
        html += '<div class="lead-details-section-header">';
        html += '<i class="fas fa-user"></i>';
        html += '<h3>Informations sur le lead</h3>';
        html += '</div>';
        
        if (categories.lead.length > 0) {
            for (var i = 0; i < categories.lead.length; i++) {
                var item = categories.lead[i];
                html += buildFieldItem(item, '#dc3545');
            }
        } else {
            html += '<div class="no-data-message">Aucune information sur le lead disponible</div>';
        }
        html += '</div>';
        
        return html;
    }
    
    /**
     * Construire un élément de champ
     */
    function buildFieldItem(item, borderColor) {
        var html = '<div class="lead-details-info-item">';
        var displayLabel = item.label;
        
        // Remplacer "Lead Label" par "Type de lead"
        if (displayLabel.toLowerCase() === 'lead label') {
            displayLabel = 'Type de lead';
        }
        
        html += '<div class="lead-details-info-label">' + escapeHtml(displayLabel) + '</div>';
        html += '<div class="lead-details-info-value">' + escapeHtml(item.value) + '</div>';
        html += '</div>';
        return html;
    }
    
    /**
     * Construire les sections de test pour le bien
     */
    function buildTestPropertySections() {
        var html = '';
        
        // Section 1: Informations générales
        html += '<div class="lead-details-info-section">';
        html += '<div class="lead-details-section-header">';
        html += '<i class="fas fa-home"></i>';
        html += '<h3>Informations générales sur le bien</h3>';
        html += '</div>';
        html += buildFieldItem({label: 'Type de bien', value: 'Maison'}, '#007cba');
        html += buildFieldItem({label: 'Adresse', value: '123 Rue de la Paix, Paris'}, '#007cba');
        html += buildFieldItem({label: 'Surface', value: '120 m²'}, '#007cba');
        html += '</div>';
        
        // Section 2: Détails techniques
        html += '<div class="lead-details-info-section">';
        html += '<div class="lead-details-section-header">';
        html += '<i class="fas fa-cogs"></i>';
        html += '<h3>Détails techniques et état du bien</h3>';
        html += '</div>';
        html += buildFieldItem({label: 'Année de construction', value: '1995'}, '#28a745');
        html += buildFieldItem({label: 'Bien rénové', value: 'Oui'}, '#28a745');
        
        // Pour les données de test, afficher "Aucune donnée disponible" car pas d'URL d'analyse
        html += '<div class="no-data-message">Aucune donnée disponible</div>';
        
        html += '</div>';
        
        return html;
    }
    
    /**
     * Construire la section de test pour le lead
     */
    function buildTestClientSection() {
        var html = '';
        
        html += '<div class="lead-details-info-section">';
        html += '<div class="lead-details-section-header">';
        html += '<i class="fas fa-user"></i>';
        html += '<h3>Informations sur le lead</h3>';
        html += '</div>';
        html += buildFieldItem({label: 'Nom', value: 'Jean Dupont'}, '#dc3545');
        html += buildFieldItem({label: 'Téléphone', value: '06 12 34 56 78'}, '#dc3545');
        html += buildFieldItem({label: 'Email', value: 'jean.dupont@email.com'}, '#dc3545');
        html += '</div>';
        
        return html;
    }
    
    /**
     * Déterminer si un champ appartient aux informations générales sur le bien
     */
    function isGeneralPropertyField(label, fieldId) {
        // IDs des champs pour les informations générales
        var generalFieldIds = [6, 50, 4, 10, 62, 24, 29, 30, 52, 57, 63, 9, 18, 61, 12, 13, 20, 40, 59, 38, 39];
        
        // Vérifier par ID si disponible
        if (fieldId && generalFieldIds.indexOf(parseInt(fieldId)) !== -1) {
            return true;
        }
        
        // Vérifier par mots-clés dans le label
        var labelLower = label.toLowerCase();
        var generalKeywords = [
            'type de bien', 'emplacement', 'adresse', 'surface', 'terrain', 'viabilisation',
            'maison', 'appartement', 'commerce', 'caractéristiques', 'pièces', 'véhicules', 'parking'
        ];
        
        for (var i = 0; i < generalKeywords.length; i++) {
            if (labelLower.indexOf(generalKeywords[i]) !== -1) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Déterminer si un champ appartient aux détails techniques
     */
    function isTechnicalPropertyField(label, fieldId) {
        // IDs des champs pour les détails techniques
        var technicalFieldIds = [11, 58, 23, 32, 25, 31, 26, 33, 53, 55];
        
        // Vérifier par ID si disponible
        if (fieldId && technicalFieldIds.indexOf(parseInt(fieldId)) !== -1) {
            return true;
        }
        
        // Vérifier par mots-clés dans le label
        var labelLower = label.toLowerCase();
        var technicalKeywords = [
            'étage', 'construction', 'rénové', 'rénovation', 'équipements', 'commentaires', 'précisions'
        ];
        
        for (var i = 0; i < technicalKeywords.length; i++) {
            if (labelLower.indexOf(technicalKeywords[i]) !== -1) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Remplir les données de manière ultra-simple (sans tri complexe)
     */
    function fillDataSimple(data) {
        var formattedData = data.formatted_data;
        var propertyHtml = '';
        var clientHtml = '';
        var item, label, value;
        
        // Remplissage ultra-rapide sans tri complexe
        for (var i = 0, len = formattedData.length; i < len; i++) {
            item = formattedData[i];
            if (!item.value || !item.value.trim()) continue;
            
            label = item.label;
            value = item.value;
            
            // Tri simple basé sur quelques mots-clés seulement
            if (isSimplePropertyField(label)) {
                propertyHtml += '<div class="lead-details-info-item">' +
                    '<div class="lead-details-info-label">' + escapeHtml(label) + '</div>' +
                    '<div class="lead-details-info-value">' + escapeHtml(value) + '</div>' +
                    '</div>';
            } else {
                clientHtml += '<div class="lead-details-info-item">' +
                    '<div class="lead-details-info-label">' + escapeHtml(label) + '</div>' +
                    '<div class="lead-details-info-value">' + escapeHtml(value) + '</div>' +
                    '</div>';
            }
        }
        
        // Mise à jour DOM directe
        $('#property-panel').html(propertyHtml);
        $('#client-panel').html(clientHtml);
    }
    
    /**
     * Détection ultra-simple des champs propriété (seulement les plus évidents)
     */
    function isSimplePropertyField(label) {
        var lowerLabel = label.toLowerCase();
        return lowerLabel.indexOf('adresse') !== -1 ||
               lowerLabel.indexOf('surface') !== -1 ||
               lowerLabel.indexOf('type') !== -1 ||
               lowerLabel.indexOf('prix') !== -1 ||
               lowerLabel.indexOf('chambre') !== -1;
    }
    
    /**
     * Construire le HTML du modal de manière ultra-simple
     */
    function buildSimpleModalHTML(data) {
        return '<div class="lead-details-modal" id="lead-details-modal" style="display:none;">' +
            '<div class="lead-details-modal-content">' +
            '<div class="lead-details-modal-header">' +
            '<div class="lead-details-header-left">' +
            '<div class="lead-details-icon"><i class="fas fa-users"></i></div>' +
            '<div class="lead-details-title-section">' +
            '<h2>Lead #' + data.entry_id + '</h2>' +
            '<p class="lead-details-subtitle">Informations complètes et actions</p>' +
            '<p class="lead-details-date">Créé le ' + formatDate(data.date_created) + '</p>' +
            '</div>' +
            '</div>' +
            '<div class="lead-details-header-right">' +
            '<span class="lead-details-modal-close">&times;</span>' +
            '</div>' +
            '</div>' +
            '<div class="lead-details-main-content">' +
            '<div class="lead-details-left-column">' +
            '<div class="lead-details-info-section">' +
            '<div class="lead-details-section-header">' +
            '<i class="fas fa-home"></i>' +
            '<h3>Informations sur le bien</h3>' +
            '</div>' +
            '<div class="lead-details-info-grid" id="property-panel"></div>' +
            '</div>' +
            '</div>' +
            '<div class="lead-details-right-column">' +
            '<div class="lead-details-info-section">' +
            '<div class="lead-details-section-header">' +
            '<i class="fas fa-info-circle"></i>' +
            '<h3>Informations sur le lead</h3>' +
            '</div>' +
            '<div class="lead-details-info-grid" id="client-panel"></div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
    }
    
    /**
     * Afficher l'indicateur de chargement
     */
    function showLoadingIndicator() {
        // Pas besoin d'indicateur séparé, le modal s'affiche directement
    }
    
    /**
     * Masquer l'indicateur de chargement
     */
    function hideLoadingIndicator() {
        // Pas nécessaire car le modal s'affiche directement
    }
    
    /**
     * Initialiser les panels avec les données triées (ultra-optimisé)
     */
    function initializePanels(data) {
        // Cache des données pour éviter les recalculs
        var formattedData = data.formatted_data;
        var propertyData = [];
        var clientData = [];
        var item, label, value, trimmedValue;
        
        // Ultra-optimisation : une seule boucle avec cache des variables
        for (var i = 0, len = formattedData.length; i < len; i++) {
            item = formattedData[i];
            value = item.value;
            
            // Vérification rapide de la valeur
            if (!value) continue;
            trimmedValue = value.trim();
            if (!trimmedValue) continue;
            
            label = item.label.toLowerCase();
            
            // Détection ultra-rapide avec cache
            if (isPropertyFieldFast(label, trimmedValue)) {
                propertyData.push(item);
            } else if (isClientFieldFast(label, trimmedValue)) {
                clientData.push(item);
            }
        }
        
        // Remplissage ultra-rapide avec batch DOM
        fillPanelsBatch(propertyData, clientData);
    }
    
    /**
     * Remplir les deux panels en une seule opération DOM (ultra-rapide)
     */
    function fillPanelsBatch(propertyData, clientData) {
        // Construction simultanée des deux panels
        var propertyContent = buildPanelContent(propertyData, 'home', 'Aucune information sur le bien disponible');
        var clientContent = buildPanelContent(clientData, 'user', 'Aucune information sur le client disponible');
        
        // Mise à jour DOM en une seule opération
        $('#property-panel').html(propertyContent);
        $('#client-panel').html(clientContent);
    }
    
    /**
     * Construire le contenu d'un panel de manière ultra-optimisée
     */
    function buildPanelContent(data, iconClass, noDataMessage) {
        if (data.length === 0) {
            return [
                '<div class="panel-data-grid">',
                '<div class="panel-no-data">',
                '<i class="fas fa-' + iconClass + '"></i>',
                '<p>' + noDataMessage + '</p>',
                '</div>',
                '</div>'
            ].join('');
        }
        
        var content = ['<div class="panel-data-grid">'];
        var item, icon, label, value, formattedPhone;
        
        for (var i = 0, len = data.length; i < len; i++) {
            item = data[i];
            icon = getFieldIconFast(item.label);
            label = escapeHtml(item.label);
            value = item.value;
            
            content.push('<div class="panel-data-item">');
            content.push('<div class="panel-data-label">');
            content.push('<i class="fas fa-' + icon + '"></i>');
            content.push('<span>' + label + '</span>');
            content.push('</div>');
            
            if (isPhoneFieldFast(item.label, value)) {
                formattedPhone = formatPhoneForDialingFast(value);
                content.push('<div class="panel-data-value">');
                content.push('<a href="tel:' + escapeHtml(formattedPhone) + '" class="phone-link-modal" title="Appeler directement">');
                content.push('<i class="fas fa-phone"></i>');
                content.push(escapeHtml(value));
                content.push('</a>');
                content.push('</div>');
            } else if (isAddressFieldFast(item.label, value)) {
                content.push('<div class="panel-data-value">');
                content.push(formatAddressWithCityFast(value, ''));
                content.push('</div>');
            } else {
                content.push('<div class="panel-data-value">' + escapeHtml(value) + '</div>');
            }
            
            content.push('</div>');
        }
        
        content.push('</div>');
        return content.join('');
    }
    
    /**
     * Remplir le panel des informations sur le bien (optimisé)
     */
    function fillPropertyPanelOptimized(propertyData) {
        var $panel = $('#property-panel');
        var content = [];
        var item, icon, label, value, formattedPhone;
        
        content.push('<div class="panel-data-grid">');
        
        if (propertyData.length === 0) {
            content.push(
                '<div class="panel-no-data">',
                '<i class="fas fa-home"></i>',
                '<p>Aucune information sur le bien disponible</p>',
                '</div>'
            );
        } else {
            // Optimisation : construction du HTML en une seule fois
            for (var i = 0, len = propertyData.length; i < len; i++) {
                item = propertyData[i];
                icon = getFieldIcon(item.label);
                label = escapeHtml(item.label);
                value = item.value;
                
                content.push('<div class="panel-data-item">');
                content.push('<div class="panel-data-label">');
                content.push('<i class="fas fa-' + icon + '"></i>');
                content.push('<span>' + label + '</span>');
                content.push('</div>');
                
                if (isPhoneField(item.label, value)) {
                    formattedPhone = formatPhoneForDialing(value);
                    content.push('<div class="panel-data-value">');
                    content.push('<a href="tel:' + escapeHtml(formattedPhone) + '" class="phone-link-modal" title="Appeler directement">');
                    content.push('<i class="fas fa-phone"></i>');
                    content.push(escapeHtml(value));
                    content.push('</a>');
                    content.push('</div>');
                } else if (isAddressField(item.label, value)) {
                    content.push('<div class="panel-data-value">');
                    content.push(formatAddressWithCity(value, ''));
                    content.push('</div>');
                } else {
                    content.push('<div class="panel-data-value">' + escapeHtml(value) + '</div>');
                }
                
                content.push('</div>');
            }
        }
        
        content.push('</div>');
        
        // Une seule opération DOM
        $panel.html(content.join(''));
    }
    
    /**
     * Remplir le panel des informations sur le client (optimisé)
     */
    function fillClientPanelOptimized(clientData) {
        var $panel = $('#client-panel');
        var content = [];
        var item, icon, label, value, formattedPhone;
        
        content.push('<div class="panel-data-grid">');
        
        if (clientData.length === 0) {
            content.push(
                '<div class="panel-no-data">',
                '<i class="fas fa-user"></i>',
                '<p>Aucune information sur le client disponible</p>',
                '</div>'
            );
        } else {
            // Optimisation : construction du HTML en une seule fois
            for (var i = 0, len = clientData.length; i < len; i++) {
                item = clientData[i];
                icon = getFieldIcon(item.label);
                label = escapeHtml(item.label);
                value = item.value;
                
                content.push('<div class="panel-data-item">');
                content.push('<div class="panel-data-label">');
                content.push('<i class="fas fa-' + icon + '"></i>');
                content.push('<span>' + label + '</span>');
                content.push('</div>');
                
                if (isPhoneField(item.label, value)) {
                    formattedPhone = formatPhoneForDialing(value);
                    content.push('<div class="panel-data-value">');
                    content.push('<a href="tel:' + escapeHtml(formattedPhone) + '" class="phone-link-modal" title="Appeler directement">');
                    content.push('<i class="fas fa-phone"></i>');
                    content.push(escapeHtml(value));
                    content.push('</a>');
                    content.push('</div>');
                } else {
                    content.push('<div class="panel-data-value">' + escapeHtml(value) + '</div>');
                }
                
                content.push('</div>');
            }
        }
        
        content.push('</div>');
        
        // Une seule opération DOM
        $panel.html(content.join(''));
    }
    
    // Cache global pour les mots-clés (évite la recréation à chaque appel)
    var propertyKeywordsCache = [
        'adresse', 'address', 'rue', 'street', 'voie', 'avenue', 'boulevard', 'place', 'lieu',
        'surface', 'm²', 'm2', 'superficie', 'taille', 'dimension',
        'type', 'bâtiment', 'building', 'maison', 'appartement', 'studio', 'loft',
        'chambre', 'chambres', 'pièce', 'pièces', 'salle de bain', 'salle de bains',
        'étage', 'etage', 'niveau', 'ascenseur', 'balcon', 'terrasse', 'jardin',
        'parking', 'garage', 'cave', 'grenier', 'sous-sol',
        'prix', 'valeur', 'estimation', 'budget',
        'énergie', 'energie', 'classe', 'dpe', 'diagnostic',
        'exposition', 'orientation', 'vue', 'calme',
        'proximité', 'proximite', 'transport', 'métro', 'metro', 'bus', 'gare',
        'école', 'ecole', 'commerce', 'pharmacie', 'médecin', 'medecin'
    ];
    
    /**
     * Déterminer si un champ appartient aux informations sur le bien (ultra-rapide)
     */
    function isPropertyFieldFast(label, value) {
        // Vérification ultra-rapide avec cache
        for (var i = 0, len = propertyKeywordsCache.length; i < len; i++) {
            if (label.indexOf(propertyKeywordsCache[i]) !== -1) {
                return true;
            }
        }
        
        // Vérification rapide d'adresse (évite l'appel de fonction)
        return /\d/.test(value) && /[a-zA-ZÀ-ÿ]/.test(value) && value.length > 5;
    }
    
    // Cache global pour les mots-clés client (évite la recréation à chaque appel)
    var clientKeywordsCache = [
        'nom', 'prénom', 'prenom', 'nom de famille', 'nom de famille',
        'téléphone', 'telephone', 'phone', 'tel', 'mobile', 'portable', 'fixe',
        'email', 'mail', 'courriel', 'adresse email', 'adresse mail',
        'société', 'societe', 'entreprise', 'company', 'firm',
        'fonction', 'poste', 'profession', 'métier', 'metier',
        'âge', 'age', 'date de naissance', 'naissance',
        'situation', 'familiale', 'marié', 'marie', 'célibataire', 'celibataire',
        'enfant', 'enfants', 'foyer',
        'revenu', 'salaire', 'budget', 'financement',
        'urgence', 'urgent', 'disponibilité', 'disponibilite',
        'préférence', 'preference', 'souhait', 'besoin', 'critère', 'critere',
        'commentaire', 'message', 'note', 'remarque'
    ];
    
    /**
     * Déterminer si un champ appartient aux informations sur le client (ultra-rapide)
     */
    function isClientFieldFast(label, value) {
        // Vérification ultra-rapide avec cache
        for (var i = 0, len = clientKeywordsCache.length; i < len; i++) {
            if (label.indexOf(clientKeywordsCache[i]) !== -1) {
                return true;
            }
        }
        
        // Vérification rapide de téléphone (évite l'appel de fonction)
        var cleanValue = value.replace(/[^0-9+]/g, '');
        return /^(0[1-9]|\+33[1-9]|33[1-9])[0-9]{8}$/.test(cleanValue);
    }
    
    // Cache des icônes pour éviter les recalculs
    var iconCache = {};
    
    /**
     * Fonction pour obtenir l'icône appropriée selon le type de champ (ultra-rapide)
     */
    function getFieldIconFast(fieldLabel) {
        // Vérifier le cache d'abord
        if (iconCache[fieldLabel]) {
            return iconCache[fieldLabel];
        }
        
        var label = fieldLabel.toLowerCase();
        var icon = 'info-circle'; // valeur par défaut
        
        // Vérifications ultra-rapides avec cache
        if (label.indexOf('adresse') !== -1 || label.indexOf('address') !== -1) {
            icon = 'map-marker-alt';
        } else if (label.indexOf('téléphone') !== -1 || label.indexOf('phone') !== -1) {
            icon = 'phone';
        } else if (label.indexOf('email') !== -1 || label.indexOf('mail') !== -1) {
            icon = 'envelope';
        } else if (label.indexOf('surface') !== -1 || label.indexOf('m²') !== -1) {
            icon = 'ruler';
        } else if (label.indexOf('type') !== -1 || label.indexOf('bâtiment') !== -1) {
            icon = 'building';
        } else if (label.indexOf('date') !== -1) {
            icon = 'calendar';
        } else if (label.indexOf('analyse') !== -1 || label.indexOf('lien') !== -1) {
            icon = 'link';
        }
        
        // Mettre en cache
        iconCache[fieldLabel] = icon;
        return icon;
    }
    
    /**
     * Vérification ultra-rapide des champs téléphone
     */
    function isPhoneFieldFast(fieldLabel, value) {
        var label = fieldLabel.toLowerCase();
        var phoneKeywords = ['téléphone', 'telephone', 'phone', 'tel', 'mobile', 'portable', 'fixe'];
        
        for (var i = 0, len = phoneKeywords.length; i < len; i++) {
            if (label.indexOf(phoneKeywords[i]) !== -1) {
                return true;
            }
        }
        
        // Vérification regex rapide
        var cleanValue = value.replace(/[^0-9+]/g, '');
        return /^(0[1-9]|\+33[1-9]|33[1-9])[0-9]{8}$/.test(cleanValue);
    }
    
    /**
     * Vérification ultra-rapide des champs adresse
     */
    function isAddressFieldFast(fieldLabel, value) {
        var label = fieldLabel.toLowerCase();
        var addressKeywords = ['adresse', 'address', 'rue', 'street', 'voie', 'avenue', 'boulevard', 'place', 'lieu'];
        
        for (var i = 0, len = addressKeywords.length; i < len; i++) {
            if (label.indexOf(addressKeywords[i]) !== -1) {
                return true;
            }
        }
        
        // Vérification rapide d'adresse
        return /\d/.test(value) && /[a-zA-ZÀ-ÿ]/.test(value) && value.length > 5;
    }
    
    /**
     * Formatage ultra-rapide du téléphone
     */
    function formatPhoneForDialingFast(phone) {
        var cleanPhone = phone.replace(/[^0-9+]/g, '');
        
        // Si le numéro commence par 0, le remplacer par +33
        var match = cleanPhone.match(/^0([1-9][0-9]{8})$/);
        if (match) {
            return '+33' + match[1];
        }
        
        // Si le numéro commence déjà par +33, le garder tel quel
        if (/^\+33([1-9][0-9]{8})$/.test(cleanPhone)) {
            return cleanPhone;
        }
        
        // Si le numéro commence par 33 (sans +), ajouter le +
        var match33 = cleanPhone.match(/^33([1-9][0-9]{8})$/);
        if (match33) {
            return '+33' + match33[1];
        }
        
        return cleanPhone;
    }
    
    /**
     * Formatage ultra-rapide de l'adresse avec ville
     */
    function formatAddressWithCityFast(addressValue, cityValue) {
        var address = addressValue ? addressValue.trim() : '';
        var city = cityValue ? cityValue.trim() : '';
        
        if (!address && !city) return '';
        if (!address) return city;
        if (!city) return address;
        
        return address + '<br><small style="color: #666;">' + city + '</small>';
    }
    
    /**
     * Fonction pour détecter si un champ est un téléphone
     */
    function isPhoneField(fieldLabel, value) {
        if (!value || value.trim() === '') return false;
        
        // Vérifier le label du champ
        var phoneKeywords = ['téléphone', 'telephone', 'phone', 'tel', 'mobile', 'portable', 'fixe'];
        var labelLower = fieldLabel.toLowerCase();
        
        for (var i = 0; i < phoneKeywords.length; i++) {
            if (labelLower.indexOf(phoneKeywords[i]) !== -1) {
                return true;
            }
        }
        
        // Vérifier si la valeur ressemble à un numéro de téléphone français
        var cleanValue = value.replace(/[^0-9+]/g, '');
        var phoneRegex = /^(0[1-9]|\+33[1-9]|33[1-9])[0-9]{8}$/;
        return phoneRegex.test(cleanValue);
    }
    
    /**
     * Fonction pour détecter si un champ est une adresse
     */
    function isAddressField(fieldLabel, value) {
        if (!value || value.trim() === '') return false;
        
        // Vérifier le label du champ - mots-clés étendus
        var addressKeywords = [
            'adresse', 'address', 'rue', 'street', 'voie', 'avenue', 'boulevard', 'place', 'lieu',
            'adr', 'addr', 'location', 'localisation', 'adresse complète', 'adresse complète',
            'adresse du bien', 'adresse du logement', 'adresse de la propriété',
            'numéro', 'numero', 'n°', 'n ', 'street number', 'numéro de rue',
            'adresse postale', 'adresse de contact', 'adresse principale'
        ];
        var labelLower = fieldLabel.toLowerCase();
        
        for (var i = 0; i < addressKeywords.length; i++) {
            if (labelLower.indexOf(addressKeywords[i]) !== -1) {
                return true;
            }
        }
        
        // Vérifier aussi si la valeur ressemble à une adresse (contient des chiffres et des lettres)
        var cleanValue = value.trim();
        if (/\d/.test(cleanValue) && /[a-zA-ZÀ-ÿ]/.test(cleanValue) && cleanValue.length > 5) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Fonction pour détecter si un champ est une ville
     */
    function isCityField(fieldLabel, value) {
        if (!value || value.trim() === '') return false;
        
        // Vérifier le label du champ
        var cityKeywords = ['ville', 'city', 'commune', 'municipalité', 'localité'];
        var labelLower = fieldLabel.toLowerCase();
        
        for (var i = 0; i < cityKeywords.length; i++) {
            if (labelLower.indexOf(cityKeywords[i]) !== -1) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Fonction pour formater une adresse avec ville
     */
    function formatAddressWithCity(addressValue, cityValue) {
        var address = addressValue ? addressValue.trim() : '';
        var city = cityValue ? cityValue.trim() : '';
        
        if (!address && !city) {
            return '';
        }
        
        if (!address) {
            return city;
        }
        
        if (!city) {
            return address;
        }
        
        return address + '<br><small style="color: #666;">' + city + '</small>';
    }
    
    /**
     * Fonction pour formater un numéro de téléphone pour l'appel direct
     */
    function formatPhoneForDialing(phone) {
        if (!phone || phone.trim() === '') return '';
        
        // Nettoyer le numéro (garder seulement les chiffres et +)
        var cleanPhone = phone.replace(/[^0-9+]/g, '');
        
        // Si le numéro commence par 0, le remplacer par +33
        var match = cleanPhone.match(/^0([1-9][0-9]{8})$/);
        if (match) {
            return '+33' + match[1];
        }
        
        // Si le numéro commence déjà par +33, le garder tel quel
        if (/^\+33([1-9][0-9]{8})$/.test(cleanPhone)) {
            return cleanPhone;
        }
        
        // Si le numéro commence par 33 (sans +), ajouter le +
        var match33 = cleanPhone.match(/^33([1-9][0-9]{8})$/);
        if (match33) {
            return '+33' + match33[1];
        }
        
        // Si le numéro commence par +, le garder tel quel
        if (cleanPhone.indexOf('+') === 0) {
            return cleanPhone;
        }
        
        // Par défaut, retourner le numéro tel quel
        return cleanPhone;
    }
    
    
    /**
     * Fermer le modal
     */
    function closeLeadDetailsModal() {
        $('#lead-details-modal').fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    /**
     * Afficher un message de notification
     */
    function showMessage(message, type) {
        var messageHtml = '<div class="lead-vendeur-message ' + type + '">' + message + '</div>';
        
        // Supprimer les anciens messages
        $('.lead-vendeur-message').remove();
        
        // Ajouter le nouveau message
        $('.my-istymo-container').prepend(messageHtml);
        
        // Afficher le message
        $('.lead-vendeur-message').fadeIn(300);
        
        // Masquer après 3 secondes
        setTimeout(function() {
            $('.lead-vendeur-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Échapper le HTML pour éviter les injections
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }
    
    /**
     * Formater une date
     */
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Auto-refresh des favoris au chargement de la page
    function refreshFavoris() {
        $('.favorite-btn').each(function() {
            var $toggle = $(this);
            var entryId = $toggle.data('entry-id');
            
            // Vérifier si c'est un favori (basé sur la classe)
            if ($toggle.hasClass('favori-active')) {
                $toggle.closest('.lead-vendeur-row').addClass('favori-row');
            }
        });
    }
    
    // Initialiser au chargement
    refreshFavoris();
    
    // ✅ NOUVEAU : Initialiser la pagination AJAX
    initializePagination();
    
    // Animation pour les nouvelles lignes
    $('.lead-vendeur-row').each(function(index) {
        $(this).css('opacity', '0').delay(index * 50).animate({
            opacity: 1
        }, 300);
    });
    
    // ✅ NOUVEAU : Gestion du bouton de débogage
    $(document).on('click', '#toggle-debug-data', function(e) {
        e.preventDefault();
        
        var $debugSection = $('#debug-data-section');
        var $button = $(this);
        var $icon = $button.find('i');
        
        if ($debugSection.is(':visible')) {
            $debugSection.slideUp(300);
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $button.html('<i class="fas fa-eye"></i> Afficher/Masquer les données brutes');
        } else {
            $debugSection.slideDown(300);
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            $button.html('<i class="fas fa-eye-slash"></i> Afficher/Masquer les données brutes');
        }
    });
    
    // ✅ NOUVEAU : Gestion de la pagination AJAX
    $(document).on('click', '.pagination-btn, .pagination-number', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var page = $button.data('page');
        
        if (page && !$button.hasClass('disabled') && !$button.hasClass('current')) {
            loadPage(page);
        }
    });
    
    // ✅ NOUVEAU : Initialiser la pagination
    function initializePagination() {
        if (typeof leadVendeurAjax !== 'undefined') {
            // Charger la première page automatiquement
            loadPage(1);
        }
    }
    
    // Fonction pour charger une page via AJAX
    function loadPage(page) {
        var $tableBody = $('#lead-vendeur-table-body');
        var $paginationContainer = $('#lead-vendeur-pagination-container');
        var $paginationInfo = $('.pagination-info');
        
        // Afficher l'indicateur de chargement
        $tableBody.html('<tr><td colspan="100%" style="text-align: center; padding: 20px;"><div class="loading-spinner"></div><p>Chargement des données...</p></td></tr>');
        $paginationContainer.html('<div style="text-align: center; padding: 20px;"><div class="loading-spinner"></div></div>');
        
        // Requête AJAX
        $.ajax({
            url: leadVendeurAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'lead_vendeur_pagination',
                nonce: leadVendeurAjax.nonce,
                page: page,
                per_page: leadVendeurAjax.per_page
            },
            success: function(response) {
                if (response.success) {
                    // Mettre à jour le tableau
                    $tableBody.html(response.data.table_html);
                    
                    // Mettre à jour la pagination
                    $paginationContainer.html(response.data.pagination_html);
                    
                    // Mettre à jour les informations de pagination
                    var info = response.data.pagination_info;
                    if (info.total_pages > 1) {
                        $paginationInfo.html(
                            '<span id="page-info">Page ' + info.current_page + ' sur ' + info.total_pages + '</span>' +
                            '<span style="margin-left: 15px; color: #666;">Affichage des entrées ' + info.start_entry + ' à ' + info.end_entry + ' sur ' + info.total_entries + '</span>'
                        );
                    }
                    
                    // Animation pour les nouvelles lignes
                    $('.lead-vendeur-row').each(function(index) {
                        $(this).css('opacity', '0').delay(index * 50).animate({
                            opacity: 1
                        }, 300);
                    });
                } else {
                    console.error('Erreur lors du chargement de la page:', response.data);
                    $tableBody.html('<tr><td colspan="100%" style="text-align: center; padding: 20px; color: #d63384;">Erreur lors du chargement des données</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                $tableBody.html('<tr><td colspan="100%" style="text-align: center; padding: 20px; color: #d63384;">Erreur de connexion</td></tr>');
            }
        });
    }
    
    // ✅ NOUVEAU : Fonction pour ouvrir le rapport d'analyse
    function openPropertyReport(websiteUrl) {
        console.log('Ouverture du rapport pour l\'URL:', websiteUrl);
        
        // Vérifier si l'URL est valide
        if (websiteUrl && websiteUrl.trim() !== '') {
            // Ajouter http:// si l'URL ne commence pas par http:// ou https://
            if (!websiteUrl.startsWith('http://') && !websiteUrl.startsWith('https://')) {
                websiteUrl = 'http://' + websiteUrl;
            }
            
            // Ouvrir l'URL dans un nouvel onglet
            window.open(websiteUrl, '_blank');
        } else {
            alert('URL du site web non disponible pour ce bien.');
        }
    }
    
    // ✅ NOUVEAU : Fonction utilitaire pour échapper le HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
});
