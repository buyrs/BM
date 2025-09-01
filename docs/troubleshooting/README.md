# Guide de Dépannage et Maintenance

## Vue d'ensemble

Ce guide fournit les procédures de résolution des problèmes courants et les tâches de maintenance pour le système de gestion Ops et Bail Mobilité.

## Problèmes Courants et Solutions

### 1. Problèmes d'Authentification et Permissions

#### Utilisateur Ops ne peut pas accéder au système

**Symptômes** :
- Message "Accès refusé" lors de la connexion
- Redirection vers la page d'accueil après connexion
- Fonctionnalités Ops non visibles

**Diagnostic** :
```bash
# Vérifier les rôles de l'utilisateur
php artisan tinker
>>> $user = User::where('email', 'ops@example.com')->first();
>>> $user->roles->pluck('name');
>>> $user->permissions->pluck('name');
```

**Solutions** :
1. **Vérifier l'attribution du rôle** :
```bash
php artisan tinker
>>> $user = User::find(ID_UTILISATEUR);
>>> $user->assignRole('ops');
```

2. **Synchroniser les permissions** :
```bash
php artisan permission:cache-reset
php artisan config:clear
```

3. **Vérifier la configuration des permissions** :
```php
// Dans PermissionSeeder.php
$opsPermissions = [
    'create_bail_mobilites',
    'edit_bail_mobilites',
    'view_bail_mobilites',
    'assign_missions',
    'validate_checklists',
    'view_ops_dashboard',
    'manage_incidents'
];
```

#### Checker ne reçoit pas les notifications d'assignation

**Diagnostic** :
```bash
# Vérifier les queues
php artisan queue:work --once
php artisan queue:failed
```

**Solutions** :
1. **Redémarrer les workers de queue** :
```bash
php artisan queue:restart
php artisan queue:work
```

2. **Vérifier la configuration email** :
```bash
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

### 2. Problèmes de Bail Mobilité

#### BM ne passe pas au statut "En cours" après validation

**Symptômes** :
- Validation effectuée mais statut reste "Assigné"
- Notification de sortie non programmée

**Diagnostic** :
```bash
# Vérifier les logs
tail -f storage/logs/laravel.log

# Vérifier en base
php artisan tinker
>>> $bm = BailMobilite::find(ID_BM);
>>> $bm->status;
>>> $bm->entryMission->status;
```

**Solutions** :
1. **Forcer la transition d'état** :
```php
$bailMobilite = BailMobilite::find(ID_BM);
$bailMobilite->update(['status' => 'in_progress']);

// Programmer la notification de sortie
$notificationService = app(NotificationService::class);
$notificationService->scheduleExitReminder($bailMobilite);
```

2. **Vérifier les validations** :
```php
// S'assurer que la checklist est complète
$mission = $bailMobilite->entryMission;
$checklist = $mission->checklist;
if (!$checklist || !$checklist->isComplete()) {
    // Problème avec la checklist
}
```

#### Missions d'entrée/sortie non générées automatiquement

**Diagnostic** :
```php
$bailMobilite = BailMobilite::find(ID_BM);
dd($bailMobilite->entryMission, $bailMobilite->exitMission);
```

**Solutions** :
1. **Générer manuellement les missions** :
```php
// Dans BailMobiliteController ou via tinker
$bailMobilite = BailMobilite::find(ID_BM);

// Mission d'entrée
$entryMission = Mission::create([
    'bail_mobilite_id' => $bailMobilite->id,
    'mission_type' => 'entry',
    'scheduled_date' => $bailMobilite->start_date,
    'address' => $bailMobilite->address,
    'status' => 'assigned'
]);

// Mission de sortie
$exitMission = Mission::create([
    'bail_mobilite_id' => $bailMobilite->id,
    'mission_type' => 'exit',
    'scheduled_date' => $bailMobilite->end_date,
    'address' => $bailMobilite->address,
    'status' => 'assigned'
]);

// Mettre à jour le BM
$bailMobilite->update([
    'entry_mission_id' => $entryMission->id,
    'exit_mission_id' => $exitMission->id
]);
```

### 3. Problèmes de Signatures Électroniques

#### Signatures ne s'affichent pas dans les PDF

**Symptômes** :
- PDF généré mais signatures manquantes
- Erreur lors de la génération du contrat

**Diagnostic** :
```bash
# Vérifier les logs de génération PDF
grep "PDF generation" storage/logs/laravel.log

# Vérifier les signatures en base
php artisan tinker
>>> $signature = BailMobiliteSignature::find(ID_SIGNATURE);
>>> $signature->tenant_signature ? 'Présente' : 'Manquante';
>>> $signature->contractTemplate->admin_signature ? 'Présente' : 'Manquante';
```

**Solutions** :
1. **Vérifier les modèles de contrats** :
```php
$template = ContractTemplate::where('type', 'entry')->active()->first();
if (!$template || !$template->admin_signature) {
    // Modèle non signé ou inactif
}
```

2. **Régénérer le PDF** :
```php
$signatureService = app(SignatureService::class);
$pdfPath = $signatureService->generateSignedContract($bailMobilite, 'entry');
```

3. **Vérifier les permissions de fichiers** :
```bash
# S'assurer que le dossier de stockage est accessible en écriture
chmod -R 755 storage/app/contracts/
chown -R www-data:www-data storage/app/contracts/
```

#### Pad de signature ne fonctionne pas sur mobile

**Symptômes** :
- Signature ne se dessine pas
- Erreur JavaScript dans la console

**Solutions** :
1. **Vérifier la compatibilité navigateur** :
```javascript
// Ajouter des fallbacks pour les anciens navigateurs
if (!HTMLCanvasElement.prototype.toBlob) {
    // Polyfill pour toBlob
}
```

2. **Optimiser pour mobile** :
```css
/* CSS pour améliorer l'expérience mobile */
.signature-pad {
    touch-action: none;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
}
```

### 4. Problèmes de Notifications

#### Notifications de rappel (10 jours) non envoyées

**Diagnostic** :
```bash
# Vérifier les notifications programmées
php artisan tinker
>>> Notification::where('type', 'exit_reminder')->where('status', 'pending')->get();

# Vérifier les commandes programmées
php artisan schedule:list
```

**Solutions** :
1. **Vérifier le cron** :
```bash
# S'assurer que le cron Laravel fonctionne
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

2. **Exécuter manuellement la commande** :
```bash
php artisan notifications:process-scheduled
```

3. **Reprogrammer les notifications manquantes** :
```php
$bailMobilites = BailMobilite::where('status', 'in_progress')
    ->whereDate('end_date', '>', now()->addDays(10))
    ->get();

foreach ($bailMobilites as $bm) {
    $notificationService->scheduleExitReminder($bm);
}
```

### 5. Problèmes de Performance

#### Interface lente, timeouts

**Diagnostic** :
```bash
# Vérifier les logs de performance
grep "slow query" storage/logs/laravel.log

# Analyser les requêtes
php artisan telescope:install # Si Telescope est installé
```

**Solutions** :
1. **Optimiser les requêtes** :
```php
// Utiliser eager loading
$bailMobilites = BailMobilite::with([
    'opsUser',
    'entryMission.assignedAgent',
    'exitMission.assignedAgent',
    'signatures'
])->paginate(15);
```

2. **Ajouter des index** :
```php
// Migration pour ajouter des index
Schema::table('bail_mobilites', function (Blueprint $table) {
    $table->index('status');
    $table->index('ops_user_id');
    $table->index(['start_date', 'end_date']);
});
```

3. **Mettre en cache les données fréquentes** :
```php
// Cache des métriques dashboard
$metrics = Cache::remember('ops_dashboard_metrics', 300, function () {
    return [
        'total_bm' => BailMobilite::count(),
        'assigned' => BailMobilite::assigned()->count(),
        'in_progress' => BailMobilite::inProgress()->count(),
        'completed' => BailMobilite::completed()->count(),
        'incidents' => BailMobilite::incident()->count(),
    ];
});
```

## Procédures de Maintenance

### 1. Maintenance Quotidienne

#### Vérification des Services

```bash
#!/bin/bash
# Script de vérification quotidienne

echo "=== Vérification des services ==="

# Vérifier les queues
php artisan queue:work --once --timeout=30
if [ $? -eq 0 ]; then
    echo "✅ Queues fonctionnelles"
else
    echo "❌ Problème avec les queues"
fi

# Vérifier les notifications en attente
PENDING_NOTIFICATIONS=$(php artisan tinker --execute="echo Notification::where('status', 'pending')->count();")
echo "📧 Notifications en attente: $PENDING_NOTIFICATIONS"

# Vérifier l'espace disque
DISK_USAGE=$(df -h /var/www/html/storage | awk 'NR==2{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "⚠️ Espace disque faible: ${DISK_USAGE}%"
else
    echo "✅ Espace disque OK: ${DISK_USAGE}%"
fi
```

#### Nettoyage des Logs

```bash
# Rotation des logs Laravel
find storage/logs -name "*.log" -mtime +30 -delete

# Nettoyage des sessions expirées
php artisan session:gc

# Nettoyage du cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### 2. Maintenance Hebdomadaire

#### Optimisation Base de Données

```sql
-- Optimiser les tables principales
OPTIMIZE TABLE bail_mobilites;
OPTIMIZE TABLE missions;
OPTIMIZE TABLE bail_mobilite_signatures;
OPTIMIZE TABLE notifications;

-- Analyser les performances
ANALYZE TABLE bail_mobilites;
ANALYZE TABLE missions;
```

#### Sauvegarde des Données

```bash
#!/bin/bash
# Script de sauvegarde hebdomadaire

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/weekly"

# Sauvegarde base de données
mysqldump -u root -p database_name > "$BACKUP_DIR/db_backup_$DATE.sql"

# Sauvegarde des fichiers de contrats
tar -czf "$BACKUP_DIR/contracts_backup_$DATE.tar.gz" storage/app/contracts/

# Sauvegarde des photos de checklists
tar -czf "$BACKUP_DIR/photos_backup_$DATE.tar.gz" storage/app/public/checklist-photos/

# Nettoyage des anciennes sauvegardes (garder 4 semaines)
find $BACKUP_DIR -name "*.sql" -mtime +28 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +28 -delete
```

### 3. Maintenance Mensuelle

#### Archivage des Données

```php
// Script d'archivage des BM terminés depuis plus de 6 mois
$oldBailMobilites = BailMobilite::where('status', 'completed')
    ->where('updated_at', '<', now()->subMonths(6))
    ->get();

foreach ($oldBailMobilites as $bm) {
    // Archiver les documents
    $this->archiveDocuments($bm);
    
    // Marquer comme archivé
    $bm->update(['archived' => true]);
}
```

#### Analyse des Performances

```php
// Rapport mensuel de performance
$report = [
    'total_bm_created' => BailMobilite::whereMonth('created_at', now()->month)->count(),
    'success_rate' => BailMobilite::completed()->whereMonth('updated_at', now()->month)->count() / 
                     BailMobilite::whereMonth('created_at', now()->month)->count() * 100,
    'average_duration' => BailMobilite::completed()
        ->whereMonth('updated_at', now()->month)
        ->avg(DB::raw('DATEDIFF(updated_at, created_at)')),
    'incidents_count' => BailMobilite::incident()->whereMonth('updated_at', now()->month)->count()
];
```

## Monitoring et Alertes

### 1. Métriques à Surveiller

#### Métriques Système
- **CPU et RAM** : Utilisation serveur
- **Espace disque** : Stockage des contrats et photos
- **Base de données** : Taille et performance
- **Réseau** : Bande passante et latence

#### Métriques Application
- **Temps de réponse** : Pages et API
- **Taux d'erreur** : 4xx et 5xx
- **Queues** : Longueur et temps de traitement
- **Notifications** : Taux de livraison

### 2. Alertes Automatiques

```php
// Service d'alertes
class AlertService
{
    public function checkSystemHealth()
    {
        // Vérifier les queues bloquées
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 10) {
            $this->sendAlert('Queue failures', "Failed jobs: $failedJobs");
        }
        
        // Vérifier les notifications en retard
        $overdueNotifications = Notification::where('scheduled_at', '<', now()->subHours(1))
            ->where('status', 'pending')
            ->count();
        if ($overdueNotifications > 0) {
            $this->sendAlert('Overdue notifications', "Count: $overdueNotifications");
        }
        
        // Vérifier l'espace disque
        $diskUsage = disk_free_space(storage_path()) / disk_total_space(storage_path()) * 100;
        if ($diskUsage < 20) {
            $this->sendAlert('Low disk space', "Free space: {$diskUsage}%");
        }
    }
}
```

### 3. Dashboard de Monitoring

```php
// Contrôleur pour dashboard de monitoring
class MonitoringController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Admin/Monitoring', [
            'metrics' => [
                'system' => $this->getSystemMetrics(),
                'application' => $this->getApplicationMetrics(),
                'business' => $this->getBusinessMetrics()
            ],
            'alerts' => $this->getActiveAlerts(),
            'health_checks' => $this->runHealthChecks()
        ]);
    }
    
    private function getSystemMetrics()
    {
        return [
            'cpu_usage' => sys_getloadavg()[0],
            'memory_usage' => memory_get_usage(true),
            'disk_usage' => disk_free_space('/'),
            'uptime' => $this->getUptime()
        ];
    }
}
```

## Procédures d'Urgence

### 1. Panne Système Complète

**Actions immédiates** :
1. **Vérifier l'infrastructure** : Serveur, base de données, réseau
2. **Activer le mode maintenance** :
```bash
php artisan down --message="Maintenance en cours" --retry=60
```
3. **Diagnostiquer la cause** : Logs, monitoring, tests
4. **Restaurer depuis sauvegarde** si nécessaire
5. **Tester le système** avant remise en service
6. **Désactiver le mode maintenance** :
```bash
php artisan up
```

### 2. Corruption de Données

**Procédure** :
1. **Isoler le problème** : Identifier les données affectées
2. **Arrêter les écritures** : Mode lecture seule temporaire
3. **Restaurer depuis sauvegarde** : Données les plus récentes possibles
4. **Vérifier l'intégrité** : Tests complets
5. **Reprendre le service** : Surveillance renforcée

### 3. Problème de Sécurité

**Actions** :
1. **Isoler le système** : Couper l'accès externe si nécessaire
2. **Analyser la menace** : Logs, traces d'intrusion
3. **Corriger la vulnérabilité** : Patch, mise à jour
4. **Changer les mots de passe** : Comptes sensibles
5. **Audit complet** : Vérifier l'intégrité des données
6. **Renforcer la sécurité** : Mesures préventives

## Contacts d'Urgence

### Support Technique
- **Niveau 1** : +33 1 23 45 67 89 (24h/7j)
- **Niveau 2** : +33 1 23 45 67 90 (heures ouvrées)
- **Email** : support-urgent@example.com

### Escalade
- **Responsable technique** : +33 6 12 34 56 78
- **Directeur IT** : +33 6 12 34 56 79
- **Astreinte** : +33 6 12 34 56 80

### Prestataires Externes
- **Hébergeur** : Support 24h/7j
- **Fournisseur base de données** : Support technique
- **Sécurité** : Expert cybersécurité