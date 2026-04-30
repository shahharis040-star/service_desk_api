<?php

//  Logica di registrazione e login

declare(strict_types=1);

class AuthService
{
    private UserRepository $userRepo;
    private JwtService     $jwt;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->jwt      = new JwtService();
    }

    //  Registrazione nuovo utente
    public function register(string $email, string $password): array
    {
        // Controlli su password ed email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Indirizzo mail non valido.');
        }

        if (strlen($password) < 6) {
            throw new InvalidArgumentException('La password deve contenere almeno sei caratteri.');
        }

        if ($this->userRepo->emailExists($email)) {
            throw new RuntimeException('Email già in uso.');
        }


        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $id     = $this->userRepo->create($email, $hashed);

        return ['user_id' => $id];
    }

    //  Login , verifica credenziali e ritorna token e scadenza in caso di successo
    public function login(string $email, string $password): array
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new RuntimeException('Email o password errate.');
        }

        $token = $this->jwt->generate((int) $user['id']);

        return [
            'token'      => $token,
            'expires_in' => (int) ($_ENV['JWT_EXPIRATION'] ?? 3600),
        ];
    }

    // Ritorna un user a partire da un token JWT
    public function me(string $token): array
    {
        $payload = $this->jwt->validate($token);

        if (!$payload) {
            throw new RuntimeException('Token scaduto o invalido.');
        }

        $user = $this->userRepo->findById((int) $payload['sub']);

        if (!$user) {
            throw new RuntimeException('User non trovato.');
        }

        return $user;
    }
}