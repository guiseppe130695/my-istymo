# 🏠 Module DPE - Diagnostic de Performance Énergétique

## 📋 Vue d'ensemble

Le module DPE (Diagnostic de Performance Énergétique) permet de rechercher, consulter et gérer les diagnostics énergétiques des logements via l'API ADEME. Il s'intègre parfaitement avec le système de leads unifié pour créer automatiquement des prospects à partir des DPE favoris.

## ✨ Fonctionnalités principales

### 🔍 Recherche DPE
- **Recherche par adresse** : Saisie libre d'adresses pour trouver les DPE
- **API ADEME** : Intégration complète avec l'API officielle ADEME
- **Résultats détaillés** : Affichage des informations énergétiques complètes
- **Pagination** : Navigation dans les résultats de recherche

### 📊 Informations affichées
- **Étiquette énergétique** : Classe énergétique (A à G)
- **Consommation énergétique** : kWh/m²/an
- **Émissions GES** : kg CO₂/m²/an
- **Complément d'adresse** : Spécifique au logement uniquement
- **Date de validité** : Période de validité du DPE
- **Surface** : Surface habitable du logement

### ⭐ Système de favoris
- **Ajout/suppression** : Gestion des DPE favoris
- **Interface dédiée** : Page de consultation des favoris
- **Création automatique de leads** : Les DPE favoris génèrent des leads unifiés
- **Synchronisation** : Sauvegarde en base de données

## 🚀 Installation et configuration

### Prérequis
- **API ADEME** : Accès à l'API officielle ADEME
- **Base de données** : Tables DPE créées automatiquement
- **Permissions** : Droits d'écriture pour les logs

### Configuration
1. **Configurer l'API ADEME** dans My Istymo > Configuration
2. **Vérifier les tables** : Les tables `dpe_favoris` sont créées automatiquement
3. **Tester la connexion** : Utiliser la recherche DPE pour valider

## 📖 Utilisation

### Shortcodes disponibles

#### Panneau de recherche DPE
```php
[dpe_panel title="🏠 Diagnostic de Performance Énergétique" show_config_warnings="true"]
```

#### Liste des favoris DPE
```php
[dpe_favoris title="⭐ Mes DPE Favoris" show_empty_message="true"]
```

### Interface utilisateur

#### Recherche DPE
1. **Saisir une adresse** dans le champ de recherche
2. **Cliquer sur "🔍 Rechercher"** pour lancer la recherche
3. **Consulter les résultats** avec les informations énergétiques
4. **Ajouter aux favoris** en cliquant sur l'étoile (⭐)
5. **Consulter les détails** en cliquant sur un résultat

#### Gestion des favoris
1. **Accéder à la page favoris** via le shortcode ou le menu
2. **Consulter la liste** des DPE sauvegardés
3. **Supprimer des favoris** si nécessaire
4. **Les favoris créent automatiquement des leads** dans le système unifié

## 🔧 Architecture technique

### Fichiers principaux

#### Backend (PHP)
- `includes/dpe-handler.php` : Gestionnaire principal de l'API DPE
- `includes/dpe-favoris-handler.php` : Gestion des favoris DPE
- `includes/dpe-shortcodes.php` : Définition des shortcodes DPE

#### Frontend (JavaScript)
- `assets/js/dpe-frontend.js` : Interface de recherche et affichage
- Templates avec JavaScript intégré pour l'affichage des résultats

#### Templates
- `templates/dpe-panel-simple.php` : Panneau de recherche simplifié
- `templates/dpe-favoris.php` : Page des favoris DPE
- `templates/unified-leads-admin.php` : Affichage des leads DPE

### Base de données

#### Table `dpe_favoris`
```sql
CREATE TABLE dpe_favoris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dpe_id VARCHAR(255) NOT NULL,
    adresse TEXT NOT NULL,
    complement_adresse_logement VARCHAR(255),
    etiquette_dpe VARCHAR(10),
    conso_5_usages_ef_energie_n1 DECIMAL(10,2),
    emission_ges_5_usages_energie_n1 DECIMAL(10,2),
    surface_habitable DECIMAL(10,2),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_dpe (user_id, dpe_id)
);
```

## 🎯 Logique des compléments d'adresse

### Règle principale
**Seul le complément d'adresse spécifique au logement est affiché** (`complement_adresse_logement`).

### Comportement
- ✅ **Affichage** : Si `complement_adresse_logement` existe
- ❌ **Pas d'affichage** : Si `complement_adresse_logement` est vide
- 🚫 **Pas de fallback** : Aucun retour vers `complement_adresse_batiment`

### Implémentation
```php
// Logique d'affichage
$complement = $item['complement_adresse_logement'] ?? '';
if ($complement) {
    // Affichage du complément
}
```

## 🔄 Intégration avec le système de leads

### Création automatique
Quand un DPE est ajouté aux favoris :
1. **Sauvegarde** du DPE en favori
2. **Création automatique** d'un lead unifié
3. **Remplissage** des informations DPE dans le lead
4. **Statut initial** : "Nouveau"

### Informations transférées
- Adresse complète du logement
- Complément d'adresse (logement uniquement)
- Étiquette énergétique
- Consommation et émissions
- Surface habitable
- Date de validité du DPE

## 🎨 Personnalisation

### Styles CSS
Le module DPE utilise les styles CSS du plugin principal :
- **Boutons** : Style cohérent avec l'interface SCI
- **Tableaux** : Police 12px, design responsive
- **Modals** : Interface harmonisée
- **Couleurs** : Palette verte du plugin

### Variables de personnalisation
- **Titre des shortcodes** : Personnalisable via les paramètres
- **Messages d'erreur** : Configurables dans les handlers
- **Limites de recherche** : Paramétrables dans l'API

## 🐛 Dépannage

### Problèmes courants

#### Recherche ne fonctionne pas
**Solutions** :
1. Vérifier la configuration de l'API ADEME
2. Contrôler les logs d'erreur API
3. Vérifier la connectivité internet
4. Tester avec une adresse simple

#### Compléments d'adresse ne s'affichent pas
**Solutions** :
1. Vérifier que les données DPE contiennent `complement_adresse_logement`
2. Contrôler la logique d'affichage dans les templates
3. Vérifier les logs JavaScript dans la console
4. Tester avec des DPE connus pour avoir des compléments

#### Favoris ne se sauvegardent pas
**Solutions** :
1. Vérifier les permissions de base de données
2. Contrôler l'existence de la table `dpe_favoris`
3. Vérifier les logs d'erreur PHP
4. Tester avec un autre utilisateur

#### Leads ne se créent pas automatiquement
**Solutions** :
1. Vérifier l'intégration avec le système de leads
2. Contrôler les logs de création de leads
3. Vérifier les permissions utilisateur
4. Tester manuellement la création de leads

### Logs de diagnostic
- **Logs API** : `wp-content/uploads/my-istymo-logs/dpe-api-logs.txt`
- **Logs favoris** : `wp-content/uploads/my-istymo-logs/dpe-favoris-logs.txt`
- **Logs leads** : `wp-content/uploads/my-istymo-logs/unified-leads-logs.txt`

## 📊 Statistiques et métriques

### Données collectées
- **Nombre de recherches** par utilisateur
- **DPE favoris** par utilisateur
- **Leads créés** depuis les DPE
- **Taux de conversion** DPE → Leads

### Tableaux de bord
- **Statistiques globales** dans l'interface admin
- **Métriques par utilisateur** dans les profils
- **Rapports d'utilisation** des fonctionnalités DPE

## 🔒 Sécurité

### Mesures implémentées
- **Validation des données** : Toutes les entrées utilisateur sont validées
- **Échappement HTML** : Protection XSS automatique
- **Nonces CSRF** : Protection contre les attaques CSRF
- **Permissions utilisateur** : Vérification des droits d'accès
- **Limitation des requêtes** : Protection contre le spam API

### Bonnes pratiques
- **API keys** : Stockage sécurisé des identifiants API
- **Logs** : Pas d'informations sensibles dans les logs
- **Base de données** : Requêtes préparées pour éviter les injections SQL

## 🚀 Évolutions futures

### Fonctionnalités prévues
- **Recherche par coordonnées GPS** : Géolocalisation précise
- **Filtres avancés** : Par classe énergétique, surface, etc.
- **Export des favoris** : CSV, Excel des DPE favoris
- **Notifications** : Alertes sur nouveaux DPE dans une zone
- **Comparaison** : Outil de comparaison entre DPE

### Améliorations techniques
- **Cache intelligent** : Mise en cache des résultats API
- **Recherche en temps réel** : Suggestions d'adresses
- **Interface mobile** : Optimisation pour smartphones
- **API REST** : Endpoints pour intégrations externes

## 📞 Support

### Documentation
- **README principal** : Vue d'ensemble du plugin
- **README templates** : Structure des templates
- **Logs de diagnostic** : Pour le dépannage

### Contact
Pour toute question sur le module DPE :
1. Consulter cette documentation
2. Vérifier les logs d'erreur
3. Contacter le support technique

---

**Note** : Le module DPE nécessite une configuration correcte de l'API ADEME pour fonctionner. Assurez-vous de configurer tous les identifiants requis avant utilisation.
