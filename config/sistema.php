<?php

return [
    'razon_social' => 'SISEN Empresarial S.A. de C.V.',
    'rfc' => 'SEN220101AB1',
    'direccion' => 'Av. Empresa 123, Ciudad de Mexico',
    'telefono' => '55 1000 1000',
    'correo' => 'contacto@sisen.com',
    'lema' => 'Sistema Empresarial de Nominas',

    'imss_cuota_obrera' => 2.375,

    'isr_tabla' => [
        ['min' => 0.01, 'max' => 746.04, 'cuota_fija' => 0.00, 'porcentaje' => 0.0192],
        ['min' => 746.05, 'max' => 6332.05, 'cuota_fija' => 14.32, 'porcentaje' => 0.0640],
        ['min' => 6332.06, 'max' => 11128.01, 'cuota_fija' => 371.83, 'porcentaje' => 0.1088],
        ['min' => 11128.02, 'max' => 12935.82, 'cuota_fija' => 893.63, 'porcentaje' => 0.1600],
        ['min' => 12935.83, 'max' => 15487.71, 'cuota_fija' => 1182.88, 'porcentaje' => 0.1792],
        ['min' => 15487.72, 'max' => 31236.49, 'cuota_fija' => 1640.18, 'porcentaje' => 0.2136],
        ['min' => 31236.50, 'max' => 49233.00, 'cuota_fija' => 5007.79, 'porcentaje' => 0.2352],
        ['min' => 49233.01, 'max' => 93993.90, 'cuota_fija' => 9238.92, 'porcentaje' => 0.3000],
        ['min' => 93993.91, 'max' => 125325.20, 'cuota_fija' => 22666.74, 'porcentaje' => 0.3200],
        ['min' => 125325.21, 'max' => 375975.61, 'cuota_fija' => 32693.36, 'porcentaje' => 0.3400],
        ['min' => 375975.62, 'max' => PHP_INT_MAX, 'cuota_fija' => 117914.98, 'porcentaje' => 0.3500],
    ],
];
