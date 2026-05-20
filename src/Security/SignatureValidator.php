<?php

declare(strict_types=1);

namespace SimplePhpWebhook\Security;

class SignatureValidator
{
    /**
     * Verify the request signature using SHA-256 HMAC.
     *
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @return bool
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        if (empty($signature) || empty($secret)) {
            return false;
        }

        $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret, false);
        
        return hash_equals($hash, $signature);
    }
}
