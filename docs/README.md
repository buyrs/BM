# Documentation Technique - Système de Gestion Ops et Bail Mobilité

## Vue d'ensemble

Cette documentation couvre l'implémentation complète du système de gestion du rôle Ops et des Bail Mobilité (BM) dans l'application de conciergerie Airbnb.

## Structure de la Documentation

### 1. [API et Endpoints](./api/README.md)
Documentation complète des nouvelles API et endpoints pour la gestion des BM et du rôle Ops.

### 2. [Guides d'Utilisation](./guides/README.md)
Guides détaillés pour chaque rôle utilisateur :
- Guide Administrateur
- Guide Utilisateur Ops
- Guide Checker

### 3. [Processus de Signature Électronique](./signatures/README.md)
Documentation technique des processus de signature électronique et génération de contrats PDF.

### 4. [Dépannage et Maintenance](./troubleshooting/README.md)
Guides de résolution des problèmes courants et procédures de maintenance.

### 5. [Migration et Déploiement](./deployment/README.md)
Scripts et procédures pour la migration des données et le déploiement en production.

## Architecture Générale

Le système étend l'architecture Laravel/Inertia.js existante avec :

- **Nouveau rôle** : `ops` avec permissions spécifiques
- **Nouveaux modèles** : `BailMobilite`, `ContractTemplate`, `BailMobiliteSignature`, `Notification`
- **Contrôleurs étendus** : `BailMobiliteController`, `OpsController`, `ContractTemplateController`
- **Services** : `NotificationService`, `SignatureService`, `IncidentDetectionService`

## Workflow Principal

1. **Création BM** par utilisateur Ops
2. **Génération automatique** des missions d'entrée et sortie
3. **Assignation** des checkers par les Ops
4. **Exécution** des missions par les checkers avec signatures
5. **Validation** par les Ops et gestion des transitions d'état
6. **Notifications automatiques** 10 jours avant fin de séjour
7. **Gestion des incidents** et actions correctives

## Technologies Utilisées

- **Backend** : Laravel 10+, PHP 8.1+
- **Frontend** : Vue.js 3, Inertia.js
- **Base de données** : MySQL/SQLite
- **Permissions** : Spatie Laravel Permission
- **Signatures** : Signature électronique avec métadonnées
- **Notifications** : Laravel Notifications + Queue
- **PDF** : Génération de contrats avec signatures intégrées

## Démarrage Rapide

1. Consulter les [guides d'utilisation](./guides/README.md) selon votre rôle
2. Référencer la [documentation API](./api/README.md) pour l'intégration
3. Suivre les [procédures de dépannage](./troubleshooting/README.md) en cas de problème

## Support

Pour toute question technique, consulter :
1. Cette documentation
2. Les guides de dépannage
3. Les logs de l'application
4. L'équipe de développement