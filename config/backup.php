<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the backup system including
    | storage locations, retention policies, and backup options.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Backup Disk
    |--------------------------------------------------------------------------
    |
    | The default disk where backups will be stored. This should be configured
    | in your filesystems.php config file.
    |
    */

    'disk' => env('BACKUP_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Backup Paths
    |--------------------------------------------------------------------------
    |
    | Define the paths where different types of backups will be stored.
    |
    */

    'path' => env('BACKUP_PATH', 'backups/database'),
    'file_backup_path' => env('FILE_BACKUP_PATH', 'backups/files'),

    /*
    |--------------------------------------------------------------------------
    | Retention Policy
    |--------------------------------------------------------------------------
    |
    | How long to keep backups before they are automatically deleted.
    |
    */

    'retention' => [
        'daily' => env('BACKUP_RETENTION_DAILY', 7), // Keep daily backups for 7 days
        'weekly' => env('BACKUP_RETENTION_WEEKLY', 4), // Keep weekly backups for 4 weeks
        'monthly' => env('BACKUP_RETENTION_MONTHLY', 12), // Keep monthly backups for 12 months
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Schedule
    |--------------------------------------------------------------------------
    |
    | Define when automated backups should run.
    |
    */

    'schedule' => [
        'database' => [
            'enabled' => env('BACKUP_SCHEDULE_ENABLED', true),
            'frequency' => env('BACKUP_SCHEDULE_FREQUENCY', 'daily'), // daily, weekly, monthly
            'time' => env('BACKUP_SCHEDULE_TIME', '02:00'), // Time in HH:MM format
        ],
        'files' => [
            'enabled' => env('FILE_BACKUP_SCHEDULE_ENABLED', true),
            'frequency' => env('FILE_BACKUP_SCHEDULE_FREQUENCY', 'weekly'),
            'time' => env('FILE_BACKUP_SCHEDULE_TIME', '03:00'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Options
    |--------------------------------------------------------------------------
    |
    | Default options for backup creation.
    |
    */

    'options' => [
        'compress' => env('BACKUP_COMPRESS', true),
        'encrypt' => env('BACKUP_ENCRYPT', false),
        'verify' => env('BACKUP_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloud Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for storing backups in cloud services.
    |
    */

    'cloud' => [
        'enabled' => env('BACKUP_CLOUD_ENABLED', false),
        'disk' => env('BACKUP_CLOUD_DISK', 's3'),
        'path' => env('BACKUP_CLOUD_PATH', 'backups'),
        'sync_local' => env('BACKUP_CLOUD_SYNC_LOCAL', true), // Keep local copy
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure notifications for backup events.
    |
    */

    'notifications' => [
        'enabled' => env('BACKUP_NOTIFICATIONS_ENABLED', true),
        'channels' => ['mail'], // mail, slack, etc.
        'events' => [
            'backup_success' => env('BACKUP_NOTIFY_SUCCESS', false),
            'backup_failure' => env('BACKUP_NOTIFY_FAILURE', true),
            'verification_failure' => env('BACKUP_NOTIFY_VERIFICATION_FAILURE', true),
        ],
        'recipients' => [
            'mail' => env('BACKUP_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for backup monitoring and health checks.
    |
    */

    'monitoring' => [
        'enabled' => env('BACKUP_MONITORING_ENABLED', true),
        'max_backup_age_hours' => env('BACKUP_MAX_AGE_HOURS', 25), // Alert if no backup in 25 hours
        'min_backup_size_mb' => env('BACKUP_MIN_SIZE_MB', 1), // Alert if backup is smaller than 1MB
        'health_check_url' => env('BACKUP_HEALTH_CHECK_URL'), // Optional webhook for monitoring services
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Database-specific backup options.
    |
    */

    'database' => [
        'mysql' => [
            'single_transaction' => true,
            'routines' => true,
            'triggers' => true,
            'add_drop_table' => true,
        ],
        'postgres' => [
            'format' => 'custom', // custom, plain, tar
            'compress' => 9,
        ],
        'sqlite' => [
            'vacuum' => true, // Run VACUUM before backup
        ],
    ],

];