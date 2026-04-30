<?php

//  Contiene tutte le query relative alla tabella "interventions"

declare(strict_types=1);

class InterventionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    //  Get di tutti gli interventi con filtri opzionali
    public function findAll(array $filters = []): array
    {
        $where  = [];
        $params = [];

        // Filtro per stato
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        // Filtro per user_id
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }

        // Cerco nel titolo e descrizione
        if (!empty($filters['search'])) {
            $where[] = '(title LIKE :search OR description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = 'SELECT * FROM interventions';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // Whitelist per il sorting
        $allowedSort  = ['id', 'title', 'status', 'created_at'];
        $allowedOrder = ['asc', 'desc'];

        $sort  = in_array($filters['sort']  ?? '', $allowedSort,  true) ? $filters['sort']  : 'created_at';
        $order = in_array($filters['order'] ?? '', $allowedOrder, true) ? $filters['order'] : 'desc';

        $sql .= " ORDER BY $sort $order";

        // Paginazione
        $limit  = max(1, min(100, (int) ($filters['limit'] ?? 20)));
        $page   = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $sql .= ' LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Conta numero totale di righe
    public function countAll(array $filters = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(title LIKE :search OR description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = 'SELECT COUNT(*) FROM interventions';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    // Filtra singolo intervento per ID
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM interventions WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    // Crea nuovo intervento (ritona ID)
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO interventions (title, description, status, user_id)
             VALUES (:title, :description, :status, :user_id)'
        );
        $stmt->execute([
            ':title'       => $data['title'],
            ':description' => $data['description'] ?? null,
            ':status'      => $data['status']      ?? 'open',
            ':user_id'     => $data['user_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    // Aggiorna un Intervento (ritorna true se aggiorno correttamente)
    public function update(int $id, array $fields): bool
    {
        if (empty($fields)) {
            return false;
        }

        $setParts = [];
        $params   = [':id' => $id];

        foreach ($fields as $column => $value) {
            $setParts[] = "$column = :$column";
            $params[":$column"] = $value;
        }

        $sql  = 'UPDATE interventions SET ' . implode(', ', $setParts) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    // Elimina un intervento
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM interventions WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}