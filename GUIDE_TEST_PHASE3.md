# 🧪 Guide de Test - Phase 3: Fonctionnalités Avancées

## 📋 Vue d'ensemble

Ce guide vous accompagne pour tester toutes les fonctionnalités de la **Phase 3: Fonctionnalités Avancées** du système unifié de gestion des leads. Cette phase inclut :

- ✅ **Système d'Actions et Suivi** : Historique et planification des actions
- ✅ **Système de Workflow** : Transitions de statuts avec validation
- ✅ **Interface Améliorée** : Vue détaillée et filtres avancés
- ✅ **Fonctionnalités d'Export** : Export CSV/Excel et statistiques

---

## 🚀 **ÉTAPE 1 : Accès à l'Interface**

### 1.1 Connexion à WordPress Admin
1. Connectez-vous à votre WordPress Admin
2. Dans le menu de gauche, vous devriez voir **"Leads"** avec l'icône 👥
3. Cliquez sur **"Leads"** pour accéder à l'interface principale

### 1.2 Vérification de l'Installation
1. Allez dans **Leads > Configuration**
2. Vérifiez que toutes les classes sont chargées :
   - ✅ `Unified_Leads_Manager`
   - ✅ `Lead_Actions_Manager`
   - ✅ `Lead_Workflow`
   - ✅ `Lead_Status_Manager`

### 1.3 Migration des Données (si nécessaire)
1. Dans **Leads > Configuration**
2. Cliquez sur **"Migrer les favoris existants"**
3. Vérifiez que des leads sont créés dans le système

---

## 🎯 **ÉTAPE 2 : Test du Système d'Actions**

### 2.1 Accès à la Vue Détaillée
1. Dans l'interface principale **Leads**
2. Cliquez sur **"Voir"** pour un lead existant
3. Le modal de vue détaillée s'ouvre

### 2.2 Test d'Ajout d'Action
1. Dans le modal de vue détaillée
2. Cliquez sur **"Ajouter une action"**
3. Remplissez le formulaire :
   - **Type d'action** : Sélectionnez "Appel téléphonique"
   - **Description** : "Premier contact avec le prospect"
   - **Résultat** : "En attente"
   - **Date programmée** : Laissez vide pour l'instant
4. Cliquez sur **"Ajouter l'action"**
5. ✅ **Vérification** : L'action apparaît dans l'historique

### 2.3 Test des Types d'Actions
Testez tous les types d'actions disponibles :
- 📞 **Appel téléphonique**
- 📧 **Email**
- 💬 **SMS**
- 📅 **Rendez-vous**
- 📝 **Note**

### 2.4 Test des Résultats d'Actions
Testez tous les résultats possibles :
- ✅ **Réussi** : Action accomplie avec succès
- ❌ **Échec** : Action non accomplie
- ⏳ **En attente** : Action à effectuer
- 🔄 **Reporté** : Action reportée à plus tard

### 2.5 Test de Modification d'Action
1. Dans l'historique des actions
2. Cliquez sur **"Modifier"** pour une action
3. Changez la description ou le résultat
4. Cliquez sur **"Modifier"**
5. ✅ **Vérification** : Les changements sont sauvegardés

### 2.6 Test de Suppression d'Action
1. Dans l'historique des actions
2. Cliquez sur **"Supprimer"** pour une action
3. Confirmez la suppression
4. ✅ **Vérification** : L'action disparaît de l'historique

---

## 🔄 **ÉTAPE 3 : Test du Système de Workflow**

### 3.1 Test des Transitions de Statut
1. Dans le modal de vue détaillée
2. Section **"Actions rapides"**
3. Testez les transitions autorisées :

#### **Workflow de Base :**
- **Nouveau** → **En cours** ✅
- **En cours** → **Qualifié** ✅
- **Qualifié** → **Proposition** ✅
- **Proposition** → **Négocié** ✅
- **Négocié** → **Gagné** ✅

#### **Transitions de Retour :**
- **En cours** → **En attente** ✅
- **Perdu** → **Nouveau** ✅ (relance)

### 3.2 Test des Règles de Validation
1. Essayez une transition non autorisée (ex: Nouveau → Gagné)
2. ✅ **Vérification** : Un message d'erreur s'affiche
3. Testez les règles métier :
   - Pour passer en "Qualifié" : au moins une action requise
   - Pour passer en "Proposition" : statut "Qualifié" requis
   - Pour passer en "Gagné" : statut "Proposition" ou "Négocié" requis

### 3.3 Test des Actions Suggérées
1. Changez le statut d'un lead
2. ✅ **Vérification** : Les actions recommandées se mettent à jour
3. Testez les actions contextuelles selon le statut :
   - **Nouveau** : Premier contact, qualification
   - **En cours** : Suivi, documentation
   - **Qualifié** : Proposition, présentation
   - **Proposition** : Négociation, relance
   - **Négocié** : Finalisation, clôture

---

## 📊 **ÉTAPE 4 : Test des Statistiques et Métriques**

### 4.1 Statistiques des Actions
1. Dans la vue détaillée d'un lead
2. Section **"Statistiques des actions"**
3. ✅ **Vérification** : Affichage des statistiques par type d'action

### 4.2 Métriques de Workflow
1. Vérifiez les indicateurs :
   - **Taux de réussite** des actions
   - **Durée dans le statut** actuel
   - **Actions recommandées** suivantes

### 4.3 Statistiques Globales
1. Dans l'interface principale **Leads**
2. Vérifiez les compteurs :
   - Total des leads
   - Répartition par statut
   - Répartition par type (SCI/DPE)

---

## 🔍 **ÉTAPE 5 : Test des Filtres Avancés**

### 5.1 Filtres de Base
1. Dans l'interface principale **Leads**
2. Testez les filtres :
   - **Type** : SCI, DPE, Tous
   - **Statut** : Nouveau, En cours, Qualifié, etc.
   - **Priorité** : Faible, Moyenne, Élevée
   - **Dates** : Date de création, Date de modification

### 5.2 Filtres Avancés
1. Testez les filtres combinés :
   - Type + Statut
   - Statut + Priorité
   - Dates + Type
2. ✅ **Vérification** : Les résultats se filtrent correctement

### 5.3 Recherche Textuelle
1. Utilisez la barre de recherche
2. Testez la recherche par :
   - ID original
   - Notes
   - Descriptions d'actions

---

## 📤 **ÉTAPE 6 : Test des Fonctionnalités d'Export**

### 6.1 Export CSV
1. Dans l'interface principale **Leads**
2. Appliquez des filtres si nécessaire
3. Cliquez sur **"Exporter CSV"**
4. ✅ **Vérification** : Le fichier CSV se télécharge avec les données

### 6.2 Export Excel
1. Même procédure que pour CSV
2. Cliquez sur **"Exporter Excel"**
3. ✅ **Vérification** : Le fichier Excel se télécharge

### 6.3 Export avec Filtres
1. Appliquez des filtres spécifiques
2. Exportez les données filtrées
3. ✅ **Vérification** : Seules les données filtrées sont exportées

---

## 🎨 **ÉTAPE 7 : Test de l'Interface Utilisateur**

### 7.1 Design Responsive
1. Testez sur différents écrans :
   - **Desktop** : Interface complète
   - **Tablet** : Adaptation des colonnes
   - **Mobile** : Interface simplifiée

### 7.2 Modals et Interactions
1. Testez l'ouverture/fermeture des modals
2. Vérifiez les animations et transitions
3. Testez les raccourcis clavier :
   - **Échap** : Fermer les modals
   - **Ctrl+N** : Nouvelle action
   - **Ctrl+S** : Sauvegarder

### 7.3 Notifications et Messages
1. Testez les messages de succès
2. Testez les messages d'erreur
3. Vérifiez les confirmations de suppression

---

## 🔧 **ÉTAPE 8 : Test des Fonctionnalités Avancées**

### 8.1 Actions Programmées
1. Ajoutez une action avec une date programmée
2. ✅ **Vérification** : L'action apparaît dans les actions programmées

### 8.2 Actions en Lot
1. Sélectionnez plusieurs leads
2. Testez les actions en lot :
   - Changement de statut
   - Changement de priorité
   - Ajout de notes
   - Suppression

### 8.3 Historique Complet
1. Vérifiez que toutes les actions sont tracées
2. Testez la pagination de l'historique
3. Vérifiez les détails de chaque action

---

## 🚨 **ÉTAPE 9 : Test des Cas d'Erreur**

### 9.1 Validation des Données
1. Testez l'ajout d'actions sans données obligatoires
2. ✅ **Vérification** : Messages d'erreur appropriés

### 9.2 Transitions Interdites
1. Essayez des transitions non autorisées
2. ✅ **Vérification** : Messages d'erreur explicatifs

### 9.3 Gestion des Erreurs AJAX
1. Simulez des erreurs réseau
2. ✅ **Vérification** : Messages d'erreur utilisateur

---

## 📝 **ÉTAPE 10 : Validation Finale**

### 10.1 Checklist de Validation
- [ ] Toutes les actions peuvent être ajoutées/modifiées/supprimées
- [ ] Le workflow fonctionne correctement
- [ ] Les statistiques s'affichent
- [ ] Les filtres fonctionnent
- [ ] L'export fonctionne
- [ ] L'interface est responsive
- [ ] Les erreurs sont gérées

### 10.2 Test de Performance
1. Testez avec beaucoup de leads (100+)
2. Vérifiez les temps de chargement
3. Testez la pagination

### 10.3 Test de Sécurité
1. Vérifiez les nonces AJAX
2. Testez les permissions utilisateur
3. Vérifiez la validation des données

---

## 🎉 **Résultats Attendus**

Après avoir suivi ce guide, vous devriez avoir validé :

✅ **Système d'Actions Complet** : Ajout, modification, suppression, programmation
✅ **Workflow Intelligent** : Transitions guidées avec validation
✅ **Interface Professionnelle** : Design moderne et responsive
✅ **Fonctionnalités d'Export** : Export CSV/Excel fonctionnel
✅ **Statistiques et Métriques** : Suivi des performances
✅ **Gestion d'Erreurs** : Messages informatifs et sécurisés

---

## 🆘 **Dépannage**

### Problèmes Courants

#### **Les actions ne s'affichent pas**
- Vérifiez que la table `my_istymo_lead_actions` existe
- Vérifiez les logs d'erreur WordPress

#### **Les transitions de statut ne fonctionnent pas**
- Vérifiez que la classe `Lead_Workflow` est chargée
- Vérifiez les permissions utilisateur

#### **L'interface ne se charge pas**
- Vérifiez que tous les fichiers CSS/JS sont chargés
- Vérifiez la console du navigateur pour les erreurs JavaScript

#### **Les exports ne fonctionnent pas**
- Vérifiez les permissions d'écriture
- Vérifiez l'espace disque disponible

### Logs de Diagnostic
Les logs sont disponibles dans : `wp-content/uploads/my-istymo-logs/`

---

## 📞 **Support**

Si vous rencontrez des problèmes :
1. Consultez les logs de diagnostic
2. Vérifiez la console du navigateur
3. Testez avec un autre navigateur
4. Contactez l'équipe de développement

---

**🎯 Phase 3 entièrement testée et validée !**
