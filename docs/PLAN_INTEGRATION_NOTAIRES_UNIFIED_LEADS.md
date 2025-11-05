# 📋 Plan d'Intégration des Favoris Notaires dans le Système Unified Leads

## 🎯 Objectif
Intégrer les favoris notaires dans le système unifié de gestion des leads, permettant leur affichage dans l'interface unified leads avec un popup dynamique similaire aux autres types de leads (SCI, DPE, Lead Vendeur, Carte de Succession).

---

## 📊 Vue d'ensemble

### **Contexte**
- Les notaires sont actuellement gérés dans une table séparée (`wp_my_istymo_notaires`)
- Les favoris notaires sont stockés dans `wp_my_istymo_notaires_favoris`
- Le système unified leads utilise `wp_my_istymo_unified_leads` avec un enum `lead_type`
- Le popup dynamique génère le HTML selon le `lead_type` via JavaScript

### **Objectif final**
Quand un utilisateur ajoute un notaire en favoris, il doit automatiquement apparaître dans l'interface unified leads avec toutes ses fonctionnalités (statut, priorité, notes, actions, etc.).

---

## 🏗️ Phase 1 : Modification de la Base de Données

### 1.1 Mise à jour de la table `wp_my_istymo_unified_leads`

**Fichier** : `includes/unified-leads-manager.php`  
**Méthode** : `create_tables()` ou nouvelle méthode `update_table_for_notaire()`

**Action requise** :
- Ajouter 'notaire' à l'enum `lead_type` dans la table
- SQL à exécuter :
```sql
ALTER TABLE wp_my_istymo_unified_leads 
MODIFY COLUMN lead_type ENUM('sci', 'dpe', 'lead_vendeur', 'carte_succession', 'notaire') NOT NULL;
```

**Vérifications** :
- [x] Vérifier que la colonne `lead_type` existe
- [x] Vérifier que l'enum est modifiable
- [x] Tester l'ALTER TABLE en environnement de développement
- [x] Créer une méthode de migration sécurisée

### 1.2 Vérification des tables existantes

**Tables à vérifier** :
- [ ] `wp_my_istymo_notaires` - Structure complète
- [ ] `wp_my_istymo_notaires_favoris` - Structure et contraintes
- [ ] `wp_my_istymo_unified_leads` - Contrainte UNIQUE (user_id, lead_type, original_id)
- [ ] `wp_my_istymo_lead_actions` - Pas de modification nécessaire

**Structure attendue pour `wp_my_istymo_notaires`** :
```sql
- id INT
- nom_office VARCHAR(255)
- telephone_office VARCHAR(20)
- email_office VARCHAR(255)
- site_internet VARCHAR(255)
- adresse TEXT
- code_postal VARCHAR(10)
- ville VARCHAR(100)
- nom_notaire VARCHAR(255)
- langues_parlees TEXT
- statut_notaire VARCHAR(50)
- date_import DATETIME
- date_modification DATETIME
```

---

## 🔧 Phase 2 : Modification du Gestionnaire Unified Leads

### 2.1 Mise à jour de `Unified_Leads_Manager`

**Fichier** : `includes/unified-leads-manager.php`

**Méthodes à modifier** :

#### `update_table_for_notaire()`
- [x] Créer une nouvelle méthode similaire à `update_table_for_lead_vendeur()`
- [x] Vérifier si 'notaire' existe dans l'enum
- [x] Exécuter l'ALTER TABLE si nécessaire
- [x] Logger les modifications

#### `add_lead()`
- [x] Vérifier que la méthode accepte `lead_type = 'notaire'`
- [ ] Tester l'insertion d'un lead notaire (à valider manuellement)
- [x] Valider le format de `data_originale` pour les notaires

#### `get_lead()`
- [x] Vérifier que la récupération fonctionne pour `lead_type = 'notaire'`
- [x] Tester la désérialisation de `data_originale`

#### `format_lead_for_display()`
- [x] Ajouter le cas 'notaire' dans la méthode (dans `render_lead_row()`)
- [x] Colonne "Company" : Utiliser `nom_office`
- [x] Colonne "Location" : Utiliser `ville + ', ' + code_postal`
- [x] Colonne "Category" : Retourner "Notaire"
- [x] Icône : `'🏛️'` ou `'<i class="fas fa-gavel"></i>'`

**Code à ajouter dans `format_lead_for_display()`** :
```php
case 'notaire':
    $data = json_decode($lead->data_originale, true);
    $company_name = $data['nom_office'] ?? 'Notaire #' . $lead->original_id;
    $location = '';
    if (!empty($data['ville']) && !empty($data['code_postal'])) {
        $location = $data['ville'] . ', ' . $data['code_postal'];
    } elseif (!empty($data['ville'])) {
        $location = $data['ville'];
    } elseif (!empty($data['code_postal'])) {
        $location = $data['code_postal'];
    }
    $category = 'Notaire';
    break;
```

### 2.2 Méthode pour créer un lead notaire

**Nouvelle méthode** : `create_notaire_lead()`

**Fichier** : `includes/unified-leads-manager.php`

**Signature** :
```php
public function create_notaire_lead($user_id, $notaire_id) {
    // Récupérer les données complètes du notaire
    // Préparer data_originale en JSON
    // Appeler add_lead() avec les bonnes données
}
```

**Paramètres** :
- `$user_id` (int) : ID de l'utilisateur
- `$notaire_id` (int) : ID du notaire

**Retour** :
- `WP_Error` en cas d'erreur
- `int` (ID du lead créé) en cas de succès

**Logique** :
1. [x] Vérifier que l'utilisateur existe
2. [x] Récupérer le notaire via `Notaires_Manager::get_instance()->get_notaire_by_id($notaire_id)`
3. [x] Vérifier que le notaire existe
4. [x] Vérifier si un lead unified existe déjà (UNIQUE constraint)
5. [x] Préparer `data_originale` avec toutes les données du notaire
6. [x] Appeler `add_lead()` avec :
   - `lead_type` = 'notaire'
   - `original_id` = (string)$notaire_id
   - `status` = 'nouveau'
   - `priorite` = 'normale'
   - `data_originale` = JSON encodé

---

## 🔗 Phase 3 : Intégration dans le Système de Favoris Notaires

### 3.1 Modification PHP - `toggle_notaire_favorite`

**Fichier** : `my-istymo.php`  
**Fonction** : `my_istymo_ajax_toggle_notaire_favorite()` (ligne ~7546)

**Modifications à apporter** :
- [x] Modifications complètes implémentées

#### Quand un notaire est ajouté aux favoris :
```php
// Après l'ajout réussi dans wp_my_istymo_notaires_favoris
if ($result['success'] && $result['is_favorite']) {
    // Créer le lead unified
    $leads_manager = Unified_Leads_Manager::get_instance();
    $lead_result = $leads_manager->create_notaire_lead($user_id, $notaire_id);
    
    if (is_wp_error($lead_result)) {
        // Logger l'erreur mais ne pas faire échouer l'ajout en favoris
        my_istymo_log('Erreur création lead unified pour notaire: ' . $lead_result->get_error_message(), 'notaires');
    }
}
```

#### Quand un notaire est retiré des favoris :
```php
// Après la suppression réussie dans wp_my_istymo_notaires_favoris
if ($result['success'] && !$result['is_favorite']) {
    // Supprimer le lead unified
    $leads_manager = Unified_Leads_Manager::get_instance();
    
    // Trouver le lead unified correspondant
    $lead = $leads_manager->get_lead_by_original_id($user_id, 'notaire', $notaire_id);
    
    if ($lead) {
        $delete_result = $leads_manager->delete_lead($lead->id);
        if (is_wp_error($delete_result)) {
            my_istymo_log('Erreur suppression lead unified pour notaire: ' . $delete_result->get_error_message(), 'notaires');
        }
    }
}
```

**Méthode à créer si elle n'existe pas** : `get_lead_by_original_id()`
- [x] Créée et implémentée
```php
public function get_lead_by_original_id($user_id, $lead_type, $original_id) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$this->leads_table} 
         WHERE user_id = %d AND lead_type = %s AND original_id = %s",
        $user_id, $lead_type, $original_id
    ));
}
```

### 3.2 Synchronisation JavaScript - Côté Notaires

**Fichier** : `assets/js/notaires-admin.js`  
**Fonction** : `handleFavoriteToggle()` (ligne ~60)

**Modifications à apporter** :

#### Après l'ajout réussi d'un favori :
```javascript
success: function(response) {
    if (response.success) {
        // Mise à jour visuelle du bouton favori
        if (response.data.is_favorite) {
            button.addClass('favorited');
            button.find('.dashicons').removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
            button.attr('title', 'Supprimer des favoris');
        } else {
            button.removeClass('favorited');
            button.find('.dashicons').removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
            button.attr('title', 'Ajouter aux favoris');
        }
        
        // Mettre à jour le compteur de favoris
        updateFavoritesCount();
        
        // ✅ NOUVEAU : Notifier l'interface unified leads si elle est ouverte
        // Déclencher un événement personnalisé pour la synchronisation
        if (typeof window.dispatchEvent !== 'undefined') {
            window.dispatchEvent(new CustomEvent('notaireFavoriteChanged', {
                detail: {
                    notaire_id: notaireId,
                    is_favorite: response.data.is_favorite,
                    action: response.data.is_favorite ? 'added' : 'removed'
                }
            }));
        }
        
        // ✅ NOUVEAU : Rafraîchir l'interface unified leads si elle est visible
        if ($('#unified-leads-table').length > 0) {
            // Optionnel : Recharger la table unified leads
            // refreshUnifiedLeadsTable();
        }
    }
}
```

### 3.3 Synchronisation JavaScript - Côté Unified Leads

**Fichier** : `assets/js/unified-leads-admin.js`  
**Fonction** : `deleteLead()` (ligne ~195)

**Modifications à apporter** :

#### Quand un lead notaire est supprimé depuis unified leads :
```javascript
function deleteLead(leadId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce lead ?')) {
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
        success: function(response) {
            if (response.success) {
                // Récupérer les informations du lead avant suppression
                var leadData = response.data.lead || {};
                
                // ✅ NOUVEAU : Si c'est un lead notaire, supprimer aussi le favori
                if (leadData.lead_type === 'notaire' && leadData.original_id) {
                    // Supprimer le favori notaire correspondant
                    $.ajax({
                        url: typeof notairesAjax !== 'undefined' ? notairesAjax.ajaxurl : unifiedLeadsAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'toggle_notaire_favorite',
                            notaire_id: leadData.original_id,
                            remove_only: true, // Nouveau paramètre pour forcer la suppression
                            nonce: typeof notairesAjax !== 'undefined' ? notairesAjax.nonce : unifiedLeadsAjax.nonce
                        },
                        success: function(favoriteResponse) {
                            // Logger pour debug
                            console.log('Favori notaire supprimé:', favoriteResponse);
                        },
                        error: function() {
                            console.error('Erreur lors de la suppression du favori notaire');
                        }
                    });
                }
                
                // Supprimer la ligne du tableau
                $('tr[data-lead-id="' + leadId + '"]').fadeOut(300, function() {
                    $(this).remove();
                    updateLeadsCount();
                });
                
                // ✅ NOUVEAU : Notifier l'interface notaires si elle est ouverte
                window.dispatchEvent(new CustomEvent('unifiedLeadDeleted', {
                    detail: {
                        lead_id: leadId,
                        lead_type: leadData.lead_type,
                        original_id: leadData.original_id
                    }
                }));
            }
        }
    });
}
```

#### Écouter les événements depuis l'interface notaires :
```javascript
// Dans notaires-admin.js - Ajouter après l'initialisation
$(document).ready(function($) {
    // Écouter les suppressions depuis unified leads
    window.addEventListener('unifiedLeadDeleted', function(event) {
        var detail = event.detail;
        
        // Si c'est un notaire qui a été supprimé, mettre à jour l'interface
        if (detail.lead_type === 'notaire' && detail.original_id) {
            var notaireId = detail.original_id;
            
            // Mettre à jour le bouton favori correspondant
            $('.favorite-toggle[data-notaire-id="' + notaireId + '"]')
                .removeClass('favorited')
                .find('.dashicons')
                .removeClass('dashicons-star-filled')
                .addClass('dashicons-star-empty')
                .closest('.favorite-toggle')
                .attr('title', 'Ajouter aux favoris');
            
            // Mettre à jour le compteur
            updateFavoritesCount();
        }
    });
    
    // Écouter les ajouts depuis unified leads (si applicable)
    window.addEventListener('unifiedLeadAdded', function(event) {
        var detail = event.detail;
        
        if (detail.lead_type === 'notaire' && detail.original_id) {
            var notaireId = detail.original_id;
            
            // Mettre à jour le bouton favori
            $('.favorite-toggle[data-notaire-id="' + notaireId + '"]')
                .addClass('favorited')
                .find('.dashicons')
                .removeClass('dashicons-star-empty')
                .addClass('dashicons-star-filled')
                .closest('.favorite-toggle')
                .attr('title', 'Supprimer des favoris');
            
            updateFavoritesCount();
        }
    });
});
```

#### Écouter les événements depuis l'interface unified leads :
```javascript
// Dans unified-leads-admin.js - Ajouter après l'initialisation
$(document).ready(function($) {
    // Écouter les changements de favoris depuis l'interface notaires
    window.addEventListener('notaireFavoriteChanged', function(event) {
        var detail = event.detail;
        
        if (detail.action === 'added') {
            // Rafraîchir la table pour afficher le nouveau lead
            // Optionnel : refreshUnifiedLeadsTable();
            // Ou ajouter dynamiquement la ligne
        } else if (detail.action === 'removed') {
            // Supprimer la ligne correspondante
            var leadRow = $('tr[data-lead-type="notaire"][data-original-id="' + detail.notaire_id + '"]');
            if (leadRow.length > 0) {
                var leadId = leadRow.data('lead-id');
                if (leadId) {
                    leadRow.fadeOut(300, function() {
                        $(this).remove();
                        updateLeadsCount();
                    });
                }
            }
        }
    });
});
```

### 3.4 Modification PHP - Suppression avec paramètre `remove_only`

**Fichier** : `my-istymo.php`  
**Fonction** : `my_istymo_ajax_toggle_notaire_favorite()` (ligne ~7546)

**Modification pour gérer la suppression forcée** :

```php
function my_istymo_ajax_toggle_notaire_favorite() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'my_istymo_notaires_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }
    
    $notaire_id = intval($_POST['notaire_id'] ?? 0);
    $user_id = get_current_user_id();
    $remove_only = isset($_POST['remove_only']) && $_POST['remove_only'] === 'true';
    
    if (!$notaire_id || !$user_id) {
        wp_send_json_error('Paramètres manquants');
        return;
    }
    
    $favoris_handler = Notaires_Favoris_Handler::get_instance();
    
    // Si remove_only est true, supprimer directement sans toggle
    if ($remove_only) {
        $result = $favoris_handler->remove_from_favorites($user_id, $notaire_id);
        
        if ($result['success']) {
            // Supprimer aussi le lead unified (déjà fait côté PHP dans toggle)
            // Mais on s'assure que c'est bien supprimé
            $leads_manager = Unified_Leads_Manager::get_instance();
            $lead = $leads_manager->get_lead_by_original_id($user_id, 'notaire', (string)$notaire_id);
            
            if ($lead) {
                $leads_manager->delete_lead($lead->id);
            }
            
            wp_send_json_success(array(
                'is_favorite' => false,
                'action' => 'removed'
            ));
        } else {
            wp_send_json_error($result['message']);
        }
        return;
    }
    
    // Sinon, comportement normal (toggle)
    $result = $favoris_handler->toggle_favorite($user_id, $notaire_id);
    
    // ... reste du code existant avec création/suppression lead unified
}
```

### 3.5 Récapitulatif de la Synchronisation Bidirectionnelle

**Diagramme de synchronisation** :

```
┌─────────────────────────────────────────────────────────────┐
│                    INTERFACE NOTAIRES                        │
│  (Shortcode [my_istymo_notaires] + Admin Panel)             │
│                                                              │
│  Bouton favori cliqué                                       │
│         ↓                                                    │
│  AJAX: toggle_notaire_favorite                              │
│         ↓                                                    │
│  PHP: Création/Suppression favori                          │
│         ↓                                                    │
│  PHP: Création/Suppression lead unified                     │
│         ↓                                                    │
│  JS: Événement 'notaireFavoriteChanged'                     │
│         ↓                                                    │
│  ────────────────────────────────────────────               │
│         ↓                                                    │
│  Écouteur unified leads → Mise à jour tableau              │
└─────────────────────────────────────────────────────────────┘
                    ↕
┌─────────────────────────────────────────────────────────────┐
│                  INTERFACE UNIFIED LEADS                    │
│                                                              │
│  Bouton supprimer cliqué                                    │
│         ↓                                                    │
│  AJAX: delete_unified_lead                                  │
│         ↓                                                    │
│  PHP: Suppression lead unified                              │
│         ↓                                                    │
│  JS: Si lead_type='notaire' → AJAX toggle_notaire_favorite │
│         ↓                                                    │
│  PHP: Suppression favori notaire                            │
│         ↓                                                    │
│  JS: Événement 'unifiedLeadDeleted'                        │
│         ↓                                                    │
│  ────────────────────────────────────────────               │
│         ↓                                                    │
│  Écouteur notaires → Mise à jour bouton favori             │
└─────────────────────────────────────────────────────────────┘
```

**Flux de synchronisation** :

1. **Ajout depuis Notaires** :
   - Utilisateur clique sur favori → AJAX `toggle_notaire_favorite`
   - PHP crée favori + lead unified
   - JS déclenche `notaireFavoriteChanged` event
   - Unified Leads écoute l'event et met à jour le tableau

2. **Suppression depuis Notaires** :
   - Utilisateur clique sur favori → AJAX `toggle_notaire_favorite`
   - PHP supprime favori + lead unified
   - JS déclenche `notaireFavoriteChanged` event
   - Unified Leads écoute l'event et supprime la ligne

3. **Suppression depuis Unified Leads** :
   - Utilisateur clique sur supprimer → AJAX `delete_unified_lead`
   - PHP supprime lead unified
   - JS détecte `lead_type='notaire'` → AJAX `toggle_notaire_favorite` avec `remove_only=true`
   - PHP supprime favori notaire
   - JS déclenche `unifiedLeadDeleted` event
   - Notaires écoute l'event et met à jour le bouton favori

**Points importants** :
- Les événements CustomEvent permettent la communication entre les deux interfaces
- La synchronisation fonctionne même si les deux interfaces sont ouvertes en même temps
- Les erreurs sont gérées indépendamment (ne pas bloquer une interface si l'autre échoue)

### 3.6 Synchronisation des données

**Fonction de vérification** : `verify_notaire_leads_sync()`
- [ ] Vérifier que tous les favoris notaires ont un lead unified correspondant
- [ ] Vérifier qu'il n'y a pas de leads unified orphelins (notaire supprimé)
- [ ] Créer une fonction de réparation automatique

**Fonction de migration** : `migrate_existing_notaire_favorites()`
- [x] Parcourir tous les utilisateurs avec des favoris notaires
- [x] Pour chaque favori, créer le lead unified correspondant
- [x] Vérifier les doublons avant insertion
- [x] Logger les erreurs de migration
- [x] Afficher un rapport de migration
- [x] Migration automatique exécutée une seule fois via transient

**Code de migration** :
```php
function migrate_existing_notaire_favorites_to_unified() {
    global $wpdb;
    
    $table_favoris = $wpdb->prefix . 'my_istymo_notaires_favoris';
    $favoris = $wpdb->get_results("SELECT DISTINCT user_id, notaire_id FROM {$table_favoris}");
    
    $leads_manager = Unified_Leads_Manager::get_instance();
    $created = 0;
    $errors = 0;
    $skipped = 0;
    
    foreach ($favoris as $favori) {
        // Vérifier si le lead existe déjà
        $existing = $leads_manager->get_lead_by_original_id(
            $favori->user_id, 
            'notaire', 
            (string)$favori->notaire_id
        );
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        // Créer le lead
        $result = $leads_manager->create_notaire_lead($favori->user_id, $favori->notaire_id);
        
        if (is_wp_error($result)) {
            $errors++;
            my_istymo_log('Erreur migration notaire ' . $favori->notaire_id . ': ' . $result->get_error_message(), 'notaires');
        } else {
            $created++;
        }
    }
    
    return [
        'created' => $created,
        'errors' => $errors,
        'skipped' => $skipped,
        'total' => count($favoris)
    ];
}
```

---

## 🎨 Phase 4 : Affichage dans le Popup Dynamique

### 4.1 Modification JavaScript - `unified-leads-admin.js`

**Fichier** : `assets/js/unified-leads-admin.js`  
**Fonction** : `openLeadDetailModal()` (ligne ~23)

**Modifications** :
- [x] Ajout du cas 'notaire' pour l'icône et le label

```javascript
// Ligne ~109 - Ajouter le cas 'notaire'
if (leadType === 'sci') {
    typeIcon = '<i class="fas fa-building"></i>';
    typeLabel = 'SCI';
} else if (leadType === 'dpe') {
    typeIcon = '<i class="fas fa-home"></i>';
    typeLabel = 'DPE';
} else if (leadType === 'lead_vendeur') {
    typeIcon = '<i class="fas fa-store"></i>';
    typeLabel = 'Lead Vendeur';
} else if (leadType === 'carte_succession') {
    typeIcon = '<i class="fas fa-map"></i>';
    typeLabel = 'Carte de Succession';
} else if (leadType === 'notaire') {
    typeIcon = '<i class="fas fa-gavel"></i>';
    typeLabel = 'Notaire';
} else {
    typeIcon = '<i class="fas fa-users"></i>';
    typeLabel = leadType.toUpperCase();
}
```

### 4.2 Génération HTML dynamique - `unified-leads-admin.php`

**Fichier** : `templates/unified-leads-admin.php`  
**Fonction** : `generateModernLeadHTML()` (ligne ~1147)

**Code complet à ajouter** (après le cas `lead_vendeur`, ligne ~1331) :
- [x] Section notaire complète ajoutée avec toutes les informations

```javascript
} else if (leadData.lead_type === 'notaire') {
    // ========================================
    // SECTION NOTAIRE
    // ========================================
    
    // Section Informations Notaire
    html += '<div class="my-istymo-info-section">';
    html += '<h5>Informations Notaire</h5>';
    
    // Nom de l'office
    if (data.nom_office) {
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Office :</span>';
        html += '<span class="my-istymo-info-value">' + escapeHtml(data.nom_office) + '</span>';
        html += '</div>';
    }
    
    // Nom du notaire
    if (data.nom_notaire) {
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Notaire :</span>';
        html += '<span class="my-istymo-info-value">' + escapeHtml(data.nom_notaire) + '</span>';
        html += '</div>';
    }
    
    // Adresse complète
    var adresseParts = [];
    if (data.adresse) {
        adresseParts.push(data.adresse.trim());
    }
    if (data.code_postal && data.ville) {
        adresseParts.push(data.code_postal.trim() + ' ' + data.ville.trim());
    } else if (data.code_postal) {
        adresseParts.push(data.code_postal.trim());
    } else if (data.ville) {
        adresseParts.push(data.ville.trim());
    }
    
    if (adresseParts.length > 0) {
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Adresse :</span>';
        html += '<span class="my-istymo-info-value">' + escapeHtml(adresseParts.join(', ')) + '</span>';
        html += '</div>';
    }
    
    html += '</div>'; // Fin section informations
    
    // Section Contact
    html += '<div class="my-istymo-info-section">';
    html += '<h5>Contact</h5>';
    
    // Téléphone
    if (data.telephone_office) {
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Téléphone :</span>';
        html += '<span class="my-istymo-info-value">';
        html += '<a href="tel:' + escapeHtml(data.telephone_office) + '" class="my-istymo-link">';
        html += '<i class="fas fa-phone"></i> ' + escapeHtml(data.telephone_office);
        html += '</a>';
        html += '</span>';
        html += '</div>';
    }
    
    // Email
    if (data.email_office) {
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Email :</span>';
        html += '<span class="my-istymo-info-value">';
        html += '<a href="mailto:' + escapeHtml(data.email_office) + '" class="my-istymo-link">';
        html += '<i class="fas fa-envelope"></i> ' + escapeHtml(data.email_office);
        html += '</a>';
        html += '</span>';
        html += '</div>';
    }
    
    // Site internet
    if (data.site_internet) {
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Site web :</span>';
        html += '<span class="my-istymo-info-value">';
        var siteUrl = data.site_internet;
        if (!siteUrl.startsWith('http://') && !siteUrl.startsWith('https://')) {
            siteUrl = 'https://' + siteUrl;
        }
        html += '<a href="' + escapeHtml(siteUrl) + '" target="_blank" rel="noopener" class="my-istymo-link">';
        html += '<i class="fas fa-external-link-alt"></i> ' + escapeHtml(data.site_internet);
        html += '</a>';
        html += '</span>';
        html += '</div>';
    }
    
    html += '</div>'; // Fin section contact
    
    // Section Informations complémentaires
    html += '<div class="my-istymo-info-section">';
    html += '<h5>Informations complémentaires</h5>';
    
    // Langues parlées
    if (data.langues_parlees) {
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Langues parlées :</span>';
        html += '<span class="my-istymo-info-value">' + escapeHtml(data.langues_parlees) + '</span>';
        html += '</div>';
    }
    
    // Statut
    if (data.statut_notaire) {
        html += '<div class="my-istymo-info-row">';
        html += '<span class="my-istymo-info-label">Statut :</span>';
        var statutClass = data.statut_notaire.toLowerCase();
        var statutBadge = '';
        if (statutClass === 'actif') {
            statutBadge = '<span class="my-istymo-status-badge my-istymo-status-success">';
        } else if (statutClass === 'inactif') {
            statutBadge = '<span class="my-istymo-status-badge my-istymo-status-danger">';
        } else if (statutClass === 'suspendu') {
            statutBadge = '<span class="my-istymo-status-badge my-istymo-status-warning">';
        } else {
            statutBadge = '<span class="my-istymo-status-badge">';
        }
        html += '<span class="my-istymo-info-value">' + statutBadge + escapeHtml(data.statut_notaire) + '</span></span>';
        html += '</div>';
    }
    
    html += '</div>'; // Fin section complémentaires
}
```

**Note** : S'assurer que la fonction `escapeHtml()` existe ou utiliser une alternative sécurisée.

### 4.3 Formatage dans le tableau - `unified-leads-admin.php`

**Fichier** : `templates/unified-leads-admin.php`  
**Fonction** : `display_lead_row()` (ligne ~1039)

**Modifications à apporter** :
- [x] Cas 'notaire' ajouté pour l'icône et le formatage

```php
// Ligne ~1039 - Ajouter le cas 'notaire' pour l'icône
if ($lead->lead_type === 'dpe') {
    echo '<span class="my-istymo-icon my-istymo-icon-house">🏠</span>';
} elseif ($lead->lead_type === 'lead_vendeur') {
    echo '<span class="my-istymo-icon my-istymo-icon-vendor">🏪</span>';
} elseif ($lead->lead_type === 'carte_succession') {
    echo '<span class="my-istymo-icon my-istymo-icon-succession">⚰️</span>';
} elseif ($lead->lead_type === 'notaire') {
    echo '<span class="my-istymo-icon my-istymo-icon-notaire">🏛️</span>';
} elseif ($lead->lead_type === 'lead_parrainage') {
    echo '<span class="my-istymo-icon my-istymo-icon-parrainage">🤝</span>';
} elseif ($lead->lead_type === 'unknown') {
    echo '<span class="my-istymo-icon my-istymo-icon-unknown">❓</span>';
} else {
    echo '<span class="my-istymo-icon my-istymo-icon-building">🏢</span>';
}
```

**Pour le formatage des données** (dans `format_lead_for_display()`) :
- Company name : `$data['nom_office'] ?? 'Notaire #' . $lead->original_id`
- Location : Construire depuis `$data['ville']` et `$data['code_postal']`
- Category : `'Notaire'`

---

## 📦 Phase 5 : Gestion des Données Originales

### 5.1 Structure des données JSON

**Format `data_originale` pour un notaire** :

```php
$data_originale = [
    'id' => $notaire->id,
    'nom_office' => $notaire->nom_office ?? '',
    'nom_notaire' => $notaire->nom_notaire ?? '',
    'telephone_office' => $notaire->telephone_office ?? '',
    'email_office' => $notaire->email_office ?? '',
    'site_internet' => $notaire->site_internet ?? '',
    'adresse' => $notaire->adresse ?? '',
    'code_postal' => $notaire->code_postal ?? '',
    'ville' => $notaire->ville ?? '',
    'langues_parlees' => $notaire->langues_parlees ?? '',
    'statut_notaire' => $notaire->statut_notaire ?? 'actif',
    'url_office' => $notaire->url_office ?? '',
    'date_import' => $notaire->date_import ?? '',
    'date_modification' => $notaire->date_modification ?? ''
];
```

**Encodage** :
```php
'data_originale' => wp_json_encode($data_originale, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
```

### 5.2 Récupération des données

**Dans `toggle_notaire_favorite`** :

```php
// Récupérer le notaire complet
$notaires_manager = Notaires_Manager::get_instance();
$notaire = $notaires_manager->get_notaire_by_id($notaire_id);

if (!$notaire) {
    wp_send_json_error('Notaire non trouvé');
    return;
}

// Préparer les données pour le lead unified
$data_originale = [
    'id' => $notaire->id,
    'nom_office' => $notaire->nom_office ?? '',
    'nom_notaire' => $notaire->nom_notaire ?? '',
    'telephone_office' => $notaire->telephone_office ?? '',
    'email_office' => $notaire->email_office ?? '',
    'site_internet' => $notaire->site_internet ?? '',
    'adresse' => $notaire->adresse ?? '',
    'code_postal' => $notaire->code_postal ?? '',
    'ville' => $notaire->ville ?? '',
    'langues_parlees' => $notaire->langues_parlees ?? '',
    'statut_notaire' => $notaire->statut_notaire ?? 'actif',
    'url_office' => $notaire->url_office ?? '',
    'date_import' => $notaire->date_import ?? '',
    'date_modification' => $notaire->date_modification ?? ''
];
```

---

## ✅ Phase 6 : Tests et Validation

### 6.1 Tests fonctionnels

**Test 1 : Ajout d'un notaire en favoris**
- [ ] Ajouter un notaire en favoris via l'interface
- [ ] Vérifier la création du lead unified dans la base
- [ ] Vérifier que le lead apparaît dans l'interface unified leads
- [ ] Vérifier les données dans `data_originale`

**Test 2 : Retrait d'un notaire des favoris**
- [ ] Retirer un notaire des favoris
- [ ] Vérifier la suppression du lead unified
- [ ] Vérifier que le lead disparaît de l'interface unified leads

**Test 3 : Affichage du popup**
- [ ] Cliquer sur "Voir détails" d'un lead notaire
- [ ] Vérifier que le popup s'ouvre correctement
- [ ] Vérifier que toutes les informations s'affichent
- [ ] Vérifier le formatage des données (téléphone cliquable, email cliquable, site web avec lien)
- [ ] Vérifier les badges de statut

**Test 4 : Affichage dans le tableau**
- [ ] Vérifier l'icône notaire dans la colonne
- [ ] Vérifier le nom de l'office
- [ ] Vérifier la localisation (ville, code postal)
- [ ] Vérifier la catégorie "Notaire"
- [ ] Vérifier les statuts et priorités

**Test 5 : Migration des favoris existants**
- [ ] Exécuter la fonction de migration
- [ ] Vérifier que tous les favoris sont migrés
- [ ] Vérifier qu'il n'y a pas de doublons
- [ ] Vérifier les logs de migration

### 6.2 Tests de cohérence

**Test de contrainte UNIQUE** :
- [ ] Essayer d'ajouter deux fois le même notaire en favoris
- [ ] Vérifier qu'un seul lead unified est créé
- [ ] Vérifier le message d'erreur si tentative de doublon

**Test de synchronisation** :
- [ ] Vérifier que tous les favoris notaires ont un lead unified
- [ ] Vérifier qu'il n'y a pas de leads unified orphelins
- [ ] Exécuter la fonction de vérification

**Test de performances** :
- [ ] Tester avec 100+ favoris notaires
- [ ] Vérifier le temps de chargement de l'interface
- [ ] Vérifier les requêtes SQL (éviter les N+1)
- [ ] Optimiser si nécessaire

### 6.3 Tests d'affichage

**Test responsive** :
- [ ] Vérifier le popup sur mobile
- [ ] Vérifier le tableau sur tablette
- [ ] Vérifier les icônes et styles CSS

**Test cross-browser** :
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

**Test d'accessibilité** :
- [ ] Vérifier les contrastes de couleurs
- [ ] Vérifier la navigation au clavier
- [ ] Vérifier les attributs ARIA si nécessaire

---

## 🛡️ Phase 7 : Gestion des Erreurs et Edge Cases

### 7.1 Gestion des erreurs

**Notaire supprimé** :
- [ ] Si le notaire n'existe plus dans `wp_my_istymo_notaires`
- [ ] Option 1 : Marquer le lead comme "supprimé" avec un statut spécial
- [ ] Option 2 : Supprimer automatiquement le lead unified
- [ ] Recommandation : Option 2 avec log

**Données incomplètes** :
- [ ] Gérer les valeurs NULL dans `data_originale`
- [ ] Afficher des valeurs par défaut dans le popup
- [ ] Utiliser `??` ou `?:` pour les valeurs par défaut

**Erreurs de JSON** :
- [ ] Gérer les erreurs de décodage JSON
- [ ] Logger les erreurs de format
- [ ] Afficher un message d'erreur dans le popup si nécessaire

### 7.2 Edge cases

**Notaire sans nom d'office** :
- [ ] Utiliser `ville` comme fallback
- [ ] Ou utiliser "Notaire #ID"

**Notaire sans adresse** :
- [ ] Afficher uniquement code postal/ville
- [ ] Ou afficher "Adresse non renseignée"

**Notaire avec données vides** :
- [ ] Gérer tous les champs vides/NULL
- [ ] Afficher des valeurs par défaut appropriées
- [ ] Ne pas afficher les sections vides

**Caractères spéciaux** :
- [ ] Utiliser `esc_html()` ou `escapeHtml()` partout
- [ ] Tester avec des apostrophes, guillemets, accents
- [ ] Tester avec des caractères Unicode

---

## 📦 Phase 8 : Migration des Données Existantes

### 8.1 Script de migration

**Fonction** : `migrate_existing_notaire_favorites_to_unified()`

**Fichier** : `includes/unified-leads-manager.php` ou nouveau fichier de migration

**Logique** :
1. Récupérer tous les favoris notaires
2. Pour chaque favori :
   - Vérifier si un lead unified existe déjà
   - Si non, créer le lead unified
   - Logger les erreurs
3. Retourner un rapport de migration

**Code complet** (voir Phase 3.2)

### 8.2 Hook de migration

**Ajout dans `Unified_Leads_Manager::create_tables()`** :

```php
// Après la création/mise à jour des tables
if (!get_transient('my_istymo_notaire_migration_done')) {
    $migration_result = $this->migrate_existing_notaire_favorites_to_unified();
    
    if ($migration_result['created'] > 0 || $migration_result['errors'] > 0) {
        my_istymo_log(
            sprintf(
                'Migration notaires: %d créés, %d erreurs, %d ignorés',
                $migration_result['created'],
                $migration_result['errors'],
                $migration_result['skipped']
            ),
            'unified_leads'
        );
    }
    
    set_transient('my_istymo_notaire_migration_done', true, DAY_IN_SECONDS * 365);
}
```

**Alternative : Commande WP-CLI** :
- [ ] Créer une commande WP-CLI pour la migration
- [ ] Permettre l'exécution manuelle
- [ ] Afficher un rapport détaillé

---

## 📁 Fichiers à Modifier

### 1. `includes/unified-leads-manager.php`
- [x] Méthode `create_tables()` ou `update_table_for_notaire()`
- [x] Méthode `add_lead()` (vérifier compatibilité)
- [x] Méthode `format_lead_for_display()` (ajouter cas notaire dans `render_lead_row()`)
- [x] Nouvelle méthode `create_notaire_lead()`
- [x] Nouvelle méthode `get_lead_by_original_id()`
- [x] Nouvelle méthode `migrate_existing_notaire_favorites_to_unified()`

### 2. `my-istymo.php`
- [x] Fonction `my_istymo_ajax_toggle_notaire_favorite()` (ligne ~7546)
  - [x] Ajouter création lead unified lors de l'ajout en favoris
  - [x] Ajouter suppression lead unified lors du retrait des favoris
  - [x] Gérer le paramètre `remove_only` pour suppression forcée

### 3. `assets/js/unified-leads-admin.js`
- [x] Fonction `openLeadDetailModal()` (ligne ~23)
  - [x] Ajouter le cas 'notaire' pour l'icône et le label
- [x] Ajout de la synchronisation JavaScript (événements CustomEvent)

### 4. `templates/unified-leads-admin.php`
- [x] Fonction `generateModernLeadHTML()` (ligne ~1147)
  - [x] Ajouter la section complète pour les notaires
- [x] Fonction `display_lead_row()` (ligne ~1039)
  - [x] Ajouter le cas 'notaire' pour l'affichage dans le tableau

### 5. `assets/js/notaires-admin.js`
- [x] Ajout de l'événement `notaireFavoriteChanged` lors du toggle
- [x] Ajout des écouteurs pour `unifiedLeadDeleted` et `unifiedLeadAdded`

### 5. CSS (si nécessaire)
- [ ] `assets/css/unified-leads.css`
  - Ajouter styles spécifiques pour les notaires si besoin
  - Vérifier les styles existants pour les autres types

---

## ⚠️ Points d'Attention Critiques

### 1. Synchronisation
- **Problème** : L'ajout/suppression d'un favori notaire doit créer/supprimer le lead unified
- **Solution** : Modifier `toggle_notaire_favorite` pour appeler les méthodes unified leads
- **Vérification** : Tester tous les cas d'usage

### 2. Cohérence des données
- **Problème** : Si un notaire est modifié dans `wp_my_istymo_notaires`, le lead unified doit être mis à jour
- **Solution** : 
  - Option 1 : Ne pas mettre à jour automatiquement (données figées au moment de l'ajout)
  - Option 2 : Mettre à jour automatiquement via hook
  - **Recommandation** : Option 1 (données figées) pour l'historique

### 3. Performance
- **Problème** : Éviter les requêtes multiples lors de la création de leads
- **Solution** : 
  - Utiliser une seule requête pour récupérer le notaire
  - Utiliser une seule insertion pour créer le lead
  - Éviter les boucles dans les migrations

### 4. UX
- **Problème** : Le popup doit être cohérent avec les autres types de leads
- **Solution** : 
  - Utiliser la même structure HTML
  - Utiliser les mêmes classes CSS
  - Utiliser les mêmes icônes Font Awesome

### 5. Migration
- **Problème** : Gérer les favoris existants sans créer de doublons
- **Solution** : 
  - Vérifier l'existence avant insertion
  - Utiliser la contrainte UNIQUE
  - Logger les doublons détectés

### 6. Gestion des erreurs
- **Problème** : Ne pas faire échouer l'ajout en favoris si la création du lead unified échoue
- **Solution** : 
  - Logger l'erreur mais continuer l'ajout en favoris
  - Permettre une réparation manuelle via une fonction de sync

---

## 📝 Checklist de Déploiement

### Avant le déploiement
- [ ] Tous les tests fonctionnels passent
- [ ] Tous les tests de cohérence passent
- [ ] Migration testée en environnement de développement
- [ ] Code review effectué
- [ ] Documentation mise à jour

### Déploiement
- [ ] Sauvegarder la base de données
- [ ] Exécuter les migrations SQL
- [ ] Déployer le code
- [ ] Exécuter la migration des favoris existants
- [ ] Vérifier les logs

### Après le déploiement
- [ ] Vérifier que les nouveaux favoris créent des leads unified
- [ ] Vérifier que les retraits suppriment les leads unified
- [ ] Vérifier l'affichage dans l'interface unified leads
- [ ] Vérifier le popup dynamique
- [ ] Monitorer les erreurs dans les logs

---

## 🔄 Ordre d'Implémentation Recommandé

1. **Phase 1** : Modification de la base de données (ALTER TABLE)
2. **Phase 2** : Modification du gestionnaire unified leads
3. **Phase 5** : Structure des données (préparer le format JSON)
4. **Phase 3** : Intégration dans toggle_notaire_favorite
5. **Phase 4** : Affichage dans le popup et tableau
6. **Phase 6** : Tests fonctionnels
7. **Phase 7** : Gestion des erreurs
8. **Phase 8** : Migration des données existantes

---

## 📚 Ressources et Références

- Structure de la table unified_leads : `includes/unified-leads-manager.php` ligne 66
- Structure de la table notaires : `my-istymo.php` ligne 111
- Fonction toggle_notaire_favorite : `my-istymo.php` ligne 7546
- Popup dynamique : `assets/js/unified-leads-admin.js` ligne 23
- Génération HTML : `templates/unified-leads-admin.php` ligne 1147

---

## ✅ Validation Finale

Une fois toutes les phases terminées, vérifier :

1. [ ] Un notaire ajouté en favoris apparaît dans unified leads (à tester manuellement)
2. [ ] Un notaire retiré des favoris disparaît de unified leads (à tester manuellement)
3. [ ] Le popup affiche toutes les informations du notaire (à tester manuellement)
4. [ ] Le tableau affiche correctement les notaires (à tester manuellement)
5. [x] Les favoris existants sont migrés (fonction implémentée et exécutée automatiquement)
6. [ ] Aucune erreur dans les logs (à vérifier après tests)
7. [ ] Les performances sont acceptables (à tester manuellement)

---

**Date de création** : 2024  
**Version** : 1.0  
**Auteur** : Plan d'intégration complet


