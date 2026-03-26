<?php

namespace App\Services\Integrations;

use App\Services\Security\OutboundEndpointGuard;
use App\Services\Security\ReplayProtectionService;
use App\Services\Security\RequestSignatureService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

class BancoDoBrasilApiClient
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly OutboundEndpointGuard $outboundEndpointGuard,
        private readonly ReplayProtectionService $replayProtectionService,
        private readonly RequestSignatureService $requestSignatureService,
    ) {}

    public function send(string $method, string $path, array $payload = [], array $query = []): Response
    {
        $baseUrl = rtrim((string) config('bb_api.base_url'), '/');
        $normalizedPath = '/'.ltrim($path, '/');
        $url = $baseUrl.$normalizedPath;

        $this->outboundEndpointGuard->assertAllowed($url);

        $timestamp = now()->toIso8601String();
        $nonce = (string) Str::uuid();

        $this->replayProtectionService->assertTimestampAndNonce($timestamp, $nonce);

        $headers = [
            'Accept' => 'application/json',
            'X-Request-Timestamp' => $timestamp,
            'X-Request-Nonce' => $nonce,
            'X-Request-Signature' => $this->requestSignatureService->sign(
                method: $method,
                path: $normalizedPath,
                timestamp: $timestamp,
                nonce: $nonce,
                payload: $payload,
            ),
        ];

        $request = $this->http
            ->baseUrl($baseUrl)
            ->acceptJson()
            ->timeout((int) config('bb_api.timeout_seconds', 10))
            ->connectTimeout((int) config('bb_api.connect_timeout_seconds', 5))
            ->withHeaders($headers)
            ->withBasicAuth(
                (string) config('bb_api.client_id'),
                (string) config('bb_api.client_secret'),
            )
            ->withOptions([
                'allow_redirects' => [
                    'max' => (int) config('bb_api.max_redirects', 0),
                    'strict' => true,
                    'referer' => false,
                    'protocols' => ['https'],
                ],
            ]);

        if ((bool) config('bb_api.mtls.enabled')) {
            $request = $request->withOptions($this->mtlsOptions());
        }

        return $request
            ->send(strtoupper($method), $normalizedPath, [
                'json' => $payload,
                'query' => $query,
            ])
            ->throw();
    }

    /**
     * @return array<string, mixed>
     */
    private function mtlsOptions(): array
    {
        $options = [];

        if ($certPath = config('bb_api.mtls.cert_path')) {
            $options['cert'] = $certPath;
        }

        if ($keyPath = config('bb_api.mtls.key_path')) {
            $passphrase = (string) config('bb_api.mtls.key_passphrase');
            $options['ssl_key'] = $passphrase !== '' ? [$keyPath, $passphrase] : $keyPath;
        }

        if ($caPath = config('bb_api.mtls.ca_path')) {
            $options['verify'] = $caPath;
        }

        return $options;
    }
}
