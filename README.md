# 🏢 Plugin SCI - Recherche et Contact

Plugin WordPress pour la prospection et la gestion des Sociétés Civiles Immobilières (SCI) avec système de campagnes de courriers.

## 📋 Description

Ce plugin permet aux utilisateurs de rechercher des SCI par code postal, de gérer leurs favoris, et de créer des campagnes d'envoi de courriers personnalisés. Intégration complète avec WooCommerce pour les paiements et l'API La Poste pour l'envoi de courriers.

## ✨ Fonctionnalités principales

### 🔍 Recherche SCI
- Recherche par code postal avec pagination AJAX
- Affichage des résultats en temps réel
- Informations détaillées : dénomination, dirigeant, SIREN, adresse, ville
- Géolocalisation Google Maps intégrée
- Système de favoris

### 📬 Campagnes de courriers
- Création de campagnes personnalisées
- Sélection multiple de SCI
- Rédaction de courriers avec variables personnalisées `[NOM]`
- Intégration API La Poste pour l'envoi
- Suivi des statuts d'envoi
- Génération de PDF

### 💳 Système de paiement
- Intégration WooCommerce
- Paiement sécurisé pour les campagnes
- Gestion des commandes et factures

### ⭐ Gestion des favoris
- Ajout/suppression de SCI aux favoris
- Interface dédiée pour consulter les favoris
- Export et gestion des données

## 🚀 Installation

1. **Télécharger le plugin** dans le dossier `wp-content/plugins/my-istymo/`
2. **Activer le plugin** depuis l'administration WordPress
3. **Configurer les identifiants API** dans SCI > Configuration
4. **Configurer les identifiants INPI** dans SCI > Identifiants INPI
5. **Configurer les données expéditeur** dans SCI > Configuration

## ⚙️ Configuration requise

### Prérequis système
- WordPress 5.0+
- PHP 7.4+
- WooCommerce 5.0+ (pour les paiements)
- Advanced Custom Fields (ACF) pour les codes postaux utilisateurs

### APIs externes
- **API INPI** : Pour la recherche des données SCI
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

### Interface utilisateur

#### Recherche SCI
1. Sélectionner un code postal dans la liste
2. Cliquer sur "🔍 Rechercher les SCI"
3. Parcourir les résultats avec la pagination
4. Ajouter des SCI aux favoris (⭐)
5. Sélectionner des SCI pour une campagne

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
- Interface responsive

### Variables de personnalisation
- `[NOM]` : Nom du destinataire dans les courriers
- Codes postaux configurables par utilisateur
- Templates de courriers personnalisables

## 🔧 Administration

### Menu SCI
- **Panneau principal** : Recherche et gestion
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

### Sécurité
- Validation des données utilisateur
- Protection CSRF avec nonces
- Échappement HTML automatique
- Vérification des permissions utilisateur

### Compatibilité
- Responsive design
- Compatible avec tous les thèmes WordPress
- Intégration WooCommerce native
- Support multilingue

## 🐛 Dépannage

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
- Logs API disponibles dans SCI > Logs API
- Mode debug disponible en développement
- Console JavaScript pour le débogage frontend

## 📈 Versions

### Version 1.6 (Actuelle)
- ✅ Interface utilisateur modernisée
- ✅ Système de pagination amélioré
- ✅ Styles CSS harmonisés
- ✅ Suppression colonne Code Postal
- ✅ Boutons d'action verts
- ✅ Police 12px pour les tableaux
- ✅ Alignement des formulaires
- ✅ Intégration WooCommerce

### Fonctionnalités ajoutées
- Système de sélection simplifié
- Persistance localStorage
- Navigation bidirectionnelle
- Interface épurée
- Styles cohérents

## 🤝 Support

Pour toute question ou problème :
1. Consulter la documentation
2. Vérifier les logs d'erreur
3. Contacter le support technique

## 📄 Licence

Plugin développé par Brio Guiseppe - Tous droits réservés

---

**Note :** Ce plugin nécessite une configuration complète des APIs externes pour fonctionner correctement. Assurez-vous de configurer tous les identifiants requis avant utilisation.
