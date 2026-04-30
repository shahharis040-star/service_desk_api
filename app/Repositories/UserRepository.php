<?php

//  Contiene tutte le query relative alla tabella "users"

declare(strict_types=1);

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    //  Ritorna uno user in base alla email (usata durante login)
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, email, password, created_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }


    //  Ricerca user per ID
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, email, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    // Ritorna tutti gli user (tranne le loro password)
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, email, created_at FROM users ORDER BY created_at DESC'
        );

        return $stmt->fetchAll();
    }

    // Crea nuovo user (ritorna ID del nuovo user)
    public function create(string $email, string $hashedPassword): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (email, password) VALUES (:email, :password)'
        );
        $stmt->execute([
            ':email'    => $email,
            ':password' => $hashedPassword,
        ]);

        return (int) $this->db->lastInsertId();
    }

    // Aggiorna un user (vengono aggiornati solmenete i campi non nulli)
    public function update(int $id, array $fields): bool
    {
        if (empty($fields)) {
            return false;
        }

        $setParts = [];
        $params   = [':id' => $id];

        foreach ($fields as $column => $value) {
            $setParts[]          = "$column = :$column";
            $params[":$column"]  = $value;
        }

        $sql  = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    // Elimina un user
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    // Controlla disponibilià di un email
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);

        return (bool) $stmt->fetchColumn();
    }
}