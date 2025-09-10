# Structure des Templates - My Istymo

## Vue d'ensemble

Le plugin My Istymo a été refactorisé pour séparer le HTML du code PHP principal. Tous les templates sont maintenant stockés dans le dossier `templates/` et chargés via un système de template loader.

## Structure des dossiers

```
my-istymo/
├── templates/                    # Dossier des templates
│   ├── sci-panel.php            # Template principal du panneau SCI
│   ├── sci-favoris.php          # Template de la page des favoris
│   ├── sci-campaigns.php        # Template de la page des campagnes
│   ├── sci-logs.php             # Template de la page des logs
│   ├── dpe-panel-simple.php     # Template du panneau DPE simplifié
│   ├── dpe-favoris.php          # Template de la page des favoris DPE
│   ├── unified-leads-admin.php  # Template de l'interface de gestion des leads
│   └── admin-notifications.php  # Template des notifications admin
├── includes/
│   └── template-loader.php      # Système de chargement des templates
└── my-istymo.php               # Fichier principal (code PHP uniquement)
```

## Système de Template Loader

### Fonctions disponibles

#### `sci_load_template($template_name, $context = [])`
Charge et affiche un template avec les variables de contexte.

**Paramètres :**
- `$template_name` (string) : Nom du template sans extension
- `$context` (array) : Variables à passer au template

**Exemple :**
```php
$context = [
    'codesPostauxArray' => $codesPostauxArray,
    'config_manager' => sci_config_manager()
];
sci_load_template('sci-panel', $context);
```

#### `sci_get_template_content($template_name, $context = [])`
Récupère le contenu d'un template comme string (sans l'afficher).

**Retour :** string - Contenu du template

## Templates disponibles

### 1. `sci-panel.php`
**Fonction :** Panneau principal de recherche SCI

**Variables attendues :**
- `$codesPostauxArray` : array des codes postaux de l'utilisateur
- `$config_manager` : instance du gestionnaire de configuration
- `$inpi_token_manager` : instance du gestionnaire de tokens INPI
- `$woocommerce_integration` : instance de l'intégration WooCommerce
- `$campaign_manager` : instance du gestionnaire de campagnes

### 2. `sci-favoris.php`
**Fonction :** Page des favoris SCI

**Variables attendues :**
- `$favoris` : array des favoris de l'utilisateur

### 3. `sci-campaigns.php`
**Fonction :** Page des campagnes de lettres

**Variables attendues :**
- `$campaigns` : array des campagnes de l'utilisateur
- `$campaign_details` : array des détails d'une campagne (si en mode vue détaillée)
- `$view_mode` : boolean indiquant si on est en mode vue détaillée

### 4. `sci-logs.php`
**Fonction :** Page des logs API

**Variables attendues :**
- `$log_file` : chemin vers le fichier de log
- `$log_content` : contenu des logs (si disponible)
- `$log_stats` : statistiques du fichier de log (taille, date de modification)

### 5. `dpe-panel-simple.php`
**Fonction :** Panneau de recherche DPE simplifié

**Variables attendues :**
- `$dpe_data` : array des données DPE à afficher
- `$search_results` : array des résultats de recherche DPE
- `$favoris_dpe` : array des DPE favoris de l'utilisateur

### 6. `dpe-favoris.php`
**Fonction :** Page des favoris DPE

**Variables attendues :**
- `$favoris_dpe` : array des DPE favoris de l'utilisateur
- `$dpe_stats` : statistiques des DPE favoris

### 7. `unified-leads-admin.php`
**Fonction :** Interface de gestion des leads unifiés

**Variables attendues :**
- `$leads` : array des leads à afficher
- `$lead_actions` : array des actions disponibles
- `$lead_statuses` : array des statuts possibles
- `$filters` : array des filtres appliqués

### 8. `admin-notifications.php`
**Fonction :** Notifications d'administration

**Variables attendues :**
- `$config_manager` : instance du gestionnaire de configuration
- `$inpi_token_manager` : instance du gestionnaire de tokens INPI
- `$woocommerce_integration` : instance de l'intégration WooCommerce
- `$campaign_manager` : instance du gestionnaire de campagnes

## Avantages de cette structure

### 1. Séparation des responsabilités
- **Code PHP** : Logique métier uniquement
- **Templates** : Présentation et affichage uniquement

### 2. Maintenabilité
- Templates faciles à modifier sans toucher au code PHP
- Structure claire et organisée
- Réutilisation possible des templates

### 3. Sécurité
- Variables passées explicitement via le contexte
- Échappement automatique des données dans les templates
- Isolation du code de présentation

### 4. Flexibilité
- Possibilité d'avoir plusieurs versions d'un même template
- Chargement conditionnel des templates
- Extension facile avec de nouveaux templates

## Bonnes pratiques

### 1. Variables de contexte
- Toujours documenter les variables attendues en commentaire
- Utiliser des noms explicites pour les variables
- Passer des objets plutôt que des tableaux quand possible

### 2. Sécurité
- Toujours utiliser `esc_html()`, `esc_attr()`, etc. dans les templates
- Valider les données avant de les passer au template
- Ne jamais faire confiance aux données utilisateur

### 3. Performance
- Éviter les requêtes dans les templates
- Préparer toutes les données dans le contexte
- Utiliser le cache quand possible

## Exemple d'utilisation

```php
// Dans une fonction PHP
function ma_fonction() {
    // Préparer les données
    $data = recuperer_donnees();
    
    // Préparer le contexte
    $context = [
        'titre' => 'Mon titre',
        'donnees' => $data,
        'utilisateur' => wp_get_current_user()
    ];
    
    // Charger le template
    sci_load_template('mon-template', $context);
}
```

```php
<!-- Dans le template mon-template.php -->
<h1><?php echo esc_html($titre); ?></h1>
<div class="donnees">
    <?php foreach ($donnees as $item): ?>
        <div class="item">
            <?php echo esc_html($item['nom']); ?>
        </div>
    <?php endforeach; ?>
</div>
```

## Migration depuis l'ancienne structure

Si vous avez du code qui utilise l'ancienne structure avec du HTML inline, voici comment le migrer :

1. **Extraire le HTML** vers un nouveau fichier template
2. **Identifier les variables** utilisées dans le HTML
3. **Créer le contexte** avec ces variables
4. **Remplacer le HTML** par un appel à `sci_load_template()`

Cette structure rend le code plus maintenable, sécurisé et professionnel. 