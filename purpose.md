# Système de Gestion des Baux Mobilité

## Objectif de l'Application

Cette PWA Laravel + Vue.js est conçue pour gérer les "Baux Mobilité" - un type spécifique d'arrangement locatif de courte durée en France. Le système gère le cycle de vie complet des inspections immobilières et de la gestion locative pour les locations mobilité.

## Fonctionnalités Principales

### Gestion des Missions

-   Planification et suivi des missions d'inspection immobilière (états des lieux d'entrée/sortie)
-   Affectation d'agents de terrain (contrôleurs) à des propriétés spécifiques
-   Suivi et mises à jour du statut en temps réel
-   Intégration calendrier avec détection de conflits

### Système de Check-lists

-   Rapports d'état des lieux numériques avec formulaires standardisés
-   Documentation photographique des conditions de propriété
-   Signatures électroniques des locataires et agents
-   Génération automatique de PDF des rapports d'inspection

### Gestion des Rôles Utilisateurs

Différents tableaux de bord et permissions pour :

-   **Super Administrateurs** : Supervision complète du système et gestion des missions
-   **Personnel Ops** : Gestion du cycle de vie des baux mobilité et coordination des contrôleurs
-   **Contrôleurs (Agents de Terrain)** : Inspections immobilières et completion des check-lists
-   **Administrateurs** : Analyses, modèles de contrats et administration système

### Cycle de Vie des Baux Mobilité

-   Gestion complète de la création du bail à sa finalisation
-   Planification des missions d'entrée et de sortie
-   Suivi des statuts (assigné → en_cours → terminé)
-   Détection et gestion des incidents
-   Notifications et rappels automatisés

### Signatures Numériques et Contrats

-   Gestion des modèles de contrats avec versioning
-   Workflows de signature électronique
-   Signature numérique de contrats avec validation
-   Archivage et récupération des signatures

### Gestion des Incidents

-   Détection automatique d'incidents lors des inspections
-   Suivi des actions correctives
-   Gestion des statuts et workflows de résolution
-   Statistiques et rapports d'incidents

## Fonctionnalités Clés

-   **Système d'authentification multi-rôles** avec permissions basées sur les rôles
-   **Workflows d'inspection immobilière** avec check-lists complètes
-   **Gestion de contrats** avec modèles numériques et signatures
-   **Système de notifications** pour alertes et rappels automatisés
-   **Génération PDF** pour rapports et contrats
-   **Intégration Google OAuth** pour connexion sociale
-   **Capacités PWA** pour fonctionnalité d'application mobile
-   **Mises à jour temps réel** avec synchronisation calendrier
-   **Tableau de bord analytique** avec métriques de performance
-   **Suivi d'incidents** avec gestion d'actions correctives

## Stack Technologique

-   **Backend** : Laravel 12 avec Inertia.js
-   **Frontend** : Vue.js 3 avec Tailwind CSS et Alpine.js
-   **Authentification** : Laravel Breeze + Socialite (Google OAuth)
-   **Permissions** : Package Spatie Laravel Permission
-   **Génération PDF** : DomPDF (barryvdh/laravel-dompdf)
-   **Graphiques** : Chart.js pour les analyses
-   **Signatures** : Vue Signature Pad pour signatures électroniques
-   **Routage** : Ziggy pour intégration des routes Laravel dans Vue
-   **Gestion des Dates** : date-fns pour opérations de dates JavaScript

## Marché Cible

Cette application est spécifiquement adaptée aux réglementations françaises du marché locatif "bail mobilité", fournissant une solution complète de gestion de workflow numérique pour :

-   Sociétés de gestion immobilière
-   Agences immobilières
-   Propriétaires gérant des locations mobilité de courte durée
-   Services d'inspection de terrain

Le système assure la conformité avec les réglementations locatives françaises tout en rationalisant le processus d'inspection et de documentation grâce à la transformation numérique.
