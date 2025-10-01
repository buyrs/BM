<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings specific to production logging.
    | These settings optimize logging for production environments with proper
    | log levels, rotation, and external service integration.
    |
    */

    'channels' => [
        'production_stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'slack', 'syslog'],
            'ignore_exceptions' => false,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_SLACK_LEVEL', 'critical'),
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_SYSLOG_LEVEL', 'error'),
            'facility' => LOG_USER,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_ERRORLOG_LEVEL', 'error'),
        ],

        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'info',
            'days' => 7,
        ],

        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
            'days' => 30,
        ],

        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 90,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Level Mapping
    |--------------------------------------------------------------------------
    |
    | Map different types of events to appropriate log levels for production.
    |
    */
    'level_mapping' => [
        'authentication_failed' => 'warning',
        'authorization_failed' => 'warning',
        'validation_failed' => 'info',
        'database_error' => 'error',
        'external_api_error' => 'warning',
        'performance_issue' => 'warning',
        'security_incident' => 'critical',
        'system_error' => 'error',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Logging Thresholds
    |--------------------------------------------------------------------------
    |
    | Define thresholds for performance-related logging.
    |
    */
    'performance_thresholds' => [
        'slow_query_time' => env('LOG_SLOW_QUERY_TIME', 1000), // milliseconds
        'slow_request_time' => env('LOG_SLOW_REQUEST_TIME', 2000), // milliseconds
        'high_memory_usage' => env('LOG_HIGH_MEMORY_USAGE', 128 * 1024 * 1024), // bytes
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Event Types
    |--------------------------------------------------------------------------
    |
    | Define security events that should be logged with high priority.
    |
    */
    'security_events' => [
        'failed_login_attempts',
        'suspicious_activity',
        'unauthorized_access',
        'privilege_escalation',
        'data_breach_attempt',
        'malicious_file_upload',
        'sql_injection_attempt',
        'xss_attempt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Retention Policies
    |--------------------------------------------------------------------------
    |
    | Define how long different types of logs should be retained.
    |
    */
    'retention_policies' => [
        'application' => env('LOG_RETENTION_APPLICATION', 14), // days
        'performance' => env('LOG_RETENTION_PERFORMANCE', 7), // days
        'security' => env('LOG_RETENTION_SECURITY', 30), // days
        'audit' => env('LOG_RETENTION_AUDIT', 90), // days
        'debug' => env('LOG_RETENTION_DEBUG', 1), // days
    ],

    /*
    |--------------------------------------------------------------------------
    | External Service Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for external logging and monitoring services.
    |
    */
    'external_services' => [
        'sentry' => [
            'enabled' => env('SENTRY_LARAVEL_DSN') !== null,
            'dsn' => env('SENTRY_LARAVEL_DSN'),
            'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),
            'release' => env('SENTRY_RELEASE'),
        ],

        'datadog' => [
            'enabled' => env('DATADOG_API_KEY') !== null,
            'api_key' => env('DATADOG_API_KEY'),
            'host' => env('DATADOG_HOST', 'localhost'),
            'port' => env('DATADOG_PORT', 8125),
        ],

        'new_relic' => [
            'enabled' => env('NEW_RELIC_LICENSE_KEY') !== null,
            'license_key' => env('NEW_RELIC_LICENSE_KEY'),
            'app_name' => env('NEW_RELIC_APP_NAME', env('APP_NAME')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Formatting
    |--------------------------------------------------------------------------
    |
    | Configure log formatting for production environments.
    |
    */
    'formatting' => [
        'include_context' => env('LOG_INCLUDE_CONTEXT', true),
        'include_extra' => env('LOG_INCLUDE_EXTRA', true),
        'max_context_length' => env('LOG_MAX_CONTEXT_LENGTH', 1000),
        'sanitize_sensitive_data' => env('LOG_SANITIZE_SENSITIVE', true),
        'sensitive_fields' => [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'ssn',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configure when and how to send alerts for critical events.
    |
    */
    'alerts' => [
        'enabled' => env('LOG_ALERTS_ENABLED', true),
        'channels' => ['slack', 'email'],
        'rate_limit' => env('LOG_ALERT_RATE_LIMIT', 5), // alerts per minute
        'critical_events' => [
            'application_down',
            'database_connection_failed',
            'high_error_rate',
            'security_breach',
            'performance_degradation',
        ],
    ],
];