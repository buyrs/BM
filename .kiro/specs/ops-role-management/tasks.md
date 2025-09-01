# Plan d'Implémentation - Gestion du Rôle Ops et Bail Mobilité

-   [x] 1. Mise en place de l'infrastructure de base

    -   Créer les migrations pour les nouvelles tables (contract_templates, bail_mobilites, bail_mobilite_signatures, notifications)
    -   Ajouter le nouveau rôle "ops" au système de permissions existant
    -   Étendre la table missions avec les nouvelles colonnes pour les BM
    -   _Exigences : 1bis.1, 9.1, 9.3_

-   [x] 2. Création des modèles Eloquent et relations

    -   Implémenter le modèle ContractTemplate avec ses relations et scopes
    -   Implémenter le modèle BailMobilite avec toutes ses relations
    -   Implémenter le modèle BailMobiliteSignature avec validation des signatures
    -   Implémenter le modèle Notification pour les alertes automatiques
    -   Étendre le modèle Mission existant pour supporter les BM
    -   _Exigences : 1.2, 2.1, 10.1_

-   [x] 3. Développement du système de gestion des modèles de contrats
-   [x] 3.1 Créer le contrôleur ContractTemplateController

    -   Implémenter les méthodes CRUD pour les modèles de contrats
    -   Ajouter la fonctionnalité de signature électronique pour l'admin
    -   Créer la validation des contenus légaux et signatures
    -   _Exigences : 1.1, 1.2, 1.3_

-   [x] 3.2 Développer l'interface de gestion des modèles de contrats

    -   Créer les vues Vue.js pour la création/édition des modèles
    -   Implémenter le composant de signature électronique pour l'admin
    -   Ajouter la prévisualisation des contrats avec signatures
    -   _Exigences : 1.4, 1.5_

-   [x] 4. Implémentation du système de Bail Mobilité
-   [x] 4.1 Créer le contrôleur BailMobiliteController

    -   Implémenter la création de BM avec génération automatique des missions d'entrée/sortie
    -   Ajouter les méthodes d'assignation des checkers aux missions
    -   Créer la logique de validation des checklists par les Ops
    -   Implémenter la gestion des transitions d'état (Assigné → En cours → Terminé/Incident)
    -   _Exigences : 2.1, 2.2, 2.3, 4.2, 7.2_

-   [x] 4.2 Développer les vues de gestion des BM

    -   Créer le formulaire de création de BM avec sélection de dates et infos locataire
    -   Implémenter la vue kanban pour le suivi des BM (colonnes : Assigné, En cours, Terminé, Incident)
    -   Ajouter les modales de détails BM avec historique complet
    -   Créer l'interface d'assignation des checkers avec disponibilité
    -   _Exigences : 2.4, 8.1, 8.2, 8.3_

-   [x] 5. Extension du système de missions pour les BM
-   [x] 5.1 Étendre le MissionController existant

    -   Ajouter les méthodes spécifiques aux missions BM (entrée/sortie)
    -   Implémenter la logique d'assignation par les Ops
    -   Créer la validation des checklists avec photos obligatoires
    -   Ajouter la gestion des signatures électroniques des locataires
    -   _Exigences : 3.1, 3.2, 6.1, 6.2_

-   [x] 5.2 Adapter les vues de missions pour les BM

    -   Modifier les interfaces checker pour afficher les informations BM
    -   Ajouter le processus de signature électronique du locataire
    -   Intégrer la génération automatique des contrats PDF avec signatures
    -   Créer les vues de validation pour les Ops
    -   _Exigences : 3.6, 6.6, 4.1, 7.1_

-   [x] 6. Développement du système de signatures électroniques
-   [x] 6.1 Créer le service de gestion des signatures

    -   Implémenter la logique de signature électronique avec horodatage
    -   Créer la génération de PDF avec intégration des signatures (admin + locataire)
    -   Ajouter la validation et vérification des signatures
    -   Implémenter le stockage sécurisé des documents signés
    -   _Exigences : 3.3, 3.4, 6.3, 6.4, 10.1_

-   [x] 6.2 Développer les composants de signature

    -   Créer le composant Vue.js de signature électronique pour les locataires
    -   Implémenter l'affichage des contrats à signer
    -   Ajouter la prévisualisation des PDF générés avec signatures
    -   Créer l'interface de consultation des documents signés pour les Ops
    -   _Exigences : 10.2, 10.3_

-   [x] 7. Implémentation du système de notifications automatiques
-   [x] 7.1 Créer le service NotificationService

    -   Implémenter la programmation des notifications 10 jours avant fin de séjour
    -   Créer la logique d'envoi d'emails et notifications push
    -   Ajouter la gestion des notifications d'incidents et validations
    -   Implémenter l'annulation des notifications en cas de modification de dates
    -   _Exigences : 5.1, 5.2, 7.4_

-   [x] 7.2 Développer l'interface de notifications

    -   Créer le système d'affichage des notifications dans le tableau de bord Ops
    -   Implémenter les actions rapides depuis les notifications
    -   Ajouter l'historique des notifications envoyées
    -   _Exigences : 5.3, 5.4_

-   [x] 8. Création du tableau de bord Ops
-   [x] 8.1 Développer le contrôleur OpsController

    -   Implémenter les métriques et statistiques pour le dashboard
    -   Créer les données pour la vue kanban des BM
    -   Ajouter les filtres et recherches pour les BM
    -   Implémenter l'export des données et rapports
    -   _Exigences : 8.4, 1bis.3_

-   [x] 8.2 Créer l'interface du tableau de bord Ops

    -   Développer la vue kanban interactive avec drag & drop
    -   Implémenter les widgets de métriques (BM en cours, incidents, performances)
    -   Ajouter les graphiques de tendances et analyses
    -   Créer les filtres avancés et options d'export
    -   _Exigences : 8.1, 8.2, 8.3, 8.4_

-   [x] 9. Gestion des permissions et sécurité
-   [x] 9.1 Configurer les permissions du rôle Ops

    -   Ajouter les permissions spécifiques aux Ops dans le seeder
    -   Implémenter les middleware de contrôle d'accès pour les routes Ops
    -   Créer les restrictions d'accès aux fonctions administratives
    -   Ajouter la journalisation des tentatives d'accès non autorisées
    -   _Exigences : 9.1, 9.2, 9.4_

-   [x] 9.2 Sécuriser les signatures électroniques

    -   Implémenter la capture des métadonnées de signature (IP, device, timestamp)
    -   Créer le système de vérification d'intégrité des signatures
    -   Ajouter le chiffrement des données sensibles
    -   Implémenter la traçabilité complète des actions
    -   _Exigences : 10.5_

-   [-] 10. Gestion des incidents et cas d'erreur
-   [-] 10.1 Implémenter la détection automatique d'incidents

    -   Créer la logique de détection des problèmes (clés non remises, signatures manquantes)
    -   Implémenter le passage automatique au statut "Incident"
    -   Ajouter les alertes automatiques aux Ops en cas d'incident
    -   Créer le système de tâches correctives
    -   _Exigences : 7.3, 7.4_

-   [ ] 10.2 Développer l'interface de gestion des incidents

    -   Créer les vues de détail des incidents avec toutes les informations
    -   Implémenter les actions correctives et suivi des résolutions
    -   Ajouter l'historique complet des incidents et leurs résolutions
    -   _Exigences : 7.4_

-   [ ] 11. Tests et validation
-   [ ] 11.1 Créer les tests unitaires

    -   Tester tous les modèles avec leurs relations et validations
    -   Tester les services (signatures, notifications, BM)
    -   Tester les contrôleurs avec leurs permissions
    -   Tester la logique métier des transitions d'état
    -   _Exigences : Toutes_

-   [ ] 11.2 Créer les tests d'intégration

    -   Tester le workflow complet : Création BM → Entrée → Validation → Sortie
    -   Tester le système de notifications automatiques
    -   Tester les permissions et restrictions d'accès par rôle
    -   Tester la génération et intégrité des PDF signés
    -   _Exigences : Workflow complet_

-   [ ] 12. Documentation et formation
-   [ ] 12.1 Créer la documentation technique

    -   Documenter les nouvelles API et endpoints
    -   Créer les guides d'utilisation pour chaque rôle
    -   Documenter les processus de signature électronique
    -   Ajouter les guides de dépannage et maintenance
    -   _Exigences : Support utilisateur_

-   [ ] 12.2 Préparer la migration des données
    -   Créer les scripts de migration des missions existantes vers les BM
    -   Implémenter la sauvegarde et restauration des données
    -   Tester la migration sur un environnement de test
    -   Préparer le plan de déploiement en production
    -   _Exigences : Continuité de service_
