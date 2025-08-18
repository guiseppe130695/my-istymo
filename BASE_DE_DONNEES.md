# 🗄️ My Istymo - Documentation Complète de la Base de Données

## 📋 Vue d'ensemble

Ce document décrit la structure complète de la base de données utilisée par le plugin **My Istymo**, incluant toutes les tables personnalisées, les options WordPress, et les métadonnées utilisateur.

**Version actuelle :** 1.6  
**Dernière mise à jour :** 2025

---

## 🏗️ Architecture de la Base de Données

### Structure générale
Le plugin utilise une architecture hybride combinant :
- **Tables personnalisées** : 10 tables dédiées aux fonctionnalités spécifiques
- **Options WordPress** : Configuration et paramètres système
- **Métadonnées utilisateur** : Codes postaux et préférences utilisateur
- **Tables WordPress natives** : Intégration avec WooCommerce et ACF

### Répartition par module
- **Module SCI** : 4 tables + options de configuration
- **Module DPE** : 3 tables + options de configuration  
- **Module CRM** : 3 tables + options de configuration
- **Système global** : Options WordPress + métadonnées

---

## 📊 Tables Personnalisées

### 🔍 Module SCI (Sociétés Civiles Immobilières)

#### 1. Table `{prefix}sci_favoris`

**Fichier de création** : `includes/favoris-handler.php`  
**Description** : Stockage des SCI favorites par utilisateur

```sql
CREATE TABLE {prefix}sci_favoris (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    siren varchar(20) NOT NULL,
    denomination text NOT NULL,
    dirigeant text,
    adresse text,
    ville varchar(100),
    code_postal varchar(10),
    date_added datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_siren (user_id, siren),
    KEY user_id (user_id)
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique auto-incrémenté
- `user_id` : ID de l'utilisateur WordPress (bigint)
- `siren` : Numéro SIREN de la SCI (varchar 20)
- `denomination` : Nom de la société (text)
- `dirigeant` : Nom du dirigeant (text, nullable)
- `adresse` : Adresse complète (text, nullable)
- `ville` : Ville (varchar 100, nullable)
- `code_postal` : Code postal (varchar 10, nullable)
- `date_added` : Date d'ajout aux favoris (datetime)

**Index et contraintes** :
- **Clé primaire** : `id`
- **Clé unique** : `unique_user_siren` (user_id, siren)
- **Index** : `user_id` pour les performances

---

#### 2. Table `{prefix}sci_campaigns`

**Fichier de création** : `includes/campaign-manager.php`  
**Description** : Gestion des campagnes de prospection SCI

```sql
CREATE TABLE {prefix}sci_campaigns (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    title varchar(255) NOT NULL,
    content longtext NOT NULL,
    status varchar(50) DEFAULT 'draft',
    total_letters int(11) DEFAULT 0,
    sent_letters int(11) DEFAULT 0,
    failed_letters int(11) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY status (status)
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique de la campagne
- `user_id` : ID de l'utilisateur créateur
- `title` : Titre de la campagne (varchar 255)
- `content` : Contenu du modèle de lettre (longtext)
- `status` : Statut (draft, active, completed, cancelled)
- `total_letters` : Nombre total de lettres prévues
- `sent_letters` : Nombre de lettres envoyées
- `failed_letters` : Nombre de lettres en échec
- `created_at` : Date de création
- `updated_at` : Date de dernière modification

**Index et contraintes** :
- **Clé primaire** : `id`
- **Index** : `user_id`, `status`

---

#### 3. Table `{prefix}sci_campaign_letters`

**Fichier de création** : `includes/campaign-manager.php`  
**Description** : Détail des lettres envoyées par campagne

```sql
CREATE TABLE {prefix}sci_campaign_letters (
    id int(11) NOT NULL AUTO_INCREMENT,
    campaign_id int(11) NOT NULL,
    sci_siren varchar(20) NOT NULL,
    sci_denomination varchar(255) NOT NULL,
    sci_dirigeant varchar(255),
    sci_adresse text,
    sci_ville varchar(100),
    sci_code_postal varchar(10),
    laposte_uid varchar(100),
    status varchar(50) DEFAULT 'pending',
    error_message text,
    sent_at datetime NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY campaign_id (campaign_id),
    KEY status (status),
    KEY laposte_uid (laposte_uid),
    KEY sci_siren (sci_siren),
    FOREIGN KEY (campaign_id) REFERENCES {prefix}sci_campaigns(id) ON DELETE CASCADE
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique de la lettre
- `campaign_id` : Référence vers la campagne (clé étrangère)
- `sci_siren` : SIREN de la SCI ciblée
- `sci_denomination` : Nom de la SCI
- `sci_dirigeant` : Dirigeant de la SCI
- `sci_adresse` : Adresse de la SCI
- `sci_ville` : Ville de la SCI
- `sci_code_postal` : Code postal de la SCI
- `laposte_uid` : Identifiant La Poste pour le suivi
- `status` : Statut d'envoi (pending, sent, failed, delivered)
- `error_message` : Message d'erreur en cas d'échec
- `sent_at` : Date d'envoi effective
- `created_at` : Date de création

**Index et contraintes** :
- **Clé primaire** : `id`
- **Clé étrangère** : `campaign_id` → `sci_campaigns.id`
- **Index** : `campaign_id`, `status`, `laposte_uid`, `sci_siren`

---

#### 4. Table `{prefix}sci_inpi_credentials`

**Fichier de création** : `includes/inpi-token-manager.php`  
**Description** : Stockage des tokens d'authentification API INPI

```sql
CREATE TABLE {prefix}sci_inpi_credentials (
    id int(11) NOT NULL AUTO_INCREMENT,
    token text NOT NULL,
    token_expiry datetime NOT NULL,
    username varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    firstname varchar(255),
    lastname varchar(255),
    last_login datetime,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique
- `token` : Token d'authentification INPI (text)
- `token_expiry` : Date d'expiration du token
- `username` : Nom d'utilisateur INPI
- `email` : Email associé
- `firstname` : Prénom de l'utilisateur
- `lastname` : Nom de l'utilisateur
- `last_login` : Dernière connexion
- `created_at` : Date de création
- `updated_at` : Date de dernière modification

**Index et contraintes** :
- **Clé primaire** : `id`

---

### 🏠 Module DPE (Diagnostic de Performance Énergétique)

#### 5. Table `{prefix}dpe_favoris`

**Fichier de création** : `includes/dpe-favoris-handler.php`  
**Description** : Stockage des biens immobiliers favoris avec DPE

```sql
CREATE TABLE {prefix}dpe_favoris (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    dpe_id varchar(255) NOT NULL,
    adresse_ban varchar(500) NOT NULL,
    code_postal_ban varchar(10) NOT NULL,
    nom_commune_ban varchar(100) NOT NULL,
    etiquette_dpe varchar(10) NOT NULL,
    etiquette_ges varchar(10) NOT NULL,
    conso_5_usages_ef_energie_n1 decimal(10,2),
    emission_ges_5_usages_energie_n1 decimal(10,2),
    surface_habitable_logement int(11),
    annee_construction int(11),
    type_batiment varchar(100),
    date_etablissement_dpe date,
    numero_dpe varchar(50),
    complement_adresse_logement varchar(255),
    coordonnee_cartographique_x_ban decimal(15,6),
    coordonnee_cartographique_y_ban decimal(15,6),
    dpe_data longtext,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_dpe (user_id, dpe_id),
    KEY user_id (user_id),
    KEY code_postal (code_postal_ban),
    KEY etiquette_dpe (etiquette_dpe),
    KEY etiquette_ges (etiquette_ges)
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique
- `user_id` : ID de l'utilisateur
- `dpe_id` : Identifiant unique du DPE
- `adresse_ban` : Adresse normalisée (500 caractères)
- `code_postal_ban` : Code postal normalisé
- `nom_commune_ban` : Nom de la commune normalisé
- `etiquette_dpe` : Classe énergétique (A à G)
- `etiquette_ges` : Classe GES (A à G)
- `conso_5_usages_ef_energie_n1` : Consommation énergétique (kWh/m²/an)
- `emission_ges_5_usages_energie_n1` : Émissions de GES (kgCO2/m²/an)
- `surface_habitable_logement` : Surface en m²
- `annee_construction` : Année de construction
- `type_batiment` : Type de bâtiment
- `date_etablissement_dpe` : Date d'établissement du DPE
- `numero_dpe` : Numéro du DPE
- `complement_adresse_logement` : Complément d'adresse
- `coordonnee_cartographique_x_ban` : Coordonnée X (longitude)
- `coordonnee_cartographique_y_ban` : Coordonnée Y (latitude)
- `dpe_data` : Données complètes du DPE (JSON)
- `created_at` : Date d'ajout aux favoris

**Index et contraintes** :
- **Clé primaire** : `id`
- **Clé unique** : `user_dpe` (user_id, dpe_id)
- **Index** : `user_id`, `code_postal_ban`, `etiquette_dpe`, `etiquette_ges`

---

#### 6. Table `{prefix}dpe_campaigns`

**Fichier de création** : `includes/dpe-campaign-manager.php`  
**Description** : Gestion des campagnes de prospection DPE

```sql
CREATE TABLE {prefix}dpe_campaigns (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    title varchar(255) NOT NULL,
    content longtext NOT NULL,
    type varchar(50) DEFAULT 'dpe_maison',
    status varchar(50) DEFAULT 'draft',
    total_letters int(11) DEFAULT 0,
    sent_letters int(11) DEFAULT 0,
    failed_letters int(11) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY status (status),
    KEY type (type),
    KEY created_at (created_at)
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique de la campagne
- `user_id` : ID de l'utilisateur créateur
- `title` : Titre de la campagne
- `content` : Contenu du modèle de lettre
- `type` : Type de campagne (dpe_maison, dpe_appartement, etc.)
- `status` : Statut de la campagne
- `total_letters` : Nombre total de lettres prévues
- `sent_letters` : Nombre de lettres envoyées
- `failed_letters` : Nombre de lettres en échec
- `created_at` : Date de création
- `updated_at` : Date de dernière modification

**Index et contraintes** :
- **Clé primaire** : `id`
- **Index** : `user_id`, `status`, `type`, `created_at`

---

#### 7. Table `{prefix}dpe_campaign_letters`

**Fichier de création** : `includes/dpe-campaign-manager.php`  
**Description** : Détail des lettres envoyées par campagne DPE

```sql
CREATE TABLE {prefix}dpe_campaign_letters (
    id int(11) NOT NULL AUTO_INCREMENT,
    campaign_id int(11) NOT NULL,
    numero_dpe varchar(50) NOT NULL,
    type_batiment varchar(50),
    adresse text,
    commune varchar(100),
    code_postal varchar(10),
    surface varchar(20),
    etiquette_dpe varchar(10),
    etiquette_ges varchar(10),
    date_dpe varchar(20),
    proprietaire_nom varchar(255),
    proprietaire_adresse text,
    proprietaire_ville varchar(100),
    proprietaire_code_postal varchar(10),
    laposte_uid varchar(100),
    status varchar(50) DEFAULT 'pending',
    error_message text,
    sent_at datetime NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY campaign_id (campaign_id),
    KEY numero_dpe (numero_dpe),
    KEY status (status),
    KEY commune (commune),
    KEY etiquette_dpe (etiquette_dpe),
    KEY sent_at (sent_at),
    FOREIGN KEY (campaign_id) REFERENCES {prefix}dpe_campaigns(id) ON DELETE CASCADE
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique de la lettre
- `campaign_id` : Référence vers la campagne DPE
- `numero_dpe` : Numéro du DPE
- `type_batiment` : Type de bâtiment
- `adresse` : Adresse du bien
- `commune` : Commune
- `code_postal` : Code postal
- `surface` : Surface du bien
- `etiquette_dpe` : Classe énergétique
- `etiquette_ges` : Classe GES
- `date_dpe` : Date du DPE
- `proprietaire_nom` : Nom du propriétaire
- `proprietaire_adresse` : Adresse du propriétaire
- `proprietaire_ville` : Ville du propriétaire
- `proprietaire_code_postal` : Code postal du propriétaire
- `laposte_uid` : Identifiant La Poste
- `status` : Statut d'envoi
- `error_message` : Message d'erreur
- `sent_at` : Date d'envoi
- `created_at` : Date de création

**Index et contraintes** :
- **Clé primaire** : `id`
- **Clé étrangère** : `campaign_id` → `dpe_campaigns.id`
- **Index** : `campaign_id`, `numero_dpe`, `status`, `commune`, `etiquette_dpe`, `sent_at`

---

### 👥 Module CRM (Customer Relationship Management)

#### 8. Table `{prefix}my_istymo_contacts`

**Fichier de création** : `includes/crm-manager.php`  
**Description** : Gestion des contacts clients/prospects

```sql
CREATE TABLE {prefix}my_istymo_contacts (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    nom varchar(100) NOT NULL,
    prenom varchar(100) NOT NULL,
    email varchar(255),
    telephone varchar(20),
    adresse text,
    code_postal varchar(10),
    ville varchar(100),
    type_contact enum('sci', 'dpe') NOT NULL DEFAULT 'sci',
    statut enum('prospect', 'client', 'inactif') NOT NULL DEFAULT 'prospect',
    notes text,
    date_creation datetime DEFAULT CURRENT_TIMESTAMP,
    date_modification datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY type_contact (type_contact),
    KEY statut (statut)
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique du contact
- `user_id` : ID de l'utilisateur propriétaire
- `nom` : Nom du contact
- `prenom` : Prénom du contact
- `email` : Email du contact
- `telephone` : Téléphone du contact
- `adresse` : Adresse complète
- `code_postal` : Code postal
- `ville` : Ville
- `type_contact` : Type (sci ou dpe)
- `statut` : Statut (prospect, client, inactif)
- `notes` : Notes personnalisées
- `date_creation` : Date de création
- `date_modification` : Date de dernière modification

**Index et contraintes** :
- **Clé primaire** : `id`
- **Index** : `user_id`, `type_contact`, `statut`

---

#### 9. Table `{prefix}my_istymo_interactions`

**Fichier de création** : `includes/crm-manager.php`  
**Description** : Historique des interactions avec les contacts

```sql
CREATE TABLE {prefix}my_istymo_interactions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    contact_id bigint(20) NOT NULL,
    user_id bigint(20) NOT NULL,
    type_interaction enum('appel', 'email', 'rendez_vous', 'note') NOT NULL,
    sujet varchar(255) NOT NULL,
    description text,
    date_interaction datetime NOT NULL,
    date_creation datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY contact_id (contact_id),
    KEY user_id (user_id),
    KEY type_interaction (type_interaction),
    FOREIGN KEY (contact_id) REFERENCES {prefix}my_istymo_contacts(id) ON DELETE CASCADE
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique de l'interaction
- `contact_id` : Référence vers le contact
- `user_id` : ID de l'utilisateur qui a créé l'interaction
- `type_interaction` : Type (appel, email, rendez_vous, note)
- `sujet` : Sujet de l'interaction
- `description` : Description détaillée
- `date_interaction` : Date de l'interaction
- `date_creation` : Date de création de l'enregistrement

**Index et contraintes** :
- **Clé primaire** : `id`
- **Clé étrangère** : `contact_id` → `my_istymo_contacts.id`
- **Index** : `contact_id`, `user_id`, `type_interaction`

---

#### 10. Table `{prefix}my_istymo_favoris_contacts`

**Fichier de création** : `includes/crm-manager.php`  
**Description** : Association entre favoris SCI/DPE et contacts CRM

```sql
CREATE TABLE {prefix}my_istymo_favoris_contacts (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    favori_id bigint(20) NOT NULL,
    contact_id bigint(20) NOT NULL,
    type_favori enum('sci', 'dpe') NOT NULL,
    date_liaison datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_favori_contact (favori_id, contact_id, type_favori),
    KEY favori_id (favori_id),
    KEY contact_id (contact_id),
    FOREIGN KEY (contact_id) REFERENCES {prefix}my_istymo_contacts(id) ON DELETE CASCADE
);
```

**Colonnes détaillées** :
- `id` : Identifiant unique de la liaison
- `favori_id` : ID du favori (SCI ou DPE)
- `contact_id` : Référence vers le contact
- `type_favori` : Type du favori (sci ou dpe)
- `date_liaison` : Date de création de la liaison

**Index et contraintes** :
- **Clé primaire** : `id`
- **Clé unique** : `unique_favori_contact` (favori_id, contact_id, type_favori)
- **Clé étrangère** : `contact_id` → `my_istymo_contacts.id`
- **Index** : `favori_id`, `contact_id`

---

## ⚙️ Options WordPress

### Configuration SCI

#### Options de base
- `sci_api_config` : Configuration complète des APIs SCI
- `sci_inpi_username` : Nom d'utilisateur INPI
- `sci_inpi_password` : Mot de passe INPI (chiffré)
- `sci_inpi_token` : Token d'authentification INPI
- `sci_inpi_token_expiry` : Date d'expiration du token
- `sci_inpi_user_data` : Données utilisateur INPI
- `sci_woocommerce_product_id` : ID du produit WooCommerce pour les campagnes SCI

#### Configuration La Poste
- `laposte_token` : Token d'authentification La Poste
- `laposte_api_url` : URL de l'API La Poste
- `laposte_type_affranchissement` : Type d'affranchissement (lrar)
- `laposte_type_enveloppe` : Type d'enveloppe (auto)
- `laposte_enveloppe` : Format d'enveloppe (fenetre)
- `laposte_couleur` : Couleur d'impression (nb)
- `laposte_recto_verso` : Impression recto-verso (rectoverso)
- `laposte_placement_adresse` : Placement de l'adresse
- `laposte_surimpression_adresses` : Surimpression des adresses (1/0)
- `laposte_impression_expediteur` : Impression expéditeur (1/0)
- `laposte_ar_scan` : AR scan (1/0)
- `laposte_ar_champ1` : Champ AR 1
- `laposte_ar_champ2` : Champ AR 2
- `laposte_reference` : Référence
- `laposte_nom_entite` : Nom de l'entité
- `laposte_nom_dossier` : Nom du dossier
- `laposte_nom_sousdossier` : Nom du sous-dossier

#### URLs des pages
- `sci_panel_page_url` : URL de la page panneau SCI
- `sci_favoris_page_url` : URL de la page favoris SCI
- `sci_campaigns_page_url` : URL de la page campagnes SCI

### Configuration DPE

#### Options de base
- `dpe_api_config` : Configuration complète de l'API DPE
- `dpe_product_id` : ID du produit WooCommerce pour les campagnes DPE
- `dpe_default_letter_price` : Prix par défaut des lettres DPE
- `dpe_max_letters_per_campaign` : Nombre maximum de lettres par campagne
- `dpe_enable_notifications` : Activation des notifications DPE

### Configuration CRM

#### Options de base
- `my_istymo_crm_tables_created` : Version des tables CRM créées
- `my_istymo_crm_config` : Configuration générale du CRM

---

## 👤 Métadonnées Utilisateur

### Codes postaux utilisateur
- `code_postal_user` : Codes postaux de l'utilisateur (format: "75001;75002;75003")
- `user_code_postal` : Alias pour compatibilité

### Préférences utilisateur
- `sci_user_preferences` : Préférences utilisateur SCI
- `dpe_user_preferences` : Préférences utilisateur DPE
- `crm_user_preferences` : Préférences utilisateur CRM

---

## 🔗 Relations entre Tables

### Clés étrangères principales

#### Module SCI
- `sci_campaign_letters.campaign_id` → `sci_campaigns.id` (CASCADE)

#### Module DPE
- `dpe_campaign_letters.campaign_id` → `dpe_campaigns.id` (CASCADE)

#### Module CRM
- `my_istymo_interactions.contact_id` → `my_istymo_contacts.id` (CASCADE)
- `my_istymo_favoris_contacts.contact_id` → `my_istymo_contacts.id` (CASCADE)

### Relations logiques
- `sci_favoris.user_id` → `wp_users.ID`
- `dpe_favoris.user_id` → `wp_users.ID`
- `my_istymo_contacts.user_id` → `wp_users.ID`
- `sci_campaigns.user_id` → `wp_users.ID`
- `dpe_campaigns.user_id` → `wp_users.ID`

---

## 📈 Statistiques et Performance

### Taille estimée des tables

#### Tables principales
- `sci_favoris` : ~50-200 KB par utilisateur
- `dpe_favoris` : ~100-500 KB par utilisateur
- `sci_campaigns` : ~10-50 KB par campagne
- `dpe_campaigns` : ~10-50 KB par campagne
- `sci_campaign_letters` : ~5-20 KB par lettre
- `dpe_campaign_letters` : ~5-20 KB par lettre

#### Tables CRM
- `my_istymo_contacts` : ~1-5 KB par contact
- `my_istymo_interactions` : ~2-10 KB par interaction
- `my_istymo_favoris_contacts` : ~1 KB par liaison

### Optimisation recommandée

#### Index de performance
```sql
-- Index pour les recherches fréquentes
CREATE INDEX idx_sci_favoris_user_code ON {prefix}sci_favoris(user_id, code_postal);
CREATE INDEX idx_dpe_favoris_user_etiquette ON {prefix}dpe_favoris(user_id, etiquette_dpe);
CREATE INDEX idx_campaigns_user_status ON {prefix}sci_campaigns(user_id, status);
CREATE INDEX idx_letters_status_sent ON {prefix}sci_campaign_letters(status, sent_at);
```

#### Nettoyage automatique
```sql
-- Suppression des anciennes données (2 ans)
DELETE FROM {prefix}sci_campaign_letters 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);

DELETE FROM {prefix}dpe_campaign_letters 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);

-- Suppression des interactions anciennes (1 an)
DELETE FROM {prefix}my_istymo_interactions 
WHERE date_interaction < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

---

## 🔒 Sécurité et Maintenance

### Bonnes pratiques

#### Validation des données
- Utilisation de `wpdb->prepare()` pour toutes les requêtes
- Sanitisation des données d'entrée avec `sanitize_text_field()`
- Validation des types de données avant insertion
- Échappement HTML avec `esc_html()`

#### Gestion des permissions
- Vérification des capacités utilisateur avec `current_user_can()`
- Contrôle d'accès aux données par utilisateur
- Logs d'activité pour audit

#### Sauvegarde
- Sauvegarde quotidienne recommandée
- Sauvegarde critique avant chaque mise à jour
- Rétention des sauvegardes : 30 jours minimum

### Maintenance préventive

#### Vérifications régulières
```sql
-- Vérification de l'intégrité des clés étrangères
SELECT COUNT(*) FROM {prefix}sci_campaign_letters 
WHERE campaign_id NOT IN (SELECT id FROM {prefix}sci_campaigns);

-- Vérification des données orphelines
SELECT COUNT(*) FROM {prefix}dpe_campaign_letters 
WHERE campaign_id NOT IN (SELECT id FROM {prefix}dpe_campaigns);

-- Nettoyage des tokens expirés
DELETE FROM {prefix}sci_inpi_credentials 
WHERE token_expiry < NOW();
```

#### Optimisation des tables
```sql
-- Optimisation mensuelle
OPTIMIZE TABLE {prefix}sci_favoris;
OPTIMIZE TABLE {prefix}dpe_favoris;
OPTIMIZE TABLE {prefix}my_istymo_contacts;
OPTIMIZE TABLE {prefix}sci_campaigns;
OPTIMIZE TABLE {prefix}dpe_campaigns;
```

---

## 🚀 Migration et Mise à Jour

### Gestion des versions

#### Version actuelle
- **Plugin** : 1.6
- **Base de données** : 1.0.0
- **Tables CRM** : 1.0.0

#### Processus de mise à jour
1. **Sauvegarde** : Sauvegarde complète avant mise à jour
2. **Vérification** : Contrôle de la structure existante
3. **Migration** : Application des modifications avec `dbDelta()`
4. **Validation** : Vérification de l'intégrité des données
5. **Nettoyage** : Suppression des données temporaires

### Scripts de migration

#### Création des tables
```php
// Activation automatique lors de l'activation du plugin
register_activation_hook(__FILE__, 'my_istymo_create_tables');

function my_istymo_create_tables() {
    // Création des tables SCI
    require_once 'includes/favoris-handler.php';
    require_once 'includes/campaign-manager.php';
    
    // Création des tables DPE
    require_once 'includes/dpe-favoris-handler.php';
    require_once 'includes/dpe-campaign-manager.php';
    
    // Création des tables CRM
    require_once 'includes/crm-manager.php';
    
    // Mise à jour de la version
    update_option('my_istymo_db_version', '1.0.0');
}
```

---

## 📊 Monitoring et Logs

### Logs de base de données
- **Emplacement** : `/wp-content/uploads/my-istymo-logs/`
- **Fichiers** : `database-logs.txt`, `error-logs.txt`
- **Format** : `[timestamp][context] message`

### Métriques à surveiller
- **Taille des tables** : Croissance mensuelle
- **Performance** : Temps de réponse des requêtes
- **Erreurs** : Échecs d'insertion/mise à jour
- **Utilisation** : Nombre de favoris par utilisateur

---

## 📞 Support et Dépannage

### Problèmes courants

#### Tables manquantes
```sql
-- Vérification de l'existence des tables
SHOW TABLES LIKE '{prefix}sci_%';
SHOW TABLES LIKE '{prefix}dpe_%';
SHOW TABLES LIKE '{prefix}my_istymo_%';
```

#### Données corrompues
```sql
-- Vérification de l'intégrité
CHECK TABLE {prefix}sci_favoris;
CHECK TABLE {prefix}dpe_favoris;
CHECK TABLE {prefix}my_istymo_contacts;
```

#### Performance dégradée
```sql
-- Analyse des index
SHOW INDEX FROM {prefix}sci_favoris;
SHOW INDEX FROM {prefix}dpe_favoris;

-- Statistiques des tables
SHOW TABLE STATUS LIKE '{prefix}sci_%';
SHOW TABLE STATUS LIKE '{prefix}dpe_%';
```

### Ressources de support
- **Documentation** : Ce fichier README
- **Code source** : Fichiers dans `/includes/`
- **Logs** : `/wp-content/uploads/my-istymo-logs/`
- **Support technique** : Contact développeur

---

## 📄 Licence et Informations

**Développeur :** Brio Guiseppe  
**Version du plugin :** 1.6  
**Version de la base de données :** 1.0.0  
**Dernière mise à jour :** 2025

### Historique des versions
- **v1.0.0** : Création initiale des tables SCI
- **v1.1.0** : Ajout des tables DPE
- **v1.2.0** : Ajout des tables CRM
- **v1.3.0** : Optimisation des index
- **v1.4.0** : Amélioration de la sécurité
- **v1.5.0** : Ajout des contraintes de clés étrangères
- **v1.6.0** : Refactoring et documentation complète

---

*Documentation générée automatiquement - Dernière mise à jour : 2025*
