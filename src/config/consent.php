<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Consent Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by the consent package.
    |
    */
    'tables' => [
        'consents' => 'consents',
        'consent_types' => 'consent_types',
        'consent_sources' => 'consent_sources',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Consent Types
    |--------------------------------------------------------------------------
    |
    | Default consent types to seed when running migrations.
    |
    */
    'default_types' => [
        'email-marketing' => 'Email Marketing',
        'sms-marketing' => 'SMS Marketing',
        'data-processing' => 'Data Processing',
        'third-party-sharing' => 'Third Party Sharing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Consent Sources
    |--------------------------------------------------------------------------
    |
    | Default consent sources to seed when running migrations.
    |
    */
    'default_sources' => [
        'web-form' => 'Web Form',
        'mobile-app' => 'Mobile App',
        'email' => 'Email',
        'phone' => 'Phone',
        'in-person' => 'In Person',
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent Retention Period
    |--------------------------------------------------------------------------
    |
    | How long to retain revoked consent records (in days).
    | Set to null to keep indefinitely.
    |
    */
    'retention_days' => null,
];
