<?php

/**
 * Configuración para cumplimiento normativo ARCA 2025
 * Resolución General N° 5616
 * 
 * Este archivo configura las funcionalidades preparatorias para los cambios
 * normativos que implementará ARCA durante 2025.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Estado de Migración ARCA 2025
    |--------------------------------------------------------------------------
    |
    | Control del estado de la migración a la normativa ARCA 2025
    | Estados: 'preparacion', 'testing', 'produccion'
    |
    */
    'migration_status' => env('ARCA_2025_STATUS', 'preparacion'),

    /*
    |--------------------------------------------------------------------------
    | Validación Condición IVA del Receptor
    |--------------------------------------------------------------------------
    |
    | Habilita la validación obligatoria de la condición frente al IVA
    | del receptor en todos los comprobantes electrónicos.
    |
    */
    'validacion_condicion_iva' => [
        'enabled' => env('ARCA_VALIDACION_IVA', false),
        'strict_mode' => env('ARCA_IVA_STRICT', false),
        'default_condition' => env('ARCA_IVA_DEFAULT', 'IVA Responsable Inscripto'),
        'require_validation' => env('ARCA_IVA_REQUIRE_VALIDATION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validación Padrón ARCA
    |--------------------------------------------------------------------------
    |
    | Configuración para validación automática contra el padrón de ARCA
    | para verificar condiciones IVA de receptores.
    |
    */
    'validacion_padron' => [
        'enabled' => env('ARCA_VALIDACION_PADRON', false),
        'automatic' => env('ARCA_PADRON_AUTO', false),
        'cache_duration' => env('ARCA_PADRON_CACHE', 86400), // 24 horas
        'fallback_on_error' => env('ARCA_PADRON_FALLBACK', true),
        'api_url' => env('ARCA_PADRON_URL', 'https://soa.arca.gob.ar/...'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Operaciones en Moneda Extranjera
    |--------------------------------------------------------------------------
    |
    | Nuevo tratamiento para operaciones en moneda extranjera según
    | los cambios normativos de ARCA 2025.
    |
    */
    'moneda_extranjera' => [
        'strict_validation' => env('ARCA_MONEDA_STRICT', false),
        'require_cotizacion_date' => env('ARCA_COTIZACION_DATE', false),
        'max_cotizacion_age_days' => env('ARCA_COTIZACION_MAX_AGE', 1),
        'auto_update_rates' => env('ARCA_AUTO_RATES', false),
        'bcra_integration' => env('ARCA_BCRA_INTEGRATION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Versionado de Webservices
    |--------------------------------------------------------------------------
    |
    | Configuración para el manejo de versiones de webservices ARCA
    | para transición gradual a la normativa 2025.
    |
    */
    'webservices' => [
        'version' => env('ARCA_WS_VERSION', '1.0'),
        'target_version_2025' => env('ARCA_WS_VERSION_2025', '2025.1'),
        'enable_legacy_fallback' => env('ARCA_LEGACY_FALLBACK', true),
        'force_new_version' => env('ARCA_FORCE_NEW_VERSION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | URLs de Webservices ARCA 2025
    |--------------------------------------------------------------------------
    |
    | URLs actualizadas para los webservices que cumplan con la normativa 2025
    | Se mantendrán las URLs legacy hasta completar la migración.
    |
    */
    'urls' => [
        'wsfe_2025' => env('ARCA_WSFE_2025_URL', 'https://servicios1.afip.gov.ar/wsfev1/service.asmx'),
        'wsbfe_2025' => env('ARCA_WSBFE_2025_URL', 'https://servicios1.afip.gov.ar/wsbfev1/service.asmx'),
        'padron_a5' => env('ARCA_PADRON_A5_URL', 'https://soa.afip.gov.ar/sr-padron/v1/persona/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tablas de Códigos ARCA 2025
    |--------------------------------------------------------------------------
    |
    | Códigos y catálogos actualizados para cumplir con la normativa 2025
    |
    */
    'codigos' => [
        'condiciones_iva' => [
            1 => 'IVA Responsable Inscripto',
            2 => 'IVA Responsable no Inscripto',
            3 => 'IVA no Responsable',
            4 => 'IVA Sujeto Exento',
            5 => 'Consumidor Final',
            6 => 'Responsable Monotributo',
            7 => 'Sujeto no Categorizado',
            8 => 'Proveedor del Exterior',
            9 => 'Cliente del Exterior',
            10 => 'IVA Liberado – Ley Nº 19.640',
            11 => 'IVA Responsable Inscripto – Agente de Percepción',
            12 => 'Pequeño Contribuyente Eventual',
            13 => 'Monotributista Social',
            14 => 'Pequeño Contribuyente Eventual Social',
        ],
        
        'monedas_extranjeras' => [
            'USD' => ['codigo' => 'DOL', 'nombre' => 'Dólar Estadounidense'],
            'EUR' => ['codigo' => 'EUR', 'nombre' => 'Euro'],
            'BRL' => ['codigo' => 'BRL', 'nombre' => 'Real'],
            'UYU' => ['codigo' => 'UYU', 'nombre' => 'Peso Uruguayo'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validaciones y Reglas de Negocio
    |--------------------------------------------------------------------------
    |
    | Reglas de validación específicas para cumplir con ARCA 2025
    |
    */
    'validaciones' => [
        'condicion_iva_obligatoria' => env('ARCA_IVA_OBLIGATORIO', false),
        'validar_cuit_receptor' => env('ARCA_VALIDAR_CUIT_RECEPTOR', true),
        'moneda_extranjera_requiere_justificacion' => env('ARCA_MONEDA_JUSTIFICACION', false),
        'limite_monto_sin_validacion' => env('ARCA_LIMITE_SIN_VALIDACION', 1000000), // $1M ARS
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Logging para Compliance
    |--------------------------------------------------------------------------
    |
    | Logging especializado para auditoría de cumplimiento normativo
    |
    */
    'logging' => [
        'audit_compliance' => env('ARCA_AUDIT_COMPLIANCE', true),
        'log_padron_queries' => env('ARCA_LOG_PADRON', true),
        'log_validation_failures' => env('ARCA_LOG_VALIDATION_FAILURES', true),
        'retention_days' => env('ARCA_LOG_RETENTION_DAYS', 2555), // 7 años
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing y Desarrollo
    |--------------------------------------------------------------------------
    |
    | Configuraciones para testing de compliance ARCA 2025
    |
    */
    'testing' => [
        'use_sandbox' => env('ARCA_USE_SANDBOX', true),
        'mock_padron_responses' => env('ARCA_MOCK_PADRON', true),
        'simulate_validation_errors' => env('ARCA_SIMULATE_ERRORS', false),
        'test_cuits' => [
            '20112233440', // CUIT de testing válido
            '27999999992', // CUIT de testing inválido
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migración de Datos Legacy
    |--------------------------------------------------------------------------
    |
    | Configuración para migración de datos existentes a formato ARCA 2025
    |
    */
    'migration' => [
        'auto_migrate_invoices' => env('ARCA_AUTO_MIGRATE', false),
        'backup_before_migration' => env('ARCA_BACKUP_MIGRATION', true),
        'batch_size' => env('ARCA_MIGRATION_BATCH_SIZE', 1000),
        'default_receptor_condition' => env('ARCA_DEFAULT_RECEPTOR_CONDITION', 5), // Consumidor Final
    ],

    /*
    |--------------------------------------------------------------------------
    | Notificaciones y Alertas
    |--------------------------------------------------------------------------
    |
    | Sistema de notificaciones para cambios normativos y compliance
    |
    */
    'notifications' => [
        'compliance_alerts' => env('ARCA_COMPLIANCE_ALERTS', true),
        'validation_warnings' => env('ARCA_VALIDATION_WARNINGS', true),
        'regulatory_updates' => env('ARCA_REGULATORY_UPDATES', true),
        'slack_webhook' => env('ARCA_SLACK_WEBHOOK', null),
        'email_notifications' => env('ARCA_EMAIL_NOTIFICATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance y Caché
    |--------------------------------------------------------------------------
    |
    | Optimizaciones de performance para nuevas validaciones
    |
    */
    'performance' => [
        'cache_padron_results' => env('ARCA_CACHE_PADRON', true),
        'cache_ttl_seconds' => env('ARCA_CACHE_TTL', 3600),
        'enable_rate_limiting' => env('ARCA_RATE_LIMITING', true),
        'max_requests_per_minute' => env('ARCA_MAX_REQUESTS_PER_MINUTE', 60),
    ],
]; 