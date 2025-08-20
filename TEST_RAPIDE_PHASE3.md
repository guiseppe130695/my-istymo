# ⚡ Test Rapide - Phase 3: Fonctionnalités Avancées

## 🎯 Test Express (15 minutes)

Ce guide permet de valider rapidement les fonctionnalités essentielles de la Phase 3.

---

## 🚀 **Test 1 : Accès et Interface (2 min)**

### ✅ Vérifications Rapides
1. **Menu WordPress** : Vérifiez que "Leads" apparaît dans le menu admin
2. **Page principale** : Accédez à **Leads** → Interface se charge sans erreur
3. **Configuration** : Allez dans **Leads > Configuration** → Classes chargées ✅

### 🔍 Points à Vérifier
- [ ] Menu "Leads" visible
- [ ] Interface se charge sans erreur JavaScript
- [ ] Au moins quelques leads affichés
- [ ] Filtres fonctionnels

---

## 📞 **Test 2 : Système d'Actions (5 min)**

### ✅ Test d'Ajout d'Action
1. Cliquez sur **"Voir"** pour un lead
2. Cliquez sur **"Ajouter une action"**
3. Remplissez :
   - Type : **Appel téléphonique**
   - Description : **"Test action"**
   - Résultat : **En attente**
4. Cliquez **"Ajouter l'action"**

### ✅ Test de Modification
1. Dans l'historique, cliquez **"Modifier"**
2. Changez le résultat en **"Réussi"**
3. Cliquez **"Modifier"**

### ✅ Test de Suppression
1. Cliquez **"Supprimer"** sur une action
2. Confirmez la suppression

### 🔍 Points à Vérifier
- [ ] Action ajoutée dans l'historique
- [ ] Modification sauvegardée
- [ ] Suppression fonctionne
- [ ] Messages de succès/erreur

---

## 🔄 **Test 3 : Workflow (3 min)**

### ✅ Test de Transition
1. Dans la vue détaillée d'un lead
2. Section **"Actions rapides"**
3. Changez le statut : **Nouveau** → **En cours**
4. Vérifiez que les actions suggérées se mettent à jour

### ✅ Test de Validation
1. Essayez de passer directement de **Nouveau** → **Gagné**
2. Vérifiez que l'erreur s'affiche

### 🔍 Points à Vérifier
- [ ] Transition autorisée fonctionne
- [ ] Transition interdite bloque avec message
- [ ] Actions suggérées se mettent à jour
- [ ] Statut change dans l'interface

---

## 📊 **Test 4 : Statistiques (2 min)**

### ✅ Vérification des Stats
1. Dans la vue détaillée d'un lead
2. Section **"Statistiques des actions"**
3. Vérifiez l'affichage des statistiques

### ✅ Vérification des Métriques
1. Vérifiez les indicateurs :
   - Taux de réussite
   - Durée dans le statut
   - Actions recommandées

### 🔍 Points à Vérifier
- [ ] Statistiques s'affichent
- [ ] Métriques sont cohérentes
- [ ] Pas d'erreurs de calcul

---

## 🔍 **Test 5 : Filtres (2 min)**

### ✅ Test des Filtres
1. Dans l'interface principale
2. Testez les filtres :
   - **Type** : SCI, DPE
   - **Statut** : Nouveau, En cours
   - **Priorité** : Faible, Moyenne, Élevée

### 🔍 Points à Vérifier
- [ ] Filtres fonctionnent
- [ ] Résultats se mettent à jour
- [ ] Combinaison de filtres possible

---

## 📤 **Test 6 : Export (1 min)**

### ✅ Test d'Export
1. Appliquez un filtre
2. Cliquez sur **"Exporter CSV"**
3. Vérifiez le téléchargement

### 🔍 Points à Vérifier
- [ ] Export se lance
- [ ] Fichier se télécharge
- [ ] Données filtrées exportées

---

## ✅ **Validation Finale**

### Checklist Express
- [ ] Interface se charge ✅
- [ ] Actions : Ajout/Modif/Suppression ✅
- [ ] Workflow : Transitions + Validation ✅
- [ ] Statistiques s'affichent ✅
- [ ] Filtres fonctionnent ✅
- [ ] Export fonctionne ✅

### 🎉 Résultat
Si tous les points sont cochés : **Phase 3 opérationnelle !**

---

## 🚨 **Problèmes Rapides**

### Si l'interface ne se charge pas
1. Vérifiez la console (F12)
2. Rechargez la page
3. Vérifiez les permissions admin

### Si les actions ne fonctionnent pas
1. Vérifiez que les tables existent
2. Consultez les logs : `wp-content/uploads/my-istymo-logs/`

### Si le workflow ne fonctionne pas
1. Vérifiez que la classe `Lead_Workflow` est chargée
2. Testez avec un autre lead

---

**⚡ Test rapide terminé en 15 minutes !**
