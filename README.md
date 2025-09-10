# 🏢 My Istymo - Plugin de Gestion Immobilière

Plugin WordPress complet pour la prospection immobilière, la gestion des leads et l'envoi de campagnes de courriers personnalisés.

## 📋 Description

My Istymo est une solution complète pour les professionnels de l'immobilier qui combine :
- **Recherche et prospection** de Sociétés Civiles Immobilières (SCI)
- **Gestion avancée des leads** avec suivi des actions et statuts
- **Campagnes de courriers** automatisées via l'API La Poste
- **Intégration WooCommerce** pour les paiements sécurisés
- **Diagnostics de Performance Énergétique (DPE)** avec recherche et gestion

## ✨ Fonctionnalités principales

### 🔍 Recherche et Prospection SCI
- Recherche par code postal avec pagination AJAX
- Affichage des résultats en temps réel
- Informations détaillées : dénomination, dirigeant, SIREN, adresse, ville
- Géolocalisation Google Maps intégrée
- Système de favoris avec gestion avancée

### 🏠 Diagnostic de Performance Énergétique (DPE)
- Recherche DPE via l'API ADEME
- Affichage des informations énergétiques détaillées
- Gestion des compléments d'adresse spécifiques au logement
- Système de favoris DPE intégré
- Création automatique de leads unifiés depuis les DPE

### 👥 Gestion des Leads
- **Interface unifiée** pour la gestion des prospects
- **5 actions essentielles** : Changer statut, Ajouter action, Programmer appel, Ajouter note, Supprimer
- **Suivi des statuts** : Nouveau, En cours, Qualifié, Proposition, Négociation, Gagné, Perdu
- **Historique des actions** : Appels, Emails, SMS, Rendez-vous, Notes
- **Planification d'appels** avec rappels automatiques

### 📬 Campagnes de courriers
- Création de campagnes personnalisées
- Sélection multiple de SCI
- Rédaction de courriers avec variables personnalisées `[NOM]`
- Intégration API La Poste pour l'envoi
- Suivi des statuts d'envoi
- Génération de PDF automatique

### 💳 Système de paiement
- Intégration WooCommerce native
- Paiement sécurisé pour les campagnes
- Gestion des commandes et factures
- Support Stripe et autres passerelles

### ⭐ Gestion des favoris
- Ajout/suppression de SCI aux favoris
- Interface dédiée pour consulter les favoris
- Export et gestion des données
- Synchronisation multi-appareils

## 🚀 Installation

### Prérequis système
- **WordPress** 5.0+
- **PHP** 7.4+
- **WooCommerce** 5.0+ (pour les paiements)
- **Advanced Custom Fields** (ACF) pour les codes postaux utilisateurs

### Étapes d'installation
1. **Télécharger le plugin** dans le dossier `wp-content/plugins/my-istymo/`
2. **Activer le plugin** depuis l'administration WordPress
3. **Configurer les identifiants API** dans My Istymo > Configuration
4. **Configurer les identifiants INPI** dans My Istymo > Identifiants INPI
5. **Configurer les données expéditeur** dans My Istymo > Configuration

### APIs externes requises
- **API INPI** : Pour la recherche des données SCI
- **API ADEME** : Pour la recherche des données DPE
- **API La Poste** : Pour l'envoi de courriers
- **Google Maps** : Pour la géolocalisation

## 📖 Utilisation

### Shortcodes disponibles

#### 1. Panneau de recherche principal
```php
[sci_panel title="🏢 SCI – Recherche et Contact" show_config_warnings="true"]
```

#### 2. Liste des favoris
```php
[sci_favoris title="⭐ Mes SCI Favoris" show_empty_message="true"]
```

#### 3. Gestion des campagnes
```php
[sci_campaigns title="📬 Mes Campagnes de Lettres" show_empty_message="true"]
```

#### 4. Panneau DPE
```php
[dpe_panel title="🏠 Diagnostic de Performance Énergétique" show_config_warnings="true"]
```

#### 5. Favoris DPE
```php
[dpe_favoris title="⭐ Mes DPE Favoris" show_empty_message="true"]
```

#### 6. Interface de gestion des leads
```php
[unified_leads_admin]
```

### Interface utilisateur

#### Recherche SCI
1. Sélectionner un code postal dans la liste
2. Cliquer sur "🔍 Rechercher les SCI"
3. Parcourir les résultats avec la pagination
4. Ajouter des SCI aux favoris (⭐)
5. Sélectionner des SCI pour une campagne

#### Recherche DPE
1. Saisir une adresse dans le champ de recherche
2. Cliquer sur "🔍 Rechercher les DPE"
3. Consulter les informations énergétiques détaillées
4. Ajouter des DPE aux favoris (⭐)
5. Les DPE favoris créent automatiquement des leads unifiés

#### Gestion des Leads
1. Accéder à l'interface de gestion des leads
2. Utiliser les **5 actions essentielles** :
   - **🔄 Changer Statut** : Faire évoluer le lead
   - **➕ Ajouter Action** : Enregistrer une activité
   - **📞 Programmer Appel** : Planifier un contact
   - **📝 Ajouter Note** : Documenter rapidement
   - **🗑️ Supprimer** : Gérer avec confirmation

#### Création de campagne
1. Sélectionner les SCI désirées (checkboxes)
2. Cliquer sur "📬 Créez une campagne d'envoi de courriers"
3. Vérifier la sélection dans l'étape 1
4. Rédiger le titre et contenu du courrier
5. Utiliser `[NOM]` pour personnaliser le destinataire
6. Passer la commande et procéder au paiement

## 🎨 Personnalisation

### Styles CSS
Le plugin utilise des styles CSS personnalisés pour :
- Boutons avec fond blanc et hover vert
- Boutons d'action en vert dégradé
- Tableaux avec police 12px
- Popups harmonisés
- Interface responsive et moderne

### Variables de personnalisation
- `[NOM]` : Nom du destinataire dans les courriers
- Codes postaux configurables par utilisateur
- Templates de courriers personnalisables
- Statuts de leads personnalisables

## 🔧 Administration

### Menu My Istymo
- **Panneau principal** : Recherche et gestion
- **Gestion des Leads** : Interface unifiée des prospects
- **Mes Favoris** : Gestion des SCI favorites
- **Mes Campagnes** : Suivi des campagnes
- **Logs API** : Surveillance des appels API

### Configuration
- **Tokens API** : Configuration des accès externes
- **Identifiants INPI** : Gestion des tokens INPI
- **Données expéditeur** : Configuration des informations d'envoi
- **Intégration WooCommerce** : Paramètres de paiement

## 📊 Fonctionnalités techniques

### Système de cache
- Cache de pagination pour éviter les rechargements
- Persistance des sélections SCI (24h)
- Optimisation des performances
- Cache des données de leads

### Sécurité
- Validation des données utilisateur
- Protection CSRF avec nonces
- Échappement HTML automatique
- Vérification des permissions utilisateur
- Confirmation pour les actions destructives

### Compatibilité
- Responsive design
- Compatible avec tous les thèmes WordPress
- Intégration WooCommerce native
- Support multilingue
- Compatible mobile et tablette

## 🐛 Dépannage

### Problèmes courants

#### Recherche ne fonctionne pas
- Vérifier la configuration API INPI
- Contrôler les logs d'erreur
- S'assurer que les codes postaux sont configurés

#### Gestion des leads ne s'affiche pas
- Vérifier les permissions utilisateur
- Contrôler la configuration de la base de données
- Vérifier les logs d'erreur PHP

#### Envoi de courriers échoue
- Vérifier la configuration API La Poste
- Contrôler les données expéditeur
- Vérifier le solde API La Poste

#### Problèmes de paiement
- S'assurer que WooCommerce est activé
- Vérifier la configuration des méthodes de paiement
- Contrôler les logs WooCommerce

### Logs et débogage
- Logs API disponibles dans My Istymo > Logs API
- Mode debug disponible en développement
- Console JavaScript pour le débogage frontend
- Logs de base de données pour les leads

## 📈 Versions

### Version 1.8 (Actuelle)
- ✅ **Intégration DPE** complète avec API ADEME
- ✅ **Gestion des compléments d'adresse** spécifiques au logement
- ✅ **Système de favoris DPE** intégré
- ✅ **Création automatique de leads** depuis les DPE
- ✅ **Interface de gestion des leads** unifiée
- ✅ **5 actions essentielles** pour les leads
- ✅ **Système de statuts** avancé
- ✅ **Planification d'appels** intégrée
- ✅ **Historique des actions** complet
- ✅ Interface utilisateur modernisée
- ✅ Système de pagination amélioré
- ✅ Styles CSS harmonisés
- ✅ **Système unifié de gestion des leads** avec automatisation bidirectionnelle
- ✅ **Workflow de gestion** avec transitions de statuts
- ✅ **Actions en lot** et sélection multiple
- ✅ **Export et statistiques** avancées

### Fonctionnalités ajoutées
- **Intégration DPE** : Recherche et gestion des diagnostics énergétiques
- **Compléments d'adresse** : Gestion spécifique des compléments de logement
- **Favoris DPE** : Système de sauvegarde des DPE intéressants
- **Leads automatiques** : Création de prospects depuis les DPE favoris
- **Système unifié de leads** : Transformation des favoris en système professionnel
- **Automatisation bidirectionnelle** : Synchronisation entre favoris et leads
- **Workflow intelligent** : Transitions de statuts avec validation
- **Actions contextuelles** : Actions suggérées selon le statut
- **Interface professionnelle** : Design moderne et responsive
- **Export de données** : CSV/Excel avec filtres
- **Statistiques avancées** : Métriques et graphiques

## 🏗️ Architecture du Plugin

### Structure des dossiers
```
wp-content/my-istymo/
├── assets/
│   ├── css/           # Styles CSS du plugin
│   └── js/            # Scripts JavaScript
├── includes/          # Classes et gestionnaires PHP
├── templates/         # Templates d'affichage
├── lib/              # Bibliothèques externes (TCPDF)
├── tcpdf/            # Générateur de PDF
└── my-istymo.php     # Fichier principal du plugin
```

### Dépendances externes
- **WordPress 5.0+**
- **PHP 7.4+**
- **WooCommerce 5.0+** (paiements)
- **Advanced Custom Fields (ACF)** (codes postaux utilisateurs)
- **TCPDF** (génération de PDF)

## 🗄️ Base de Données

### Tables personnalisées
- **`{prefix}sci_favoris`** : Favoris SCI par utilisateur
- **`{prefix}dpe_favoris`** : Favoris DPE par utilisateur
- **`{prefix}sci_campaigns`** : Campagnes de courriers
- **`{prefix}sci_letters`** : Lettres individuelles
- **`{prefix}my_istymo_unified_leads`** : Leads unifiés
- **`{prefix}my_istymo_lead_actions`** : Actions sur les leads

### Options WordPress
- **`my_istymo_config`** : Configuration générale
- **`my_istymo_dpe_config`** : Configuration DPE
- **`my_istymo_inpi_credentials`** : Identifiants INPI

## 🎨 Interface Utilisateur

### Styles CSS
- **`style.css`** : Styles généraux (83KB)
- **`dpe-style.css`** : Styles DPE (17KB)
- **`campaigns.css`** : Styles campagnes (6.5KB)
- **`admin-sci.css`** : Styles administration (5.1KB)
- **`unified-leads.css`** : Styles leads unifiés

### Scripts JavaScript
- **`admin-sci.js`** : Administration SCI (21KB)
- **`favoris.js`** : Gestion des favoris (11KB)
- **`lettre.js`** : Création de lettres (14KB)
- **`payment.js`** : Intégration paiement (33KB)
- **`dpe-frontend.js`** : Interface DPE (14KB)
- **`unified-leads-admin.js`** : Gestion des leads (16KB)

## 🔧 Classes PHP Principales

### Gestionnaires de Configuration
- **`SCI_Config_Manager`** : Configuration générale
- **`DPE_Config_Manager`** : Configuration DPE
- **`SCI_INPI_Token_Manager`** : Gestion des tokens INPI

### Gestionnaires de Fonctionnalités
- **`SCI_Shortcodes`** : Shortcodes SCI
- **`DPE_Shortcodes`** : Shortcodes DPE
- **`SCI_Favoris_Handler`** : Gestion des favoris SCI
- **`DPE_Favoris_Handler`** : Gestion des favoris DPE
- **`SCI_Campaign_Manager`** : Gestion des campagnes
- **`SCI_WooCommerce_Integration`** : Intégration WooCommerce

### Système de Leads Unifiés
- **`Unified_Leads_Manager`** : Gestionnaire principal des leads
- **`Lead_Status_Manager`** : Gestion des statuts
- **`Lead_Actions_Manager`** : Gestion des actions
- **`Lead_Workflow`** : Gestion du workflow

## 📊 Système de Leads Unifiés

### Fonctionnalités principales
- **Automatisation bidirectionnelle** : Synchronisation entre favoris et leads
- **Workflow intelligent** : Transitions de statuts avec validation
- **Actions contextuelles** : Actions suggérées selon le statut
- **Interface professionnelle** : Design moderne et responsive
- **Export de données** : CSV/Excel avec filtres
- **Statistiques avancées** : Métriques et graphiques

### Statuts disponibles
- **Nouveau** : Lead récemment créé
- **En cours** : Lead en cours de traitement
- **Qualifié** : Lead qualifié
- **Proposition** : Proposition envoyée
- **Négociation** : En cours de négociation
- **Gagné** : Lead converti
- **Perdu** : Lead perdu

### Actions disponibles
- **Changer Statut** : Faire évoluer le lead
- **Ajouter Action** : Enregistrer une activité
- **Programmer Appel** : Planifier un contact
- **Ajouter Note** : Documenter rapidement
- **Supprimer** : Gérer avec confirmation

## 🔒 Sécurité

### Validation des données
- Sanitisation avec `sanitize_text_field()`
- Validation avec `wp_verify_nonce()`
- Échappement avec `esc_html()`

### Authentification
- Vérification des permissions avec `current_user_can()`
- Protection CSRF avec nonces
- Validation des tokens API

### Sécurité JavaScript
- Désactivation du menu contextuel
- Protection contre les raccourcis clavier
- Validation côté client

## 📈 Performance

### Optimisations CSS
- Minification des styles
- Utilisation de variables CSS
- Responsive design optimisé

### Optimisations JavaScript
- Chargement asynchrone
- Debouncing des recherches
- Lazy loading des images

### Optimisations PHP
- Cache des requêtes API
- Pagination des résultats
- Optimisation des requêtes SQL

## 🚀 Maintenance et Évolutions

### Structure modulaire
- Séparation claire des responsabilités
- Couplage faible entre modules
- Extensibilité facilitée

### Documentation
- Commentaires détaillés
- Documentation des APIs
- Exemples d'utilisation

### Tests
- Validation des données
- Gestion d'erreurs
- Logs de débogage

## 🤝 Support

Pour toute question ou problème :
1. Consulter la documentation
2. Vérifier les logs d'erreur
3. Contacter le support technique

## 📄 Licence

Plugin développé par Brio Guiseppe - Tous droits réservés

---

**Note :** Ce plugin nécessite une configuration complète des APIs externes pour fonctionner correctement. Assurez-vous de configurer tous les identifiants requis avant utilisation.
