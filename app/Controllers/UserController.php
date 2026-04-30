<?php

//  Gestione richieste HTTP per gli endpoints /user/* 
//  Tutti i routes sono protetti, viene invocato AuthMiddleware prima di ognuno

declare(strict_types=1);

class UserController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }


    //  GET /users
    public function index(array $params = []): void
    {
        $users = $this->userRepo->findAll();
        $this->respond(200, $users);
    }

    //  GET /users/{id}
    public function show(array $params = []): void
    {
        $id   = $this->resolveId($params);
        $user = $this->userRepo->findById($id);

        if ($user === null) {
            $this->respond(404, ['error' => 'User not found.']);
            return;
        }

        $this->respond(200, $user);
    }

    //  PUT /users/{id}
    //  Permette aggiornamento di email e password da parte dell'utente propietario
    public function update(array $params = []): void
    {
        $id            = $this->resolveId($params);
        $authenticatedId = (int) ($_REQUEST['auth_user_id'] ?? 0);

        // Authorization check: only the owner can edit their profile
        if ($id !== $authenticatedId) {
            $this->respond(403, ['error' => 'You can only update your own profile.']);
            return;
        }

        $user = $this->userRepo->findById($id);

        if ($user === null) {
            $this->respond(404, ['error' => 'User not found.']);
            return;
        }

        $body   = $this->getJsonBody();
        $fields = [];

        // Update email if provided
        if (!empty($body['email'])) {
            $email = trim($body['email']);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->respond(400, ['error' => 'Invalid email address.']);
                return;
            }

            if ($email !== $user['email'] && $this->userRepo->emailExists($email)) {
                $this->respond(409, ['error' => 'Email address already in use.']);
                return;
            }

            $fields['email'] = $email;
        }

        // Update password if provided
        if (!empty($body['password'])) {
            if (strlen($body['password']) < 6) {
                $this->respond(400, ['error' => 'Password must be at least 6 characters.']);
                return;
            }

            $fields['password'] = password_hash($body['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            $this->respond(400, ['error' => 'No valid fields provided for update.']);
            return;
        }

        $this->userRepo->update($id, $fields);
        $this->respond(200, ['message' => 'User updated successfully.']);
    }

    // --------------------------------------------------------
    //  DELETE /users/{id}
    //  Un user può eliminare il proprio account
    // --------------------------------------------------------
    public function destroy(array $params = []): void
    {
        $id              = $this->resolveId($params);
        $authenticatedId = (int) ($_REQUEST['auth_user_id'] ?? 0);

        // Authorization check
        if ($id !== $authenticatedId) {
            $this->respond(403, ['error' => 'You can only delete your own account.']);
            return;
        }

        $deleted = $this->userRepo->delete($id);

        if (!$deleted) {
            $this->respond(404, ['error' => 'User not found.']);
            return;
        }

        $this->respond(200, ['message' => 'User deleted successfully.']);
    }

    private function resolveId(array $params): int
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->respond(400, ['error' => 'Invalid user ID.']);
            exit;
        }

        return $id;
    }

    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    private function respond(int $status, array $data): void
    {
        http_response_code($status);
        echo json_encode($data);
    }
}