<?php

// ============================================================
//  Chiamato dal router prima di ogni azione protetta del controller
//  Valida il JWT dall'Authorization header
//  In caso di successo: salva user ID in $_REQUEST['auth_user_id']
//  In caso di fallimento: aborto e ritorno 401 
// ============================================================

declare(strict_types=1);

class AuthMiddleware
{
    public static function handle(): void
    {
        $token = self::extractToken();

        if ($token === null) {
            self::abort('Token mancante.');
        }

        $jwt     = new JwtService();
        $payload = $jwt->validate($token);

        if ($payload === null) {
            self::abort('Token non valido o scaduto.');
        }

        // Salvo l'user id rendendolo disponibile al controllore
        $_REQUEST['auth_user_id'] = (int) $payload['sub'];
    }

    //  Estraggo token bearer dall'header
    private static function extractToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (str_starts_with($header, 'Bearer ')) {
            $token = trim(substr($header, 7));
            return $token !== '' ? $token : null;
        }

        return null;
    }

    //  Ritorna 401 e ferma esecuzione
    private static function abort(string $message): never
    {
        http_response_code(401);
        echo json_encode(['error' => $message]);
        exit;
    }
}