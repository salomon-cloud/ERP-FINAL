<?php

declare(strict_types=1);

/*
 * SISEN ERP static configuration.
 *
 * These are the DEFAULTS. Values an administrator may change at runtime live in
 * the `settings` table and are read through the Settings facade, which falls
 * back to the values below. Never call env() outside this file.
 */
return [

    'auth' => [
        // Account lockout after this many consecutive failed logins.
        'max_attempts' => 5,
        'lockout_minutes' => 15,
    ],

    'attachments' => [
        'disk' => env('SISEN_ATTACHMENTS_DISK', 'public'),
        'max_size_kb' => 10240,
        'allowed_mimes' => [
            'pdf', 'xml', 'jpg', 'jpeg', 'png', 'webp',
            'doc', 'docx', 'xls', 'xlsx', 'csv',
        ],
    ],

    'company' => [
        'currency' => 'MXN',
        'locale' => 'es_MX',
        'fiscal_year_start_month' => 1,
    ],

    'finance' => [
        // Voiding a posted entry always writes a reversing entry.
        'reverse_on_void' => true,
        'allow_reopen_closed_period' => false,
    ],

    'sales' => [
        // Discount rate above which sales.discounts.override is required.
        'max_discount' => 15.0,
    ],

    'inventory' => [
        // Tolerance (percent) allowed when receiving more than ordered.
        'over_receipt_tolerance' => 0.0,
        'negative_stock_allowed' => false,
    ],

    'hr' => [
        'payroll_frequency' => 'biweekly',
        // Minutes after the shift start that still count as "late" (not absent).
        'late_threshold_minutes' => 15,
    ],

    'documents' => [
        // Zero padding for numbers produced by SequenceService (SO-000123).
        'number_padding' => 6,
    ],

];
