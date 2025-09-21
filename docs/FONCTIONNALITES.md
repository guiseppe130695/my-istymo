# My Istymo - Documentation Complète des Fonctionnalités

## Vue d'ensemble

**My Istymo** est un plugin WordPress personnalisé développé par Brio Guiseppe, spécialisé dans la prospection et la gestion des Sociétés Civiles Immobilières (SCI) et des Diagnostics de Performance Énergétique (DPE). Le plugin offre une solution complète pour la recherche, la gestion des favoris, et l'envoi de campagnes de courriers personnalisés.

**Version actuelle :** 1.6  
**Dernière mise à jour :** 2025

---

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

---

## Module SCI - Recherche et Prospection

### Fonctionnalités principales

#### 1. Recherche par code postal
- **API INPI** : Recherche en temps réel des SCI par code postal
- **Pagination AJAX** : Navigation fluide dans les résultats (50 résultats par page)
- **Filtrage automatique** : Recherche uniquement des entités "SCI"
- **Géolocalisation** : Intégration Google Maps pour visualiser les adresses

#### 2. Gestion des favoris
- **Ajout/suppression** : Système de favoris avec icône étoile
- **Persistance** : Sauvegarde en base de données WordPress
- **Interface dédiée** : Page d'administration pour gérer les favoris
- **Export** : Possibilité d'exporter les données des favoris

#### 3. Interface utilisateur
- **Design responsive** : Compatible mobile et desktop
- **Styles personnalisés** : Interface moderne avec boutons verts
- **Notifications** : Messages d'erreur et de succès
- **Chargement dynamique** : AJAX pour une expérience fluide

### Shortcodes disponibles

#### `[sci_panel]`
```php
[sci_panel title="SCI – Recherche et Contact" show_config_warnings="true"]
```
- **Fonction** : Affiche le panneau principal de recherche SCI
- **Paramètres** :
  - `title` : Titre personnalisé du panneau
  - `show_config_warnings` : Afficher les avertissements de configuration

#### `[sci_favoris]`
```php
[sci_favoris title="Mes SCI Favoris" show_empty_message="true"]
```
- **Fonction** : Liste des SCI ajoutées aux favoris
- **Paramètres** :
  - `title` : Titre personnalisé
  - `show_empty_message` : Message si aucun favori

#### `[sci_campaigns]`
```php
[sci_campaigns title="Mes Campagnes de Lettres" show_empty_message="true"]
```
- **Fonction** : Gestion des campagnes de courriers
- **Paramètres** :
  - `title` : Titre personnalisé
  - `show_empty_message` : Message si aucune campagne

### API et intégrations

#### API INPI
- **Authentification** : Système de tokens automatique
- **Gestion d'erreurs** : Régénération automatique des tokens expirés
- **Rate limiting** : Protection contre les abus
- **Logs détaillés** : Traçabilité complète des appels API

#### Configuration requise
- **Identifiants INPI** : Client ID et Client Secret
- **URL API** : Endpoint de recherche INPI
- **Permissions** : Accès aux données des entreprises

---

## Module Campagnes - Envoi de Courriers

### Fonctionnalités principales

#### 1. Création de campagnes
- **Sélection multiple** : Choix de plusieurs SCI simultanément
- **Personnalisation** : Variables `[NOM]` pour personnaliser les courriers
- **Templates** : Modèles de courriers préconfigurés
- **Prévisualisation** : Aperçu avant envoi

#### 2. Intégration La Poste
- **API La Poste** : Envoi en lettre recommandée avec AR
- **Suivi** : Numéros de suivi et statuts d'envoi
- **Gestion des erreurs** : Retry automatique en cas d'échec
- **Logs détaillés** : Traçabilité complète des envois

#### 3. Génération de PDF
- **TCPDF** : Génération automatique des PDF
- **Mise en page** : Format lettre professionnel
- **Personnalisation** : En-têtes et pieds de page
- **Archivage** : Sauvegarde des PDF générés

### Processus d'envoi

1. **Sélection des SCI** : Interface de sélection multiple
2. **Rédaction du courrier** : Éditeur avec variables
3. **Validation** : Vérification des données
4. **Paiement** : Intégration WooCommerce
5. **Génération PDF** : Création automatique
6. **Envoi** : API La Poste
7. **Suivi** : Numéros de suivi

### Variables disponibles

- `[NOM]` : Nom du destinataire (dénomination SCI)
- `[ADRESSE]` : Adresse complète
- `[CODE_POSTAL]` : Code postal
- `[VILLE]` : Ville

---

## 🏠 Module DPE - Diagnostics de Performance Énergétique

### Fonctionnalités principales

#### 1. Recherche DPE
- **Base de données DPE** : Recherche par adresse
- **Filtres avancés** : Par type de bien, classe énergétique
- **Résultats détaillés** : Consommations, émissions CO2
- **Géolocalisation** : Intégration cartographique

#### 2. Gestion des favoris DPE
- **Système de favoris** : Identique au module SCI
- **Interface dédiée** : Page d'administration séparée
- **Export** : Données DPE exportables

#### 3. Interface utilisateur
- **Design cohérent** : Même style que le module SCI
- **Responsive** : Compatible tous appareils
- **Navigation fluide** : AJAX et pagination

### Shortcodes disponibles

#### `[dpe_panel]`
```php
[dpe_panel title="🏠 DPE – Recherche et Consultation" show_config_warnings="true"]
```
- **Fonction** : Panneau principal de recherche DPE
- **Paramètres** :
  - `title` : Titre personnalisé
  - `show_config_warnings` : Avertissements de configuration

---

## Module Paiement - Intégration WooCommerce

### Fonctionnalités principales

#### 1. Intégration complète
- **WooCommerce** : Système de paiement natif
- **Produits virtuels** : Création automatique des produits
- **Paniers** : Gestion des commandes
- **Facturation** : Génération automatique des factures

#### 2. Sécurité
- **Validation** : Vérification des données
- **Nonces** : Protection CSRF
- **Permissions** : Contrôle d'accès
- **Logs** : Traçabilité des paiements

#### 3. Interface utilisateur
- **Checkout personnalisé** : Interface adaptée
- **Confirmation** : Messages de succès
- **Erreurs** : Gestion des échecs de paiement

### Processus de paiement

1. **Sélection des services** : Choix des options
2. **Validation** : Vérification des données
3. **Redirection** : Vers WooCommerce
4. **Paiement** : Méthodes configurées
5. **Confirmation** : Retour et traitement
6. **Exécution** : Lancement de la campagne

---

## ⚙️ Module Administration

### Menus d'administration

#### Menu SCI
- **Panneau principal** : Vue d'ensemble et recherche
- **Mes Favoris** : Gestion des SCI favorites
- **Mes Campagnes** : Suivi des campagnes
- **Logs API** : Surveillance des appels API

#### Menu DPE
- **Panneau principal** : Recherche DPE
- **Mes Favoris DPE** : Gestion des favoris DPE

### Configuration

#### Gestionnaire de configuration
- **Tokens API** : Stockage sécurisé des identifiants
- **Paramètres** : Configuration globale du plugin
- **Validation** : Vérification des données
- **Cache** : Optimisation des performances

#### Gestionnaire de tokens INPI
- **Authentification** : Gestion automatique des tokens
- **Renouvellement** : Régénération automatique
- **Sécurité** : Chiffrement des données sensibles
- **Logs** : Traçabilité des authentifications

### Notifications et logs

#### Système de logs
- **Fichiers de log** : Stockage dans `/uploads/my-istymo-logs/`
- **Catégories** : Logs séparés par fonctionnalité
- **Rotation** : Gestion automatique de la taille
- **Format** : Timestamp + contexte + message

#### Notifications d'administration
- **Configuration** : Avertissements de configuration manquante
- **Erreurs API** : Alertes en cas de problème
- **Statuts** : Informations sur l'état du système

---

## 🎨 Module Interface Utilisateur

### Styles CSS

#### Fichiers principaux
- `style.css` : Styles généraux du plugin (83KB)
- `dpe-style.css` : Styles spécifiques DPE (17KB)
- `campaigns.css` : Styles des campagnes (6.5KB)
- `admin-sci.css` : Styles d'administration (5.1KB)

#### Design system
- **Couleurs** : Palette verte cohérente
- **Typographie** : Police 12px pour les tableaux
- **Boutons** : Styles dégradés et hover effects
- **Responsive** : Design adaptatif

### Scripts JavaScript

#### Fichiers principaux
- `admin-sci.js` : Administration SCI (21KB)
- `favoris.js` : Gestion des favoris (11KB)
- `lettre.js` : Création de campagnes (14KB)
- `payment.js` : Intégration paiement (33KB)
- `dpe-frontend.js` : Interface DPE (14KB)
- `enhanced-features.js` : Fonctionnalités avancées (13KB)

#### Fonctionnalités JavaScript
- **AJAX** : Communication asynchrone
- **Validation** : Vérification côté client
- **Animations** : Transitions fluides
- **Sécurité** : Désactivation menu contextuel

---

## 🔧 Module Technique

### Gestionnaires PHP

#### Classes principales
- `SCI_Shortcodes` : Gestion des shortcodes SCI
- `DPE_Shortcodes` : Gestion des shortcodes DPE
- `Config_Manager` : Configuration du plugin
- `Campaign_Manager` : Gestion des campagnes
- `Favoris_Handler` : Gestion des favoris
- `INPI_Token_Manager` : Authentification INPI
- `WooCommerce_Integration` : Intégration paiement

#### Fonctionnalités techniques
- **Sécurité** : Validation et échappement des données
- **Performance** : Cache et optimisation
- **Compatibilité** : Support multilingue
- **Maintenance** : Code modulaire et extensible

### Système de templates

#### Structure
- `sci-panel.php` : Panneau principal SCI
- `dpe-panel.php` : Panneau DPE
- `sci-campaigns.php` : Gestion des campagnes
- `sci-favoris.php` : Liste des favoris
- `admin-notifications.php` : Notifications admin

#### Fonctionnalités
- **Séparation** : Logique et présentation
- **Réutilisabilité** : Templates modulaires
- **Personnalisation** : Variables dynamiques
- **Maintenance** : Code propre et documenté

---

## 📊 Fonctionnalités Avancées

### Système de cache
- **Pagination** : Cache des résultats de recherche
- **Sélections** : Persistance des choix utilisateur (24h)
- **Performance** : Optimisation des requêtes
- **Nettoyage** : Gestion automatique du cache

### Sécurité renforcée
- **Nonces** : Protection CSRF sur tous les formulaires
- **Validation** : Vérification stricte des données
- **Permissions** : Contrôle d'accès granulaire
- **Logs** : Traçabilité complète des actions

### Compatibilité
- **Thèmes** : Compatible avec tous les thèmes WordPress
- **Plugins** : Intégration avec WooCommerce et ACF
- **Responsive** : Design adaptatif
- **Accessibilité** : Standards WCAG

---

## 🚀 Installation et Configuration

### Prérequis
1. **WordPress 5.0+** installé et configuré
2. **PHP 7.4+** avec extensions requises
3. **WooCommerce 5.0+** activé
4. **Advanced Custom Fields** activé
5. **Permissions** d'écriture sur `/uploads/`

### Installation
1. **Télécharger** le plugin dans `wp-content/plugins/my-istymo/`
2. **Activer** le plugin depuis l'administration WordPress
3. **Configurer** les identifiants API dans SCI > Configuration
4. **Configurer** les identifiants INPI dans SCI > Identifiants INPI
5. **Configurer** les données expéditeur dans SCI > Configuration

### Configuration des APIs
- **API INPI** : Client ID et Client Secret
- **API La Poste** : Identifiants d'authentification
- **Google Maps** : Clé API pour la géolocalisation

---

## 📈 Versions et Évolutions

### Version 1.6 (Actuelle)
- Interface utilisateur modernisée
- Système de pagination amélioré
- Styles CSS harmonisés
- Intégration WooCommerce complète
- Module DPE ajouté
- Sécurité renforcée

### Fonctionnalités prévues
- Module de reporting avancé
- Intégration avec d'autres APIs
- Système de notifications push
- Application mobile

---

## 🐛 Dépannage et Support

### Problèmes courants

#### Recherche ne fonctionne pas
- Vérifier la configuration API INPI
- Contrôler les logs d'erreur
- S'assurer que les codes postaux sont configurés

#### Envoi de courriers échoue
- Vérifier la configuration API La Poste
- Contrôler les données expéditeur
- Vérifier le solde API La Poste

#### Problèmes de paiement
- S'assurer que WooCommerce est activé
- Vérifier la configuration des méthodes de paiement
- Contrôler les logs WooCommerce

### Logs et débogage
- **Logs API** : Disponibles dans SCI > Logs API
- **Mode debug** : Disponible en développement
- **Console JavaScript** : Pour le débogage frontend

---

## Licence et Support

**Développeur :** Brio Guiseppe  
**Version :** 1.6  
**Licence :** Tous droits réservés

### Support technique
1. Consulter la documentation
2. Vérifier les logs d'erreur
3. Contacter le support technique

---

*Documentation générée automatiquement - Dernière mise à jour : 2025*
