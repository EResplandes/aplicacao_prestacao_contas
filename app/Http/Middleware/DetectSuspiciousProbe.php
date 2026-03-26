<?php

namespace App\Http\Middleware;

use App\Services\SecurityEventService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DetectSuspiciousProbe
{
    /**
     * @var array<int, string>
     */
    private array $patterns = [
        '.env',
        'wp-admin',
        'wp-login',
        'phpmyadmin',
        'pma',
        'xmlrpc.php',
        'boaform',
        'cgi-bin',
        'vendor/phpunit',
        '.git/config',
        'server-status',
        'actuator',
    ];

    public function __construct(private readonly SecurityEventService $securityEventService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $matchedPatterns = $this->matchedPatterns($request->path());

        if ($matchedPatterns !== [] && $this->shouldRecord($request, $matchedPatterns)) {
            $this->securityEventService->recordSuspiciousProbe(
                path: '/'.ltrim($request->path(), '/'),
                matchedPatterns: $matchedPatterns,
            );
        }

        return $next($request);
    }

    /**
     * @return array<int, string>
     */
    private function matchedPatterns(string $path): array
    {
        $normalizedPath = Str::lower(rawurldecode($path));

        return array_values(array_filter(
            $this->patterns,
            fn (string $pattern): bool => Str::contains($normalizedPath, $pattern),
        ));
    }

    /**
     * @param  array<int, string>  $matchedPatterns
     */
    private function shouldRecord(Request $request, array $matchedPatterns): bool
    {
        $cacheKey = sprintf(
            'security-probe:%s:%s:%s',
            $request->ip(),
            md5('/'.ltrim($request->path(), '/')),
            md5(implode('|', $matchedPatterns)),
        );

        return Cache::add($cacheKey, true, now()->addMinutes(5));
    }
}
