<?php

namespace App\Services\Security;

use App\Exceptions\SecurityViolation;

class OutboundEndpointGuard
{
    public function assertAllowed(string $url): void
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            throw new SecurityViolation('URL externa invalida.');
        }

        if (strtolower((string) $parts['scheme']) !== 'https') {
            throw new SecurityViolation('Somente chamadas HTTPS sao permitidas para integracoes externas.');
        }

        $host = strtolower((string) $parts['host']);

        if (! in_array($host, array_map('strtolower', config('bb_api.allowed_hosts', [])), true)) {
            throw new SecurityViolation('Dominio externo nao permitido para integracao.');
        }

        $resolvedHosts = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : (gethostbynamel($host) ?: []);

        if ($resolvedHosts === []) {
            throw new SecurityViolation('Nao foi possivel resolver o dominio externo.');
        }

        foreach ($resolvedHosts as $resolvedHost) {
            if (! filter_var($resolvedHost, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new SecurityViolation('Resolucao DNS aponta para endereco interno ou reservado.');
            }
        }
    }
}
