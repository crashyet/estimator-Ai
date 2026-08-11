<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    public array $default = [
        /**
         * Menggunakan ['*'] berarti mengizinkan semua IP atau Domain 
         * untuk mengakses API ini.
         */
        'allowedOrigins' => ['*'],

        'allowedOriginsPatterns' => [],

        /**
         * Jika allowedOrigins menggunakan '*', supportsCredentials HARUS false.
         * Browser akan menolak request jika ini diatur ke true saat origin-nya '*'.
         */
        'supportsCredentials' => false,

        /**
         * Mengizinkan semua jenis header yang dikirim oleh client (laptop lain),
         * seperti 'Content-Type', 'Authorization', dll.
         */
        'allowedHeaders' => ['*'],

        'exposedHeaders' => [],

        /**
         * Menentukan metode HTTP apa saja yang boleh diakses.
         * OPTIONS sangat penting untuk preflight request dari browser.
         */
        'allowedMethods' => ['GET', 'POST', 'OPTIONS', 'PUT', 'DELETE', 'PATCH'],

        'maxAge' => 7200,
    ];
}