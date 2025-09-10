# Guide d'utilisation du Composant de Tableau Unifié

## Vue d'ensemble

Le composant de tableau unifié (`unified-table-component.php`) est un système modulaire et réutilisable qui permet de créer des tableaux de données avec un design cohérent et des fonctionnalités avancées. Il est basé sur le design des leads existant mais peut être adapté à tous types de données.

## Fonctionnalités principales

- ✅ **Design moderne et cohérent** - Même structure visuelle que les leads
- ✅ **Filtres intégrés** - Filtres par type, statut, date, etc.
- ✅ **Actions en lot** - Sélection multiple et actions groupées
- ✅ **Pagination** - Navigation entre les pages de résultats
- ✅ **Menus dropdown** - Actions contextuelles par ligne
- ✅ **Responsive** - Adaptation automatique mobile/desktop
- ✅ **Accessibilité** - Support clavier et attributs ARIA
- ✅ **Tri des colonnes** - Tri ascendant/descendant
- ✅ **Notifications** - Système de notifications toast
- ✅ **Types de cellules** - Badges, dates, statuts, priorités, etc.

## Structure des fichiers

```
wp-content/plugins/my-istymo/
├── templates/
│   ├── unified-table-component.php          # Composant principal
│   └── unified-leads-admin-example.php      # Exemple d'utilisation
├── assets/
│   ├── css/
│   │   └── unified-leads.css                # Styles (déjà existant)
│   └── js/
│       └── unified-table-component.js       # JavaScript du composant
└── docs/
    └── unified-table-component-guide.md     # Ce guide
```

## Utilisation de base

### 1. Inclure le composant

```php
// Inclure le composant de tableau unifié
require_once plugin_dir_path(__FILE__) . 'unified-table-component.php';
```

### 2. Configurer le tableau

```php
$table_config = array(
    'title' => '📋 Mon Tableau',
    'table_id' => 'mon-tableau',
    'show_filters' => true,
    'show_actions' => true,
    'show_checkboxes' => true,
    'per_page' => 20,
    'is_shortcode' => false,
    
    // Configuration des colonnes
    'columns' => array(
        'nom' => array(
            'label' => 'Nom',
            'type' => 'text',
            'icon' => 'admin-users',
            'width' => '25%'
        ),
        'statut' => array(
            'label' => 'Statut',
            'type' => 'status',
            'icon' => 'info',
            'width' => '15%',
            'status_map' => array(
                'actif' => array('class' => 'active', 'text' => 'Actif'),
                'inactif' => array('class' => 'inactive', 'text' => 'Inactif')
            )
        ),
        'date' => array(
            'label' => 'Date',
            'type' => 'date',
            'icon' => 'calendar',
            'width' => '15%',
            'format' => 'd/m/Y'
        )
    ),
    
    // Configuration des filtres
    'filters' => array(
        'statut' => array(
            'type' => 'select',
            'placeholder' => 'Tous les statuts',
            'options' => array(
                'actif' => 'Actif',
                'inactif' => 'Inactif'
            )
        ),
        'date_debut' => array(
            'type' => 'date',
            'placeholder' => 'Date de début'
        )
    ),
    
    // Configuration des actions
    'actions' => array(
        'voir' => array(
            'label' => 'Voir',
            'icon' => 'visibility',
            'onclick' => 'voirElement($(this).data(\'item-id\'));'
        ),
        'modifier' => array(
            'label' => 'Modifier',
            'icon' => 'edit',
            'onclick' => 'modifierElement($(this).data(\'item-id\'));'
        ),
        'supprimer' => array(
            'label' => 'Supprimer',
            'icon' => 'trash',
            'onclick' => 'if(confirm(\'Êtes-vous sûr ?\')) { supprimerElement($(this).data(\'item-id\')); }'
        )
    )
);
```

### 3. Préparer les données

```php
$table_data = array();
foreach ($mes_donnees as $item) {
    $table_data[] = (object) array(
        'id' => $item->id,
        'nom' => $item->nom,
        'statut' => $item->statut,
        'date' => $item->date_creation
    );
}
```

### 4. Définir le contexte

```php
$component_context = array(
    'page_slug' => 'ma-page',
    'shortcode_id' => '', // Si utilisé en shortcode
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('my_istymo_nonce')
);
```

### 5. Utiliser le composant

```php
unified_table_component($table_config, $table_data, $component_context);
```

## Types de colonnes disponibles

### 1. Texte simple (`text`)
```php
'nom' => array(
    'label' => 'Nom',
    'type' => 'text',
    'icon' => 'admin-users',
    'width' => '25%'
)
```

### 2. Badge (`badge`)
```php
'type' => array(
    'label' => 'Type',
    'type' => 'badge',
    'icon' => 'tag',
    'width' => '15%',
    'badge_class' => 'primary', // CSS class pour le style
    'badge_text' => 'Mon Badge' // Texte à afficher
)
```

### 3. Statut (`status`)
```php
'statut' => array(
    'label' => 'Statut',
    'type' => 'status',
    'icon' => 'info',
    'width' => '15%',
    'status_map' => array(
        'nouveau' => array('class' => 'pending', 'text' => 'Nouveau'),
        'en_cours' => array('class' => 'progress', 'text' => 'En cours'),
        'termine' => array('class' => 'completed', 'text' => 'Terminé')
    )
)
```

### 4. Priorité (`priority`)
```php
'priorite' => array(
    'label' => 'Priorité',
    'type' => 'priority',
    'icon' => 'flag',
    'width' => '12%',
    'priority_map' => array(
        'haute' => array('class' => 'high', 'text' => 'Haute'),
        'normale' => array('class' => 'normal', 'text' => 'Normale'),
        'basse' => array('class' => 'low', 'text' => 'Basse')
    )
)
```

### 5. Date (`date`)
```php
'date_creation' => array(
    'label' => 'Date création',
    'type' => 'date',
    'icon' => 'calendar',
    'width' => '15%',
    'format' => 'd/m/Y' // Format de date PHP
)
```

### 6. Entreprise (`company`)
```php
'entreprise' => array(
    'label' => 'Entreprise',
    'type' => 'company',
    'icon' => 'admin-home',
    'width' => '25%',
    'subtitle' => 'siren' // Propriété pour le sous-titre
)
```

### 7. Icône et texte (`icon_text`)
```php
'categorie' => array(
    'label' => 'Catégorie',
    'type' => 'icon_text',
    'icon' => 'category',
    'width' => '15%',
    'text' => 'Ma catégorie' // Texte à afficher
)
```

## Types de filtres disponibles

### 1. Sélection (`select`)
```php
'statut' => array(
    'type' => 'select',
    'placeholder' => 'Tous les statuts',
    'options' => array(
        'actif' => 'Actif',
        'inactif' => 'Inactif'
    )
)
```

### 2. Date (`date`)
```php
'date_debut' => array(
    'type' => 'date',
    'placeholder' => 'Date de début'
)
```

### 3. Texte (`text`)
```php
'recherche' => array(
    'type' => 'text',
    'placeholder' => 'Rechercher...'
)
```

## Configuration des actions

```php
'actions' => array(
    'voir' => array(
        'label' => 'Voir',
        'icon' => 'visibility',
        'onclick' => 'voirElement($(this).data(\'item-id\'));'
    ),
    'modifier' => array(
        'label' => 'Modifier',
        'icon' => 'edit',
        'onclick' => 'modifierElement($(this).data(\'item-id\'));'
    ),
    'supprimer' => array(
        'label' => 'Supprimer',
        'icon' => 'trash',
        'onclick' => 'if(confirm(\'Êtes-vous sûr ?\')) { supprimerElement($(this).data(\'item-id\')); }'
    )
)
```

## Utilisation en shortcode

```php
function mon_shortcode_tableau($atts) {
    $atts = shortcode_atts(array(
        'id' => 'tableau-' . uniqid()
    ), $atts);
    
    // Configuration du tableau
    $table_config = array(
        'title' => '📋 Mon Tableau',
        'table_id' => $atts['id'],
        'is_shortcode' => true,
        // ... autres configurations
    );
    
    // Contexte pour shortcode
    $component_context = array(
        'shortcode_id' => $atts['id'],
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_istymo_nonce')
    );
    
    // Utiliser le composant
    unified_table_component($table_config, $table_data, $component_context);
}

add_shortcode('mon_tableau', 'mon_shortcode_tableau');
```

## Fonctions JavaScript disponibles

### Utilitaires (`UnifiedTableUtils`)

```javascript
// Formater une date
UnifiedTableUtils.formatDate('2024-01-15', 'd/m/Y'); // "15/01/2024"

// Formater un nombre
UnifiedTableUtils.formatNumber(1234.56, 2); // "1 234,56"

// Tronquer un texte
UnifiedTableUtils.truncateText('Texte très long...', 20); // "Texte très long..."

// Valider des données
const errors = UnifiedTableUtils.validateData(data, {
    nom: { required: true, label: 'Nom' },
    email: { required: true, pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, label: 'Email' }
});

// Afficher une notification
UnifiedTableUtils.showNotification('Opération réussie !', 'success');

// Obtenir les éléments sélectionnés
const selectedItems = UnifiedTableUtils.getSelectedItems();
```

### Actions (`UnifiedTableActions`)

```javascript
// Exécuter une action en lot
UnifiedTableActions.executeBulkAction('supprimer', [1, 2, 3]);

// Filtrer les lignes du tableau
UnifiedTableActions.filterTableRows(table, 0, 'recherche');

// Trier le tableau
UnifiedTableActions.sortTable(table, 0, 'asc');

// Fermer tous les menus
UnifiedTableActions.closeAllMenus();
```

## Personnalisation CSS

Le composant utilise les classes CSS existantes du fichier `unified-leads.css`. Vous pouvez personnaliser l'apparence en ajoutant vos propres styles :

```css
/* Personnaliser les badges */
.my-istymo-badge.my-istymo-badge-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

/* Personnaliser les statuts */
.my-istymo-status-badge.my-istymo-status-custom {
    background: #e3f2fd;
    color: #1976d2;
    border-color: #bbdefb;
}

/* Personnaliser les priorités */
.my-istymo-priority-badge.my-istymo-priority-custom {
    background: #fff3e0;
    color: #f57c00;
    border-color: #ffcc02;
}
```

## Exemple complet

Voir le fichier `unified-leads-admin-example.php` pour un exemple complet d'utilisation du composant avec les leads.

## Bonnes pratiques

1. **Nommage cohérent** - Utilisez des noms de colonnes et de filtres cohérents
2. **Validation des données** - Validez toujours les données avant de les passer au composant
3. **Gestion des erreurs** - Implémentez une gestion d'erreur appropriée dans vos actions
4. **Performance** - Limitez le nombre d'éléments par page pour de meilleures performances
5. **Accessibilité** - Testez l'accessibilité avec un lecteur d'écran
6. **Responsive** - Testez sur différents appareils et tailles d'écran

## Support et maintenance

Le composant est conçu pour être maintenu et étendu facilement. Pour ajouter de nouveaux types de colonnes ou de filtres, modifiez les fonctions de rendu dans `unified-table-component.php`.

## Migration depuis l'ancien système

Pour migrer un tableau existant vers le nouveau composant :

1. Identifiez les colonnes et leurs types
2. Configurez les filtres existants
3. Adaptez les actions JavaScript
4. Testez la fonctionnalité
5. Supprimez l'ancien code

Le composant est rétrocompatible avec les styles CSS existants.

