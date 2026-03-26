<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-Id') ?: (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        /** @var Response $response */
        $response = $next($request);

        $headers = config('security.headers');

        if ($csp = data_get($headers, 'content_security_policy')) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        if ($frameOptions = data_get($headers, 'x_frame_options')) {
            $response->headers->set('X-Frame-Options', $frameOptions);
        }

        if ($contentTypeOptions = data_get($headers, 'x_content_type_options')) {
            $response->headers->set('X-Content-Type-Options', $contentTypeOptions);
        }

        if ($referrerPolicy = data_get($headers, 'referrer_policy')) {
            $response->headers->set('Referrer-Policy', $referrerPolicy);
        }

        if ($permissionsPolicy = data_get($headers, 'permissions_policy')) {
            $response->headers->set('Permissions-Policy', $permissionsPolicy);
        }

        if ($request->isSecure() && ($strictTransportSecurity = data_get($headers, 'strict_transport_security'))) {
            $response->headers->set('Strict-Transport-Security', $strictTransportSecurity);
        }

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
