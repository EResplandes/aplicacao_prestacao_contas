<?php

return [
    'base_url' => env('BB_API_BASE_URL'),
    'allowed_hosts' => array_values(array_filter(array_map(
        static fn (string $host): string => trim($host),
        explode(',', (string) env('BB_API_ALLOWED_HOSTS', 'api.bb.com.br'))
    ))),
    'timeout_seconds' => (int) env('BB_API_TIMEOUT_SECONDS', 10),
    'connect_timeout_seconds' => (int) env('BB_API_CONNECT_TIMEOUT_SECONDS', 5),
    'max_redirects' => (int) env('BB_API_MAX_REDIRECTS', 0),
    'signature_secret' => env('BB_API_SIGNATURE_SECRET'),
    'client_id' => env('BB_API_CLIENT_ID'),
    'client_secret' => env('BB_API_CLIENT_SECRET'),
    'mtls' => [
        'enabled' => filter_var(env('BB_API_MTLS_ENABLED', false), FILTER_VALIDATE_BOOL),
        'cert_path' => env('BB_API_MTLS_CERT_PATH'),
        'key_path' => env('BB_API_MTLS_KEY_PATH'),
        'key_passphrase' => env('BB_API_MTLS_KEY_PASSPHRASE'),
        'ca_path' => env('BB_API_MTLS_CA_PATH'),
    ],
];
