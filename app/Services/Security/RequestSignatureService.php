<?php

namespace App\Services\Security;

use App\Exceptions\SecurityViolation;

class RequestSignatureService
{
    public function sign(string $method, string $path, string $timestamp, string $nonce, array $payload = []): string
    {
        $secret = (string) config('bb_api.signature_secret');

        if ($secret === '') {
            throw new SecurityViolation('Segredo de assinatura da API do Banco do Brasil nao configurado.');
        }

        $body = $payload === [] ? '' : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $canonicalPayload = implode("\n", [
            strtoupper($method),
            trim($path),
            $timestamp,
            $nonce,
            $body,
        ]);

        return hash_hmac('sha256', $canonicalPayload, $secret);
    }
}
