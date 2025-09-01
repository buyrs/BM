# Guide Administrateur - Système Ops et Bail Mobilité

## Vue d'ensemble

En tant qu'administrateur, vous avez la responsabilité de configurer et superviser l'ensemble du système de gestion des Bail Mobilité. Ce guide vous accompagne dans toutes vos tâches administratives.

## Accès au Système

### Connexion
1. Accédez à l'URL de l'application
2. Connectez-vous avec vos identifiants administrateur
3. Vous arrivez sur le tableau de bord administrateur

### Interface Administrateur
L'interface administrateur comprend :
- **Tableau de bord** : Vue d'ensemble des activités
- **Gestion des utilisateurs** : Création et gestion des comptes
- **Modèles de contrats** : Configuration des contrats légaux
- **Rapports et analytics** : Métriques et performances
- **Configuration système** : Paramètres généraux

## Gestion des Utilisateurs

### Créer un Utilisateur Ops

1. **Navigation** : Menu "Utilisateurs" → "Créer un utilisateur"
2. **Informations** :
   - Nom complet
   - Adresse email (identifiant de connexion)
   - Mot de passe temporaire
   - **Rôle** : Sélectionner "Ops"
3. **Validation** : Cliquer sur "Créer l'utilisateur"
4. **Notification** : L'utilisateur reçoit ses identifiants par email

### Permissions du Rôle Ops

Les utilisateurs Ops ont automatiquement accès à :
- ✅ Création et gestion des Bail Mobilité
- ✅ Assignation des checkers aux missions
- ✅ Validation des checklists d'entrée et sortie
- ✅ Gestion des incidents et actions correctives
- ✅ Tableau de bord opérationnel
- ✅ Consultation des contrats signés
- ❌ Gestion des utilisateurs (réservé aux admins)
- ❌ Configuration des modèles de contrats
- ❌ Accès aux fonctions système critiques

### Gestion des Checkers

Les checkers sont gérés comme dans le système existant :
1. **Création** : Menu "Agents" → "Créer un agent"
2. **Informations** : Nom, contact, disponibilités
3. **Assignation** : Les Ops peuvent les assigner aux missions BM

## Gestion des Modèles de Contrats

### Créer un Modèle de Contrat

1. **Navigation** : Menu "Contrats" → "Modèles de contrats"
2. **Nouveau modèle** : Cliquer sur "Créer un modèle"
3. **Configuration** :
   - **Nom** : Ex. "Contrat d'entrée standard 2025"
   - **Type** : Entrée ou Sortie
   - **Contenu légal** : Saisir le texte du contrat
4. **Sauvegarde** : Le modèle est créé en statut "Brouillon"

### Signer un Modèle de Contrat

⚠️ **Important** : Seuls les modèles signés peuvent être utilisés pour les BM.

1. **Sélection** : Choisir le modèle à signer
2. **Révision** : Vérifier le contenu légal
3. **Signature** :
   - Cliquer sur "Signer le modèle"
   - Utiliser le pad de signature électronique
   - Confirmer la signature
4. **Activation** : Le modèle devient automatiquement actif

### Gestion des Versions

- **Modification** : Créer une nouvelle version du modèle
- **Désactivation** : L'ancienne version est automatiquement désactivée
- **Historique** : Toutes les versions sont conservées pour traçabilité

## Supervision des Activités

### Tableau de Bord Administrateur

Le tableau de bord affiche :
- **Métriques globales** : Nombre de BM, taux de réussite, incidents
- **Activité récente** : Dernières actions des Ops et checkers
- **Alertes système** : Problèmes nécessitant attention
- **Performance** : Temps de traitement, satisfaction client

### Rapports et Analytics

1. **Rapports mensuels** : Performance des équipes, incidents
2. **Analytics** : Tendances, pics d'activité, optimisations
3. **Export** : Données au format Excel/PDF pour analyse

### Monitoring des Incidents

- **Vue d'ensemble** : Tous les incidents en cours
- **Escalade** : Incidents non résolus dans les délais
- **Actions correctives** : Suivi des résolutions
- **Analyse** : Causes récurrentes, améliorations

## Configuration Système

### Paramètres Généraux

1. **Notifications** :
   - Délai de rappel avant fin de séjour (défaut: 10 jours)
   - Destinataires des alertes système
   - Templates d'emails

2. **Signatures électroniques** :
   - Durée de validité des sessions de signature
   - Métadonnées à capturer
   - Paramètres de sécurité

3. **Stockage** :
   - Rétention des documents signés
   - Sauvegarde automatique
   - Archivage des BM terminés

### Sécurité et Permissions

1. **Audit des accès** :
   - Logs des connexions
   - Actions sensibles tracées
   - Tentatives d'accès non autorisées

2. **Gestion des sessions** :
   - Durée de session
   - Déconnexion automatique
   - Sécurité des mots de passe

## Procédures de Maintenance

### Sauvegarde des Données

1. **Automatique** : Sauvegarde quotidienne programmée
2. **Manuelle** : Menu "Système" → "Sauvegarde"
3. **Vérification** : Test de restauration mensuel
4. **Stockage** : Copies locales et cloud sécurisé

### Mise à Jour du Système

1. **Planification** : Maintenance programmée hors heures d'activité
2. **Sauvegarde** : Backup complet avant mise à jour
3. **Test** : Validation sur environnement de test
4. **Déploiement** : Application en production
5. **Vérification** : Tests post-déploiement

### Nettoyage des Données

1. **Archivage** : BM terminés depuis plus de 2 ans
2. **Purge** : Logs anciens, sessions expirées
3. **Optimisation** : Base de données, index, performances

## Résolution des Problèmes Courants

### Problème : Utilisateur Ops ne peut pas créer de BM

**Diagnostic** :
1. Vérifier les permissions du rôle Ops
2. Contrôler l'activation du compte utilisateur
3. Vérifier la disponibilité des modèles de contrats signés

**Solution** :
1. Menu "Utilisateurs" → Sélectionner l'utilisateur
2. Vérifier que le rôle "Ops" est bien attribué
3. S'assurer qu'au moins un modèle de contrat d'entrée est signé et actif

### Problème : Signatures électroniques ne fonctionnent pas

**Diagnostic** :
1. Vérifier la configuration SSL/HTTPS
2. Contrôler les permissions de stockage
3. Tester sur différents navigateurs/devices

**Solution** :
1. S'assurer que l'application est accessible en HTTPS
2. Vérifier les droits d'écriture dans le dossier de stockage
3. Mettre à jour les navigateurs si nécessaire

### Problème : Notifications non envoyées

**Diagnostic** :
1. Vérifier la configuration email (SMTP)
2. Contrôler les queues Laravel
3. Vérifier les logs d'erreur

**Solution** :
1. Tester l'envoi d'email depuis l'interface admin
2. Redémarrer les workers de queue si nécessaire
3. Corriger la configuration SMTP si défaillante

## Bonnes Pratiques

### Sécurité
- **Mots de passe** : Politique stricte, renouvellement régulier
- **Accès** : Principe du moindre privilège
- **Audit** : Révision régulière des permissions
- **Sauvegarde** : Tests de restauration périodiques

### Performance
- **Monitoring** : Surveillance des temps de réponse
- **Optimisation** : Nettoyage régulier des données
- **Mise à jour** : Application des correctifs de sécurité
- **Capacité** : Anticipation de la montée en charge

### Formation
- **Nouveaux utilisateurs** : Formation initiale obligatoire
- **Mises à jour** : Communication des nouvelles fonctionnalités
- **Documentation** : Maintien à jour des guides
- **Support** : Disponibilité pour assistance

## Contact et Support

### Support Technique
- **Email** : admin-support@example.com
- **Téléphone** : +33 1 23 45 67 89
- **Horaires** : 9h-18h, lundi-vendredi

### Escalade
- **Incidents critiques** : Notification immédiate
- **Problèmes récurrents** : Analyse et amélioration
- **Demandes d'évolution** : Processus de validation

### Documentation
- **Mise à jour** : Révision trimestrielle
- **Feedback** : Retours utilisateurs intégrés
- **Versions** : Historique des modifications