# Document de Conception - Gestion du Rôle Ops et Bail Mobilité

## Vue d'ensemble

Cette conception étend le système existant de conciergerie Airbnb pour introduire le rôle "Ops" et la gestion complète des "Bail Mobilité" (BM). Le système actuel gère déjà les missions, checklists et agents avec les rôles super-admin et checker. Nous ajoutons une couche intermédiaire avec le rôle Ops qui gère le cycle de vie complet des BM.

## Architecture

### Architecture Existante
- **Laravel/Inertia.js** avec Vue.js pour l'interface
- **Spatie Laravel Permission** pour la gestion des rôles
- **Base de données relationnelle** avec migrations Laravel
- **Modèles principaux** : User, Agent, Mission, Checklist
- **Contrôleurs** : MissionController, ChecklistController, DashboardController

### Extensions Architecturales

#### Nouveau Modèle : BailMobilite
```php
// Représente un séjour complet avec entrée et sortie
BailMobilite {
    id, start_date, end_date, address, tenant_info,
    status (assigned, in_progress, completed, incident),
    ops_user_id, entry_mission_id, exit_mission_id
}
```

#### Extension du Modèle Mission
```php
// Ajout de la relation avec BailMobilite
Mission {
    // Champs existants...
    bail_mobilite_id, // Nouvelle relation
    mission_type (entry, exit), // Spécialisation du type
    ops_assigned_by, // Qui a assigné la mission
    scheduled_time // Heure précise pour les sorties
}
```

#### Nouveau Modèle : Notification
```php
// Système de notifications automatiques
Notification {
    id, type, recipient_id, bail_mobilite_id,
    scheduled_at, sent_at, status, data
}
```

## Composants et Interfaces

### 1. Gestion des Rôles et Permissions

#### Nouveau Rôle : "ops"
```php
// Permissions spécifiques au rôle Ops
$opsPermissions = [
    'create_bail_mobilite',
    'edit_bail_mobilite', 
    'assign_missions',
    'validate_checklists',
    'view_ops_dashboard',
    'manage_incidents'
];
```

#### Middleware de Contrôle d'Accès
```php
// Extension du middleware CheckRole existant
class CheckOpsAccess extends CheckRole {
    // Vérifications spécifiques aux Ops
    // Restriction d'accès aux fonctions admin
}
```

### 2. Contrôleurs

#### BailMobiliteController
```php
class BailMobiliteController extends Controller {
    // CRUD des Bail Mobilité
    public function index() // Liste avec kanban
    public function create() // Création avec dates
    public function store() // Sauvegarde + génération missions
    public function show() // Détails complets
    public function update() // Modification dates
    public function assignEntry() // Assignation entrée
    public function assignExit() // Assignation sortie
    public function validateEntry() // Validation entrée
    public function validateExit() // Validation sortie
    public function handleIncident() // Gestion incidents
}
```

#### ContractTemplateController
```php
class ContractTemplateController extends Controller {
    public function index() // Liste des modèles
    public function create() // Création nouveau modèle
    public function store() // Sauvegarde modèle
    public function show() // Affichage modèle
    public function edit() // Édition modèle
    public function update() // Mise à jour modèle
    public function signTemplate() // Signature admin du modèle
    public function activate() // Activation/désactivation
    public function preview() // Prévisualisation contrat
}
```

#### Extension MissionController
```php
// Nouvelles méthodes pour les Ops
public function assignToChecker() // Assignation par Ops
public function validateChecklist() // Validation par Ops
public function getOpsAssignedMissions() // Missions assignées par cet Ops
```

#### OpsController
```php
class OpsController extends Controller {
    public function dashboard() // Tableau de bord Ops
    public function kanban() // Vue kanban des BM
    public function notifications() // Notifications 10 jours
    public function metrics() // Métriques opérationnelles
}
```

### 3. Interfaces Utilisateur

#### Tableau de Bord Ops
- **Vue Kanban** : Colonnes Assigné, En cours, Terminé, Incident
- **Notifications** : Alertes 10 jours avant fin de séjour
- **Métriques** : Performance checkers, incidents, délais
- **Actions rapides** : Assignation, validation, gestion incidents

#### Formulaires de Gestion BM
- **Création BM** : Dates, adresse, infos locataire, notes
- **Assignation** : Sélection checker avec disponibilité
- **Validation** : Révision checklist avec photos
- **Gestion incidents** : Création tâches correctives

### 4. Système de Notifications

#### NotificationService
```php
class NotificationService {
    public function scheduleExitReminder() // Programme notification 10 jours
    public function sendOpsAlert() // Alerte incident/validation
    public function notifyChecker() // Notification assignation
    public function cancelScheduledNotifications() // Annulation si modification
}
```

#### Types de Notifications
- **EXIT_REMINDER** : 10 jours avant fin de séjour
- **CHECKLIST_VALIDATION** : Checklist à valider
- **INCIDENT_ALERT** : Incident détecté
- **MISSION_ASSIGNED** : Mission assignée à checker

## Modèles de Données

### Schéma Base de Données

#### Table : bail_mobilites
```sql
CREATE TABLE bail_mobilites (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    address VARCHAR(255) NOT NULL,
    tenant_name VARCHAR(255) NOT NULL,
    tenant_phone VARCHAR(20),
    tenant_email VARCHAR(255),
    notes TEXT,
    status ENUM('assigned', 'in_progress', 'completed', 'incident') DEFAULT 'assigned',
    ops_user_id BIGINT,
    entry_mission_id BIGINT,
    exit_mission_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (ops_user_id) REFERENCES users(id),
    FOREIGN KEY (entry_mission_id) REFERENCES missions(id),
    FOREIGN KEY (exit_mission_id) REFERENCES missions(id)
);
```

#### Extension Table : missions
```sql
ALTER TABLE missions ADD COLUMN bail_mobilite_id BIGINT;
ALTER TABLE missions ADD COLUMN mission_type ENUM('entry', 'exit');
ALTER TABLE missions ADD COLUMN ops_assigned_by BIGINT;
ALTER TABLE missions ADD COLUMN scheduled_time TIME;
ALTER TABLE missions ADD FOREIGN KEY (bail_mobilite_id) REFERENCES bail_mobilites(id);
ALTER TABLE missions ADD FOREIGN KEY (ops_assigned_by) REFERENCES users(id);
```

#### Table : contract_templates
```sql
CREATE TABLE contract_templates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type ENUM('entry', 'exit') NOT NULL,
    content TEXT NOT NULL, -- Contenu légal du contrat
    admin_signature TEXT, -- Signature de l'admin/hôte
    admin_signed_at TIMESTAMP,
    is_active BOOLEAN DEFAULT true,
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

#### Table : bail_mobilite_signatures
```sql
CREATE TABLE bail_mobilite_signatures (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    bail_mobilite_id BIGINT NOT NULL,
    signature_type ENUM('entry', 'exit') NOT NULL,
    contract_template_id BIGINT NOT NULL, -- Référence au modèle utilisé
    tenant_signature TEXT, -- Signature électronique du locataire
    tenant_signed_at TIMESTAMP,
    contract_pdf_path VARCHAR(255), -- Chemin vers le contrat généré
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (bail_mobilite_id) REFERENCES bail_mobilites(id) ON DELETE CASCADE,
    FOREIGN KEY (contract_template_id) REFERENCES contract_templates(id)
);
```

#### Table : notifications
```sql
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    recipient_id BIGINT NOT NULL,
    bail_mobilite_id BIGINT,
    scheduled_at TIMESTAMP,
    sent_at TIMESTAMP,
    status ENUM('pending', 'sent', 'cancelled') DEFAULT 'pending',
    data JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    FOREIGN KEY (bail_mobilite_id) REFERENCES bail_mobilites(id)
);
```

### Relations Eloquent

#### BailMobilite Model
```php
class BailMobilite extends Model {
    public function opsUser() { return $this->belongsTo(User::class, 'ops_user_id'); }
    public function entryMission() { return $this->belongsTo(Mission::class, 'entry_mission_id'); }
    public function exitMission() { return $this->belongsTo(Mission::class, 'exit_mission_id'); }
    public function notifications() { return $this->hasMany(Notification::class); }
    public function signatures() { return $this->hasMany(BailMobiliteSignature::class); }
    
    // Relations pour les signatures spécifiques
    public function entrySignature() { 
        return $this->hasOne(BailMobiliteSignature::class)->where('signature_type', 'entry'); 
    }
    public function exitSignature() { 
        return $this->hasOne(BailMobiliteSignature::class)->where('signature_type', 'exit'); 
    }
    
    // Scopes pour les statuts
    public function scopeAssigned($query) { return $query->where('status', 'assigned'); }
    public function scopeInProgress($query) { return $query->where('status', 'in_progress'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopeIncident($query) { return $query->where('status', 'incident'); }
}
```

#### ContractTemplate Model
```php
class ContractTemplate extends Model {
    protected $fillable = [
        'name', 'type', 'content', 'admin_signature', 
        'admin_signed_at', 'is_active', 'created_by'
    ];
    
    protected $casts = [
        'admin_signed_at' => 'datetime',
        'is_active' => 'boolean'
    ];
    
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function signatures() { return $this->hasMany(BailMobiliteSignature::class); }
    
    // Scopes
    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeEntry($query) { return $query->where('type', 'entry'); }
    public function scopeExit($query) { return $query->where('type', 'exit'); }
}
```

#### BailMobiliteSignature Model
```php
class BailMobiliteSignature extends Model {
    protected $fillable = [
        'bail_mobilite_id', 'signature_type', 'contract_template_id',
        'tenant_signature', 'tenant_signed_at', 'contract_pdf_path'
    ];
    
    protected $casts = [
        'tenant_signed_at' => 'datetime'
    ];
    
    public function bailMobilite() { return $this->belongsTo(BailMobilite::class); }
    public function contractTemplate() { return $this->belongsTo(ContractTemplate::class); }
    
    // Vérification si toutes les signatures sont complètes
    public function isComplete() {
        return !empty($this->tenant_signature) && 
               !empty($this->contractTemplate->admin_signature);
    }
}
```

## Gestion des Photos

### Système de Photos Existant
Le système utilise déjà les modèles `ChecklistItem` et `ChecklistPhoto` pour gérer les photos des checklists :

```php
// Relations existantes
ChecklistItem -> hasMany(ChecklistPhoto)
ChecklistPhoto -> belongsTo(ChecklistItem)
```

### Intégration avec les BM
- **Photos obligatoires** : Certains éléments de checklist nécessitent des photos
- **Validation** : Le système vérifie la présence des photos requises avant soumission
- **Stockage** : Photos stockées via le système de fichiers Laravel existant
- **Affichage** : Les Ops peuvent consulter toutes les photos lors de la validation

## Gestion des Erreurs

### Validation des Données
- **Dates BM** : Date fin > date début, dates futures
- **Assignations** : Checker disponible, pas de conflit horaire
- **Checklists** : Photos obligatoires, signatures requises
- **Photos** : Format valide, taille limite, photos obligatoires présentes
- **Permissions** : Vérification rôle pour chaque action

### Gestion des Incidents
- **Détection automatique** : Checklist incomplète, pas de signature, clés non remises
- **Escalade** : Notification Ops, création tâches correctives
- **Suivi** : Historique des actions, résolution documentée

### Cas d'Erreur
- **Checker indisponible** : Proposition alternatives, réassignation
- **Modification dates** : Recalcul notifications, mise à jour missions
- **Panne système** : Sauvegarde état, récupération données

## Stratégie de Test

### Tests Unitaires
- **Modèles** : Relations, scopes, validations
- **Services** : Logique métier, calculs dates
- **Notifications** : Programmation, envoi, annulation

### Tests d'Intégration
- **Workflow complet** : Création BM → Entrée → Validation → Sortie
- **Permissions** : Accès par rôle, restrictions
- **API** : Endpoints, réponses, codes erreur

### Tests Fonctionnels
- **Interface utilisateur** : Navigation, formulaires, kanban
- **Notifications** : Réception, affichage, actions
- **Rapports** : Génération PDF, export données

### Scénarios de Test
1. **Création BM par Ops** → Génération missions automatique
2. **Assignation entrée** → Notification checker
3. **Validation entrée** → Passage "En cours" + programmation notification
4. **Notification 10 jours** → Assignation sortie
5. **Validation sortie** → Passage "Terminé"
6. **Gestion incident** → Passage "Incident" + alertes

## Considérations de Performance

### Optimisations Base de Données
- **Index** sur dates, statuts, relations fréquentes
- **Eager loading** pour éviter N+1 queries
- **Pagination** pour listes importantes

### Cache et Sessions
- **Cache** des métriques dashboard
- **Sessions** pour états temporaires formulaires
- **Queue** pour notifications asynchrones

### Monitoring
- **Logs** des actions critiques
- **Métriques** temps de réponse, erreurs
- **Alertes** sur incidents système