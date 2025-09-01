# Documentation API - Gestion Ops et Bail Mobilité

## Vue d'ensemble

Cette documentation détaille tous les endpoints API pour la gestion des Bail Mobilité et du rôle Ops.

## Authentification et Permissions

Tous les endpoints nécessitent une authentification via session Laravel. Les permissions sont vérifiées selon le rôle :

- **Super Admin** : Accès complet
- **Ops** : Gestion des BM, assignation missions, validation checklists
- **Checker** : Exécution des missions assignées

## Endpoints Bail Mobilité

### GET /bail-mobilites
Liste tous les Bail Mobilité avec pagination et filtres.

**Permissions** : `view_bail_mobilites`

**Paramètres de requête** :
- `status` : Filtrer par statut (assigned, in_progress, completed, incident)
- `ops_user_id` : Filtrer par utilisateur Ops
- `date_from` : Date de début minimum
- `date_to` : Date de fin maximum
- `page` : Numéro de page (défaut: 1)
- `per_page` : Éléments par page (défaut: 15)

**Réponse** :
```json
{
  "data": [
    {
      "id": 1,
      "start_date": "2025-02-01",
      "end_date": "2025-02-28",
      "address": "123 Rue Example, Paris",
      "tenant_name": "Jean Dupont",
      "tenant_phone": "+33123456789",
      "tenant_email": "jean.dupont@example.com",
      "status": "in_progress",
      "ops_user": {
        "id": 2,
        "name": "Marie Ops"
      },
      "entry_mission": {
        "id": 10,
        "status": "completed",
        "assigned_agent": {
          "id": 5,
          "name": "Pierre Checker"
        }
      },
      "exit_mission": {
        "id": 11,
        "status": "assigned",
        "assigned_agent": null
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 25,
    "per_page": 15
  }
}
```

### POST /bail-mobilites
Crée un nouveau Bail Mobilité.

**Permissions** : `create_bail_mobilites`

**Corps de la requête** :
```json
{
  "start_date": "2025-02-01",
  "end_date": "2025-02-28",
  "address": "123 Rue Example, Paris",
  "tenant_name": "Jean Dupont",
  "tenant_phone": "+33123456789",
  "tenant_email": "jean.dupont@example.com",
  "notes": "Locataire préfère les rendez-vous le matin"
}
```

**Réponse** : Objet BailMobilite créé avec missions générées automatiquement.

### GET /bail-mobilites/{id}
Récupère les détails d'un Bail Mobilité.

**Permissions** : `view_bail_mobilites`

**Réponse** : Objet BailMobilite complet avec relations.

### PUT /bail-mobilites/{id}
Met à jour un Bail Mobilité.

**Permissions** : `edit_bail_mobilites`

**Corps de la requête** : Champs modifiables du BailMobilite.

### POST /bail-mobilites/{id}/assign-entry
Assigne un checker à la mission d'entrée.

**Permissions** : `assign_missions`

**Corps de la requête** :
```json
{
  "agent_id": 5,
  "scheduled_time": "10:00"
}
```

### POST /bail-mobilites/{id}/assign-exit
Assigne un checker à la mission de sortie.

**Permissions** : `assign_missions`

**Corps de la requête** :
```json
{
  "agent_id": 5,
  "scheduled_time": "14:00"
}
```

### POST /bail-mobilites/{id}/validate-entry
Valide la checklist d'entrée et passe le BM en statut "in_progress".

**Permissions** : `validate_checklists`

**Corps de la requête** :
```json
{
  "approved": true,
  "comments": "Entrée validée, tout est conforme"
}
```

### POST /bail-mobilites/{id}/validate-exit
Valide la checklist de sortie et finalise le BM.

**Permissions** : `validate_checklists`

**Corps de la requête** :
```json
{
  "approved": true,
  "keys_returned": true,
  "comments": "Sortie validée, clés récupérées"
}
```

## Endpoints Modèles de Contrats

### GET /contract-templates
Liste tous les modèles de contrats.

**Permissions** : `view_contract_templates`

**Paramètres de requête** :
- `type` : Filtrer par type (entry, exit)
- `active` : Filtrer par statut actif (true, false)

### POST /contract-templates
Crée un nouveau modèle de contrat.

**Permissions** : `create_contract_templates`

**Corps de la requête** :
```json
{
  "name": "Contrat d'entrée standard",
  "type": "entry",
  "content": "Contenu légal du contrat..."
}
```

### POST /contract-templates/{id}/sign
Signe un modèle de contrat par l'administrateur.

**Permissions** : `sign_contract_templates`

**Corps de la requête** :
```json
{
  "signature": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
}
```

## Endpoints Signatures

### POST /signatures/tenant-sign
Signature électronique par le locataire.

**Permissions** : Accessible aux checkers pendant une mission

**Corps de la requête** :
```json
{
  "bail_mobilite_id": 1,
  "signature_type": "entry",
  "signature": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
  "metadata": {
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "device_info": "iPhone 12"
  }
}
```

### GET /signatures/{id}/contract-pdf
Télécharge le contrat PDF signé.

**Permissions** : `view_signed_contracts`

**Réponse** : Fichier PDF avec signatures intégrées.

## Endpoints Ops Dashboard

### GET /ops/dashboard
Données du tableau de bord Ops.

**Permissions** : `view_ops_dashboard`

**Réponse** :
```json
{
  "metrics": {
    "total_bm": 45,
    "assigned": 12,
    "in_progress": 28,
    "completed": 3,
    "incidents": 2
  },
  "recent_notifications": [...],
  "pending_validations": [...],
  "upcoming_exits": [...]
}
```

### GET /ops/kanban
Données pour la vue kanban des BM.

**Permissions** : `view_ops_dashboard`

**Réponse** :
```json
{
  "assigned": [...],
  "in_progress": [...],
  "completed": [...],
  "incident": [...]
}
```

## Endpoints Notifications

### GET /notifications
Liste des notifications pour l'utilisateur connecté.

**Permissions** : Authentifié

**Paramètres de requête** :
- `type` : Filtrer par type de notification
- `read` : Filtrer par statut lu/non lu
- `limit` : Nombre maximum de notifications

### POST /notifications/{id}/mark-read
Marque une notification comme lue.

**Permissions** : Propriétaire de la notification

## Endpoints Incidents

### GET /incidents
Liste tous les incidents.

**Permissions** : `view_incidents`

### GET /incidents/{id}
Détails d'un incident.

**Permissions** : `view_incidents`

### POST /incidents/{id}/corrective-actions
Crée une action corrective pour un incident.

**Permissions** : `manage_incidents`

**Corps de la requête** :
```json
{
  "description": "Contacter le locataire pour récupérer les clés",
  "assigned_to": 2,
  "due_date": "2025-01-15"
}
```

## Codes de Réponse HTTP

- **200 OK** : Requête réussie
- **201 Created** : Ressource créée avec succès
- **400 Bad Request** : Données de requête invalides
- **401 Unauthorized** : Authentification requise
- **403 Forbidden** : Permissions insuffisantes
- **404 Not Found** : Ressource non trouvée
- **422 Unprocessable Entity** : Erreurs de validation
- **500 Internal Server Error** : Erreur serveur

## Gestion des Erreurs

Toutes les réponses d'erreur suivent le format :

```json
{
  "message": "Description de l'erreur",
  "errors": {
    "field_name": ["Message d'erreur spécifique"]
  }
}
```

## Rate Limiting

Les endpoints API sont soumis à une limitation de débit :
- **60 requêtes par minute** pour les utilisateurs authentifiés
- **10 requêtes par minute** pour les requêtes non authentifiées

## Webhooks (Futur)

Des webhooks pourront être configurés pour recevoir des notifications en temps réel :
- Création/modification de BM
- Changements de statut
- Incidents détectés
- Validations effectuées