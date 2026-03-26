<?php

return [
    'headers' => [
        'content_security_policy' => env(
            'SECURITY_CSP',
            "default-src 'self'; base-uri 'self'; frame-ancestors 'none'; object-src 'none'; form-action 'self'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; connect-src 'self'; upgrade-insecure-requests"
        ),
        'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'strict_transport_security' => env('SECURITY_HSTS', 'max-age=31536000; includeSubDomains'),
        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=(self)'),
    ],
    'trusted_origins' => array_values(array_filter(array_map(
        static fn (string $origin): string => trim($origin),
        explode(',', (string) env('SECURITY_TRUSTED_ORIGINS', env('APP_URL', 'http://localhost')))
    ))),
    'sanitize' => [
        'strip_tags' => true,
        'collapse_whitespace' => true,
    ],
    'auth' => [
        'access_token_ttl_minutes' => (int) env('SECURITY_ACCESS_TOKEN_TTL', 60),
        'refresh_token_ttl_minutes' => (int) env('SECURITY_REFRESH_TOKEN_TTL', 10080),
        'refresh_cookie_name' => env('SECURITY_REFRESH_COOKIE_NAME', 'caixa_pulse_refresh_token'),
        'refresh_cookie_same_site' => env('SECURITY_REFRESH_COOKIE_SAME_SITE', env('SESSION_SAME_SITE', 'lax')),
        'refresh_cookie_secure' => filter_var(env('SECURITY_REFRESH_COOKIE_SECURE', env('SESSION_SECURE_COOKIE', false)), FILTER_VALIDATE_BOOL),
    ],
    'replay' => [
        'window_seconds' => (int) env('SECURITY_REPLAY_WINDOW_SECONDS', 300),
        'nonce_cache_prefix' => env('SECURITY_NONCE_CACHE_PREFIX', 'security:nonce:'),
    ],
    'mfa' => [
        'session_key' => env('SECURITY_MFA_SESSION_KEY', 'mfa_passed_at'),
        'max_age_seconds' => (int) env('SECURITY_MFA_MAX_AGE_SECONDS', 300),
    ],
];
