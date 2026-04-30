<?php

//  Gestisce generazione JWT usando HMAC-SHA256

declare(strict_types=1);

class JwtService
{
    private string $secret;
    private int $expiration;

    public function __construct()
    {
        $secret = $_ENV['JWT_SECRET'] ?? null;

        if (empty($secret)) {
            throw new \RuntimeException("Errore Critico: JWT_SECRET non configurato nelle variabili d'ambiente.");
        }

        $this->secret = $secret;
        $this->expiration = (int) ($_ENV['JWT_EXPIRATION'] ?? 3600);
    }

    // Genera un token JWT per il dato user ID
    public function generate(int $userId): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + $this->expiration,
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secret, true)
        );

        return "$header.$payload.$signature";
    }

    //  Controlla il token e ritorna eventuale payload
    public function validate(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        // Ricalcolo firma e comparo
        $expectedSig = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secret, true)
        );

        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }

        $data = json_decode($this->base64UrlDecode($payload), true);

        // Controlla scadenza 
        if (!isset($data['exp']) || $data['exp'] < time()) {
            return null;
        }

        return $data;
    }

    // Funzioni di encode e decode
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}