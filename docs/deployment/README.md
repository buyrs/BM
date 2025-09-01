# Migration et Déploiement - Système Ops et Bail Mobilité

## Vue d'ensemble

Cette documentation couvre les procédures de migration des données existantes et le déploiement du nouveau système de gestion Ops et Bail Mobilité.

## Structure des Scripts

### 1. [Scripts de Migration](./migration-scripts/)
Scripts pour migrer les missions existantes vers le nouveau système BM.

### 2. [Sauvegarde et Restauration](./backup-restore/)
Procédures de sauvegarde complète et restauration des données.

### 3. [Tests de Migration](./migration-tests/)
Scripts de validation pour tester la migration sur environnement de test.

### 4. [Plan de Déploiement](./deployment-plan.md)
Plan détaillé pour le déploiement en production.

## Prérequis

### Environnement Technique
- **PHP** : 8.1 ou supérieur
- **Laravel** : 10.x
- **Base de données** : MySQL 8.0+ ou SQLite 3.35+
- **Node.js** : 18.x pour la compilation des assets
- **Composer** : 2.x pour les dépendances PHP

### Permissions Système
- **Lecture/écriture** sur les dossiers storage/ et bootstrap/cache/
- **Accès base de données** avec privilèges CREATE, ALTER, INSERT, UPDATE, DELETE
- **Accès réseau** pour les notifications email et webhooks

### Sauvegarde Préalable
⚠️ **OBLIGATOIRE** : Effectuer une sauvegarde complète avant toute migration

```bash
# Sauvegarde base de données
mysqldump -u root -p database_name > backup_pre_migration_$(date +%Y%m%d_%H%M%S).sql

# Sauvegarde fichiers
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz storage/ public/storage/
```

## Processus de Migration

### Phase 1 : Préparation

1. **Vérification de l'environnement**
2. **Sauvegarde complète des données**
3. **Installation des nouvelles dépendances**
4. **Exécution des migrations de base de données**

### Phase 2 : Migration des Données

1. **Analyse des missions existantes**
2. **Conversion vers le format Bail Mobilité**
3. **Création des relations et dépendances**
4. **Validation de l'intégrité des données**

### Phase 3 : Tests et Validation

1. **Tests automatisés de migration**
2. **Validation manuelle des données critiques**
3. **Tests fonctionnels du nouveau système**
4. **Performance et charge**

### Phase 4 : Déploiement Production

1. **Mode maintenance activé**
2. **Exécution de la migration en production**
3. **Tests de validation post-migration**
4. **Activation du nouveau système**

## Estimation des Temps

| Phase | Durée Estimée | Fenêtre de Maintenance |
|-------|---------------|------------------------|
| Préparation | 2-4 heures | Non |
| Migration données | 1-3 heures | Oui |
| Tests validation | 1-2 heures | Oui |
| Déploiement | 30 minutes | Oui |
| **Total** | **4-9 heures** | **2-5 heures** |

## Rollback

En cas de problème, procédure de retour en arrière :

1. **Arrêt immédiat** de la migration
2. **Restauration** de la sauvegarde
3. **Vérification** de l'intégrité des données
4. **Redémarrage** de l'ancien système
5. **Analyse** des causes d'échec

## Points de Contrôle

### Avant Migration
- [ ] Sauvegarde complète effectuée
- [ ] Environnement de test validé
- [ ] Scripts de migration testés
- [ ] Équipe technique disponible
- [ ] Plan de rollback préparé

### Pendant Migration
- [ ] Logs de migration surveillés
- [ ] Intégrité des données vérifiée
- [ ] Performance système monitored
- [ ] Communication avec les utilisateurs

### Après Migration
- [ ] Tests fonctionnels réussis
- [ ] Données migrées validées
- [ ] Performance acceptable
- [ ] Formation utilisateurs effectuée
- [ ] Documentation mise à jour

## Support et Contacts

### Équipe Technique
- **Chef de projet** : +33 1 23 45 67 89
- **Développeur principal** : +33 1 23 45 67 90
- **DBA** : +33 1 23 45 67 91
- **Support** : support-migration@example.com

### Escalade
- **Directeur technique** : +33 6 12 34 56 78
- **Responsable produit** : +33 6 12 34 56 79