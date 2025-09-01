# Exigences - Gestion du Rôle Ops et Bail Mobilité

## Introduction

Cette fonctionnalité introduit un nouveau rôle "Ops" (Opérations) dans le système de conciergerie Airbnb existant, ainsi que la gestion complète des "Bail Mobilité" (BM). Les utilisateurs Ops créent et gèrent les BM de bout en bout : création avec dates de début/fin, assignation des checkers pour l'entrée, validation des checklists d'entrée, suivi automatique avec notifications 10 jours avant la fin, assignation pour la sortie, et validation finale. Le système gère automatiquement les transitions d'état (Assigné → En cours → Terminé/Incident) et les notifications appropriées.

## Exigences

### Exigence 1

**User Story:** En tant qu'administrateur, je veux créer et gérer des modèles de contrats de bail mobilité avec mon contenu légal et ma signature d'hôte, afin de standardiser les documents contractuels.

#### Critères d'Acceptation

1. QUAND un administrateur accède à la gestion des contrats ALORS le système DOIT permettre de créer des modèles de contrats d'entrée et de sortie
2. QUAND un administrateur crée un modèle de contrat ALORS le système DOIT permettre de saisir le contenu légal et d'apposer sa signature électronique d'hôte
3. QUAND un administrateur signe un modèle ALORS le système DOIT enregistrer automatiquement la date et l'heure de signature
4. QUAND un modèle de contrat est finalisé ALORS le système DOIT permettre de l'activer pour utilisation dans les BM
5. QUAND un administrateur modifie un modèle ALORS le système DOIT créer une nouvelle version et désactiver l'ancienne

### Exigence 1bis

**User Story:** En tant qu'administrateur, je veux créer des comptes utilisateur avec le rôle "Ops", afin de déléguer la gestion opérationnelle des missions et des rapports.

#### Critères d'Acceptation

1. QUAND un administrateur accède à la gestion des utilisateurs ALORS le système DOIT permettre de créer un nouvel utilisateur avec le rôle "Ops"
2. QUAND un utilisateur Ops est créé ALORS le système DOIT lui attribuer automatiquement les permissions appropriées pour gérer les missions et les rapports
3. QUAND un utilisateur Ops se connecte ALORS le système DOIT lui afficher un tableau de bord adapté à ses responsabilités opérationnelles

### Exigence 2

**User Story:** En tant qu'utilisateur Ops, je veux créer et gérer des Bail Mobilité (BM) avec leurs dates de début et fin, afin d'organiser les séjours des locataires.

#### Critères d'Acceptation

1. QUAND un utilisateur Ops crée un nouveau BM ALORS le système DOIT permettre de saisir la date de début, date de fin, adresse, informations locataire et notes
2. QUAND un BM est créé ALORS le système DOIT automatiquement générer deux missions : une d'entrée (à la date de début) et une de sortie (à la date de fin)
3. QUAND un utilisateur Ops assigne un checker à l'entrée d'un BM ALORS le système DOIT notifier le checker et passer le BM au statut "Assigné"
4. QUAND un utilisateur Ops modifie la date de fin d'un BM ALORS le système DOIT automatiquement ajuster la mission de sortie et recalculer les notifications

### Exigence 3

**User Story:** En tant que checker, je veux effectuer l'entrée du locataire avec checklist et signature de contrat, afin de valider le début du séjour.

#### Critères d'Acceptation

1. QUAND un checker arrive pour une entrée BM ALORS le système DOIT permettre de remplir la checklist d'entrée avec photos obligatoires
2. QUAND un checker complète la checklist d'entrée ALORS le système DOIT permettre la signature électronique du contrat de bail mobilité par le locataire
3. QUAND le locataire signe électroniquement ALORS le système DOIT générer automatiquement le contrat de bail mobilité en PDF en utilisant le modèle de contrat d'entrée pré-signé par l'administrateur
4. QUAND le contrat est généré ALORS le système DOIT intégrer automatiquement la signature de l'administrateur (hôte) et la signature du locataire avec leurs dates respectives
6. QUAND toutes les validations sont complètes ALORS le système DOIT notifier l'utilisateur Ops pour validation
5. SI la checklist ou la signature du locataire manque ALORS le système DOIT empêcher la soumission et afficher les éléments manquants

### Exigence 4

**User Story:** En tant qu'utilisateur Ops, je veux valider les checklists d'entrée et faire passer les BM à l'état "En cours", afin de confirmer le début officiel du séjour.

#### Critères d'Acceptation

1. QUAND un checker soumet une checklist d'entrée complète ALORS le système DOIT notifier l'utilisateur Ops avec les détails et photos
2. QUAND un utilisateur Ops valide une checklist d'entrée ALORS le système DOIT automatiquement passer le BM au statut "En cours"
3. QUAND un BM passe en statut "En cours" ALORS le système DOIT programmer automatiquement une notification 10 jours avant la date de fin
4. SI un utilisateur Ops rejette une checklist d'entrée ALORS le système DOIT renvoyer le BM au checker avec les commentaires de correction

### Exigence 5

**User Story:** En tant qu'utilisateur Ops, je veux recevoir des notifications automatiques 10 jours avant la fin des BM et assigner les checkers pour les sorties, afin d'organiser les fins de séjour.

#### Critères d'Acceptation

1. QUAND un BM arrive à 10 jours de sa date de fin ALORS le système DOIT envoyer automatiquement une notification et un email à l'équipe Ops
2. QUAND un utilisateur Ops reçoit une notification de fin de séjour ALORS le système DOIT permettre d'assigner un checker pour la sortie
3. QUAND un utilisateur Ops assigne une sortie ALORS le système DOIT permettre de définir l'heure précise de la sortie
4. QUAND une sortie est assignée ALORS le système DOIT notifier le checker avec tous les détails du BM et l'heure de rendez-vous

### Exigence 6

**User Story:** En tant que checker, je veux effectuer la sortie du locataire avec le même processus que l'entrée (inspection, checklist, signature), afin de finaliser le séjour.

#### Critères d'Acceptation

1. QUAND un checker arrive pour une sortie BM ALORS le système DOIT permettre de remplir la checklist de sortie avec photos obligatoires (processus identique à l'entrée)
2. QUAND un checker complète la checklist de sortie ALORS le système DOIT permettre la signature électronique du rapport d'inspection et du contrat de fin de bail mobilité par le locataire
3. QUAND le locataire signe électroniquement ALORS le système DOIT générer automatiquement le document de fin de bail mobilité en PDF en utilisant le modèle de contrat de sortie pré-signé par l'administrateur
4. QUAND le document de sortie est généré ALORS le système DOIT intégrer automatiquement la signature de l'administrateur (hôte) et la signature du locataire avec leurs dates respectives
5. QUAND toutes les validations sont complètes ALORS le système DOIT permettre au checker de confirmer la récupération des clés du locataire
6. QUAND la checklist, la signature du locataire et la récupération des clés sont confirmées ALORS le système DOIT automatiquement notifier l'utilisateur Ops

### Exigence 7

**User Story:** En tant qu'utilisateur Ops, je veux valider les sorties et gérer les transitions d'état des BM, afin de finaliser ou traiter les incidents.

#### Critères d'Acceptation

1. QUAND un checker complète une sortie ALORS le système DOIT notifier l'utilisateur Ops sur son tableau de bord et son kanban
2. QUAND un utilisateur Ops valide une sortie complète ALORS le système DOIT automatiquement passer le BM au statut "Terminé"
3. SI une sortie présente des problèmes (pas de remise de clés, checklist non validée, pas de signature du locataire) ALORS le système DOIT automatiquement passer le BM au statut "Incident"
4. QUAND un BM passe en statut "Incident" ALORS le système DOIT alerter l'utilisateur Ops et permettre la création de tâches correctives
5. QUAND un BM est terminé avec succès ALORS le système DOIT archiver automatiquement tous les documents signés (contrats d'entrée et de sortie) pour référence future

### Exigence 8

**User Story:** En tant qu'utilisateur Ops, je veux accéder à un tableau de bord avec vue kanban des BM, afin de suivre visuellement l'état de tous les séjours.

#### Critères d'Acceptation

1. QUAND un utilisateur Ops accède à son tableau de bord ALORS le système DOIT afficher un kanban avec les colonnes : Assigné, En cours, Terminé, Incident
2. QUAND un BM change d'état ALORS le système DOIT automatiquement déplacer la carte dans la colonne appropriée du kanban
3. QUAND un utilisateur Ops clique sur une carte BM ALORS le système DOIT afficher tous les détails, checklists et historique des actions
4. QUAND un utilisateur Ops filtre le kanban ALORS le système DOIT permettre de filtrer par checker, date, ou statut d'incident

### Exigence 9

**User Story:** En tant qu'administrateur, je veux définir les permissions du rôle Ops, afin de contrôler l'accès aux fonctionnalités de gestion des BM.

#### Critères d'Acceptation

1. QUAND un administrateur configure le rôle Ops ALORS le système DOIT permettre l'accès à la création/modification des BM, assignation des checkers, et validation des checklists
2. QUAND un utilisateur Ops tente d'accéder aux fonctions administratives ALORS le système DOIT refuser l'accès et afficher un message d'erreur approprié
3. QUAND un administrateur crée un compte Ops ALORS le système DOIT automatiquement attribuer les permissions appropriées
4. SI un utilisateur Ops tente des actions non autorisées ALORS le système DOIT enregistrer la tentative et notifier l'administrateur
### Exigence 10

**User Story:** En tant qu'utilisateur Ops, je veux accéder aux contrats de bail mobilité signés et les gérer, afin d'assurer la traçabilité légale des séjours.

#### Critères d'Acceptation

1. QUAND un contrat de bail mobilité est généré avec signatures ALORS le système DOIT le stocker de manière sécurisée avec horodatage et référence au modèle utilisé
2. QUAND un utilisateur Ops consulte un BM ALORS le système DOIT afficher les contrats d'entrée et de sortie avec les deux signatures (administrateur/hôte, locataire) et leurs dates respectives
3. QUAND un utilisateur Ops a besoin d'un contrat ALORS le système DOIT permettre le téléchargement du PDF avec les deux signatures (admin/hôte et locataire) intégrées dans l'ordre chronologique
4. QUAND un BM est archivé ALORS le système DOIT conserver tous les documents signés et les modèles de contrats utilisés pour la durée légale requise
5. SI une signature électronique est contestée ALORS le système DOIT fournir les métadonnées de signature (date, heure, IP, device) pour validation légale ainsi que la traçabilité du modèle de contrat utilisé