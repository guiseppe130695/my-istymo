# 🏗️ My Istymo - Documentation Complète de l'Architecture du Code

## 📋 Vue d'ensemble

Ce document décrit l'architecture complète du code du plugin **My Istymo**, incluant tous les fichiers, classes, et leur organisation. Le plugin suit une architecture modulaire avec séparation des responsabilités.

**Version actuelle :** 1.6  
**Dernière mise à jour :** 2025

---

## 🏗️ Structure Générale du Plugin

### Arborescence complète
```
wp-content/my-istymo/
├── 📁 assets/                    # Ressources frontend
│   ├── 📁 css/                   # Styles CSS
│   │   ├── style.css (83KB)      # Styles généraux
│   │   ├── dpe-style.css (17KB)  # Styles DPE
│   │   ├── campaigns.css (6.5KB) # Styles campagnes
│   │   ├── campaigns-popup.css (2.3KB) # Styles popups
│   │   └── admin-sci.css (5.1KB) # Styles admin
│   └── 📁 js/                    # Scripts JavaScript
│       ├── admin-sci.js (21KB)   # Administration SCI
│       ├── favoris.js (11KB)     # Gestion favoris
│       ├── lettre.js (14KB)      # Création lettres
│       ├── payment.js (33KB)     # Intégration paiement
│       ├── dpe-frontend.js (14KB) # Interface DPE
│       ├── dpe-favoris.js (11KB) # Favoris DPE
│       └── enhanced-features.js (13KB) # Fonctionnalités avancées
├── 📁 includes/                  # Classes et gestionnaires PHP
│   ├── config-manager.php (44KB) # Gestionnaire configuration
│   ├── campaign-manager.php (19KB) # Gestionnaire campagnes
│   ├── favoris-handler.php (8.1KB) # Gestionnaire favoris
│   ├── inpi-token-manager.php (20KB) # Gestionnaire tokens INPI
│   ├── shortcodes.php (50KB)     # Shortcodes SCI
│   ├── woocommerce-integration.php (44KB) # Intégration WooCommerce
│   ├── template-loader.php (1.2KB) # Chargeur de templates
│   ├── dpe-handler.php (16KB)    # Gestionnaire DPE
│   ├── dpe-favoris-handler.php (12KB) # Favoris DPE
│   ├── dpe-config-manager.php (14KB) # Configuration DPE
│   └── dpe-shortcodes.php (29KB) # Shortcodes DPE
├── 📁 templates/                 # Templates d'affichage
│   ├── sci-panel.php (9.7KB)     # Panneau principal SCI
│   ├── dpe-panel.php (19KB)        # Panneau DPE
│   ├── sci-campaigns.php (13KB)  # Gestion campagnes
│   ├── sci-favoris.php (4.2KB)   # Liste favoris SCI
│   ├── dpe-favoris.php (10KB)    # Liste favoris DPE
│   ├── sci-logs.php (3.4KB)      # Logs d'administration
│   └── admin-notifications.php (4.2KB) # Notifications admin
├── 📁 tcpdf/                     # Bibliothèque TCPDF
│   ├── tcpdf.php (889KB)         # Classe principale TCPDF
│   ├── tcpdf_barcodes_1d.php (72KB) # Codes-barres 1D
│   ├── tcpdf_barcodes_2d.php (14KB) # Codes-barres 2D
│   ├── 📁 include/               # Fichiers inclus TCPDF
│   ├── 📁 fonts/                 # Polices TCPDF
│   ├── 📁 config/                # Configuration TCPDF
│   └── 📁 tools/                 # Outils TCPDF
├── 📁 lib/                       # Bibliothèques externes
│   └── 📁 tcpdf/                 # Copie de sauvegarde TCPDF
├── 📁 .git/                      # Contrôle de version Git
├── my-istymo.php (38KB)          # Fichier principal du plugin
├── popup-lettre.php (3.4KB)      # Popup de création de lettres
├── index.php (28B)               # Fichier de sécurité
├── .gitignore (18B)              # Configuration Git
├── README.md (5.8KB)             # Documentation principale
├── README-TEMPLATES.md (5.4KB)   # Documentation templates
├── CHANGELOG.md (2.9KB)          # Journal des modifications
├── FONCTIONNALITES.md (13KB)     # Documentation fonctionnalités
└── BASE_DE_DONNEES.md (26KB)     # Documentation base de données
```

### Statistiques globales
- **Total fichiers** : ~50 fichiers
- **Total lignes de code** : ~15,000 lignes
- **Taille totale** : ~2.5 MB
- **Classes PHP** : 11 classes principales
- **Fichiers JavaScript** : 7 fichiers
- **Fichiers CSS** : 5 fichiers

---

## 🎯 Fichier Principal

### `my-istymo.php` (38KB, 1055 lignes)

**Description** : Point d'entrée principal du plugin

**Fonctionnalités principales** :
- Définition du plugin WordPress
- Inclusion des fichiers de dépendances
- Initialisation des hooks WordPress
- Gestion des menus d'administration
- Fonctions utilitaires globales

**Fonctions clés** :
```php
// Fonctions utilitaires
function sci_get_user_postal_codes($user_id = null)
function my_istymo_log($message, $context = 'general')

// Hooks d'administration
add_action('admin_menu', 'sci_ajouter_menu')
add_action('wp_ajax_sci_inpi_search_ajax', 'sci_inpi_search_ajax')

// Gestion des tokens INPI
function sci_fetch_inpi_data_with_pagination($code_postal, $page = 1, $page_size = 50)
```

---

## 📁 Dossier Includes - Classes PHP

### 🔧 Gestionnaires de Configuration

#### `config-manager.php` (44KB, 963 lignes)
**Classe** : `SCI_Config_Manager`

**Responsabilités** :
- Gestion de la configuration des APIs
- Interface d'administration pour les paramètres
- Validation et sanitisation des données
- Stockage sécurisé des identifiants

**Méthodes principales** :
```php
class SCI_Config_Manager {
    public function add_config_menu()
    public function register_settings()
    public function sanitize_config($input)
    public function get_config()
    public function get($key, $default = '')
    public function is_configured()
}
```

#### `dpe-config-manager.php` (14KB, 341 lignes)
**Classe** : `DPE_Config_Manager`

**Responsabilités** :
- Configuration spécifique au module DPE
- Gestion des paramètres API DPE
- Interface d'administration DPE

**Méthodes principales** :
```php
class DPE_Config_Manager {
    public function add_config_menu()
    public function register_settings()
    public function sanitize_config($input)
    public function get_config()
}
```

### 🔍 Gestionnaires de Recherche

#### `inpi-token-manager.php` (20KB, 495 lignes)
**Classe** : `SCI_INPI_Token_Manager`

**Responsabilités** :
- Gestion automatique des tokens INPI
- Authentification avec l'API INPI
- Régénération automatique des tokens expirés
- Stockage sécurisé des credentials

**Méthodes principales** :
```php
class SCI_INPI_Token_Manager {
    public function create_credentials_table()
    public function get_token()
    public function refresh_token()
    public function handle_auth_error()
    public function add_credentials_menu()
}
```

#### `dpe-handler.php` (16KB, 429 lignes)
**Classe** : `DPE_Handler`

**Responsabilités** :
- Gestion des requêtes DPE
- Interface avec l'API DPE ADEME
- Traitement des données DPE
- Validation des résultats

**Méthodes principales** :
```php
class DPE_Handler {
    public function search_dpe($address)
    public function process_dpe_results($data)
    public function validate_dpe_data($dpe_data)
    public function format_dpe_for_display($dpe)
}
```

### ⭐ Gestionnaires de Favoris

#### `favoris-handler.php` (8.1KB, 251 lignes)
**Classe** : `SCI_Favoris_Handler`

**Responsabilités** :
- Gestion des SCI favorites
- Ajout/suppression de favoris
- Interface AJAX pour les favoris
- Création de la table des favoris

**Méthodes principales** :
```php
class SCI_Favoris_Handler {
    public function create_favoris_table()
    public function ajax_add_favori()
    public function ajax_remove_favori()
    public function get_user_favoris($user_id)
    public function add_favori($user_id, $sci_data)
}
```

#### `dpe-favoris-handler.php` (12KB, 362 lignes)
**Classe** : `DPE_Favoris_Handler`

**Responsabilités** :
- Gestion des favoris DPE
- Stockage des données DPE complètes
- Interface AJAX pour les favoris DPE
- Création de la table des favoris DPE

**Méthodes principales** :
```php
class DPE_Favoris_Handler {
    public function create_favoris_table()
    public function ajax_add_favori()
    public function ajax_remove_favori()
    public function get_user_favoris($user_id)
    public function add_favori($user_id, $dpe_data)
}
```

### 📬 Gestionnaires de Campagnes

#### `campaign-manager.php` (19KB, 552 lignes)
**Classe** : `SCI_Campaign_Manager`

**Responsabilités** :
- Gestion des campagnes de lettres
- Création et suivi des campagnes
- Intégration avec l'API La Poste
- Génération de PDF

**Méthodes principales** :
```php
class SCI_Campaign_Manager {
    public function create_tables()
    public function create_campaign($user_id, $title, $content, $scis)
    public function send_campaign($campaign_id)
    public function generate_pdf($campaign_id)
    public function get_campaign_status($campaign_id)
}
```

### 🎨 Gestionnaires de Shortcodes

#### `shortcodes.php` (50KB, 1186 lignes)
**Classe** : `SCI_Shortcodes`

**Responsabilités** :
- Gestion des shortcodes SCI
- Interface frontend pour la recherche
- Gestion des assets CSS/JS
- Handlers AJAX pour le frontend

**Méthodes principales** :
```php
class SCI_Shortcodes {
    public function sci_panel_shortcode($atts)
    public function sci_favoris_shortcode($atts)
    public function sci_campaigns_shortcode($atts)
    public function enqueue_frontend_scripts()
    public function frontend_search_ajax()
}
```

#### `dpe-shortcodes.php` (29KB, 687 lignes)
**Classe** : `DPE_Shortcodes`

**Responsabilités** :
- Gestion des shortcodes DPE
- Interface frontend pour la recherche DPE
- Gestion des assets DPE
- Handlers AJAX pour le frontend DPE

**Méthodes principales** :
```php
class DPE_Shortcodes {
    public function dpe_panel_shortcode($atts)
    public function enqueue_frontend_scripts()
    public function force_enqueue_assets($codesPostauxArray = [])
}
```

### 💳 Intégration WooCommerce

#### `woocommerce-integration.php` (44KB, 1068 lignes)
**Classe** : `SCI_WooCommerce_Integration`

**Responsabilités** :
- Intégration complète avec WooCommerce
- Création automatique de produits
- Gestion des commandes
- Traitement des paiements

**Méthodes principales** :
```php
class SCI_WooCommerce_Integration {
    public function create_product()
    public function process_order($order_id)
    public function handle_payment_success($order_id)
    public function create_campaign_from_order($order_id)
    public function add_order_meta($order_id, $campaign_data)
}
```

### 🔧 Utilitaires

#### `template-loader.php` (1.2KB, 42 lignes)
**Responsabilités** :
- Chargement des templates
- Gestion du contexte des templates
- Séparation logique/présentation

**Fonctions principales** :
```php
function sci_load_template($template_name, $context = [])
function sci_get_template_path($template_name)
function sci_render_template($template_path, $context)
```

---

## 📁 Dossier Templates - Interface Utilisateur

### 🏢 Templates SCI

#### `sci-panel.php` (9.7KB, 223 lignes)
**Description** : Panneau principal de recherche SCI

**Fonctionnalités** :
- Interface de recherche par code postal
- Affichage des résultats avec pagination
- Gestion des favoris
- Intégration Google Maps

**Variables de contexte** :
```php
$codesPostauxArray    // Codes postaux de l'utilisateur
$config_manager       // Gestionnaire de configuration
$inpi_token_manager   // Gestionnaire de tokens INPI
$woocommerce_integration // Intégration WooCommerce
$campaign_manager     // Gestionnaire de campagnes
```

#### `sci-favoris.php` (4.2KB, 106 lignes)
**Description** : Liste des SCI favorites

**Fonctionnalités** :
- Affichage des favoris de l'utilisateur
- Actions de suppression
- Export des données
- Interface de gestion

#### `sci-campaigns.php` (13KB, 305 lignes)
**Description** : Gestion des campagnes de lettres

**Fonctionnalités** :
- Liste des campagnes existantes
- Création de nouvelles campagnes
- Suivi des statuts d'envoi
- Gestion des lettres individuelles

#### `sci-logs.php` (3.4KB, 68 lignes)
**Description** : Logs d'administration

**Fonctionnalités** :
- Affichage des logs API
- Surveillance des erreurs
- Historique des actions
- Outils de débogage

### 🏠 Templates DPE

#### `dpe-panel.php` (19KB, 488 lignes)
**Description** : Panneau principal de recherche DPE

**Fonctionnalités** :
- Interface de recherche par adresse
- Affichage des résultats DPE
- Gestion des favoris DPE
- Intégration cartographique

#### `dpe-favoris.php` (10KB, 285 lignes)
**Description** : Liste des favoris DPE

**Fonctionnalités** :
- Affichage des biens favoris
- Détails complets des DPE
- Actions de gestion
- Export des données

### 🔔 Templates d'Administration

#### `admin-notifications.php` (4.2KB, 78 lignes)
**Description** : Notifications d'administration

**Fonctionnalités** :
- Avertissements de configuration
- Statuts des APIs
- Messages d'erreur
- Recommandations

---

## 📁 Dossier Assets - Frontend

### 🎨 Styles CSS

#### `style.css` (83KB, 3110 lignes)
**Description** : Styles généraux du plugin

**Sections principales** :
- **Variables CSS** : Couleurs, polices, espacements
- **Layout** : Grilles, conteneurs, responsive
- **Composants** : Boutons, formulaires, tableaux
- **Modules** : SCI, campagnes, favoris
- **Responsive** : Adaptations mobile/tablette

#### `dpe-style.css` (17KB, 664 lignes)
**Description** : Styles spécifiques au module DPE

**Sections principales** :
- **Interface DPE** : Panneau de recherche
- **Résultats DPE** : Affichage des données
- **Favoris DPE** : Gestion des favoris
- **Responsive DPE** : Adaptations mobile

#### `campaigns.css` (6.5KB, 326 lignes)
**Description** : Styles des campagnes

**Sections principales** :
- **Création de campagne** : Formulaires
- **Liste des campagnes** : Tableaux
- **Statuts** : Indicateurs visuels
- **Popups** : Modales de confirmation

#### `campaigns-popup.css` (2.3KB, 109 lignes)
**Description** : Styles des popups de campagne

**Sections principales** :
- **Modales** : Fenêtres popup
- **Overlays** : Arrière-plans
- **Animations** : Transitions
- **Responsive** : Adaptations

#### `admin-sci.css` (5.1KB, 193 lignes)
**Description** : Styles d'administration

**Sections principales** :
- **Menus admin** : Navigation
- **Pages admin** : Interfaces
- **Formulaires** : Configuration
- **Tableaux** : Données

### ⚡ Scripts JavaScript

#### `admin-sci.js` (21KB, 498 lignes)
**Description** : Administration SCI

**Fonctionnalités principales** :
- Gestion des menus d'administration
- Interface de configuration
- Gestion des favoris
- Logs et monitoring

**Fonctions clés** :
```javascript
// Gestion des favoris
function addToFavorites(siren, denomination)
function removeFromFavorites(siren)
function refreshFavoritesList()

// Configuration
function saveConfiguration(formData)
function testApiConnection()
function validateSettings()
```

#### `favoris.js` (11KB, 291 lignes)
**Description** : Gestion des favoris

**Fonctionnalités principales** :
- Ajout/suppression de favoris
- Interface utilisateur
- Synchronisation AJAX
- Animations

**Fonctions clés** :
```javascript
// Actions favoris
function toggleFavorite(element, siren)
function updateFavoriteIcon(element, isFavorite)
function showFavoriteNotification(message)

// Interface
function refreshFavoritesDisplay()
function exportFavorites()
function bulkActions()
```

#### `lettre.js` (14KB, 372 lignes)
**Description** : Création de lettres

**Fonctionnalités principales** :
- Éditeur de lettres
- Variables de personnalisation
- Prévisualisation
- Validation

**Fonctions clés** :
```javascript
// Édition
function initializeEditor()
function insertVariable(variable)
function previewLetter()
function validateContent()

// Variables
function replaceVariables(content, data)
function getVariableList()
function autoSave()
```

#### `payment.js` (33KB, 834 lignes)
**Description** : Intégration paiement

**Fonctionnalités principales** :
- Intégration WooCommerce
- Gestion des commandes
- Sécurité
- Confirmation

**Fonctions clés** :
```javascript
// Paiement
function processPayment(orderData)
function createWooCommerceOrder(campaignData)
function handlePaymentSuccess(response)
function handlePaymentError(error)

// Sécurité
function disableContextMenu()
function enableContextMenu()
function preventKeyboardShortcuts()
```

#### `dpe-frontend.js` (14KB, 388 lignes)
**Description** : Interface frontend DPE

**Fonctionnalités principales** :
- Recherche DPE
- Affichage des résultats
- Gestion des favoris DPE
- Interface utilisateur

**Fonctions clés** :
```javascript
// Recherche
function searchDPE(address)
function displayDPEResults(results)
function filterResults(criteria)

// Interface
function initializeDPEPanel()
function handleDPEInteraction()
function updateDPEUI()
```

#### `dpe-favoris.js` (11KB, 335 lignes)
**Description** : Gestion des favoris DPE

**Fonctionnalités principales** :
- Ajout/suppression de favoris DPE
- Interface utilisateur
- Synchronisation AJAX
- Gestion des données

**Fonctions clés** :
```javascript
// Actions DPE
function addDPEFavorite(dpeId, dpeData)
function removeDPEFavorite(dpeId)
function updateDPEFavoritesList()

// Interface
function refreshDPEDisplay()
function exportDPEData()
function bulkDPEActions()
```

#### `enhanced-features.js` (13KB, 355 lignes)
**Description** : Fonctionnalités avancées

**Fonctionnalités principales** :
- Améliorations UX
- Optimisations de performance
- Fonctionnalités avancées
- Intégrations

**Fonctions clés** :
```javascript
// Performance
function lazyLoadImages()
function debounceSearch()
function optimizeAnimations()

// UX
function enhanceForms()
function improveAccessibility()
function addKeyboardShortcuts()
```

---

## 📁 Dossier TCPDF - Génération PDF

### `tcpdf.php` (889KB, 24897 lignes)
**Classe** : `TCPDF`

**Description** : Bibliothèque principale de génération PDF

**Fonctionnalités principales** :
- Génération de PDF
- Gestion des polices
- Mise en page
- Codes-barres

### `tcpdf_barcodes_1d.php` (72KB, 2357 lignes)
**Classe** : `TCPDFBarcode`

**Description** : Gestion des codes-barres 1D

### `tcpdf_barcodes_2d.php` (14KB, 350 lignes)
**Classe** : `TCPDF2DBarcode`

**Description** : Gestion des codes-barres 2D

### Dossier `include/`
**Contenu** :
- `tcpdf_static.php` : Fonctions statiques
- `tcpdf_images.php` : Gestion des images
- `tcpdf_font_data.php` : Données de polices
- `tcpdf_fonts.php` : Gestion des polices
- `tcpdf_filters.php` : Filtres PDF
- `tcpdf_colors.php` : Gestion des couleurs

### Dossier `fonts/`
**Contenu** : Polices TTF et métriques

### Dossier `config/`
**Contenu** : Configuration TCPDF

### Dossier `tools/`
**Contenu** : Outils de développement TCPDF

---

## 🔗 Relations entre Classes

### Diagramme de dépendances

```
my-istymo.php (Principal)
├── SCI_Config_Manager
│   ├── SCI_INPI_Token_Manager
│   └── DPE_Config_Manager
├── SCI_Shortcodes
│   ├── SCI_Favoris_Handler
│   └── SCI_Campaign_Manager
├── DPE_Shortcodes
│   ├── DPE_Handler
│   └── DPE_Favoris_Handler
└── SCI_WooCommerce_Integration
    └── SCI_Campaign_Manager
```

### Flux de données

1. **Initialisation** : `my-istymo.php` charge toutes les classes
2. **Configuration** : `SCI_Config_Manager` gère les paramètres
3. **Authentification** : `SCI_INPI_Token_Manager` gère les tokens
4. **Recherche** : `SCI_Shortcodes` / `DPE_Shortcodes` gèrent l'interface
5. **Favoris** : `SCI_Favoris_Handler` / `DPE_Favoris_Handler` gèrent les favoris
6. **Campagnes** : `SCI_Campaign_Manager` gère les campagnes
7. **Paiement** : `SCI_WooCommerce_Integration` gère les paiements

---

## 🎯 Patterns de Conception

### Singleton Pattern
- `SCI_Config_Manager` : Instance unique de configuration
- `SCI_INPI_Token_Manager` : Instance unique de gestion des tokens
- `DPE_Config_Manager` : Instance unique de configuration DPE

### Factory Pattern
- `SCI_Campaign_Manager` : Création de campagnes
- `SCI_WooCommerce_Integration` : Création de commandes

### Observer Pattern
- Hooks WordPress pour les événements
- AJAX handlers pour les interactions

### Template Pattern
- `template-loader.php` : Chargement de templates
- Séparation logique/présentation

---

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

---

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

---

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

---

## 📞 Support et Développement

### Standards de code
- PSR-4 pour l'autoloading
- PSR-12 pour le style de code
- Documentation PHPDoc

### Outils de développement
- Git pour le versioning
- Composer pour les dépendances
- ESLint pour JavaScript
- PHP_CodeSniffer pour PHP

---

## 📄 Licence et Informations

**Développeur :** Brio Guiseppe  
**Version du plugin :** 1.6  
**Architecture :** Modulaire orientée objet  
**Dernière mise à jour :** 2025

### Historique des versions
- **v1.0.0** : Architecture initiale
- **v1.1.0** : Ajout du module DPE
- **v1.2.0** : Intégration WooCommerce
- **v1.3.0** : Optimisations de performance
- **v1.4.0** : Amélioration de la sécurité
- **v1.5.0** : Refactoring du code
- **v1.6.0** : Documentation complète

---

*Documentation générée automatiquement - Dernière mise à jour : 2025*
