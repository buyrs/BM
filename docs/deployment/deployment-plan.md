# Plan de Déploiement - Système Ops et Bail Mobilité

## Vue d'ensemble

Ce document détaille le plan de déploiement complet pour la mise en production du système de gestion Ops et Bail Mobilité.

## Informations Générales

- **Version** : 1.0.0
- **Date prévue** : À définir
- **Durée estimée** : 4-6 heures
- **Fenêtre de maintenance** : 3-5 heures
- **Environnements** : Staging → Production

## Équipe de Déploiement

### Rôles et Responsabilités

| Rôle | Responsable | Contact | Responsabilités |
|------|-------------|---------|-----------------|
| **Chef de projet** | [Nom] | +33 X XX XX XX XX | Coordination générale, décisions |
| **Développeur principal** | [Nom] | +33 X XX XX XX XX | Déploiement technique, code |
| **DBA** | [Nom] | +33 X XX XX XX XX | Base de données, migrations |
| **DevOps** | [Nom] | +33 X XX XX XX XX | Infrastructure, monitoring |
| **Testeur** | [Nom] | +33 X XX XX XX XX | Validation fonctionnelle |
| **Support** | [Nom] | +33 X XX XX XX XX | Communication utilisateurs |

### Disponibilité Requise

- **Équipe technique** : 2 heures avant → 2 heures après
- **Support utilisateurs** : Pendant + 4 heures après
- **Management** : Disponible pour décisions critiques

## Prérequis

### Technique

- [ ] **Environnement de staging** validé et fonctionnel
- [ ] **Tests de migration** exécutés avec succès
- [ ] **Sauvegarde complète** effectuée et testée
- [ ] **Scripts de déploiement** testés sur staging
- [ ] **Plan de rollback** préparé et testé
- [ ] **Monitoring** configuré et opérationnel

### Organisationnel

- [ ] **Utilisateurs informés** de la maintenance
- [ ] **Formation équipe** effectuée
- [ ] **Documentation** mise à jour
- [ ] **Support client** préparé
- [ ] **Validation métier** obtenue

### Infrastructure

- [ ] **Serveurs** dimensionnés et disponibles
- [ ] **Base de données** optimisée
- [ ] **Espace disque** suffisant (min 20GB libre)
- [ ] **Sauvegardes** automatiques configurées
- [ ] **Accès réseau** validés

## Chronologie Détaillée

### J-7 : Préparation Finale

**Objectif** : Finaliser tous les préparatifs

- [ ] **09:00** - Réunion équipe de déploiement
- [ ] **10:00** - Test final sur environnement de staging
- [ ] **14:00** - Validation des scripts de migration
- [ ] **16:00** - Communication aux utilisateurs (planning maintenance)
- [ ] **17:00** - Préparation des environnements

### J-1 : Validation Pré-Déploiement

**Objectif** : Dernières vérifications avant production

- [ ] **09:00** - Vérification complète environnement staging
- [ ] **10:00** - Test de charge et performance
- [ ] **14:00** - Validation finale des données de test
- [ ] **15:00** - Briefing équipe de déploiement
- [ ] **16:00** - Préparation matériel et accès
- [ ] **17:00** - Confirmation GO/NO-GO

### Jour J : Déploiement Production

#### Phase 1 : Préparation (30 min)

**Horaire** : 06:00 - 06:30

- [ ] **06:00** - Connexion équipe technique
- [ ] **06:05** - Vérification état système production
- [ ] **06:10** - Activation monitoring renforcé
- [ ] **06:15** - Dernière sauvegarde de sécurité
- [ ] **06:25** - GO/NO-GO final
- [ ] **06:30** - **DÉBUT MAINTENANCE**

#### Phase 2 : Mise en Maintenance (15 min)

**Horaire** : 06:30 - 06:45

- [ ] **06:30** - Activation mode maintenance
  ```bash
  php artisan down --message="Mise à jour système en cours" --retry=60
  ```
- [ ] **06:32** - Arrêt des workers de queue
  ```bash
  supervisorctl stop laravel-worker:*
  ```
- [ ] **06:35** - Vérification arrêt des processus
- [ ] **06:40** - Sauvegarde finale pré-migration
- [ ] **06:45** - Validation état système

#### Phase 3 : Déploiement Code (45 min)

**Horaire** : 06:45 - 07:30

- [ ] **06:45** - Récupération du code depuis Git
  ```bash
  git fetch origin
  git checkout v1.0.0
  ```
- [ ] **06:50** - Installation des dépendances
  ```bash
  composer install --no-dev --optimize-autoloader
  npm ci && npm run build
  ```
- [ ] **07:00** - Exécution des migrations de base
  ```bash
  php artisan migrate --force
  ```
- [ ] **07:10** - Mise à jour des permissions
  ```bash
  php artisan permission:sync
  ```
- [ ] **07:15** - Configuration des nouveaux services
- [ ] **07:25** - Vérification intégrité du code
- [ ] **07:30** - Validation déploiement code

#### Phase 4 : Migration des Données (90 min)

**Horaire** : 07:30 - 09:00

- [ ] **07:30** - Ajout des colonnes de migration
  ```bash
  php artisan migrate:add-migration-columns
  ```
- [ ] **07:35** - Test de migration en mode dry-run
  ```bash
  php artisan migrate:missions-to-bm --dry-run
  ```
- [ ] **07:45** - **POINT DE CONTRÔLE** - Validation dry-run
- [ ] **07:50** - Exécution migration réelle
  ```bash
  php artisan migrate:missions-to-bm --batch-size=50
  ```
- [ ] **08:30** - Vérification intégrité données migrées
- [ ] **08:45** - Validation des relations et contraintes
- [ ] **08:55** - **POINT DE CONTRÔLE** - Migration terminée
- [ ] **09:00** - Génération rapport de migration

#### Phase 5 : Configuration et Optimisation (30 min)

**Horaire** : 09:00 - 09:30

- [ ] **09:00** - Configuration des caches
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- [ ] **09:05** - Création des liens symboliques
  ```bash
  php artisan storage:link
  ```
- [ ] **09:10** - Optimisation base de données
  ```sql
  OPTIMIZE TABLE bail_mobilites, missions, notifications;
  ```
- [ ] **09:15** - Configuration des queues
- [ ] **09:20** - Test des services critiques
- [ ] **09:25** - Vérification des permissions fichiers
- [ ] **09:30** - Validation configuration

#### Phase 6 : Tests de Validation (45 min)

**Horaire** : 09:30 - 10:15

- [ ] **09:30** - Tests automatisés post-déploiement
  ```bash
  php artisan test:migration --verbose
  ```
- [ ] **09:45** - Tests fonctionnels manuels
  - [ ] Connexion utilisateurs (Admin, Ops, Checker)
  - [ ] Création d'un BM de test
  - [ ] Assignation d'une mission
  - [ ] Processus de signature
  - [ ] Génération de PDF
- [ ] **10:00** - Tests de performance
  - [ ] Temps de chargement pages
  - [ ] Requêtes base de données
  - [ ] Génération de rapports
- [ ] **10:10** - Validation des notifications
- [ ] **10:15** - **POINT DE CONTRÔLE** - Tests validés

#### Phase 7 : Remise en Service (15 min)

**Horaire** : 10:15 - 10:30

- [ ] **10:15** - Redémarrage des workers de queue
  ```bash
  supervisorctl start laravel-worker:*
  ```
- [ ] **10:18** - Test des queues et notifications
- [ ] **10:20** - Désactivation mode maintenance
  ```bash
  php artisan up
  ```
- [ ] **10:22** - Vérification accès utilisateurs
- [ ] **10:25** - Activation monitoring normal
- [ ] **10:30** - **FIN MAINTENANCE**

#### Phase 8 : Surveillance Post-Déploiement (60 min)

**Horaire** : 10:30 - 11:30

- [ ] **10:30** - Monitoring intensif activé
- [ ] **10:35** - Vérification logs d'erreur
- [ ] **10:40** - Test avec utilisateurs pilotes
- [ ] **10:50** - Validation métriques performance
- [ ] **11:00** - Communication fin de maintenance
- [ ] **11:15** - Débriefing équipe technique
- [ ] **11:30** - **DÉPLOIEMENT TERMINÉ**

## Points de Contrôle et Validation

### Point de Contrôle 1 : Dry-Run Migration (07:45)

**Critères de validation** :
- [ ] Migration dry-run exécutée sans erreur
- [ ] Statistiques cohérentes (nombre de missions, BM créés)
- [ ] Aucune corruption de données détectée
- [ ] Temps d'exécution acceptable (< 30 min estimé)

**Actions si échec** :
1. Analyser les logs d'erreur
2. Corriger les problèmes identifiés
3. Relancer le dry-run
4. Si problème majeur : déclencher rollback

### Point de Contrôle 2 : Migration Terminée (08:55)

**Critères de validation** :
- [ ] Migration réelle terminée avec succès
- [ ] Toutes les missions éligibles migrées
- [ ] Relations BM ↔ Missions correctes
- [ ] Intégrité référentielle respectée
- [ ] Aucune perte de données

**Actions si échec** :
1. Évaluer l'ampleur des problèmes
2. Tentative de correction si mineur
3. Si majeur : rollback immédiat

### Point de Contrôle 3 : Tests Validés (10:15)

**Critères de validation** :
- [ ] Tests automatisés passés (100%)
- [ ] Tests fonctionnels validés
- [ ] Performance acceptable
- [ ] Aucune régression détectée

**Actions si échec** :
1. Identifier les fonctionnalités impactées
2. Évaluer l'impact utilisateur
3. Décision GO/NO-GO pour remise en service
4. Rollback si impact critique

## Procédure de Rollback

### Déclenchement

**Critères de rollback** :
- Échec d'un point de contrôle critique
- Corruption de données détectée
- Performance dégradée > 50%
- Fonctionnalité critique indisponible
- Décision management

### Procédure d'Urgence (30 min)

1. **Arrêt immédiat** (5 min)
   ```bash
   php artisan down --message="Problème technique - Restauration en cours"
   supervisorctl stop laravel-worker:*
   ```

2. **Restauration base de données** (15 min)
   ```bash
   ./docs/deployment/backup-restore/restore-script.sh /backups/pre-migration-production-[timestamp] database-only
   ```

3. **Restauration code** (5 min)
   ```bash
   git checkout [previous-stable-version]
   composer install --no-dev --optimize-autoloader
   ```

4. **Remise en service** (5 min)
   ```bash
   php artisan config:cache
   supervisorctl start laravel-worker:*
   php artisan up
   ```

### Validation Post-Rollback

- [ ] Système fonctionnel sur version précédente
- [ ] Données intègres
- [ ] Utilisateurs peuvent se connecter
- [ ] Fonctionnalités critiques opérationnelles

## Communication

### Avant Déploiement (J-7)

**Destinataires** : Tous les utilisateurs
**Canal** : Email + notification in-app
**Message** :
```
Objet: Maintenance programmée - Nouvelles fonctionnalités Ops

Chers utilisateurs,

Une maintenance est programmée le [DATE] de 06h30 à 10h30 pour déployer de nouvelles fonctionnalités de gestion opérationnelle.

Nouvelles fonctionnalités :
- Gestion des Bail Mobilité
- Rôle Ops pour la supervision
- Signatures électroniques
- Notifications automatiques

L'application sera indisponible pendant cette période.

Merci de votre compréhension.
L'équipe technique
```

### Pendant Maintenance

**Destinataires** : Équipe technique + Management
**Canal** : Slack/Teams + SMS si critique
**Fréquence** : À chaque point de contrôle

### Fin de Maintenance

**Destinataires** : Tous les utilisateurs
**Canal** : Email + notification in-app
**Message** :
```
Objet: Maintenance terminée - Nouvelles fonctionnalités disponibles

La maintenance est terminée avec succès !

Nouvelles fonctionnalités disponibles :
- [Liste des fonctionnalités]

Guides d'utilisation : [lien vers documentation]
Support : [contact support]

Merci de votre patience.
L'équipe technique
```

## Métriques de Succès

### Techniques

- [ ] **Disponibilité** : > 99.9% post-déploiement
- [ ] **Performance** : Temps de réponse < 2s
- [ ] **Erreurs** : Taux d'erreur < 0.1%
- [ ] **Migration** : 100% des données éligibles migrées

### Fonctionnelles

- [ ] **Connexions** : Tous les rôles peuvent se connecter
- [ ] **Création BM** : Processus complet fonctionnel
- [ ] **Signatures** : Génération PDF opérationnelle
- [ ] **Notifications** : Système d'alertes actif

### Utilisateurs

- [ ] **Adoption** : > 80% des Ops utilisent le nouveau système
- [ ] **Satisfaction** : Pas de plaintes majeures J+1
- [ ] **Formation** : Support < 10 tickets/jour
- [ ] **Performance** : Temps de traitement BM réduit

## Post-Déploiement

### Surveillance Renforcée (J+0 à J+7)

- **Monitoring** : Vérification toutes les heures
- **Logs** : Analyse quotidienne des erreurs
- **Performance** : Métriques surveillées en continu
- **Support** : Équipe renforcée pour questions utilisateurs

### Actions de Suivi

#### J+1 : Bilan 24h
- [ ] Rapport de stabilité
- [ ] Analyse des métriques
- [ ] Retours utilisateurs
- [ ] Ajustements mineurs si nécessaire

#### J+7 : Bilan semaine
- [ ] Rapport complet de déploiement
- [ ] Analyse de l'adoption utilisateur
- [ ] Optimisations identifiées
- [ ] Planification des améliorations

#### J+30 : Bilan mensuel
- [ ] ROI du déploiement
- [ ] Satisfaction utilisateur
- [ ] Performance vs objectifs
- [ ] Leçons apprises

## Contacts d'Urgence

### Équipe Technique
- **Chef de projet** : +33 X XX XX XX XX
- **Développeur principal** : +33 X XX XX XX XX
- **DBA** : +33 X XX XX XX XX
- **DevOps** : +33 X XX XX XX XX

### Escalade
- **Directeur technique** : +33 X XX XX XX XX
- **Directeur produit** : +33 X XX XX XX XX

### Support
- **Support utilisateurs** : +33 X XX XX XX XX
- **Hotline urgence** : +33 X XX XX XX XX (24h/7j)

## Annexes

### A. Scripts de Déploiement
- [Scripts de migration](./migration-scripts/)
- [Scripts de sauvegarde](./backup-restore/)
- [Scripts de test](./migration-tests/)

### B. Documentation Technique
- [Guide API](../api/README.md)
- [Guide utilisateurs](../guides/README.md)
- [Dépannage](../troubleshooting/README.md)

### C. Checklist de Validation
- [ ] Tous les prérequis validés
- [ ] Équipe de déploiement briefée
- [ ] Scripts testés sur staging
- [ ] Plan de rollback validé
- [ ] Communication utilisateurs effectuée