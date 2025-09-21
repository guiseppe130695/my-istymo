# 🎨 Système de Composants Génériques

## 📋 Vue d'ensemble

Ce document décrit le système de composants génériques réutilisables mis en place pour améliorer la maintenabilité et la cohérence du code CSS dans le plugin My Istymo.

## 🏗️ Architecture

### Fichier principal
- **`assets/css/components.css`** : Contient tous les composants génériques réutilisables

### Principe de fonctionnement
- **Classes modulaires** : Chaque composant a une classe de base et des variantes
- **Variables CSS** : Utilisation de variables CSS pour la cohérence des couleurs et espacements
- **Système BEM** : Nomenclature basée sur la méthodologie BEM (Block__Element--Modifier)

## 🎯 Composants disponibles

### 1. Alertes et Notifications

```html
<!-- Alerte d'information -->
<div class="alert alert--info">
    <p class="alert__text">Message d'information</p>
</div>

<!-- Alerte de succès -->
<div class="alert alert--success">
    <p class="alert__text">Message de succès</p>
</div>

<!-- Alerte d'erreur -->
<div class="alert alert--error">
    <p class="alert__text">Message d'erreur</p>
</div>

<!-- Alerte d'avertissement -->
<div class="alert alert--warning">
    <p class="alert__text">Message d'avertissement</p>
</div>
```

### 2. Boutons

```html
<!-- Bouton principal -->
<button class="btn btn--primary">Action principale</button>

<!-- Bouton secondaire -->
<button class="btn btn--secondary">Action secondaire</button>

<!-- Bouton de danger -->
<button class="btn btn--danger">Action dangereuse</button>

<!-- Tailles -->
<button class="btn btn--primary btn--small">Petit</button>
<button class="btn btn--primary btn--large">Grand</button>
```

### 3. Formulaires

```html
<form class="form">
    <!-- Groupe de champs standard -->
    <div class="form__group">
        <label for="input" class="form__label">Label</label>
        <input type="text" id="input" class="form__input">
    </div>
    
    <!-- Groupe de champs inline -->
    <div class="form__group form__group--inline">
        <div class="form__group">
            <label for="select" class="form__label">Sélection</label>
            <select id="select" class="form__select">
                <option>Option 1</option>
            </select>
        </div>
        <button class="btn btn--primary">Valider</button>
    </div>
</form>
```

### 4. Tableaux

```html
<table class="table">
    <thead>
        <tr>
            <th>Colonne 1</th>
            <th>Colonne 2</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Donnée 1</td>
            <td>Donnée 2</td>
        </tr>
    </tbody>
</table>
```

### 5. Pagination

```html
<div class="pagination">
    <button class="pagination__button" disabled>Page précédente</button>
    <span class="pagination__info">1/5</span>
    <button class="pagination__button">Page suivante</button>
</div>
```

### 6. États de chargement

```html
<div class="loading loading--visible">
    <div class="loading__spinner"></div>
    <span class="loading__text">Chargement en cours...</span>
</div>
```

### 7. Badges et Labels

```html
<!-- Badges DPE -->
<span class="badge badge--dpe-a">A</span>
<span class="badge badge--dpe-b">B</span>
<span class="badge badge--dpe-c">C</span>
<span class="badge badge--dpe-d">D</span>
<span class="badge badge--dpe-e">E</span>
<span class="badge badge--dpe-f">F</span>
<span class="badge badge--dpe-g">G</span>
```

### 8. Liens

```html
<!-- Lien standard -->
<a href="#" class="link">Lien standard</a>

<!-- Lien externe -->
<a href="#" class="link link--external" target="_blank">Lien externe</a>
```

## 🛠️ Classes utilitaires

### Espacements
```html
<!-- Marges -->
<div class="mb-0">Pas de marge bottom</div>
<div class="mb-sm">Petite marge bottom</div>
<div class="mb-md">Marge bottom moyenne</div>
<div class="mb-lg">Grande marge bottom</div>
<div class="mb-xl">Très grande marge bottom</div>

<!-- Marges top -->
<div class="mt-0">Pas de marge top</div>
<div class="mt-sm">Petite marge top</div>
<!-- etc. -->
```

### Typographie
```html
<div class="text-center">Texte centré</div>
<div class="text-left">Texte aligné à gauche</div>
<div class="text-right">Texte aligné à droite</div>
<div class="text-muted">Texte atténué</div>
<div class="text-small">Petit texte</div>
<div class="text-large">Grand texte</div>
```

### Affichage
```html
<div class="hidden">Élément masqué</div>
<div class="visible">Élément visible</div>
```

### Flexbox
```html
<div class="d-flex justify-content-between align-items-center">
    <span>Élément 1</span>
    <span>Élément 2</span>
</div>
```

## 🎨 Variables CSS disponibles

### Couleurs
```css
--primary-color: #0073aa;
--secondary-color: #005177;
--success-color: #155724;
--info-color: #004085;
--warning-color: #856404;
--error-color: #721c24;
```

### Espacements
```css
--spacing-xs: 4px;
--spacing-sm: 8px;
--spacing-md: 12px;
--spacing-lg: 15px;
--spacing-xl: 20px;
--spacing-xxl: 30px;
```

### Bordures
```css
--border-radius: 8px;
--border-radius-sm: 4px;
--border-width: 1px;
```

## 📱 Responsive Design

Le système inclut des breakpoints responsive :
- **Mobile** : < 768px
- **Tablet/Desktop** : ≥ 768px

Les composants s'adaptent automatiquement aux différentes tailles d'écran.

## 🔧 Utilisation dans le code

### PHP/HTML
```php
// Utiliser les classes génériques dans les templates
echo '<div class="alert alert--info">';
echo '<p class="alert__text">Message d\'information</p>';
echo '</div>';
```

### JavaScript
```javascript
// Utiliser les classes pour manipuler l'affichage
element.classList.add('hidden');        // Masquer
element.classList.remove('hidden');     // Afficher
element.classList.add('loading--visible'); // Afficher le loading
```

## 🚀 Avantages

1. **Cohérence** : Design uniforme dans toute l'application
2. **Maintenabilité** : Modifications centralisées dans un seul fichier
3. **Réutilisabilité** : Composants utilisables partout
4. **Performance** : CSS optimisé et modulaire
5. **Accessibilité** : Respect des standards d'accessibilité
6. **Responsive** : Adaptation automatique aux écrans

## 📝 Bonnes pratiques

1. **Toujours utiliser les classes génériques** avant de créer du CSS custom
2. **Respecter la nomenclature BEM** pour les nouveaux composants
3. **Utiliser les variables CSS** pour les couleurs et espacements
4. **Tester la responsivité** sur différentes tailles d'écran
5. **Documenter les nouveaux composants** dans ce fichier

## 🔄 Migration

Pour migrer du code existant :

1. Remplacer les styles inline par les classes génériques
2. Utiliser les variables CSS au lieu des valeurs hardcodées
3. Adopter la nomenclature BEM pour les nouveaux éléments
4. Tester l'affichage sur différentes pages

## 📚 Exemples d'utilisation

Voir le fichier `templates/dpe-panel.php` pour un exemple complet d'utilisation du système de composants génériques.
