# Guide Utilisateur Ops - Gestion des Bail Mobilité

## Vue d'ensemble

En tant qu'utilisateur Ops, vous êtes responsable de la gestion opérationnelle des Bail Mobilité (BM). Ce guide vous accompagne dans toutes vos tâches quotidiennes, de la création des BM jusqu'à leur finalisation.

## Accès au Système

### Connexion
1. Accédez à l'URL de l'application
2. Connectez-vous avec vos identifiants Ops
3. Vous arrivez sur votre tableau de bord opérationnel

### Interface Ops
Votre interface comprend :
- **Tableau de bord** : Vue kanban des BM et métriques
- **Bail Mobilité** : Gestion complète des BM
- **Missions** : Suivi des assignations et validations
- **Notifications** : Alertes et rappels automatiques
- **Incidents** : Gestion des problèmes et actions correctives

## Tableau de Bord Ops

### Vue Kanban
Le tableau de bord principal affiche vos BM organisés en colonnes :

#### 📋 Assigné
- BM créés en attente d'assignation du checker d'entrée
- **Actions** : Assigner checker, modifier dates, voir détails

#### 🏃 En cours
- BM avec entrée validée, locataire en séjour
- **Actions** : Préparer sortie, modifier date fin, voir contrats

#### ✅ Terminé
- BM finalisés avec succès, clés récupérées
- **Actions** : Consulter historique, télécharger contrats

#### ⚠️ Incident
- BM avec problèmes nécessitant intervention
- **Actions** : Gérer incident, créer actions correctives

### Métriques Rapides
- **Total BM** : Nombre total de BM gérés
- **Taux de réussite** : Pourcentage de BM terminés sans incident
- **Délai moyen** : Temps moyen de traitement
- **Incidents actifs** : Nombre d'incidents en cours

## Gestion des Bail Mobilité

### Créer un Nouveau BM

1. **Navigation** : Cliquer sur "Nouveau BM" depuis le tableau de bord
2. **Informations du séjour** :
   - **Date de début** : Date d'entrée du locataire
   - **Date de fin** : Date de sortie prévue
   - **Adresse** : Adresse complète du logement
3. **Informations locataire** :
   - **Nom complet** : Nom et prénom du locataire
   - **Téléphone** : Numéro de contact
   - **Email** : Adresse email (optionnel)
4. **Notes** : Informations complémentaires (préférences horaires, instructions spéciales)
5. **Validation** : Cliquer sur "Créer le BM"

**Résultat** : Le système génère automatiquement :
- Une mission d'entrée à la date de début
- Une mission de sortie à la date de fin
- Le BM passe en statut "Assigné"

### Modifier un BM

1. **Sélection** : Cliquer sur la carte BM dans le kanban
2. **Modification** : Cliquer sur "Modifier"
3. **Changements possibles** :
   - Dates (recalcul automatique des missions)
   - Informations locataire
   - Notes et instructions
4. **Sauvegarde** : Les modifications sont appliquées immédiatement

⚠️ **Attention** : Modifier la date de fin recalcule automatiquement la notification de rappel (10 jours avant).

## Assignation des Checkers

### Assigner l'Entrée

1. **Depuis le kanban** : Cliquer sur "Assigner entrée" sur la carte BM
2. **Sélection du checker** :
   - Liste des checkers disponibles
   - Indication de leur charge de travail
   - Historique de performance
3. **Horaire** : Définir l'heure de rendez-vous (ex: 10:00)
4. **Confirmation** : Le checker reçoit une notification automatique

### Assigner la Sortie

L'assignation de sortie se fait généralement après réception de la notification de rappel (10 jours avant fin).

1. **Notification reçue** : "Sortie à programmer pour [Nom locataire]"
2. **Clic sur notification** : Accès direct au BM
3. **Assigner sortie** : Même processus que l'entrée
4. **Horaire précis** : Important pour la récupération des clés

### Bonnes Pratiques d'Assignation

- **Anticipation** : Assigner les sorties dès réception de la notification
- **Disponibilité** : Vérifier la charge de travail des checkers
- **Géolocalisation** : Privilégier les checkers proches du logement
- **Historique** : Considérer les performances passées

## Validation des Checklists

### Validation d'Entrée

Quand un checker complète une entrée, vous recevez une notification de validation.

1. **Notification** : "Checklist d'entrée à valider - [Nom locataire]"
2. **Accès aux détails** :
   - Checklist complète avec photos
   - Signature du locataire
   - Contrat généré automatiquement
   - Commentaires du checker
3. **Validation** :
   - **Approuver** : Le BM passe en statut "En cours"
   - **Rejeter** : Retour au checker avec commentaires
4. **Programmation automatique** : Notification de sortie programmée (10 jours avant fin)

### Validation de Sortie

Processus similaire à l'entrée avec vérifications supplémentaires :

1. **Éléments à vérifier** :
   - Checklist de sortie complète
   - Photos de l'état final
   - Signature du rapport par le locataire
   - **Confirmation de récupération des clés** ⚠️
2. **Décision** :
   - **Tout conforme** : BM passe en "Terminé"
   - **Problème détecté** : BM passe en "Incident"

### Critères de Validation

#### ✅ Validation Positive
- Checklist complète avec toutes les photos requises
- Signature électronique du locataire présente
- Aucun dégât ou problème signalé
- Clés récupérées (pour les sorties)

#### ❌ Rejet/Incident
- Photos manquantes ou de mauvaise qualité
- Signature du locataire absente
- Dégâts importants non documentés
- Clés non récupérées (sortie)
- Problème de sécurité

## Gestion des Notifications

### Types de Notifications

#### 🔔 Rappel de Sortie (10 jours avant)
- **Contenu** : "Sortie à programmer pour [Nom] - Fin le [Date]"
- **Action** : Assigner un checker pour la sortie
- **Urgence** : Normale

#### ⚠️ Checklist à Valider
- **Contenu** : "Checklist [entrée/sortie] soumise par [Checker]"
- **Action** : Valider ou rejeter la checklist
- **Urgence** : Haute (traitement dans les 2h)

#### 🚨 Incident Détecté
- **Contenu** : "Incident sur BM [Référence] - [Type de problème]"
- **Action** : Gérer l'incident immédiatement
- **Urgence** : Critique

### Gestion des Notifications

1. **Centre de notifications** : Icône cloche en haut à droite
2. **Tri par urgence** : Critiques en premier
3. **Actions rapides** : Clic direct vers l'action requise
4. **Historique** : Consultation des notifications passées

## Gestion des Incidents

### Types d'Incidents Courants

#### 🔑 Clés Non Récupérées
- **Cause** : Locataire absent au rendez-vous de sortie
- **Actions** : Reprogrammer rendez-vous, contacter locataire
- **Escalade** : Si pas de réponse sous 48h

#### 📋 Checklist Incomplète
- **Cause** : Checker n'a pas pu accéder à certaines zones
- **Actions** : Nouvelle visite, contact propriétaire
- **Résolution** : Checklist complétée

#### 🏠 Dégâts Importants
- **Cause** : Dommages découverts lors de la sortie
- **Actions** : Photos détaillées, devis réparations
- **Suivi** : Négociation avec locataire/assurance

### Processus de Gestion d'Incident

1. **Détection** : Automatique ou signalement checker
2. **Évaluation** : Gravité et urgence
3. **Actions correctives** :
   - Créer des tâches spécifiques
   - Assigner des responsables
   - Définir des échéances
4. **Suivi** : Monitoring jusqu'à résolution
5. **Clôture** : Validation de la résolution

### Créer une Action Corrective

1. **Depuis l'incident** : Cliquer sur "Nouvelle action"
2. **Description** : Détailler l'action à effectuer
3. **Assignation** : Choisir le responsable
4. **Échéance** : Date limite de réalisation
5. **Priorité** : Normale, Haute, Critique
6. **Suivi** : Notifications automatiques de rappel

## Consultation des Contrats

### Accès aux Contrats Signés

1. **Depuis le BM** : Cliquer sur "Voir contrats"
2. **Types disponibles** :
   - **Contrat d'entrée** : Signé lors de l'entrée
   - **Rapport de sortie** : Signé lors de la sortie
3. **Informations affichées** :
   - Signatures (admin + locataire) avec dates
   - Métadonnées de signature (IP, device, timestamp)
   - Référence au modèle utilisé

### Téléchargement des PDF

1. **Sélection** : Choisir le contrat à télécharger
2. **Format** : PDF avec signatures intégrées
3. **Utilisation** : Archivage, envoi client, juridique

## Rapports et Métriques

### Rapports Disponibles

#### 📊 Performance Mensuelle
- Nombre de BM traités
- Taux de réussite
- Délais moyens
- Incidents par type

#### 👥 Performance Checkers
- Nombre de missions par checker
- Taux de validation première fois
- Délais de traitement
- Satisfaction client

### Export des Données

1. **Filtres** : Période, statut, checker
2. **Format** : Excel, PDF, CSV
3. **Contenu** : Données détaillées ou synthèse
4. **Utilisation** : Analyse, reporting, facturation

## Bonnes Pratiques

### Organisation Quotidienne

#### 🌅 Début de Journée
1. Consulter les notifications urgentes
2. Vérifier les validations en attente
3. Planifier les assignations du jour
4. Contrôler les incidents actifs

#### 🌆 Fin de Journée
1. Valider les checklists reçues
2. Programmer les sorties à venir
3. Suivre l'avancement des actions correctives
4. Préparer le planning du lendemain

### Communication

#### Avec les Checkers
- **Instructions claires** lors des assignations
- **Feedback constructif** sur les validations
- **Disponibilité** pour questions urgentes
- **Reconnaissance** du bon travail

#### Avec les Locataires (si nécessaire)
- **Professionnalisme** en toutes circonstances
- **Clarté** des explications
- **Empathie** face aux problèmes
- **Solutions** orientées résolution

### Gestion du Temps

- **Priorisation** : Incidents > Validations > Assignations
- **Batch processing** : Traiter les validations par groupes
- **Anticipation** : Programmer les sorties dès les notifications
- **Délégation** : Utiliser les actions correctives pour les incidents

## Résolution des Problèmes Courants

### Problème : Checker ne répond pas à l'assignation

**Solutions** :
1. Vérifier ses disponibilités dans le système
2. Le contacter directement par téléphone
3. Réassigner à un autre checker disponible
4. Signaler le problème à l'administrateur

### Problème : Locataire refuse de signer

**Solutions** :
1. Expliquer l'importance légale de la signature
2. Proposer de relire le contrat ensemble
3. Contacter l'administrateur si refus persistant
4. Documenter le refus dans les commentaires

### Problème : Photos de mauvaise qualité

**Solutions** :
1. Demander au checker de reprendre les photos
2. Fournir des conseils techniques (éclairage, angle)
3. Valider temporairement avec commentaire d'amélioration
4. Former le checker si problème récurrent

## Contact et Support

### Support Ops
- **Email** : ops-support@example.com
- **Téléphone** : +33 1 23 45 67 90
- **Chat** : Disponible dans l'application
- **Horaires** : 8h-20h, 7j/7

### Escalade
- **Incidents critiques** : Notification immédiate admin
- **Problèmes techniques** : Support technique
- **Conflits locataires** : Manager opérationnel

### Formation Continue
- **Nouvelles fonctionnalités** : Formation trimestrielle
- **Bonnes pratiques** : Partage d'expérience mensuel
- **Outils** : Mise à jour des guides utilisateur