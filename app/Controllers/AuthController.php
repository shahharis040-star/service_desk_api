<?php


//  Gestione richieste HTTP per gli endpoints /auth/* 


declare(strict_types=1);

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    
    //  POST /auth/register
    public function register(array $params = []): void
    {
        $body = $this->getJsonBody();

        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->respond(400, ['error' => 'Inserire Email e Password.']);
            return;
        }

        try {
            $result = $this->authService->register($email, $password);
            $this->respond(201, ['message' => 'User registrato correttamente.', 'user_id' => $result['user_id']]);
        } catch (InvalidArgumentException $e) {
            $this->respond(400, ['error' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            $this->respond(409, ['error' => $e->getMessage()]);
        }
    }

    //  POST /auth/login
    public function login(array $params = []): void
    {
        $body = $this->getJsonBody();

        $email    = trim($body['email']    ?? '');
        $password = trim($body['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->respond(400, ['error' => 'Inserire Email e Password.']);
            return;
        }

        try {
            $result = $this->authService->login($email, $password);
            $this->respond(200, $result);
        } catch (RuntimeException $e) {
            $this->respond(401, ['error' => $e->getMessage()]);
        }
    }

    //  GET /auth/me   (protected)
    public function me(array $params = []): void
    {
        // Token validato da AuthMiddleware.
        // user ID dentro a  $_REQUEST['auth_user_id'].
        $token = $this->getBearerToken();

        try {
            $user = $this->authService->me($token);
            $this->respond(200, $user);
        } catch (RuntimeException $e) {
            $this->respond(401, ['error' => $e->getMessage()]);
        }
    }

    //  Helpers
    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    private function getBearerToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return '';
    }

    private function respond(int $status, array $data): void
    {
        http_response_code($status);
        echo json_encode($data);
    }
}