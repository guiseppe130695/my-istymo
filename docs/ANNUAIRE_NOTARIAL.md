# 🏛️ Annuaire Notarial - Documentation

## 📋 Vue d'ensemble

L'**Annuaire Notarial** est une fonctionnalité complète de My Istymo qui permet aux partenaires de consulter et gérer une base de données de notaires filtrés par leurs codes postaux géographiques.

### 🎯 Objectifs
- **Service à valeur ajoutée** : Base de données locale de notaires pour les partenaires
- **Filtrage géographique** : Affichage automatique selon les codes postaux configurés
- **Gestion des favoris** : Système de favoris intégré
- **Import mensuel** : Mise à jour facile via fichiers CSV

---

## 🏗️ Architecture technique

### 📁 Structure des fichiers

```
wp-content/plugins/my-istymo/
├── includes/
│   ├── notaires-manager.php          # Gestionnaire principal
│   ├── notaires-import-handler.php   # Import CSV
│   └── notaires-favoris-handler.php  # Système de favoris
├── templates/
│   └── notaires-admin.php            # Pages d'administration
├── assets/
│   ├── css/notaires-admin.css        # Styles
│   └── js/notaires-admin.js          # Scripts
└── my-istymo.php                     # Intégration principale
```

### 🗄️ Base de données

#### Table principale : `wp_my_istymo_notaires`
```sql
CREATE TABLE wp_my_istymo_notaires (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_office VARCHAR(255) NOT NULL,
    telephone_office VARCHAR(20),
    langues_parlees TEXT,
    site_internet VARCHAR(255),
    email_office VARCHAR(255),
    adresse TEXT,
    code_postal VARCHAR(10) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    nom_notaire VARCHAR(255),
    statut_notaire VARCHAR(50) DEFAULT 'actif',
    url_office VARCHAR(255),
    page_source VARCHAR(255),
    date_extraction DATETIME,
    date_import DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code_postal (code_postal),
    INDEX idx_ville (ville),
    INDEX idx_statut (statut_notaire)
);
```

#### Table favoris : `wp_my_istymo_notaires_favoris`
```sql
CREATE TABLE wp_my_istymo_notaires_favoris (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notaire_id INT NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_notaire (user_id, notaire_id),
    FOREIGN KEY (notaire_id) REFERENCES wp_my_istymo_notaires(id) ON DELETE CASCADE
);
```

---

## 🔧 Classes principales

### 1. `Notaires_Manager`
**Rôle** : Gestionnaire principal des données notaires

**Méthodes principales** :
- `get_notaires_by_postal_codes()` : Récupération filtrée par codes postaux
- `get_notaires_count()` : Comptage avec filtres
- `get_notaire_by_id()` : Détails d'un notaire
- `get_available_cities()` : Villes disponibles
- `get_available_languages()` : Langues disponibles
- `bulk_insert_notaires()` : Insertion en masse
- `truncate_notaires()` : Vidage pour import

### 2. `Notaires_Import_Handler`
**Rôle** : Gestion de l'import CSV

**Méthodes principales** :
- `validate_csv_structure()` : Validation de la structure
- `parse_csv_data()` : Parsing des données
- `clean_csv_row()` : Nettoyage des données
- `process_csv_file()` : Traitement complet
- `generate_import_report()` : Rapport d'import

### 3. `Notaires_Favoris_Handler`
**Rôle** : Gestion des favoris

**Méthodes principales** :
- `add_to_favorites()` : Ajout aux favoris
- `remove_from_favorites()` : Suppression des favoris
- `toggle_favorite()` : Basculement
- `get_user_favorites()` : Récupération des favoris
- `export_favorites_csv()` : Export CSV
- `get_favorites_stats()` : Statistiques

---

## 🎨 Interface utilisateur

### 📱 Pages principales

#### 1. Page "Notaires" (`notaires-panel`)
- **URL** : `/wp-admin/admin.php?page=notaires-panel`
- **Permissions** : `read`
- **Fonctionnalités** :
  - Affichage des notaires par codes postaux
  - Filtres (ville, langue, statut, recherche)
  - Système de favoris
  - Détails en modal
  - Pagination
  - Export des favoris

#### 2. Page "Import CSV" (`notaires-import`)
- **URL** : `/wp-admin/admin.php?page=notaires-import`
- **Permissions** : `manage_options`
- **Fonctionnalités** :
  - Upload de fichier CSV
  - Validation de structure
  - Mode prévisualisation
  - Import en base
  - Rapports détaillés

### 🎛️ Filtres disponibles

1. **Ville** : Dropdown avec villes disponibles
2. **Langue** : Dropdown avec langues disponibles
3. **Statut** : Actif, Inactif, Suspendu
4. **Recherche** : Recherche textuelle en temps réel

### ⭐ Système de favoris

- **Ajout/Suppression** : Clic sur l'étoile
- **Compteur** : Affichage du nombre de favoris
- **Export** : CSV des favoris
- **Persistance** : Sauvegarde en base

---

## 🔌 Actions AJAX

### 📡 Endpoints disponibles

| Action | Méthode | Description |
|--------|---------|-------------|
| `filter_notaires` | POST | Filtrage des notaires |
| `toggle_notaire_favorite` | POST | Basculement favori |
| `get_notaire_details` | POST | Détails d'un notaire |
| `get_favorites_count` | POST | Compteur de favoris |
| `export_notaires_favorites` | POST | Export CSV favoris |
| `import_notaires_csv` | POST | Import CSV (réservé) |

### 🔐 Sécurité

- **Nonce** : `my_istymo_notaires_nonce`
- **Permissions** : Vérification des droits utilisateur
- **Sanitisation** : Toutes les données d'entrée
- **Validation** : Contrôles stricts des paramètres

---

## 📊 Import CSV

### 📋 Format attendu

Le fichier CSV doit contenir les colonnes suivantes :

| Colonne | Obligatoire | Description |
|---------|-------------|-------------|
| `nom_office` | ✅ | Nom de l'office notarial |
| `telephone_office` | ❌ | Numéro de téléphone |
| `langues_parlees` | ❌ | Langues parlées (séparées par virgules) |
| `site_internet` | ❌ | Site web |
| `email_office` | ❌ | Adresse email |
| `adresse` | ❌ | Adresse complète |
| `code_postal` | ✅ | Code postal |
| `ville` | ✅ | Ville |
| `nom_notaire` | ❌ | Nom du notaire |
| `statut_notaire` | ❌ | Statut (actif/inactif/suspendu) |
| `url_office` | ❌ | URL officielle |
| `page_source` | ❌ | Source des données |
| `date_extraction` | ❌ | Date d'extraction |

### 🔄 Processus d'import

1. **Upload** : Sélection du fichier CSV
2. **Validation** : Vérification de la structure
3. **Parsing** : Lecture et nettoyage des données
4. **Prévisualisation** : Mode test (optionnel)
5. **Import** : Remplacement complet des données
6. **Rapport** : Résumé de l'opération

### ⚠️ Limitations

- **Taille** : Maximum 10 MB
- **Format** : UTF-8 obligatoire
- **Remplacement** : Import complet (pas d'ajout incrémental)
- **Permissions** : Administrateurs uniquement

---

## 🎯 Utilisation

### 👤 Pour les partenaires

1. **Configuration** : Configurer les codes postaux dans le profil
2. **Consultation** : Accéder au menu "Notaires"
3. **Filtrage** : Utiliser les filtres pour affiner la recherche
4. **Favoris** : Ajouter des notaires aux favoris
5. **Contact** : Utiliser les liens directs (téléphone, email)
6. **Export** : Exporter les favoris en CSV

### 👨‍💼 Pour les administrateurs

1. **Import mensuel** : Upload du nouveau fichier CSV
2. **Validation** : Vérifier la structure avant import
3. **Prévisualisation** : Tester avec quelques lignes
4. **Import complet** : Remplacer toutes les données
5. **Monitoring** : Vérifier les logs d'import

---

## 🔧 Configuration

### 📍 Codes postaux utilisateur

L'annuaire utilise le système de codes postaux existant de My Istymo :

```php
// Récupération des codes postaux
$codes_postaux = sci_get_user_postal_codes($user_id);

// Format attendu : "75001;75002;75003"
// Séparateur : point-virgule
```

### 🎨 Personnalisation CSS

Les styles sont dans `assets/css/notaires-admin.css` :

```css
/* Variables principales */
.notaires-container { max-width: 1200px; }
.my-istymo-info-box { background: linear-gradient(...); }
.favorite-toggle.favorited { color: #ffb900; }
```

### ⚙️ Paramètres JavaScript

```javascript
// Variables globales
notairesAjax = {
    ajaxurl: '/wp-admin/admin-ajax.php',
    nonce: 'nonce_value'
};
```

---

## 🐛 Dépannage

### ❌ Problèmes courants

#### 1. "Codes postaux non configurés"
**Cause** : Utilisateur sans codes postaux dans le profil
**Solution** : Configurer les codes postaux ACF

#### 2. "Aucun notaire trouvé"
**Cause** : Aucun notaire dans les codes postaux de l'utilisateur
**Solution** : Vérifier les données d'import

#### 3. "Erreur d'import CSV"
**Cause** : Structure du fichier incorrecte
**Solution** : Vérifier les colonnes requises

#### 4. "Nonce invalide"
**Cause** : Session expirée ou problème de sécurité
**Solution** : Recharger la page

### 📝 Logs

Les logs sont disponibles dans :
- **WordPress** : `wp-content/debug.log`
- **My Istymo** : `wp-content/uploads/my-istymo-logs/notaires-logs.txt`

### 🔍 Debug

Activer le mode debug :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

---

## 🚀 Évolutions futures

### 📈 Fonctionnalités prévues

1. **Géolocalisation** : Carte interactive des notaires
2. **Notifications** : Alertes pour nouveaux notaires
3. **API REST** : Endpoints pour intégrations externes
4. **Statistiques** : Tableaux de bord avancés
5. **Synchronisation** : Import automatique mensuel
6. **Multi-langues** : Interface traduite
7. **Mobile** : Application mobile dédiée

### 🔧 Améliorations techniques

1. **Cache** : Mise en cache des requêtes fréquentes
2. **Index** : Optimisation des index de base de données
3. **CDN** : Distribution des assets statiques
4. **Monitoring** : Surveillance des performances
5. **Tests** : Suite de tests automatisés

---

## 📞 Support

### 🆘 Contact
- **Développeur** : Brio Guiseppe
- **Version** : 1.0
- **Date** : 2025

### 📚 Ressources
- **Documentation** : Ce fichier
- **Code source** : GitHub (si disponible)
- **Issues** : Système de tickets

---

## 📄 Licence

Ce module fait partie du plugin My Istymo et suit la même licence GPL v2 ou ultérieure.

---

*Documentation générée automatiquement - Version 1.0*



