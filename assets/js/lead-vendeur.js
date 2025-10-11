/**
 * JavaScript pour la gestion des leads vendeur
 */

jQuery(document).ready(function($) {
    
    // Toggle favori
    $(document).on('click', '.favori-toggle', function(e) {
        e.preventDefault();
        
        var $toggle = $(this);
        var entryId = $toggle.data('entry-id');
        var $row = $toggle.closest('.lead-vendeur-row');
        
        if ($toggle.hasClass('loading')) {
            return; // Éviter les clics multiples
        }
        
        $toggle.addClass('loading');
        
        $.ajax({
            url: leadVendeurAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'lead_vendeur_toggle_favori',
                entry_id: entryId,
                nonce: leadVendeurAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.action === 'added') {
                        $toggle.addClass('favori-active');
                        $row.addClass('favori-row');
                        showMessage('Lead ajouté aux favoris', 'success');
                    } else {
                        $toggle.removeClass('favori-active');
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
     * Afficher le modal avec les détails du lead
     */
    function showLeadDetailsModal(data) {
        var modalHtml = '<div class="lead-details-modal" id="lead-details-modal">';
        modalHtml += '<div class="lead-details-modal-content">';
        
        // Header avec icône, titre et boutons
        modalHtml += '<div class="lead-details-modal-header">';
        modalHtml += '<div class="lead-details-header-left">';
        modalHtml += '<div class="lead-details-icon">';
        modalHtml += '<i class="fas fa-users"></i>';
        modalHtml += '</div>';
        modalHtml += '<div class="lead-details-title-section">';
        modalHtml += '<h2>Lead #' + data.entry_id + ' - ' + (data.title || 'Lead Vendeur') + '</h2>';
        modalHtml += '<p class="lead-details-subtitle">Informations complètes et actions</p>';
        modalHtml += '<p class="lead-details-date">Créé le ' + formatDate(data.date_created) + '</p>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '<div class="lead-details-header-right">';
        modalHtml += '<span class="lead-details-modal-close">&times;</span>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        
        // Contenu principal en deux colonnes
        modalHtml += '<div class="lead-details-main-content">';
        
        // Colonne gauche - Informations du lead
        modalHtml += '<div class="lead-details-left-column">';
        
        
        // Section Informations du Lead
        modalHtml += '<div class="lead-details-info-section">';
        modalHtml += '<div class="lead-details-section-header">';
        modalHtml += '<i class="fas fa-info-circle"></i>';
        modalHtml += '<h3>Informations du Lead</h3>';
        modalHtml += '</div>';
        modalHtml += '<div class="lead-details-info-grid">';
        
        // Préparer les données pour la combinaison adresse/ville
        var addressData = [];
        var cityData = [];
        
        // Première passe : identifier les champs d'adresse et ville
        data.formatted_data.forEach(function(item) {
            if (item.value && item.value.trim() !== '') {
                if (isAddressField(item.label, item.value)) {
                    addressData.push(item);
                } else if (isCityField(item.label, item.value)) {
                    cityData.push(item);
                }
            }
        });
        
        // Afficher les données formatées du formulaire
        data.formatted_data.forEach(function(item) {
            if (item.value && item.value.trim() !== '') {
                // Vérifier si c'est un champ ville qui a déjà été utilisé dans une adresse
                var isCityUsed = false;
                if (isCityField(item.label, item.value)) {
                    for (var i = 0; i < addressData.length; i++) {
                        if (addressData[i].value && addressData[i].value.trim() !== '') {
                            isCityUsed = true;
                            break;
                        }
                    }
                }
                
                // Ne pas afficher les villes déjà utilisées dans les adresses
                if (isCityUsed) {
                    return;
                }
                
                modalHtml += '<div class="lead-details-info-item">';
                modalHtml += '<div class="lead-details-info-label">';
                modalHtml += '<i class="fas fa-' + getFieldIcon(item.label) + '"></i>';
                modalHtml += '<span>' + escapeHtml(item.label) + '</span>';
                modalHtml += '</div>';
                
                // Vérifier si c'est un champ téléphone
                if (isPhoneField(item.label, item.value)) {
                    var formattedPhone = formatPhoneForDialing(item.value);
                    modalHtml += '<div class="lead-details-info-value">';
                    modalHtml += '<a href="tel:' + escapeHtml(formattedPhone) + '" class="phone-link-modal" title="Appeler directement">';
                    modalHtml += '<i class="fas fa-phone"></i>';
                    modalHtml += escapeHtml(item.value);
                    modalHtml += '</a>';
                    modalHtml += '</div>';
                }
                // Vérifier si c'est un champ d'adresse (combiner avec ville si disponible)
                else if (isAddressField(item.label, item.value)) {
                    modalHtml += '<div class="lead-details-info-value">';
                    
                    // Chercher une ville correspondante
                    var cityValue = '';
                    for (var i = 0; i < cityData.length; i++) {
                        if (cityData[i].value && cityData[i].value.trim() !== '') {
                            cityValue = cityData[i].value;
                            break;
                        }
                    }
                    
                    var formattedAddress = formatAddressWithCity(item.value, cityValue);
                    modalHtml += formattedAddress;
                    modalHtml += '</div>';
                } else {
                    modalHtml += '<div class="lead-details-info-value">' + escapeHtml(item.value) + '</div>';
                }
                
                modalHtml += '</div>';
            }
        });
        
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        
        
        modalHtml += '</div>'; // Fin du contenu principal
        modalHtml += '</div>'; // Fin du modal content
        modalHtml += '</div>'; // Fin du modal
        
        $('body').append(modalHtml);
        $('#lead-details-modal').fadeIn(300);
    }
    
    /**
     * Fonction pour obtenir l'icône appropriée selon le type de champ
     */
    function getFieldIcon(fieldLabel) {
        var label = fieldLabel.toLowerCase();
        if (label.includes('adresse') || label.includes('adresse')) return 'map-marker-alt';
        if (label.includes('téléphone') || label.includes('phone')) return 'phone';
        if (label.includes('email') || label.includes('mail')) return 'envelope';
        if (label.includes('surface') || label.includes('m²')) return 'ruler';
        if (label.includes('type') || label.includes('bâtiment')) return 'building';
        if (label.includes('date')) return 'calendar';
        if (label.includes('analyse') || label.includes('lien')) return 'link';
        return 'info-circle';
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
        $('.favori-toggle').each(function() {
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
    
});
